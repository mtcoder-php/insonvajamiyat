<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Parolni tiklash xati (o'zbekcha). Foydalanuvchi o'zi so'raganda ham,
 * administrator "Tiklash havolasini yuborish" bosganda ham shu xat ketadi.
 * Navbat orqali yuboriladi (SMTP sekin bo'lsa ham forma kutmaydi).
 */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $broker = (string) config('auth.defaults.passwords');
        $minutes = (int) config("auth.passwords.{$broker}.expire", 60);
        $name = $notifiable instanceof User ? $notifiable->name : null;

        return (new MailMessage)
            ->subject(__('Parolni tiklash — :app', ['app' => config('app.name')]))
            ->greeting($name ? __('Assalomu alaykum, :name!', ['name' => $name]) : __('Assalomu alaykum!'))
            ->line(__("Akkauntingiz uchun parolni tiklash so'rovi olindi."))
            ->action(__("Yangi parol o'rnatish"), $url)
            ->line(__('Havola :minutes daqiqa davomida amal qiladi.', ['minutes' => $minutes]))
            ->line(__("Agar siz so'rov yubormagan bo'lsangiz, bu xatga e'tibor bermang — parolingiz o'zgarmaydi."))
            ->salutation(__("Hurmat bilan,\n«Inson va Jamiyat» tahririyati"));
    }
}
