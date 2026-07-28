<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Import\PeopleCsvImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PeopleImportController extends Controller
{
    public function __construct(
        private PeopleCsvImportService $imports,
    ) {}

    public function template(Request $request, string $type): StreamedResponse
    {
        $this->authorizeImport($request, $type);

        $headers = $this->imports->templateHeaders($type);
        $filename = "{$type}-import-template.csv";

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request, string $type)
    {
        $this->authorizeImport($request, $type);

        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'create_login_users' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schoolId = (int) $request->user()->school_id;
        $file = $request->file('file');
        $createLogins = $request->boolean('create_login_users');

        try {
            $result = match ($type) {
                'students' => $this->imports->importStudents($file, $schoolId, $request->user()?->id),
                'teachers' => $this->imports->importTeachers($file, $schoolId, $createLogins),
                'employees' => $this->imports->importEmployees($file, $schoolId),
                default => null,
            };
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        if ($result === null) {
            return response()->json(['message' => 'Unknown import type.'], 404);
        }

        return response()->json([
            'message' => sprintf(
                'Import finished: %d created, %d updated, %d failed.',
                $result['created'],
                $result['updated'],
                $result['failed'],
            ),
            'data' => $result,
        ]);
    }

    private function authorizeImport(Request $request, string $type): void
    {
        $map = [
            'students' => [
                'capabilities' => ['canManageStudents'],
                'permissions' => ['students.manage', 'people.manage'],
            ],
            'teachers' => [
                'capabilities' => ['canManageTeachers'],
                'permissions' => ['teachers.manage', 'people.manage'],
            ],
            'employees' => [
                'capabilities' => ['canManageTeachers'],
                'permissions' => ['hr.manage', 'people.manage'],
            ],
        ];

        if (! isset($map[$type])) {
            abort(404, 'Unknown import type.');
        }

        $this->authorizeModuleAccess(
            $request,
            capabilities: $map[$type]['capabilities'],
            permissionSlugs: $map[$type]['permissions'],
        );
    }
}
