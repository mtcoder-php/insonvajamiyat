<?php

namespace App\Jobs;

use App\Services\Settings\LaunchReadiness;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

/**
 * Navbat ishchisi (worker) tirikligini tekshirish: rejalashtiruvchi har 5 daqiqada
 * navbatga qo'yadi, worker bajarganda vaqt keshga yoziladi ("Tizim holati" o'qiydi).
 */
class QueueHeartbeat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function handle(): void
    {
        Cache::forever(LaunchReadiness::QUEUE_HEARTBEAT, now()->getTimestamp());
    }
}
