<?php

namespace App\Services\Users;

use App\Models\AuthorProfile;
use App\Models\User;

/**
 * Foydalanuvchining shaxsiy va ilmiy ma'lumotlari.
 *
 * Familiya/ism/otasining ismi va ilmiy ma'lumotlar author_profiles jadvalida
 * (barcha foydalanuvchilar uchun 1:1), users.name esa ko'rsatish uchun
 * "Familiya Ism" ko'rinishida sinxron saqlanadi.
 */
class ProfileService
{
    /** Shaxsiy maydonlar (users + author_profiles) */
    public const PERSONAL_FIELDS = ['last_name', 'first_name', 'middle_name', 'email', 'phone', 'locale'];

    /** Ilmiy maydonlar (author_profiles) */
    public const ACADEMIC_FIELDS = [
        'position', 'organization', 'department', 'academic_degree',
        'academic_title', 'orcid', 'city', 'bio',
    ];

    /**
     * Profil yo'q bo'lsa (masalan, eski xodim akkaunti) users.name dan yaratadi.
     */
    public function ensureProfile(User $user): AuthorProfile
    {
        if ($user->authorProfile !== null) {
            return $user->authorProfile;
        }

        [$lastName, $firstName] = array_pad(explode(' ', trim($user->name), 2), 2, '');

        $profile = $user->authorProfile()->create([
            'last_name' => $lastName,
            'first_name' => $firstName,
            // Xodimlar "Mualliflar" ommaviy ro'yxatiga avtomatik chiqmaydi
            'is_public' => ! $user->isStaff(),
        ]);

        $user->setRelation('authorProfile', $profile);

        return $profile;
    }

    /**
     * Shaxsiy ma'lumotlarni saqlaydi. Email o'zgarsa — qayta tasdiqlash talab qilinadi
     * ($keepVerified = true bo'lsa, masalan admin "tasdiqlangan" deb belgilaganda, saqlanadi).
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePersonal(User $user, array $data, bool $keepVerified = false): void
    {
        $profile = $this->ensureProfile($user);

        $profile->fill([
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
        ])->save();

        $user->fill([
            'name' => $profile->displayName(),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'locale' => $data['locale'] ?? $user->locale,
        ]);

        if ($user->isDirty('email') && ! $keepVerified) {
            $user->email_verified_at = null;
        }

        $user->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAcademic(User $user, array $data): void
    {
        $this->ensureProfile($user)
            ->fill(array_intersect_key($data, array_flip(self::ACADEMIC_FIELDS)))
            ->save();
    }
}
