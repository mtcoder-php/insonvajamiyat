<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\RoleName;
use App\Models\User;
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
        $validated = Validator::make($input, [
            'last_name' => $this->personNameRules(),
            'first_name' => $this->personNameRules(),
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
        ], [], [
            'last_name' => __('Familiya'),
            'first_name' => __('Ism'),
            'email' => __('Elektron pochta'),
            'password' => __('Parol'),
        ])->validate();

        return DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => trim($validated['last_name'].' '.$validated['first_name']),
                'email' => $validated['email'],
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
