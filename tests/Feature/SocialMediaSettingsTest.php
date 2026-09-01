<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialMediaSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin Vihara',
            'role' => 'Super Admin',
            'status' => 'aktif',
        ]);
    }

    public function test_admin_can_switch_to_sosmed_tab_and_save_links(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin::pengaturan')
            ->call('setTab', 'sosmed')
            ->assertSet('activeTab', 'sosmed')
            ->set('socialYoutube', 'https://youtube.com/@samaggigama_official')
            ->set('socialInstagram', 'https://instagram.com/samaggigama_vihara')
            ->set('socialFacebook', 'https://facebook.com/samaggigama.id')
            ->set('socialTiktok', 'https://tiktok.com/@samaggigama_dhamma')
            ->set('socialWhatsapp', 'https://wa.me/6281299887766')
            ->set('socialSpotify', 'https://open.spotify.com/show/dhamma-samaggi')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertEquals('https://youtube.com/@samaggigama_official', Setting::get('social_youtube'));
        $this->assertEquals('https://instagram.com/samaggigama_vihara', Setting::get('social_instagram'));
        $this->assertEquals('https://facebook.com/samaggigama.id', Setting::get('social_facebook'));
        $this->assertEquals('https://tiktok.com/@samaggigama_dhamma', Setting::get('social_tiktok'));
        $this->assertEquals('https://wa.me/6281299887766', Setting::get('social_whatsapp'));
        $this->assertEquals('https://open.spotify.com/show/dhamma-samaggi', Setting::get('social_spotify'));
    }

    public function test_public_pages_render_configured_social_media_links(): void
    {
        Setting::set('social_youtube', 'https://youtube.com/@samaggigama_test');
        Setting::set('social_instagram', 'https://instagram.com/samaggigama_test');

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('https://youtube.com/@samaggigama_test');
        $response->assertSee('https://instagram.com/samaggigama_test');
    }
}
