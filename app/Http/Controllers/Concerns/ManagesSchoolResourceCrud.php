<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

trait ManagesSchoolResourceCrud
{
    abstract protected function resourceModel(): string;

    /**
     * @return list<string>
     */
    abstract protected function manageCapabilities(): array;

    /**
     * @return list<string>
     */
    abstract protected function managePermissionSlugs(): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function storeRules(Request $request, int $schoolId): array;

    /**
     * @return array<string, mixed>
     */
    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        return $this->storeRules($request, $schoolId);
    }

    /**
     * @return list<string>
     */
    protected function fillableFromRequest(): array
    {
        return [];
    }

    protected function resourceLabel(): string
    {
        return class_basename($this->resourceModel());
    }

    protected function authorizeManage(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: $this->manageCapabilities(),
            permissionSlugs: $this->managePermissionSlugs(),
        );
    }

    protected function schoolQuery(Request $request): Builder
    {
        /** @var class-string<Model> $model */
        $model = $this->resourceModel();

        return $model::query()->where('school_id', $request->user()->school_id);
    }

    protected function applyIndexFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
                if ($q->getModel()->isFillable('description')) {
                    $q->orWhere('description', 'like', "%{$search}%");
                }
                if ($q->getModel()->isFillable('code')) {
                    $q->orWhere('code', 'like', "%{$search}%");
                }
            });
        }

        if ($request->has('is_active') && $query->getModel()->isFillable('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $query;
    }

    protected function orderIndex(Builder $query): Builder
    {
        $model = $query->getModel();
        if ($model->isFillable('order')) {
            $query->orderBy('order');
        }

        return $query->orderBy('name');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $query = $this->applyIndexFilters($this->schoolQuery($request), $request);
        $this->orderIndex($query);

        return response()->json(['data' => $query->get()]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);

        $record = $this->schoolQuery($request)->findOrFail($id);

        return response()->json(['data' => $record]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeManage($request);
        $this->prepareMutationRequest($request);
        $schoolId = (int) $request->user()->school_id;

        $run = function () use ($request, $schoolId) {
            $validator = Validator::make($request->all(), $this->storeRules($request, $schoolId));
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            /** @var class-string<Model> $model */
            $model = $this->resourceModel();
            $instance = new $model;
            $payload = $validator->validated();
            $payload['school_id'] = $schoolId;

            if (array_key_exists('is_active', $payload) === false && $instance->isFillable('is_active')) {
                $payload['is_active'] = true;
            }

            $record = $model::create(collect($payload)->only($instance->getFillable())->all());
            $this->afterStore($request, $record);

            return response()->json([
                'message' => $this->resourceLabel().' created successfully',
                'data' => $record->fresh(),
            ], 201);
        };

        return $this->wrapMutationsInTransaction() ? DB::transaction($run) : $run();
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);
        $this->prepareMutationRequest($request);
        $schoolId = (int) $request->user()->school_id;
        $record = $this->schoolQuery($request)->findOrFail($id);

        $run = function () use ($request, $schoolId, $id, $record) {
            $validator = Validator::make($request->all(), $this->updateRules($request, $schoolId, $id));
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $payload = $validator->validated();
            $fillable = $this->fillableFromRequest();
            if ($fillable !== []) {
                $payload = collect($payload)->only($fillable)->all();
            }

            $record->update($payload);
            $this->afterUpdate($request, $record);

            return response()->json([
                'message' => $this->resourceLabel().' updated successfully',
                'data' => $record->fresh(),
            ]);
        };

        return $this->wrapMutationsInTransaction() ? DB::transaction($run) : $run();
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);
        $record = $this->schoolQuery($request)->findOrFail($id);

        if ($response = $this->preventDestroy($record)) {
            return $response;
        }

        $record->delete();

        return response()->json([
            'message' => $this->resourceLabel().' deleted successfully',
        ]);
    }

    protected function afterStore(Request $request, Model $record): void {}

    protected function afterUpdate(Request $request, Model $record): void {}

    protected function prepareMutationRequest(Request $request): void {}

    protected function wrapMutationsInTransaction(): bool
    {
        return false;
    }

    protected function preventDestroy(Model $record): ?JsonResponse
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function uniqueNameRule(int $schoolId, string $table, ?int $ignoreId = null): array
    {
        $rule = Rule::unique($table)->where(fn ($query) => $query->where('school_id', $schoolId));
        if ($ignoreId) {
            $rule = $rule->ignore($ignoreId);
        }

        return ['required', 'string', 'max:255', $rule];
    }
}
