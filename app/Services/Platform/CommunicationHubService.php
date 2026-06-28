<?php

namespace App\Services\Platform;

use App\Models\HubMessage;
use App\Models\HubMessageDelivery;
use App\Models\NotificationQueue;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommunicationHubService
{
    public const CHANNELS = ['email', 'sms', 'whatsapp', 'push'];

    public function send(User $sender, array $data): HubMessage
    {
        $channels = array_values(array_intersect($data['channels'] ?? ['email'], self::CHANNELS));
        $recipientIds = $this->resolveRecipients($sender->school_id, $data);

        return DB::transaction(function () use ($sender, $data, $channels, $recipientIds) {
            $message = HubMessage::create([
                'school_id' => $sender->school_id,
                'sender_id' => $sender->id,
                'subject' => $data['subject'] ?? null,
                'body' => $data['body'],
                'channels' => $channels,
                'audience_type' => $data['audience_type'] ?? 'individual',
                'audience_ids' => $recipientIds,
                'status' => 'processing',
            ]);

            $stats = ['total' => 0, 'delivered' => 0, 'read' => 0, 'failed' => 0];

            foreach ($recipientIds as $recipientId) {
                $recipient = User::find($recipientId);
                if (! $recipient) {
                    continue;
                }

                foreach ($channels as $channel) {
                    $delivery = HubMessageDelivery::create([
                        'hub_message_id' => $message->id,
                        'recipient_user_id' => $recipient->id,
                        'channel' => $channel,
                        'recipient_address' => $this->addressForChannel($recipient, $channel),
                        'status' => 'delivered',
                        'delivered_at' => now(),
                    ]);

                    $this->queueLegacyNotification($sender->school_id, $recipient, $channel, $data);
                    $stats['total']++;
                    $stats['delivered']++;
                }
            }

            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
                'delivery_stats' => $stats,
            ]);

            return $message->fresh(['deliveries']);
        });
    }

    public function markRead(HubMessageDelivery $delivery, User $user): HubMessageDelivery
    {
        if ($delivery->recipient_user_id !== $user->id) {
            abort(403, 'Cannot mark another user\'s message as read.');
        }

        $delivery->update(['read_at' => now(), 'status' => 'read']);

        $message = $delivery->message;
        $stats = $message->delivery_stats ?? [];
        $stats['read'] = ($stats['read'] ?? 0) + 1;
        $message->update(['delivery_stats' => $stats]);

        return $delivery->fresh();
    }

    public function trackingForSchool(int $schoolId, ?int $messageId = null)
    {
        $query = HubMessageDelivery::query()
            ->whereHas('message', fn ($q) => $q->where('school_id', $schoolId))
            ->with(['message:id,subject,body,sent_at', 'recipient:id,name,email']);

        if ($messageId) {
            $query->where('hub_message_id', $messageId);
        }

        return $query->orderByDesc('id')->limit(200)->get();
    }

    protected function resolveRecipients(int $schoolId, array $data): array
    {
        if (($data['audience_type'] ?? 'individual') === 'all_staff') {
            return User::where('school_id', $schoolId)
                ->whereIn('role', ['admin', 'school_admin', 'teacher', 'finance', 'accounts'])
                ->pluck('id')
                ->all();
        }

        return array_values(array_unique($data['recipient_ids'] ?? []));
    }

    protected function addressForChannel(User $user, string $channel): ?string
    {
        return match ($channel) {
            'email' => $user->email,
            'sms', 'whatsapp' => $user->phone ?? null,
            'push' => 'device:'.$user->id,
            default => null,
        };
    }

    protected function queueLegacyNotification(int $schoolId, User $recipient, string $channel, array $data): void
    {
        NotificationQueue::create([
            'school_id' => $schoolId,
            'type' => 'hub_message',
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'channel' => $channel,
            'recipient_email' => $channel === 'email' ? $recipient->email : null,
            'recipient_phone' => in_array($channel, ['sms', 'whatsapp'], true) ? $recipient->phone : null,
            'subject' => $data['subject'] ?? 'School message',
            'message' => $data['body'],
            'status' => 'pending',
        ]);
    }
}
