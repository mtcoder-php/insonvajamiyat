<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\RoleName;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Ro'yxatdan o'tish (TZ 4.1.2).
 *
 * Qaror: web orqali FAQAT muallif ro'yxatdan o'tadi. Xodim rollari
 * (muharrir, taqrizchi, ...) faqat Super Admin tomonidan beriladi —
 * so'rovda "role" maydoni yuborilsa ham e'tiborga olinmaydi.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // "+998 90 123-45-67" → "+998901234567" (validatsiyadan oldin)
        $data = [...$input, 'phone' => PhoneNumber::normalize($input['phone'] ?? null)];

        $validated = Validator::make($data, [
            'last_name' => $this->personNameRules(),
            'first_name' => $this->personNameRules(),
            'email' => $this->emailRules(),
            'phone' => $this->phoneRules(),
            'password' => $this->passwordRules(),
        ], [
            'phone.regex' => __("Telefon raqamini to'g'ri kiriting, masalan: +998 90 123 45 67"),
        ], [
            'last_name' => __('Familiya'),
            'first_name' => __('Ism'),
            'email' => __('Elektron pochta'),
            'phone' => __('Telefon raqam'),
            'password' => __('Parol'),
        ])->validate();

        return DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => trim($validated['last_name'].' '.$validated['first_name']),
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'locale' => app()->getLocale(),
            ]);

            $user->authorProfile()->create([
                'last_name' => $validated['last_name'],
                'first_name' => $validated['first_name'],
            ]);

            $user->assignRole(RoleName::Author);

            return $user;
        });
    }
}
