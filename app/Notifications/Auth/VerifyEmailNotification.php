<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

/**
 * Elektron pochtani tasdiqlash xati (o'zbekcha, jurnal uslubida).
 * Havola imzolangan va auth.verification.expire daqiqa amal qiladi.
 * Navbat orqali yuboriladi (SMTP sekin bo'lsa ham forma kutmaydi).
 */
class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);
        $minutes = (int) Config::get('auth.verification.expire', 60);
        $name = $notifiable instanceof User ? $notifiable->name : null;

        return (new MailMessage)
            ->subject(__('Elektron pochtangizni tasdiqlang — :app', ['app' => config('app.name')]))
            ->greeting($name ? __('Assalomu alaykum, :name!', ['name' => $name]) : __('Assalomu alaykum!'))
            ->line(__("«Inson va Jamiyat» ilmiy jurnali tizimida akkauntingiz uchun ushbu elektron pochta manzili ko'rsatildi.")
            )
            ->line(__('Manzilni tasdiqlash uchun quyidagi tugmani bosing:'))
            ->action(__('Pochtani tasdiqlash'), $url)
            ->line(__('Havola :minutes daqiqa davomida amal qiladi.', ['minutes' => $minutes]))
            ->line(__("Agar siz ro'yxatdan o'tmagan bo'lsangiz, bu xatga e'tibor bermang — hech qanday amal talab qilinmaydi."))
            ->salutation(__("Hurmat bilan,\n«Inson va Jamiyat» tahririyati"));
    }
}
