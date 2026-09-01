<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_all_admin_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->get('/mimin/dashboard')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/mimin/transaksi')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/mimin/pengeluaran')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/mimin/donasi')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/mimin/users')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/mimin/pengaturan')
            ->assertStatus(200);
    }

    public function test_bendahara_can_access_allowed_financial_routes(): void
    {
        $bendahara = User::factory()->create([
            'role' => 'Bendahara',
            'status' => 'aktif',
        ]);

        $this->actingAs($bendahara)
            ->get('/mimin/dashboard')
            ->assertStatus(200);

        $this->actingAs($bendahara)
            ->get('/mimin/transaksi')
            ->assertStatus(200);

        $this->actingAs($bendahara)
            ->get('/mimin/pengeluaran')
            ->assertStatus(200);

        $this->actingAs($bendahara)
            ->get('/mimin/profil')
            ->assertStatus(200);
    }

    public function test_bendahara_cannot_access_restricted_admin_routes(): void
    {
        $bendahara = User::factory()->create([
            'role' => 'Bendahara',
            'status' => 'aktif',
        ]);

        // Attempting to access admin-only routes should redirect to dashboard
        $this->actingAs($bendahara)
            ->get('/mimin/donasi')
            ->assertRedirect('/mimin/dashboard');

        $this->actingAs($bendahara)
            ->get('/mimin/users')
            ->assertRedirect('/mimin/dashboard');

        $this->actingAs($bendahara)
            ->get('/mimin/pengaturan')
            ->assertRedirect('/mimin/dashboard');

        $this->actingAs($bendahara)
            ->get('/mimin/piagam')
            ->assertRedirect('/mimin/dashboard');

        $this->actingAs($bendahara)
            ->get('/mimin/berita')
            ->assertRedirect('/mimin/dashboard');
    }
}
