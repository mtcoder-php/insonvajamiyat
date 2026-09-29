<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'last_name' => 'Karimov',
            'first_name' => 'Muxtor',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registered_user_becomes_author_with_profile()
    {
        $this->post(route('register.store'), [
            'last_name' => 'Oʻralov',
            'first_name' => 'Gʻayrat',
            'email' => 'author@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'author@example.com')->firstOrFail();

        $this->assertSame('Oʻralov Gʻayrat', $user->name);
        $this->assertTrue($user->hasRole(RoleName::Author));
        $this->assertFalse($user->isStaff());
        $this->assertSame('Oʻralov', $user->authorProfile->last_name);
        $this->assertSame('Gʻayrat', $user->authorProfile->first_name);
    }

    public function test_registration_cannot_assign_staff_role()
    {
        $this->post(route('register.store'), [
            'last_name' => 'Hacker',
            'first_name' => 'Test',
            'email' => 'hacker@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => RoleName::SuperAdmin->value,
            'roles' => [RoleName::Editor->value],
        ]);

        $user = User::where('email', 'hacker@example.com')->firstOrFail();

        $this->assertSame(['author'], $user->getRoleNames()->all());
    }

    public function test_registration_requires_valid_names()
    {
        $response = $this->post(route('register.store'), [
            'last_name' => '<script>',
            'first_name' => '1',
            'email' => 'bad@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['last_name', 'first_name']);
        $this->assertGuest();
    }
}
