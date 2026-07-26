<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CertificateTemplateController extends Controller
{
    use ManagesSchoolResourceCrud;

    protected function resourceModel(): string
    {
        return CertificateTemplate::class;
    }

    protected function manageCapabilities(): array
    {
        return ['canManageTeachers'];
    }

    protected function managePermissionSlugs(): array
    {
        return ['students.manage'];
    }

    protected function resourceLabel(): string
    {
        return 'Certificate template';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'name' => $this->uniqueNameRule($schoolId, 'certificate_templates'),
            'certificate_type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        return [
            'name' => $this->uniqueNameRule($schoolId, 'certificate_templates', $id),
            'certificate_type' => ['sometimes', 'string', 'max:100'],
            'title' => ['sometimes', 'string', 'max:255'],
            'body_html' => ['sometimes', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function issue(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);

        $schoolId = (int) $request->user()->school_id;
        $template = $this->schoolQuery($request)->findOrFail($id);

        if (! $template->is_active) {
            return response()->json([
                'message' => 'This certificate template is inactive.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $student = Student::query()
            ->where('school_id', $schoolId)
            ->findOrFail((int) $request->input('student_id'));

        $school = $request->user()->school;
        $issuedAt = now();
        $verificationCode = Str::upper(Str::random(10));
        $dateLabel = $issuedAt->timezone('Africa/Harare')->format('d M Y');

        $body = str_replace(
            ['{{student_name}}', '{{date}}', '{{school_name}}'],
            [
                e((string) $student->full_name),
                e($dateLabel),
                e((string) ($school?->name ?? '')),
            ],
            (string) $template->body_html,
        );

        $certificate = Certificate::create([
            'school_id' => $schoolId,
            'certificate_template_id' => $template->id,
            'student_id' => $student->id,
            'certificate_type' => $template->certificate_type,
            'title' => $template->title,
            'verification_code' => $verificationCode,
            'content_html' => $body,
            'issued_by' => $request->user()->id,
            'issued_at' => $issuedAt,
        ]);

        $certificate->load(['student:id,full_name,student_number', 'template:id,name,certificate_type']);

        return response()->json([
            'message' => 'Certificate issued successfully',
            'data' => $certificate,
        ], 201);
    }
}
