<?php

namespace App\Http\Resources\Admin;

use App\Models\Article;
use App\Services\Payments\PaymentReminderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin → To'lovlar → "To'lov kutilmoqda": nashr to'lovi tasdiqlanishini kutayotgan maqola.
 *
 * @mixin Article
 */
class AwaitingPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'subject' => $this->subject?->name,
            'type' => $this->articleType->name,
            'amountDue' => (float) $this->articleType->price,
            'currency' => $this->articleType->currency,
            'author' => [
                'name' => $this->submitter->name,
                'email' => $this->submitter->email,
            ],
            'submittedAt' => $this->submitted_at?->toIso8601String(),
            'waitingDays' => $this->submitted_at !== null
                ? (int) $this->submitted_at->diffInDays(now(), true)
                : null,
            'reminders' => [
                'count' => $this->payment_reminders_count,
                'lastAt' => $this->payment_reminded_at?->toIso8601String(),
                'availableAt' => $this->resource instanceof Article ? PaymentReminderService::availableAt($this->resource)?->toIso8601String() : null,
            ],
            'urls' => [
                'remind' => route('admin.payments.remind', $this->uuid),
                'confirm' => route('admin.payments.confirm', $this->uuid),
                'waive' => route('admin.payments.waive', $this->uuid),
            ],
        ];
    }
}
