<?php

namespace App\Services;

use App\Models\AlumniRecord;
use App\Models\Certificate;
use App\Models\CrossSchoolTransfer;
use App\Models\Enrollment;
use App\Models\HostelAllocation;
use App\Models\Student;
use App\Models\StudentStatusEvent;
use App\Models\StudentTransportAllocation;
use App\Models\User;
use App\Services\Platform\CertificateGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentLifecycleActionService
{
    public const EXIT_STATUSES = ['withdrawn', 'transferred', 'expelled', 'graduated', 'suspended', 'inactive'];

    public function __construct(
        private CertificateGeneratorService $certificates,
    ) {}

    /**
     * @param  array{action:string,reason?:string,effective_date?:string,to_school_id?:int,issue_tc?:bool,deactivate_account?:bool}  $payload
     * @return array{student: Student, certificate?: Certificate|null, transfer?: CrossSchoolTransfer|null, alumni?: AlumniRecord|null}
     */
    public function transition(Student $student, array $payload, User $actor): array
    {
        $action = (string) $payload['action'];
        $reason = (string) ($payload['reason'] ?? '');
        $effectiveDate = $payload['effective_date'] ?? now()->toDateString();
        $deactivate = (bool) ($payload['deactivate_account'] ?? true);

        $targetStatus = match ($action) {
            'suspend' => 'suspended',
            'reinstate' => 'active',
            'withdraw' => 'withdrawn',
            'expel' => 'expelled',
            'transfer_out' => 'transferred',
            'graduate' => 'graduated',
            'deactivate' => 'inactive',
            'activate' => 'active',
            default => throw ValidationException::withMessages(['action' => ['Unsupported lifecycle action.']]),
        };

        $this->assertTransitionAllowed($student->status ?? 'active', $targetStatus, $action);

        return DB::transaction(function () use ($student, $action, $targetStatus, $reason, $effectiveDate, $deactivate, $payload, $actor) {
            $from = $student->status;
            $certificate = null;
            $transfer = null;
            $alumni = null;

            if (in_array($targetStatus, ['withdrawn', 'transferred', 'expelled', 'graduated'], true)) {
                Enrollment::query()
                    ->where('student_id', $student->id)
                    ->whereIn('status', ['active', 'repeating'])
                    ->update([
                        'status' => $targetStatus === 'graduated' ? 'graduated' : 'completed',
                        'left_at' => $effectiveDate,
                    ]);

                StudentTransportAllocation::query()
                    ->where('student_id', $student->id)
                    ->where('status', 'active')
                    ->update(['status' => 'ended']);

                HostelAllocation::query()
                    ->where('student_id', $student->id)
                    ->where('status', 'active')
                    ->update(['status' => 'ended', 'left_at' => $effectiveDate]);
            }

            $student->update([
                'status' => $targetStatus,
                'status_reason' => $reason ?: null,
                'status_changed_at' => now(),
                'exited_at' => in_array($targetStatus, ['withdrawn', 'transferred', 'expelled', 'graduated'], true)
                    ? $effectiveDate
                    : null,
            ]);

            if ($deactivate && in_array($targetStatus, ['withdrawn', 'transferred', 'expelled', 'graduated', 'suspended', 'inactive'], true)) {
                $this->syncUserAccount($student, false);
            }

            if ($action === 'reinstate' || $action === 'activate') {
                $this->syncUserAccount($student, true);
            }

            if ($action === 'transfer_out') {
                $toSchoolId = (int) ($payload['to_school_id'] ?? 0);
                $transfer = CrossSchoolTransfer::create([
                    'student_id' => $student->id,
                    'from_school_id' => $student->school_id,
                    'to_school_id' => $toSchoolId ?: $student->school_id,
                    'status' => 'completed',
                    'requested_by' => $actor->id,
                    'completed_at' => now(),
                ]);
            }

            if (! empty($payload['issue_tc']) || $action === 'transfer_out' || $action === 'withdraw') {
                $certificate = $this->certificates->issue(
                    $actor,
                    $student->fresh(),
                    'transfer_certificate',
                    'Transfer / Leaving Certificate',
                    [
                        'action' => $action,
                        'reason' => $reason,
                        'effective_date' => $effectiveDate,
                        'class' => $student->class,
                        'student_number' => $student->student_number,
                    ],
                );
            }

            if ($action === 'graduate') {
                $alumni = AlumniRecord::query()->firstOrCreate(
                    [
                        'school_id' => $student->school_id,
                        'student_id' => $student->id,
                    ],
                    [
                        'full_name' => $student->full_name,
                        'graduation_year' => (int) date('Y', strtotime((string) $effectiveDate)),
                        'email' => $student->email,
                        'phone' => $student->phone,
                        'engagement_history' => [],
                    ],
                );
            }

            StudentStatusEvent::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'from_status' => $from,
                'to_status' => $targetStatus,
                'action' => $action,
                'reason' => $reason ?: null,
                'effective_date' => $effectiveDate,
                'performed_by' => $actor->id,
                'metadata' => [
                    'certificate_id' => $certificate?->id,
                    'transfer_id' => $transfer?->id,
                    'alumni_id' => $alumni?->id,
                ],
            ]);

            return [
                'student' => $student->fresh(['classModel.teacher', 'stream', 'house', 'gradeLevel', 'user']),
                'certificate' => $certificate,
                'transfer' => $transfer,
                'alumni' => $alumni,
            ];
        });
    }

    public function downloadTransferCertificate(Student $student): ?Certificate
    {
        return Certificate::query()
            ->where('student_id', $student->id)
            ->where('certificate_type', 'transfer_certificate')
            ->whereNull('revoked_at')
            ->latest('issued_at')
            ->first();
    }

    private function assertTransitionAllowed(?string $from, string $to, string $action): void
    {
        $from = $from ?: 'active';

        if ($action === 'reinstate' && ! in_array($from, ['suspended', 'inactive'], true)) {
            throw ValidationException::withMessages([
                'action' => ['Only suspended or inactive students can be reinstated.'],
            ]);
        }

        if ($from === $to && $action !== 'transfer_out') {
            throw ValidationException::withMessages([
                'action' => ["Student is already {$to}."],
            ]);
        }
    }

    private function syncUserAccount(Student $student, bool $active): void
    {
        if (! $student->user_id) {
            return;
        }

        User::query()->where('id', $student->user_id)->update([
            'status' => $active ? 'active' : 'inactive',
        ]);
    }
}
