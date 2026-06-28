<?php

namespace App\Services\Platform;

use App\Enums\UserRole;
use App\Models\SecureDocument;
use App\Models\SecureDocumentAccessLog;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ExamVaultService
{
    public function store(User $user, array $data): SecureDocument
    {
        $payload = $data['content'];
        $encrypted = Crypt::encryptString($payload);

        return SecureDocument::create([
            'school_id' => $user->school_id,
            'vault_type' => $data['vault_type'] ?? 'exam_paper',
            'title' => $data['title'],
            'encrypted_payload' => $encrypted,
            'content_hash' => hash('sha256', $payload),
            'access_roles' => $data['access_roles'] ?? ['admin', 'examination_officer'],
            'uploaded_by' => $user->id,
            'exam_id' => $data['exam_id'] ?? null,
        ]);
    }

    public function retrieve(SecureDocument $document, User $user): array
    {
        $this->assertAccess($document, $user);

        SecureDocumentAccessLog::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'action' => 'view',
            'ip_address' => request()->ip(),
            'accessed_at' => now(),
        ]);

        $content = Crypt::decryptString($document->encrypted_payload);

        return [
            'id' => $document->id,
            'title' => $document->title,
            'vault_type' => $document->vault_type,
            'content' => $content,
            'content_hash' => $document->content_hash,
        ];
    }

    protected function assertAccess(SecureDocument $document, User $user): void
    {
        if ($document->school_id !== $user->school_id) {
            throw new AccessDeniedHttpException('Cross-school vault access denied.');
        }

        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        if ($role === 'school_admin') {
            $role = 'admin';
        }

        $allowed = in_array($role, $document->access_roles ?? [], true)
            || in_array($role, ['admin', 'super_admin'], true);

        if (! $allowed) {
            throw new AccessDeniedHttpException('Insufficient vault permissions.');
        }
    }
}
