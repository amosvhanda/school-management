<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\DisciplinaryRecord;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\NotificationQueue;
use App\Models\ParentNotification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

class ParentNotificationService
{
    public function __construct(private ParentAccessService $parentAccess) {}

    public function notify(User $parent, Student $student, string $type, string $title, string $body, array $data = []): ParentNotification
    {
        $notification = ParentNotification::create([
            'school_id' => $student->school_id,
            'parent_user_id' => $parent->id,
            'student_id' => $student->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        $this->queueOutbound($parent, $student, $type, $title, $body, $data);

        return $notification;
    }

    public function notifyStudentParents(Student $student, string $type, string $title, string $body, array $data = []): void
    {
        foreach ($this->parentAccess->parentUsersForStudent($student) as $parent) {
            $this->notify($parent, $student, $type, $title, $body, $data);
        }
    }

    public function notifyExamResultsPublished(Exam $exam): void
    {
        $students = Student::query()
            ->where('school_id', $exam->school_id)
            ->where('grade_level_id', $exam->grade_level_id)
            ->where('status', 'active')
            ->get();

        foreach ($students as $student) {
            $result = $exam->examResults()->where('student_id', $student->id)->first();
            if (! $result) {
                continue;
            }

            $this->notifyStudentParents(
                $student,
                'exam_results',
                "Exam results: {$exam->name}",
                "{$student->full_name} scored {$result->marks_obtained}/{$exam->total_marks} in {$exam->name}.",
                [
                    'exam_id' => $exam->id,
                    'marks_obtained' => $result->marks_obtained,
                    'total_marks' => $exam->total_marks,
                ],
            );
        }
    }

    public function notifyFeeStatement(Student $student, float $outstanding, string $currency): void
    {
        $this->notifyStudentParents(
            $student,
            'fee_statement',
            'Fee statement update',
            "Outstanding balance for {$student->full_name}: {$currency} ".number_format($outstanding, 2),
            ['outstanding' => $outstanding, 'currency' => $currency],
        );
    }

    public function notifyDisciplinaryNotice(DisciplinaryRecord $record): void
    {
        $student = $record->student;
        if (! $student) {
            return;
        }

        $this->notifyStudentParents(
            $student,
            'disciplinary',
            'Disciplinary notice',
            "A {$record->severity} disciplinary incident was recorded for {$student->full_name} on {$record->incident_date->format('Y-m-d')}: {$record->category}.",
            ['disciplinary_record_id' => $record->id],
        );

        $record->update(['parent_notified' => true, 'parent_notified_at' => now()]);
    }

    public function notifyAnnouncement(Announcement $announcement): void
    {
        if (! in_array($announcement->target_audience, ['all', 'parents'], true)) {
            return;
        }

        $query = User::query()
            ->where('school_id', $announcement->school_id)
            ->where('role', 'parent');

        foreach ($query->get() as $parent) {
            ParentNotification::create([
                'school_id' => $announcement->school_id,
                'parent_user_id' => $parent->id,
                'student_id' => null,
                'type' => 'announcement',
                'title' => $announcement->title,
                'body' => $announcement->message,
                'data' => ['announcement_id' => $announcement->id],
            ]);

            $this->queueOutbound($parent, null, 'announcement', $announcement->title, $announcement->message, [
                'announcement_id' => $announcement->id,
            ]);
        }
    }

    public function notifyAttendanceAbsence(Student $student, array $context): void
    {
        $title = "Absence alert: {$student->full_name}";
        $body = "Your child was marked absent on {$context['date']} for {$context['subject']} ({$context['class']}).";

        $this->notifyStudentParents($student, 'attendance_absence', $title, $body, $context);
    }

    protected function queueOutbound(
        User $parent,
        ?Student $student,
        string $type,
        string $subject,
        string $message,
        array $data = [],
    ): void {
        if (empty($parent->email) && empty($parent->phone)) {
            return;
        }

        NotificationQueue::create([
            'school_id' => $parent->school_id,
            'type' => $type,
            'notifiable_type' => $student ? Student::class : User::class,
            'notifiable_id' => $student?->id ?? $parent->id,
            'parent_user_id' => $parent->id,
            'guardian_id' => Guardian::where('user_id', $parent->id)->value('id'),
            'channel' => ! empty($parent->email) && ! empty($parent->phone) ? 'both' : (! empty($parent->email) ? 'email' : 'sms'),
            'recipient_email' => $parent->email,
            'recipient_phone' => $parent->phone,
            'subject' => $subject,
            'message' => $message,
            'data' => $data,
            'status' => 'pending',
        ]);
    }
}
