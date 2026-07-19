<?php

namespace App\Services\Platform;

use App\Enums\UserRole;
use App\Models\DocumentSignature;
use App\Models\SignableDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentSigningService
{
    public function create(User $creator, array $data): SignableDocument
    {
        $schoolId = $data['school_id'] ?? $creator->school_id;
        if (! $schoolId) {
            throw ValidationException::withMessages([
                'school_id' => ['Select a school for this document.'],
            ]);
        }

        return SignableDocument::create([
            'school_id' => $schoolId,
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'content' => $data['content'],
            'status' => 'pending_signatures',
            'created_by' => $creator->id,
        ]);
    }

    public function sign(SignableDocument $document, User $signer, ?string $role = null): DocumentSignature
    {
        if ($document->status === 'completed') {
            abort(422, 'Document is already fully signed.');
        }

        $isSuperAdmin = $signer->role === UserRole::SuperAdmin;
        if (! $isSuperAdmin && $document->school_id !== $signer->school_id) {
            abort(403, 'Cross-school signing denied.');
        }

        return DB::transaction(function () use ($document, $signer, $role) {
            $signature = DocumentSignature::updateOrCreate(
                ['document_id' => $document->id, 'signer_id' => $signer->id],
                [
                    'signer_role' => $role,
                    'signature_hash' => hash('sha256', $document->content.$signer->id.now()->timestamp),
                    'ip_address' => request()->ip(),
                    'signed_at' => now(),
                ],
            );

            $required = 1;
            if ($document->signatures()->count() >= $required) {
                $document->update(['status' => 'completed', 'completed_at' => now()]);
            }

            return $signature;
        });
    }

    public function listForSchool(?int $schoolId = null)
    {
        return SignableDocument::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['signatures.signer:id,name,email', 'school:id,name,code'])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();
    }
}
