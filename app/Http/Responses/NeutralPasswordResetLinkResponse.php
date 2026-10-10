<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Http\Responses\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;

/**
 * "Parolni unutdingizmi?" — email bazada yo'q bo'lsa yoki shu foydalanuvchiga havola yaqinda
 * yuborilgan bo'lsa ham, javob muvaffaqiyatli yuborilgandagidek bo'ladi. Shunda forma orqali
 * qaysi email ro'yxatdan o'tganini bilib bo'lmaydi (user enumeration). Boshqa holatlar
 * Fortify'ning odatiy xato javobi bilan qaytadi.
 */
class NeutralPasswordResetLinkResponse extends FailedPasswordResetLinkRequestResponse
{
    public function toResponse($request)
    {
        if (in_array($this->status, [Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            return (new SuccessfulPasswordResetLinkRequestResponse(Password::RESET_LINK_SENT))->toResponse($request);
        }

        return parent::toResponse($request);
    }
}
