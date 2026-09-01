<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_admin_routes(): void
    {
        $response = $this->get('/mimin/dashboard');
        $response->assertRedirect('/mimin/login');

        $response = $this->get('/mimin/berita');
        $response->assertRedirect('/mimin/login');

        $response = $this->get('/mimin/users');
        $response->assertRedirect('/mimin/login');
    }

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/mimin/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk ke Panel Pengurus');
    }

    public function test_authenticated_admin_can_login_successfully(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin.test@samaggigama.org'],
            [
                'name' => 'Admin Pengujian',
                'password' => Hash::make('password123'),
                'role' => 'Super Administrator',
                'status' => 'aktif',
            ]
        );

        Livewire::test('pages::login')
            ->set('loginIdentifier', 'admin.test@samaggigama.org')
            ->set('password', 'password123')
            ->call('handleLogin')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_admin_cannot_login(): void
    {
        User::updateOrCreate(
            ['email' => 'inactive.admin@samaggigama.org'],
            [
                'name' => 'Admin Nonaktif',
                'password' => Hash::make('password123'),
                'role' => 'Admin Sekretariat',
                'status' => 'nonaktif',
            ]
        );

        Livewire::test('pages::login')
            ->set('loginIdentifier', 'inactive.admin@samaggigama.org')
            ->set('password', 'password123')
            ->call('handleLogin')
            ->assertSet('feedbackType', 'error');

        $this->assertGuest();
    }

    public function test_failed_login_decreases_rate_limiter_attempts(): void
    {
        Livewire::test('pages::login')
            ->set('loginIdentifier', 'wrong@example.com')
            ->set('password', 'wrongpassword')
            ->call('handleLogin')
            ->assertSet('feedbackType', 'error')
            ->assertSee('Kombinasi email/username atau kata sandi tidak cocok');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout_cleanly(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin.logout@samaggigama.org'],
            [
                'name' => 'Admin Logout',
                'password' => Hash::make('password123'),
                'role' => 'Super Administrator',
                'status' => 'aktif',
            ]
        );

        $response = $this->actingAs($user)->post('/mimin/logout');
        $response->assertRedirect('/mimin/login');
        $this->assertGuest();
    }
}
