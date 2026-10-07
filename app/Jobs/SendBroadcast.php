<?php

namespace App\Jobs;

use App\Models\Broadcast;
use App\Notifications\BroadcastNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Ommaviy xabarni auditoriyaga tarqatish: foydalanuvchilar 200 tadan bo'lib olinadi,
 * har biriga BroadcastNotification (o'zi navbatga qo'yiladi). Holat broadcasts jadvalida.
 */
class SendBroadcast implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public readonly Broadcast $broadcast) {}

    public function handle(): void
    {
        $broadcast = $this->broadcast;
        $broadcast->forceFill(['status' => Broadcast::SENDING])->save();

        $sent = 0;

        $broadcast->audience->query()->chunkById(200, function (Collection $users) use ($broadcast, &$sent): void {
            Notification::send($users, new BroadcastNotification($broadcast));
            $sent += $users->count();
            $broadcast->forceFill(['sent_count' => $sent])->save();
        });

        $broadcast->forceFill([
            'status' => Broadcast::SENT,
            'sent_count' => $sent,
            'sent_at' => now(),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        $this->broadcast->forceFill([
            'status' => Broadcast::FAILED,
            'error' => mb_substr((string) $exception?->getMessage(), 0, 500),
        ])->save();
    }
}
