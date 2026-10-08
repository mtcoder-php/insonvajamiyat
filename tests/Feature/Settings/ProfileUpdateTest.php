<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesFakeImages;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use CreatesFakeImages, RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'last_name' => 'Karimov',
                'first_name' => 'Muxtor',
                'middle_name' => 'Alisher',
                'email' => 'test@example.com',
                'phone' => '+998 90 123 45 67',
                'locale' => 'ru',
                'organization' => 'Yangi Asr universiteti',
                'orcid' => '0000-0002-1825-009x',
                'current_password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Karimov Muxtor', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('+998901234567', $user->phone);
        $this->assertSame('ru', $user->locale);
        $this->assertNull($user->email_verified_at);

        $profile = $user->authorProfile;
        $this->assertNotNull($profile);
        $this->assertSame('Alisher', $profile->middle_name);
        $this->assertSame('Yangi Asr universiteti', $profile->organization);
        $this->assertSame('0000-0002-1825-009X', $profile->orcid);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'last_name' => 'Karimov',
                'first_name' => 'Muxtor',
                'email' => $user->email,
                'locale' => 'uz',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_profile_validation_rejects_invalid_names_and_orcid()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'last_name' => 'K',
                'first_name' => '123',
                'email' => $user->email,
                'locale' => 'xx',
                'orcid' => '1234',
            ])
            ->assertSessionHasErrors(['last_name', 'first_name', 'locale', 'orcid']);
    }

    public function test_user_can_upload_and_remove_avatar()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.avatar.store'), [
                'avatar' => $this->fakePng('me.png', 300, 300),
            ])
            ->assertSessionHasNoErrors();

        $path = $user->refresh()->authorProfile?->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)->delete(route('profile.avatar.destroy'))->assertSessionHasNoErrors();

        $this->assertNull($user->refresh()->authorProfile?->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_avatar_must_be_an_image_of_minimum_size()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.avatar.store'), [
                'avatar' => $this->fakePng('tiny.png', 40, 40),
            ])
            ->assertSessionHasErrors('avatar');

        $this->actingAs($user)
            ->post(route('profile.avatar.store'), [
                'avatar' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('avatar');
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertGuest();

        // Hisob yumshoq o'chiriladi: maqola/to'lov tarixi saqlanadi, lekin qayta kira olmaydi
        $this->assertSoftDeleted($user);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh());
    }
}
