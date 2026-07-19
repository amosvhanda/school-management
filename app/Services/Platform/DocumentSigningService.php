<?php

namespace App\Services\Platform;

use App\Models\DocumentSignature;
use App\Models\SignableDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocumentSigningService
{
    public function create(User $creator, array $data): SignableDocument
    {
        return SignableDocument::create([
            'school_id' => $creator->school_id,
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

        if ($document->school_id !== $signer->school_id) {
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
