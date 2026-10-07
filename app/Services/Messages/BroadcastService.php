<?php

namespace App\Services\Messages;

use App\Enums\AuditEvent;
use App\Enums\BroadcastAudience;
use App\Jobs\SendBroadcast;
use App\Models\Broadcast;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Ommaviy xabar: auditoriyani tanlash (oldindan soni ko'rsatiladi), navbatga qo'yish va tarix.
 */
class BroadcastService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return array<int, array{value: string, label: string, count: int}>
     */
    public function audiences(): array
    {
        return array_map(fn (BroadcastAudience $a): array => [
            'value' => $a->value,
            'label' => $a->label(),
            'count' => $a->query()->count(),
        ], BroadcastAudience::cases());
    }

    /**
     * @param  array{subject: string, body: string, audience: BroadcastAudience, send_email: bool}  $data
     */
    public function send(array $data, User $sender): Broadcast
    {
        $recipients = $data['audience']->query()->count();

        if ($recipients === 0) {
            throw ValidationException::withMessages([
                'audience' => __("Tanlangan guruhda faol foydalanuvchi yo'q."),
            ]);
        }

        $broadcast = new Broadcast([
            'subject' => $data['subject'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'send_email' => $data['send_email'],
            'sender_id' => $sender->id,
        ]);
        $broadcast->forceFill(['status' => Broadcast::QUEUED, 'recipients_count' => $recipients])->save();

        $this->audit->log(AuditEvent::BroadcastSent, null, [
            'subject' => $data['subject'],
            'audience' => $data['audience']->value,
            'recipients' => $recipients,
            'email' => $data['send_email'],
        ], actor: $sender);

        SendBroadcast::dispatch($broadcast);

        return $broadcast;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(int $limit = 30): array
    {
        return Broadcast::query()
            ->with('sender:id,name')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Broadcast $b): array => [
                'uuid' => $b->uuid,
                'subject' => $b->subject,
                'body' => $b->body,
                'audience' => $b->audience->label(),
                'sendEmail' => $b->send_email,
                'status' => $b->status,
                'recipients' => $b->recipients_count,
                'sent' => $b->sent_count,
                'sender' => $b->sender->name,
                'createdAt' => $b->created_at?->toIso8601String(),
                'sentAt' => $b->sent_at?->toIso8601String(),
                'error' => $b->error,
            ])
            ->all();
    }
}
