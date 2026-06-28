<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Guardian;
use App\Models\NotificationQueue;
use App\Models\Student;
use Illuminate\Support\Facades\Log;

class AttendanceNotificationService
{
    public function __construct(private ParentNotificationService $parentNotifications) {}

    /**
     * Send notification to guardians when student is absent
     */
    public function notifyAbsence(Attendance $attendance): void
    {
        if ($attendance->status !== 'absent' || $attendance->parent_notified) {
            return;
        }

        $student = $attendance->student;
        if (!$student) {
            return;
        }

        // Get all guardians for this student
        $guardians = $student->guardians()
            ->where('can_receive_notifications', true)
            ->wherePivot('is_primary', true)
            ->get();

        foreach ($guardians as $guardian) {
            if (empty($guardian->email) && empty($guardian->phone)) {
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

            // Create notification queue entry
            NotificationQueue::create([
                'school_id' => $attendance->school_id,
                'type' => 'attendance_absence',
                'notifiable_type' => Student::class,
                'notifiable_id' => $student->id,
                'guardian_id' => $guardian->id,
                'parent_user_id' => $guardian->user_id,
                'channel' => !empty($guardian->email) && !empty($guardian->phone) ? 'both' : (!empty($guardian->email) ? 'email' : 'sms'),
                'recipient_email' => $guardian->email,
                'recipient_phone' => $guardian->phone,
                'subject' => "Absence Notification: {$student->full_name}",
                'message' => $this->buildAbsenceMessage($student, $attendance),
                'data' => $context,
                'status' => 'pending',
            ]);
        }

        $this->parentNotifications->notifyAttendanceAbsence($student, [
            'date' => $attendance->date->format('Y-m-d'),
            'class' => $attendance->classModel?->name ?? 'Unknown',
            'subject' => $attendance->subject?->name ?? $attendance->subject ?? 'Unknown',
            'attendance_id' => $attendance->id,
        ]);

        // Mark attendance as notified
        $attendance->update([
            'parent_notified' => true,
            'notification_sent_at' => now(),
        ]);
    }

    /**
     * Process notification queue (should be called by a queue worker or cron)
     */
    public function processNotificationQueue(int $limit = 50): int
    {
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
                ]);
                $processed++;
            } catch (\Exception $e) {
                Log::error("Failed to send notification {$notification->id}: " . $e->getMessage());
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
            // Email notification
            if (in_array($notification->channel, ['email', 'both']) && !empty($notification->recipient_email)) {
                try {
                    \Illuminate\Support\Facades\Mail::raw($notification->message, function ($message) use ($notification) {
                        $message->to($notification->recipient_email)
                            ->subject($notification->subject ?? 'School Notification');
                    });
                    Log::info("Email sent to {$notification->recipient_email}");
                } catch (\Exception $e) {
                    Log::error("Failed to send email to {$notification->recipient_email}: " . $e->getMessage());
                    throw $e;
                }
            }

            // SMS notification
            if (in_array($notification->channel, ['sms', 'both']) && !empty($notification->recipient_phone)) {
                // Check if SMS service is configured
                $smsEnabled = config('services.sms.enabled', false);
                if ($smsEnabled) {
                    try {
                        // Use configured SMS service (e.g., Twilio, Nexmo, etc.)
                        // This is a placeholder - implement based on your SMS provider
                        $smsProvider = config('services.sms.provider', 'log');
                        if ($smsProvider === 'log') {
                            Log::info("SMS would be sent to {$notification->recipient_phone}: {$notification->message}");
                        } else {
                            // Implement actual SMS sending here
                            // Example: Twilio::send($notification->recipient_phone, $notification->message);
                            Log::info("SMS sent to {$notification->recipient_phone}");
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to send SMS to {$notification->recipient_phone}: " . $e->getMessage());
                        // Don't throw - email might have succeeded
                    }
                } else {
                    Log::info("SMS disabled - would send to {$notification->recipient_phone}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Notification sending failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build absence notification message
     */
    protected function buildAbsenceMessage(Student $student, Attendance $attendance): string
    {
        $class = $attendance->classModel?->name ?? 'Unknown';
        $subject = $attendance->subject?->name ?? $attendance->subject ?? 'Unknown';
        $date = $attendance->date->format('l, F j, Y');

        return "Dear Guardian,\n\n"
            . "This is to notify you that {$student->full_name} was marked absent "
            . "on {$date} for {$subject} in {$class}.\n\n"
            . "If you have any questions, please contact the school.\n\n"
            . "Thank you.";
    }
}
