<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\DonationProgram;
use App\Models\User;
use App\Services\DonationImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class DonationImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_imports_csv_and_automatically_creates_program(): void
    {
        $csvContent = <<<CSV
KegiatanID,Nama,Jumlah
Thavara Dana Tahap I Pembebasan Lahan,LENI,1000000
Thavara Dana Tahap I Pembebasan Lahan,BPK. LIAUW ENG LU,200000
CSV;

        $tempFile = tempnam(sys_get_temp_dir(), 'import_test_') . '.csv';
        file_put_contents($tempFile, $csvContent);

        $service = new DonationImportService();
        $result = $service->import($tempFile);

        @unlink($tempFile);

        $this->assertEquals(2, $result['imported']);
        $this->assertCount(1, $result['created_programs']);
        $this->assertEquals('Thavara Dana Tahap I Pembebasan Lahan', $result['created_programs'][0]);

        // Program was automatically created
        $program = DonationProgram::where('title', 'Thavara Dana Tahap I Pembebasan Lahan')->first();
        $this->assertNotNull($program);
        $this->assertEquals('aktif', $program->status);

        // Donations created
        $this->assertDatabaseHas('donations', [
            'donation_program_id' => $program->id,
            'donor_name' => 'LENI',
            'amount' => 1000000,
            'status' => 'verified',
        ]);

        $this->assertDatabaseHas('donations', [
            'donation_program_id' => $program->id,
            'donor_name' => 'BPK. LIAUW ENG LU',
            'amount' => 200000,
            'status' => 'verified',
        ]);
    }

    public function test_service_attaches_to_existing_program_without_creating_duplicate(): void
    {
        $existing = DonationProgram::create([
            'title' => 'Renovasi Dhammasala',
            'slug' => 'renovasi-dhammasala',
            'category' => 'Pembangunan & Sarana',
            'target_amount' => 50000000,
            'status' => 'aktif',
        ]);

        $csvContent = <<<CSV
KegiatanID,Nama,Jumlah
Renovasi Dhammasala,Upasaka Kevin,500000
CSV;

        $tempFile = tempnam(sys_get_temp_dir(), 'import_test_') . '.csv';
        file_put_contents($tempFile, $csvContent);

        $service = new DonationImportService();
        $result = $service->import($tempFile);

        @unlink($tempFile);

        $this->assertEquals(1, $result['imported']);
        $this->assertEmpty($result['created_programs']); // No new program created
        $this->assertEquals(1, DonationProgram::count()); // Total programs remains 1

        $this->assertDatabaseHas('donations', [
            'donation_program_id' => $existing->id,
            'donor_name' => 'Upasaka Kevin',
            'amount' => 500000,
        ]);
    }

    public function test_livewire_transaksi_imports_uploaded_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csvContent = <<<CSV
KegiatanID,Nama,Jumlah
Thavara Dana Tahap I Pembebasan Lahan,LENI,1000000
Thavara Dana Tahap I Pembebasan Lahan,BPK. LIAUW ENG LU,200000
CSV;

        $file = UploadedFile::fake()->createWithContent('donatur.csv', $csvContent);

        Livewire::actingAs($admin)
            ->test('admin::transaksi')
            ->call('openImportModal')
            ->assertSet('importModalOpen', true)
            ->set('importFile', $file)
            ->call('importExcel')
            ->assertSet('importSummary.imported', 2)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('donation_programs', [
            'title' => 'Thavara Dana Tahap I Pembebasan Lahan',
        ]);
        $this->assertEquals(2, Donation::count());
    }
}
