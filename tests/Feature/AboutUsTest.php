<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AboutUsTest extends TestCase
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

    public function test_guest_cannot_access_tentang_kami_page(): void
    {
        $response = $this->get('/mimin/tentang-kami');
        $response->assertRedirect('/mimin/login');
    }

    public function test_admin_can_access_tentang_kami_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/mimin/tentang-kami');
        $response->assertStatus(200);
        $response->assertSee('Kelola Profil Tentang Kami');
    }

    public function test_admin_can_update_tentang_kami_settings(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin::tentang-kami')
            ->set('about_badge', 'Profil Singkat Vihara')
            ->set('about_title', 'Pusat Meditasi dan Kebajikan Luhur')
            ->set('about_content', '<p>Vihara Sāmaggi Gāma adalah pusat meditasi dan kedamaian batin.</p>')
            ->set('about_image_badge', 'Gazebo Taman Bodhi')
            ->set('about_image_quote', 'Damai di hati, damai di dunia.')
            ->set('about_pillar1_title', 'Sila Utama')
            ->set('about_pillar1_desc', 'Disiplin moralitas yang teguh.')
            ->call('save');

        $this->assertEquals('Profil Singkat Vihara', Setting::get('about_badge'));
        $this->assertEquals('Pusat Meditasi dan Kebajikan Luhur', Setting::get('about_title'));
        $this->assertEquals('<p>Vihara Sāmaggi Gāma adalah pusat meditasi dan kedamaian batin.</p>', Setting::get('about_content'));
        $this->assertEquals('Gazebo Taman Bodhi', Setting::get('about_image_badge'));
        $this->assertEquals('Damai di hati, damai di dunia.', Setting::get('about_image_quote'));
        $this->assertEquals('Sila Utama', Setting::get('about_pillar1_title'));
        $this->assertEquals('Disiplin moralitas yang teguh.', Setting::get('about_pillar1_desc'));
    }

    public function test_public_homepage_displays_updated_tentang_kami_settings(): void
    {
        Setting::set('about_badge', 'Profil Resmi Vihara');
        Setting::set('about_title', 'Menebar Kebajikan ke Segala Penjuru');
        Setting::set('about_content', '<p>Selamat datang di rumah pembinaan batin kita bersama.</p>');
        Setting::set('about_image_badge', 'Ruang Meditasi Hening');

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Profil Resmi Vihara');
        $response->assertSee('Menebar Kebajikan ke Segala Penjuru');
        $response->assertSee('Selamat datang di rumah pembinaan batin kita bersama.', false);
        $response->assertSee('Ruang Meditasi Hening');
        $response->assertSee('/tentang-kami');
    }

    public function test_dedicated_public_tentang_kami_page_renders_full_content(): void
    {
        Setting::set('about_badge', 'Profil Lengkap Samaggi Gama');
        Setting::set('about_title', 'Perjalanan Menemukan Kedamaian Batin Sejati');
        Setting::set('about_content', '<p>Narasi lengkap sejarah pendirian vihara dan komitmen pelayanan umat se-Nusantara.</p>');
        Setting::set('about_pillar1_title', 'Kemoralan Murni');

        $response = $this->get('/tentang-kami');
        $response->assertStatus(200);
        $response->assertSee('Profil Lengkap Samaggi Gama');
        $response->assertSee('Perjalanan Menemukan Kedamaian Batin Sejati');
        $response->assertSee('Narasi lengkap sejarah pendirian vihara dan komitmen pelayanan umat se-Nusantara.', false);
        $response->assertSee('Kemoralan Murni');
    }
}
