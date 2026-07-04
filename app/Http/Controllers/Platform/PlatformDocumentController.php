<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\CreateSignableDocumentRequest;
use App\Http\Requests\Platform\IssueCertificateRequest;
use App\Http\Requests\Platform\StoreExamVaultRequest;
use App\Models\Certificate;
use App\Models\SecureDocument;
use App\Models\SignableDocument;
use App\Models\Student;
use App\Models\Exam; // Assuming this model holds exam records
use App\Services\Platform\CertificateGeneratorService;
use App\Services\Platform\DocumentSigningService;
use App\Services\Platform\ExamVaultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PlatformDocumentController extends Controller
{
    public function __construct(
        private DocumentSigningService $signing,
        private CertificateGeneratorService $certificates,
        private ExamVaultService $vault,
    ) {}

    public function documents(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->signing->listForSchool($request->user()->school_id)]);
    }

    public function createDocument(CreateSignableDocumentRequest $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $data = $request->validated();

        // Explicitly inject school tenant mapping into parameters
        $doc = $this->signing->create($request->user(), [...$data, 'school_id' => $schoolId]);

        return response()->json(['data' => $doc, 'message' => 'Document created for signing'], 201);
    }

    public function signDocument(Request $request, int $id): JsonResponse
    {
        $doc = SignableDocument::where('school_id', $request->user()->school_id)->findOrFail($id);
        $signature = $this->signing->sign($doc, $request->user(), $request->input('signer_role'));

        return response()->json(['data' => $signature, 'message' => 'Document signed']);
    }

    public function issueCertificate(IssueCertificateRequest $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $data = $request->validated();

        // Tighten multi-tenant lookup bounds
        $student = Student::where('school_id', $schoolId)->findOrFail($data['student_id']);

        $cert = $this->certificates->issue(
            $request->user(),
            $student,
            $data['certificate_type'],
            $data['title'],
            $data['metadata'] ?? [],
        );

        return response()->json(['data' => $cert, 'message' => 'Certificate issued'], 201);
    }

    public function verifyCertificate(string $code): JsonResponse
    {
        $result = $this->certificates->verify($code);

        if (! $result) {
            return response()->json(['message' => 'Invalid or revoked certificate'], 404);
        }

        return response()->json(['data' => $result]);
    }

    public function certificates(Request $request): JsonResponse
    {
        $certs = Certificate::where('school_id', $request->user()->school_id)
            ->with('student:id,full_name')
            ->orderByDesc('issued_at')
            ->limit(100)
            ->get();

        return response()->json(['data' => $certs]);
    }

    public function vaultStore(StoreExamVaultRequest $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $data = $request->validated();

        // Defend against cross-tenant metadata manipulation loop
        if (!empty($data['exam_id'])) {
            Exam::where('school_id', $schoolId)->findOrFail($data['exam_id']);
        }

        $doc = $this->vault->store($request->user(), [...$data, 'school_id' => $schoolId]);

        return response()->json(['data' => $doc, 'message' => 'Document secured in vault'], 201);
    }

    public function vaultRetrieve(Request $request, int $id): JsonResponse
    {
        $doc = SecureDocument::where('school_id', $request->user()->school_id)->findOrFail($id);

        return response()->json(['data' => $this->vault->retrieve($doc, $request->user())]);
    }

    public function vaultIndex(Request $request): JsonResponse
    {
        $docs = SecureDocument::where('school_id', $request->user()->school_id)
            ->select(['id', 'title', 'vault_type', 'exam_id', 'uploaded_by', 'created_at'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json(['data' => $docs]);
    }
}
