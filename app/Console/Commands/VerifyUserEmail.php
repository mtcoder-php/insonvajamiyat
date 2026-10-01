<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Foydalanuvchi emailini qo'lda tasdiqlash (masalan, pochta hali sozlanmagan
 * paytda administrator o'z manzilini o'zgartirib, panelga kira olmay qolsa).
 *
 *   php artisan app:verify-email admin@insonvajamiyat.uz
 */
#[Signature('app:verify-email {email : Tasdiqlanadigan email manzil}')]
#[Description('Foydalanuvchi elektron pochtasini tasdiqlangan deb belgilash')]
class VerifyUserEmail extends Command
{
    public function handle(): int
    {
        $argument = $this->argument('email');
        $email = is_string($argument) ? mb_strtolower(trim($argument)) : '';
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("Bunday email bilan foydalanuvchi topilmadi: {$email}");

            return self::FAILURE;
        }

        if ($user->hasVerifiedEmail()) {
            $this->info("{$user->name} ({$email}) — email allaqachon tasdiqlangan.");

            return self::SUCCESS;
        }

        $user->markEmailAsVerified();

        $this->info("{$user->name} ({$email}) — email tasdiqlandi.");

        return self::SUCCESS;
    }
}
