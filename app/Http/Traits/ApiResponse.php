<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

trait ApiResponse
{
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $payload = ['message' => $message];

        if ($data !== null) {
            $payload['data'] = $this->transformData($data);
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $payload = ['message' => $message];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    protected function unauthorized(string $message = 'Unauthenticated'): JsonResponse
    {
        return $this->error($message, 401);
    }

    private function transformData(mixed $data): mixed
    {
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->resolve();
        }

        if ($data instanceof AbstractPaginator) {
            return $data->items();
        }

        return $data;
    }
}
