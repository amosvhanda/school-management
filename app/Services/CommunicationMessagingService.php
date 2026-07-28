<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\CommunicationMessage;
use App\Models\CommunicationThread;
use App\Models\ParentNotification;
use App\Models\Student;
use App\Models\TeacherNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class CommunicationMessagingService
{
    public function __construct(
        private ParentNotificationService $parentNotifications,
        private ParentAccessService $parentAccess,
    ) {}

    public function authorizeStaff(User $user): void
    {
        if (! $user->role instanceof UserRole) {
            abort(403, 'Unauthorized action.');
        }

        if (in_array($user->role, [UserRole::Parent, UserRole::Student], true)) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function assertTeacherCanAccessThread(User $user, CommunicationThread $thread): void
    {
        if ($user->role !== UserRole::Teacher) {
            return;
        }

        if ($thread->staff_user_id !== null && (int) $thread->staff_user_id !== (int) $user->id) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * @return Builder<CommunicationThread>
     */
    public function staffInboxQuery(User $user): Builder
    {
        $schoolId = $user->school_id;

        $query = CommunicationThread::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['student:id,full_name,student_number', 'parent:id,name,email', 'staff:id,name']);

        if ($user->role === UserRole::Teacher) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('staff_user_id')
                    ->orWhere('staff_user_id', $user->id);
            });
        }

        return $query;
    }

    public function appendUnreadCount(Builder $query, int $viewerUserId): Builder
    {
        return $query->withCount([
            'messages as unread_count' => function ($q) use ($viewerUserId) {
                $q->whereNull('read_at')
                    ->where('sender_id', '!=', $viewerUserId);
            },
        ]);
    }

    public function markThreadReadForViewer(CommunicationThread $thread, int $viewerUserId): void
    {
        CommunicationMessage::query()
            ->where('thread_id', $thread->id)
            ->where('sender_id', '!=', $viewerUserId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if (Schema::hasColumn('communication_messages', 'read_by_recipient_at')) {
            CommunicationMessage::query()
                ->where('thread_id', $thread->id)
                ->where('sender_id', '!=', $viewerUserId)
                ->whereNull('read_by_recipient_at')
                ->update(['read_by_recipient_at' => now()]);
        }
    }

    public function createMessage(CommunicationThread $thread, User $sender, string $body): CommunicationMessage
    {
        $message = CommunicationMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        $thread->update([
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        $this->notifyRecipients($thread, $sender, $body);

        return $message;
    }

    public function notifyRecipients(CommunicationThread $thread, User $sender, string $body): void
    {
        $preview = mb_substr(trim($body), 0, 140);
        $subject = $thread->subject ?: 'New message';

        $isParentSender = $sender->role === UserRole::Parent
            || (int) $sender->id === (int) $thread->parent_user_id;

        if ($isParentSender) {
            $this->notifyStaffOfParentMessage($thread, $subject, $preview);
            return;
        }

        $parent = $thread->parent ?? User::find($thread->parent_user_id);
        if (! $parent) {
            return;
        }

        $student = $thread->student_id
            ? Student::query()->find($thread->student_id)
            : null;

        if ($student) {
            $this->parentNotifications->notify(
                $parent,
                $student,
                'message',
                "Message: {$subject}",
                $preview,
                [
                    'thread_id' => $thread->id,
                    'link' => '/portal/hub?tab=messages&thread='.$thread->id,
                ],
            );

            return;
        }

        ParentNotification::create([
            'school_id' => $thread->school_id,
            'parent_user_id' => $parent->id,
            'student_id' => null,
            'type' => 'message',
            'title' => "Message: {$subject}",
            'body' => $preview,
            'data' => [
                'thread_id' => $thread->id,
                'link' => '/portal/hub?tab=messages&thread='.$thread->id,
            ],
        ]);
    }

    private function notifyStaffOfParentMessage(CommunicationThread $thread, string $subject, string $preview): void
    {
        $recipients = collect();

        if ($thread->staff_user_id) {
            $staff = User::query()->find($thread->staff_user_id);
            if ($staff) {
                $recipients->push($staff);
            }
        } else {
            $recipients = User::query()
                ->where('school_id', $thread->school_id)
                ->whereIn('role', [
                    UserRole::Admin->value,
                    UserRole::SchoolAdmin->value,
                    UserRole::Receptionist->value,
                ])
                ->get();
        }

        foreach ($recipients as $staff) {
            TeacherNotification::create([
                'school_id' => $thread->school_id,
                'user_id' => $staff->id,
                'type' => 'message',
                'title' => "Parent message: {$subject}",
                'body' => $preview,
                'link' => '/communications?tab=messages&thread='.$thread->id,
            ]);
        }
    }
}
