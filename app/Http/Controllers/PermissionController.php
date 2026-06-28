<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeRoleManagement($request);

        return response()->json([
            'data' => app(PermissionService::class)->catalogWithRules(),
        ]);
    }
}
