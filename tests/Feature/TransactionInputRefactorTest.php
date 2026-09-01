<?php

namespace Tests\Feature;

use App\Models\CertificateTemplate;
use App\Models\Donation;
use App\Models\DonationProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionInputRefactorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected DonationProgram $program;
    protected CertificateTemplate $tplUmum;
    protected CertificateTemplate $tplAlm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin Vihara',
            'role' => 'Super Admin',
            'status' => 'aktif',
        ]);

        $this->program = DonationProgram::create([
            'title' => 'Renovasi Dhammasala Utama',
            'slug' => 'renovasi-dhammasala-utama',
            'category' => 'Sarana & Prasarana',
            'target_amount' => 50000000,
            'status' => 'aktif',
        ]);

        $this->tplUmum = CertificateTemplate::create([
            'name' => 'Piagam Anumodana Dānapati',
            'category' => 'umum',
            'slug' => 'piagam-anumodana-danapati',
            'orientation' => 'landscape',
            'background_image' => 'images/piagam-bg.png',
            'status' => 'aktif',
        ]);

        $this->tplAlm = CertificateTemplate::create([
            'name' => 'Piagam Pattidāna Pelimpahan Jasa Alm.',
            'category' => 'alm',
            'slug' => 'piagam-pattidana-pelimpahan-jasa-alm',
            'orientation' => 'landscape',
            'background_image' => 'images/piagam-bg.png',
            'status' => 'aktif',
        ]);
    }

    public function test_admin_can_record_donation_with_whatsapp_and_piagam_category(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin::transaksi')
            ->call('openCreateModal')
            ->set('formProgramId', $this->program->id)
            ->set('formDonorName', 'Alm. Bpk. Sugiarto Tanujaya')
            ->set('formPhone', '081234567890')
            ->set('formCategory', 'alm')
            ->set('formCertificateTemplateId', $this->tplAlm->id)
            ->set('formAmount', '5000000')
            ->call('saveDonation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('donations', [
            'donor_name' => 'Alm. Bpk. Sugiarto Tanujaya',
            'phone' => '081234567890',
            'amount' => 5000000,
            'status' => 'verified',
        ]);

        $donation = Donation::where('donor_name', 'Alm. Bpk. Sugiarto Tanujaya')->first();
        $this->assertNotNull($donation->issuedCertificate);
        $this->assertEquals($this->tplAlm->id, $donation->issuedCertificate->certificate_template_id);
    }

    public function test_changing_piagam_category_switches_available_templates(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin::transaksi')
            ->set('formCategory', 'alm')
            ->assertSet('formCertificateTemplateId', $this->tplAlm->id)
            ->set('formCategory', 'umum')
            ->assertSet('formCertificateTemplateId', $this->tplUmum->id);
    }
}
