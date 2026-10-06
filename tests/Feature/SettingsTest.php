<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        File::ensureDirectoryExists(public_path('uploads/settings'), 0755, true);
        File::ensureDirectoryExists(public_path('uploads/documents'), 0755, true);
    }

    public function test_admin_can_update_qris_image_with_drag_drop_upload(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Administrator',
            'status' => 'aktif',
        ]);

        $fakeQris = UploadedFile::fake()->image('qris-test.png', 800, 800);

        Livewire::actingAs($user)
            ->test('admin::pengaturan')
            ->set('activeTab', 'rekening')
            ->set('qrisMerchantId', 'ID999888777111')
            ->set('qrisImage', $fakeQris)
            ->call('saveSettings')
            ->assertSet('feedbackMessage', 'Seluruh pengaturan identitas yayasan, rekening, dan saluran media sosial berhasil disimpan ke database.');

        $savedQris = Setting::get('qris_image');
        $this->assertNotNull($savedQris);
        $this->assertStringContainsString('uploads/settings/', $savedQris);
        $this->assertEquals('ID999888777111', Setting::get('qris_merchant_id'));

        // Clean up test file
        if ($savedQris && File::exists(public_path($savedQris))) {
            File::delete(public_path($savedQris));
        }
    }

    public function test_admin_can_remove_qris_image(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Administrator',
            'status' => 'aktif',
        ]);

        Setting::set('qris_image', 'images/qris-vihara.jpg');

        Livewire::actingAs($user)
            ->test('admin::pengaturan')
            ->set('activeTab', 'rekening')
            ->call('removeQrisImage')
            ->call('saveSettings');

        $this->assertNull(Setting::get('qris_image'));
    }

    public function test_admin_can_upload_logo_and_legal_document(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Administrator',
            'status' => 'aktif',
        ]);

        $fakeLogo = UploadedFile::fake()->image('logo-vihara.png', 512, 512);
        $fakeDoc = UploadedFile::fake()->create('sk-kemenag.pdf', 500, 'application/pdf');

        Livewire::actingAs($user)
            ->test('admin::pengaturan')
            ->set('activeTab', 'yayasan')
            ->set('foundationName', 'Yayasan Sāmaggi Gāma Pusat')
            ->set('foundationLegalDocTitle', 'Surat Izin Operasional Resmi')
            ->set('logoImage', $fakeLogo)
            ->set('legalDocFile', $fakeDoc)
            ->call('saveSettings')
            ->assertSet('feedbackMessage', 'Seluruh pengaturan identitas yayasan, rekening, dan saluran media sosial berhasil disimpan ke database.');

        $savedLogo = Setting::get('foundation_logo');
        $savedDoc = Setting::get('foundation_legal_doc');

        $this->assertNotNull($savedLogo);
        $this->assertStringContainsString('uploads/settings/', $savedLogo);

        $this->assertNotNull($savedDoc);
        $this->assertStringContainsString('uploads/documents/', $savedDoc);
        $this->assertEquals('Surat Izin Operasional Resmi', Setting::get('foundation_legal_doc_title'));

        // Clean up test files
        if ($savedLogo && File::exists(public_path($savedLogo))) {
            File::delete(public_path($savedLogo));
        }
        if ($savedDoc && File::exists(public_path($savedDoc))) {
            File::delete(public_path($savedDoc));
        }
    }

    public function test_admin_can_remove_logo_and_legal_document(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Administrator',
            'status' => 'aktif',
        ]);

        Setting::set('foundation_logo', 'uploads/settings/custom-logo.png');
        Setting::set('foundation_legal_doc', 'uploads/documents/sk-izin.pdf');

        Livewire::actingAs($user)
            ->test('admin::pengaturan')
            ->set('activeTab', 'yayasan')
            ->call('removeLogoImage')
            ->call('removeLegalDoc')
            ->call('saveSettings');

        $this->assertNull(Setting::get('foundation_logo'));
        $this->assertNull(Setting::get('foundation_legal_doc'));
    }

    public function test_admin_can_manage_default_donation_admins_repeater(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Administrator',
            'status' => 'aktif',
        ]);

        Livewire::actingAs($user)
            ->test('admin::pengaturan')
            ->assertSet('defaultAdmins', [
                ['role' => 'Ketua', 'name' => '', 'phone' => ''],
                ['role' => 'Sekretaris', 'name' => '', 'phone' => ''],
                ['role' => 'Bendahara', 'name' => '', 'phone' => ''],
            ])
            ->call('addDefaultAdmin')
            ->assertCount('defaultAdmins', 4)
            ->set('defaultAdmins.0.name', 'Hendra Wijaya, S.E.')
            ->set('defaultAdmins.0.phone', '081234567890')
            ->set('defaultAdmins.1.name', 'Ratna Dewi, S.Kom.')
            ->set('defaultAdmins.1.phone', '081298765432')
            ->set('defaultAdmins.2.name', 'Budi Santoso, B.Sc.')
            ->set('defaultAdmins.2.phone', '081377889900')
            ->set('defaultAdmins.3.role', 'Koordinator Dana')
            ->set('defaultAdmins.3.name', 'Upasaka Kevin')
            ->set('defaultAdmins.3.phone', '081511223344')
            ->call('saveSettings');

        $saved = json_decode(Setting::get('donation_default_admins'), true);
        $this->assertIsArray($saved);
        $this->assertCount(4, $saved);
        $this->assertEquals('Hendra Wijaya, S.E.', $saved[0]['name']);
        $this->assertEquals('Koordinator Dana', $saved[3]['role']);
    }
}
