<?php

namespace App\Http\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Standardized list querying: filter[], sort, include, fields, page/per_page.
 * Also shims legacy params (search, status, sort+order, all, limit).
 *
 * @phpstan-type QueryConfig array{
 *     filters?: list<string|AllowedFilter>,
 *     sorts?: list<string>,
 *     includes?: list<string|AllowedInclude>,
 *     fields?: list<string>,
 *     default_sort?: string,
 *     search_columns?: list<string>,
 *     default_per_page?: int,
 *     max_per_page?: int,
 *     max_all?: int,
 *     with?: list<string>,
 *     with_count?: list<string>,
 *     base?: Builder|null
 * }
 */
trait HandlesResourceQueries
{
    /**
     * @param  class-string  $modelClass
     * @param  QueryConfig  $config
     */
    protected function resourceQuery(Request $request, string $modelClass, array $config = []): QueryBuilder
    {
        $this->shimLegacyQueryParams($request, $config);

        $base = $config['base'] ?? $modelClass::query();

        if (! empty($config['with'])) {
            $base->with($config['with']);
        }

        if (! empty($config['with_count'])) {
            $base->withCount($config['with_count']);
        }

        $filters = $this->normalizeFilters($config);

        $builder = QueryBuilder::for($base, $request)
            ->allowedFilters(...$filters)
            ->allowedSorts(...($config['sorts'] ?? []))
            ->allowedIncludes(...($config['includes'] ?? []));

        if (! empty($config['fields'])) {
            $builder->allowedFields(...$config['fields']);
        }

        $defaultSort = $config['default_sort'] ?? '-created_at';
        $builder->defaultSort($defaultSort);

        return $builder;
    }

    /**
     * Paginate (default) or return a capped collection when all/limit is used.
     *
     * @param  class-string<JsonResource>  $resourceClass
     * @param  QueryConfig  $config
     */
    protected function paginateResource(
        Request $request,
        string $modelClass,
        string $resourceClass,
        array $config = [],
    ): AnonymousResourceCollection {
        $builder = $this->resourceQuery($request, $modelClass, $config);

        $maxAll = (int) ($config['max_all'] ?? 500);
        $defaultPerPage = (int) ($config['default_per_page'] ?? 25);
        $maxPerPage = (int) ($config['max_per_page'] ?? 100);

        if ($request->boolean('all')) {
            return $resourceClass::collection($builder->limit($maxAll)->get())
                ->additional(['message' => 'Success']);
        }

        // Legacy: ?limit=N without page returns a capped list (not a paginator).
        if ($request->filled('limit') && ! $request->filled('page') && ! $request->filled('per_page')) {
            $limit = min(max((int) $request->input('limit'), 1), $maxPerPage);

            return $resourceClass::collection($builder->limit($limit)->get())
                ->additional(['message' => 'Success']);
        }

        $perPage = min(max($request->integer('per_page', $defaultPerPage), 1), $maxPerPage);

        return $resourceClass::collection($builder->paginate($perPage)->appends($request->query()))
            ->additional(['message' => 'Success']);
    }

    /**
     * @param  QueryConfig  $config
     * @return list<string|AllowedFilter>
     */
    private function normalizeFilters(array $config): array
    {
        $filters = [];
        $searchColumns = $config['search_columns'] ?? [];
        $hasSearchFilter = false;

        foreach ($config['filters'] ?? [] as $filter) {
            if ($filter instanceof AllowedFilter) {
                $filters[] = $filter;
                if ($filter->getName() === 'search') {
                    $hasSearchFilter = true;
                }

                continue;
            }

            if ($filter === 'search') {
                $hasSearchFilter = true;
                $filters[] = AllowedFilter::callback('search', function (Builder $query, $value) use ($searchColumns) {
                    $term = trim((string) $value);
                    if ($term === '' || $searchColumns === []) {
                        return;
                    }

                    $query->where(function (Builder $q) use ($term, $searchColumns) {
                        foreach ($searchColumns as $index => $column) {
                            if (str_contains($column, '.')) {
                                [$relation, $relColumn] = explode('.', $column, 2);
                                $method = $index === 0 ? 'whereHas' : 'orWhereHas';
                                $q->{$method}($relation, fn (Builder $rq) => $rq->where($relColumn, 'like', "%{$term}%"));
                            } else {
                                $method = $index === 0 ? 'where' : 'orWhere';
                                $q->{$method}($column, 'like', "%{$term}%");
                            }
                        }
                    });
                });

                continue;
            }

            $filters[] = AllowedFilter::exact($filter);
        }

        if (! $hasSearchFilter && $searchColumns !== []) {
            $filters[] = AllowedFilter::callback('search', function (Builder $query, $value) use ($searchColumns) {
                $term = trim((string) $value);
                if ($term === '') {
                    return;
                }

                $query->where(function (Builder $q) use ($term, $searchColumns) {
                    foreach ($searchColumns as $index => $column) {
                        if (str_contains($column, '.')) {
                            [$relation, $relColumn] = explode('.', $column, 2);
                            $method = $index === 0 ? 'whereHas' : 'orWhereHas';
                            $q->{$method}($relation, fn (Builder $rq) => $rq->where($relColumn, 'like', "%{$term}%"));
                        } else {
                            $method = $index === 0 ? 'where' : 'orWhere';
                            $q->{$method}($column, 'like', "%{$term}%");
                        }
                    }
                });
            });
        }

        return $filters;
    }

    /**
     * Map legacy query params onto Spatie's filter[] / sort contract in-place.
     *
     * @param  QueryConfig  $config
     */
    private function shimLegacyQueryParams(Request $request, array $config): void
    {
        $query = $request->query();
        $filters = is_array($query['filter'] ?? null) ? $query['filter'] : [];

        // Top-level search -> filter[search]
        if ($request->filled('search') && empty($filters['search'])) {
            $filters['search'] = $request->input('search');
        }

        // Top-level exact filters that match allowed filter names
        $allowedExactNames = [];
        foreach ($config['filters'] ?? [] as $filter) {
            if ($filter instanceof AllowedFilter) {
                $allowedExactNames[] = $filter->getName();
            } elseif (is_string($filter) && $filter !== 'search') {
                $allowedExactNames[] = $filter;
            }
        }

        foreach ($allowedExactNames as $name) {
            if ($request->filled($name) && empty($filters[$name])) {
                $filters[$name] = $request->input($name);
            }
        }

        if ($filters !== []) {
            $query['filter'] = $filters;
        }

        // sort=field&order=desc -> sort=-field (legacy pair)
        if ($request->filled('sort') && $request->filled('order')) {
            $sortField = ltrim((string) $request->input('sort'), '-');
            $direction = strtolower((string) $request->input('order'));
            $query['sort'] = $direction === 'desc' ? '-'.$sortField : $sortField;
            unset($query['order']);
        }

        $request->query->replace($query);
    }
}
