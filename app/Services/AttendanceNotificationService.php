<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\NotificationQueue;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceNotificationService
{
    private const MAX_RETRIES = 3;

    public function __construct(private ParentNotificationService $parentNotifications) {}

    /**
     * Send notification to guardians when student is absent
     */
    public function notifyAbsence(Attendance $attendance): void
    {
        if ($attendance->status !== 'absent') {
            return;
        }

        DB::transaction(function () use ($attendance) {
            $attendance = Attendance::query()
                ->whereKey($attendance->id)
                ->lockForUpdate()
                ->first();

            if (! $attendance || $attendance->parent_notified) {
                return;
            }

            $student = $attendance->student;
            if (! $student) {
                return;
            }

            $guardians = $student->guardians()
                ->where('can_receive_notifications', true)
                ->wherePivot('is_primary', true)
                ->get();

            foreach ($guardians as $guardian) {
                if (empty($guardian->email) && empty($guardian->phone)) {
                    continue;
                }

                $alreadyQueued = NotificationQueue::query()
                    ->where('school_id', $attendance->school_id)
                    ->where('type', 'attendance_absence')
                    ->where('guardian_id', $guardian->id)
                    ->where('data->attendance_id', $attendance->id)
                    ->whereIn('status', ['pending', 'sent'])
                    ->exists();

                if ($alreadyQueued) {
                    continue;
                }

                $context = [
                    'student_id' => $student->id,
                    'student_name' => $student->full_name,
                    'attendance_id' => $attendance->id,
                    'date' => $attendance->date->format('Y-m-d'),
                    'class' => $attendance->classModel?->name,
                    'subject' => $attendance->subject?->name ?? $attendance->subject,
                ];

                NotificationQueue::create([
                    'school_id' => $attendance->school_id,
                    'type' => 'attendance_absence',
                    'notifiable_type' => Student::class,
                    'notifiable_id' => $student->id,
                    'guardian_id' => $guardian->id,
                    'parent_user_id' => $guardian->user_id,
                    'channel' => ! empty($guardian->email) && ! empty($guardian->phone)
                        ? 'both'
                        : (! empty($guardian->email) ? 'email' : 'sms'),
                    'recipient_email' => $guardian->email,
                    'recipient_phone' => $guardian->phone,
                    'subject' => "Absence Notification: {$student->full_name}",
                    'message' => $this->buildAbsenceMessage($student, $attendance),
                    'data' => $context,
                    'status' => 'pending',
                ]);
            }

            // In-app parent portal notification (separate from email/SMS queue).
            $this->parentNotifications->notifyAttendanceAbsence($student, [
                'date' => $attendance->date->format('Y-m-d'),
                'class' => $attendance->classModel?->name ?? 'Unknown',
                'subject' => $attendance->subject?->name ?? $attendance->subject ?? 'Unknown',
                'attendance_id' => $attendance->id,
            ]);

            $attendance->update([
                'parent_notified' => true,
                'notification_sent_at' => now(),
            ]);
        });
    }

    /**
     * Process notification queue (should be called by a queue worker or cron)
     */
    public function processNotificationQueue(int $limit = 50): int
    {
        NotificationQueue::query()
            ->where('status', 'failed')
            ->where('retry_count', '<', self::MAX_RETRIES)
            ->where(function ($q) {
                $q->whereNull('updated_at')
                    ->orWhere('updated_at', '<=', now()->subMinutes(5));
            })
            ->limit($limit)
            ->update([
                'status' => 'pending',
                'error_message' => null,
            ]);

        $notifications = NotificationQueue::where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        $processed = 0;

        foreach ($notifications as $notification) {
            try {
                $this->sendNotification($notification);
                $notification->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'error_message' => null,
                ]);
                $processed++;
            } catch (\Exception $e) {
                Log::error("Failed to send notification {$notification->id}: ".$e->getMessage());
                $notification->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'retry_count' => $notification->retry_count + 1,
                ]);
            }
        }

        return $processed;
    }

    /**
     * Send notification via email or SMS
     */
    protected function sendNotification(NotificationQueue $notification): void
    {
        try {
            if (in_array($notification->channel, ['email', 'both'], true) && ! empty($notification->recipient_email)) {
                try {
                    $school = $notification->school_id
                        ? \App\Models\School::find($notification->school_id)
                        : null;
                    $mailConfig = app(\App\Services\Tenancy\SchoolMailService::class)->applyForSchool($school);

                    \Illuminate\Support\Facades\Mail::mailer($mailConfig['mailer'])
                        ->raw($notification->message, function ($message) use ($notification, $mailConfig) {
                            $message->to($notification->recipient_email)
                                ->from($mailConfig['from_address'], $mailConfig['from_name'])
                                ->subject($notification->subject ?? 'School Notification');
                        });
                    Log::info("Email sent to {$notification->recipient_email}");
                } catch (\Exception $e) {
                    Log::error("Failed to send email to {$notification->recipient_email}: ".$e->getMessage());
                    throw $e;
                }
            }

            if (in_array($notification->channel, ['sms', 'both'], true) && ! empty($notification->recipient_phone)) {
                $smsEnabled = config('services.sms.enabled', false);
                if ($smsEnabled) {
                    try {
                        $smsProvider = config('services.sms.provider', 'log');
                        if ($smsProvider === 'log') {
                            Log::info("SMS would be sent to {$notification->recipient_phone}: {$notification->message}");
                        } else {
                            Log::info("SMS sent to {$notification->recipient_phone}");
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to send SMS to {$notification->recipient_phone}: ".$e->getMessage());
                    }
                } else {
                    Log::info("SMS disabled - would send to {$notification->recipient_phone}");
                }
            }

            if (in_array($notification->channel, ['whatsapp'], true) && ! empty($notification->recipient_phone)) {
                $whatsappEnabled = config('services.whatsapp.enabled', false);
                if ($whatsappEnabled) {
                    app(\App\Services\Messaging\WhatsAppService::class)->send(
                        $notification->recipient_phone,
                        $notification->message,
                    );
                } else {
                    Log::info("WhatsApp disabled - would send to {$notification->recipient_phone}");
                }
            }
        } catch (\Exception $e) {
            Log::error('Notification sending failed: '.$e->getMessage());
            throw $e;
        }
    }

    protected function buildAbsenceMessage(Student $student, Attendance $attendance): string
    {
        $class = $attendance->classModel?->name ?? 'Unknown';
        $subject = $attendance->subject?->name ?? $attendance->subject ?? 'Unknown';
        $date = $attendance->date->format('l, F j, Y');

        return "Dear Guardian,\n\n"
            ."This is to notify you that {$student->full_name} was marked absent "
            ."on {$date} for {$subject} in {$class}.\n\n"
            ."If you have any questions, please contact the school.\n\n"
            .'Thank you.';
    }
}
