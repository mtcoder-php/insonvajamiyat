<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Muharrirning yakuniy qarori (editorial_decisions.decision) */
enum EditorialDecisionType: string
{
    use EnumHelpers;

    case SendToReview = 'send_to_review';
    case RequestRevision = 'request_revision';
    case Accept = 'accept';
    case Reject = 'reject';

    public function label(): string
    {
        return match ($this) {
            self::SendToReview => __('Taqrizga yuborish'),
            self::RequestRevision => __('Tuzatishga qaytarish'),
            self::Accept => __('Qabul qilish'),
            self::Reject => __('Rad etish'),
        };
    }

    /** Qaror qabul qilingach maqola o'tadigan status */
    public function resultingStatus(): ArticleStatus
    {
        return match ($this) {
            self::SendToReview => ArticleStatus::InReview,
            self::RequestRevision => ArticleStatus::RevisionRequired,
            self::Accept => ArticleStatus::Accepted,
            self::Reject => ArticleStatus::Rejected,
        };
    }
}
