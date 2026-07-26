<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolCertificateController extends Controller
{
    /**
     * @return list<string>
     */
    protected function manageCapabilities(): array
    {
        return ['canManageTeachers'];
    }

    /**
     * @return list<string>
     */
    protected function managePermissionSlugs(): array
    {
        return ['students.manage'];
    }

    protected function authorizeManage(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: $this->manageCapabilities(),
            permissionSlugs: $this->managePermissionSlugs(),
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $schoolId = (int) $request->user()->school_id;

        $query = Certificate::query()
            ->where('school_id', $schoolId)
            ->with([
                'student:id,full_name,student_number',
                'template:id,name,certificate_type',
            ])
            ->orderByDesc('issued_at');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        if ($request->filled('certificate_type')) {
            $query->where('certificate_type', $request->string('certificate_type')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('verification_code', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%");
                    });
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);

        $certificate = Certificate::query()
            ->where('school_id', $request->user()->school_id)
            ->with([
                'student:id,full_name,student_number',
                'template:id,name,certificate_type,title',
                'issuer:id,name',
            ])
            ->findOrFail($id);

        return response()->json(['data' => $certificate]);
    }

    public function download(Request $request, int $id)
    {
        $this->authorizeManage($request);

        $certificate = Certificate::query()
            ->where('school_id', $request->user()->school_id)
            ->with(['student:id,full_name,student_number'])
            ->findOrFail($id);

        $html = (string) ($certificate->content_html ?: '<p>No certificate content.</p>');
        $wrapped = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{$certificate->title}</title>
  <style>
    @media print { body { margin: 0; } }
    body { margin: 0; padding: 24px; background: #fff; color: #111; }
  </style>
</head>
<body>
{$html}
<script>window.addEventListener('load', function () { /* ready to print */ });</script>
</body>
</html>
HTML;

        $safeCode = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $certificate->verification_code) ?: 'certificate';

        return response($wrapped, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="certificate_'.$safeCode.'.html"',
        ]);
    }
}
