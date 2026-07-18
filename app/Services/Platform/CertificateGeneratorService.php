<?php

namespace App\Services\Platform;

use App\Models\Certificate;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Str;

class CertificateGeneratorService
{
    public function issue(
        User $issuer,
        Student $student,
        string $type,
        string $title,
        array $metadata = [],
    ): Certificate {
        $school = School::findOrFail($issuer->school_id);
        $code = strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4));

        $html = $this->renderHtml($school, $student, $title, $type, $code, $metadata);

        return Certificate::create([
            'school_id' => $issuer->school_id,
            'student_id' => $student->id,
            'certificate_type' => $type,
            'title' => $title,
            'verification_code' => $code,
            'content_html' => $html,
            'metadata' => $metadata,
            'issued_by' => $issuer->id,
            'issued_at' => now(),
        ]);
    }

    public function verify(string $code): ?array
    {
        $cert = Certificate::where('verification_code', $code)
            ->whereNull('revoked_at')
            ->with(['student:id,full_name,student_number', 'school:id,name,code'])
            ->first();

        if (! $cert) {
            return null;
        }

        return [
            'valid' => true,
            'verification_code' => $cert->verification_code,
            'title' => $cert->title,
            'type' => $cert->certificate_type,
            'issued_at' => $cert->issued_at?->toIso8601String(),
            'student' => $cert->student?->only(['id', 'full_name', 'student_number']),
            'school' => $cert->school?->only(['id', 'name', 'code']),
        ];
    }

    protected function renderHtml(School $school, Student $student, string $title, string $type, string $code, array $metadata): string
    {
        $date = now()->timezone('Africa/Harare')->format('d M Y');
        $studentName = e((string) $student->full_name);
        $schoolName = e((string) $school->name);
        $titleEsc = e($title);
        $typeEsc = e($type);
        $codeEsc = e($code);
        $studentNumber = e((string) ($student->student_number ?? '—'));
        $className = e((string) ($metadata['class'] ?? $student->class ?? '—'));
        $reason = e((string) ($metadata['reason'] ?? '—'));
        $effective = e((string) ($metadata['effective_date'] ?? $date));

        if ($type === 'transfer_certificate') {
            return <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>{$titleEsc}</title>
<style>
body{font-family:Georgia,serif;margin:40px;color:#111}
h1,h2{font-family:Arial,Helvetica,sans-serif}
.meta{margin:20px 0;line-height:1.6}
.footer{margin-top:48px;font-size:12px;color:#555}
</style></head>
<body>
<h1>{$schoolName}</h1>
<h2>{$titleEsc}</h2>
<p>This is to certify that</p>
<h3>{$studentName}</h3>
<div class="meta">
<div><strong>Student number:</strong> {$studentNumber}</div>
<div><strong>Last class:</strong> {$className}</div>
<div><strong>Effective date:</strong> {$effective}</div>
<div><strong>Reason / remarks:</strong> {$reason}</div>
</div>
<p>was a bona fide student of this school and is hereby issued this transfer / leaving certificate.</p>
<p class="footer">Issued on {$date}. Verification code: {$codeEsc}</p>
</body></html>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{$titleEsc}</title></head>
<body style="font-family: Georgia, serif; text-align: center; padding: 48px;">
<h1>{$schoolName}</h1>
<h2>{$titleEsc}</h2>
<p>This certifies that</p>
<h3>{$studentName}</h3>
<p>has been awarded this {$typeEsc} certificate on {$date}.</p>
<p style="margin-top: 48px; font-size: 12px;">Verification: {$codeEsc}</p>
</body></html>
HTML;
    }
}
