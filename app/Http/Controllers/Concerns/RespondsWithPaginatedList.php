<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait RespondsWithPaginatedList
{
    /**
     * Return a capped collection (limit/all) or a Laravel-style paginator payload.
     */
    protected function indexResponse(Request $request, Builder $query): JsonResponse
    {
        if ($request->boolean('all')) {
            return response()->json(['data' => $query->limit(500)->get()]);
        }

        if ($request->filled('limit') && ! $request->filled('page') && ! $request->filled('per_page')) {
            $limit = min(max((int) $request->input('limit'), 1), 200);

            return response()->json(['data' => $query->limit($limit)->get()]);
        }

        $perPage = min(max($request->integer('per_page', 25), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
