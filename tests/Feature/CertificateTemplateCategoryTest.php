<?php

namespace Tests\Feature;

use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateTemplateCategoryTest extends TestCase
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

    public function test_admin_can_view_separated_certificate_categories(): void
    {
        $tplUmum = CertificateTemplate::create([
            'name' => 'Piagam Anumodana Dānapati Utama',
            'category' => 'umum',
            'slug' => 'piagam-anumodana-danapati-utama',
            'orientation' => 'landscape',
            'background_image' => 'images/piagam-maha-anumodana.png',
            'status' => 'aktif',
        ]);

        $tplAlm = CertificateTemplate::create([
            'name' => 'Piagam Pattidāna Pelimpahan Jasa Leluhur',
            'category' => 'alm',
            'slug' => 'piagam-pattidana-pelimpahan-jasa-leluhur',
            'orientation' => 'landscape',
            'background_image' => 'images/piagam-maha-anumodana.png',
            'status' => 'aktif',
        ]);

        $component = Livewire::actingAs($this->admin)->test('admin::piagam');

        $component->assertSee('Piagam Anumodana Dānapati Utama')
            ->assertSee('Piagam Pattidāna Pelimpahan Jasa Leluhur')
            ->assertSee('Piagam Donatur / Umum')
            ->assertSee('Pelimpahan Jasa (Alm.)')
            ->assertStatus(200);

        // Filter by Alm tab
        $component->call('setCategoryTab', 'alm')
            ->assertSee('Piagam Pattidāna Pelimpahan Jasa Leluhur')
            ->assertDontSee('Piagam Anumodana Dānapati Utama');

        // Filter by Umum tab
        $component->call('setCategoryTab', 'umum')
            ->assertSee('Piagam Anumodana Dānapati Utama')
            ->assertDontSee('Piagam Pattidāna Pelimpahan Jasa Leluhur');
    }

    public function test_admin_can_save_certificate_template_with_category_option(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin::piagam')
            ->set('formName', 'Piagam Pattidāna Mendiang Ayahanda')
            ->set('formCategory', 'alm')
            ->set('formOrientation', 'landscape')
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('certificate_templates', [
            'name' => 'Piagam Pattidāna Mendiang Ayahanda',
            'category' => 'alm',
            'orientation' => 'landscape',
        ]);
    }

    public function test_admin_can_edit_existing_certificate_template(): void
    {
        $tpl = CertificateTemplate::create([
            'name' => 'Piagam Original',
            'category' => 'umum',
            'slug' => 'piagam-original',
            'orientation' => 'landscape',
            'background_image' => 'images/piagam-maha-anumodana.png',
            'status' => 'aktif',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::piagam')
            ->call('openEditModal', $tpl->id)
            ->assertSet('isEditing', true)
            ->assertSet('editingTemplateId', $tpl->id)
            ->assertSet('formName', 'Piagam Original')
            ->set('formName', 'Piagam Edited Title')
            ->set('formCategory', 'alm')
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('certificate_templates', [
            'id' => $tpl->id,
            'name' => 'Piagam Edited Title',
            'category' => 'alm',
        ]);
    }
}
