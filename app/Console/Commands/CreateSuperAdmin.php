<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Birinchi (yoki qo'shimcha) Bosh administratorni xavfsiz yaratish.
 * Parol kodda yoki seederda saqlanmaydi — interaktiv kiritiladi.
 *
 *   php artisan app:create-super-admin
 */
#[Signature('app:create-super-admin {--email= : Email manzil} {--name= : F.I.Sh}')]
#[Description('Bosh administrator (Super Admin) hisobini yaratish')]
class CreateSuperAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->option('email') ?: text(
            label: 'Email',
            required: true,
            validate: fn (string $value) => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Email noto\'g\'ri',
        );

        $name = $this->option('name') ?: text(label: 'F.I.Sh', required: true);

        $secret = password(label: 'Parol', required: true);

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $secret],
            [
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $this->callSilently('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);

        $user = DB::transaction(function () use ($email, $name, $secret): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $secret,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole(RoleName::SuperAdmin);

            return $user;
        });

        $this->components->info("Super Admin yaratildi: {$user->email}");
        $this->components->warn('Tavsiya: birinchi kirishda Sozlamalar → Xavfsizlik bo\'limida 2FA ni yoqing.');

        return self::SUCCESS;
    }
}
