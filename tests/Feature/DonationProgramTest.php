<?php

namespace Tests\Feature;

use App\Models\DonationProgram;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DonationProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_days_left_calculation_for_future_dates(): void
    {
        $futureProgram = DonationProgram::create([
            'title' => 'Program Masa Depan',
            'slug' => 'program-masa-depan',
            'category' => 'Pembangunan & Sarana',
            'target_amount' => 10000000,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
            'status' => 'aktif',
        ]);

        $this->assertEquals('5 Hari Lagi', $futureProgram->days_left_text);
        $this->assertEquals(5, $futureProgram->days_remaining);
    }

    public function test_days_left_calculation_for_today(): void
    {
        $todayProgram = DonationProgram::create([
            'title' => 'Program Hari Terakhir',
            'slug' => 'program-hari-terakhir',
            'category' => 'Sosial & Kemanusiaan',
            'target_amount' => 5000000,
            'start_date' => now()->subDays(10),
            'end_date' => now(),
            'status' => 'aktif',
        ]);

        $this->assertEquals('Hari Terakhir', $todayProgram->days_left_text);
        $this->assertEquals(0, $todayProgram->days_remaining);
    }

    public function test_days_left_calculation_for_past_dates(): void
    {
        $pastProgram = DonationProgram::create([
            'title' => 'Program Sudah Berlalu',
            'slug' => 'program-sudah-berlalu',
            'category' => 'Sosial & Kemanusiaan',
            'target_amount' => 5000000,
            'start_date' => now()->subDays(20),
            'end_date' => now()->subDays(2),
            'status' => 'aktif',
        ]);

        $this->assertEquals('Selesai & Tersalurkan', $pastProgram->days_left_text);
        $this->assertEquals(0, $pastProgram->days_remaining);
    }

    public function test_days_left_calculation_for_open_ended_program(): void
    {
        $openProgram = DonationProgram::create([
            'title' => 'Program Berkelanjutan',
            'slug' => 'program-berkelanjutan',
            'category' => 'Operasional Vihara',
            'target_amount' => 50000000,
            'start_date' => now()->subDays(30),
            'end_date' => null,
            'status' => 'aktif',
        ]);

        $this->assertEquals('Sedang Berjalan', $openProgram->days_left_text);
        $this->assertNull($openProgram->days_remaining);
    }

    public function test_public_donation_page_renders_clean_days_left_string(): void
    {
        DonationProgram::create([
            'title' => 'Program Donasi Publik',
            'slug' => 'program-donasi-publik',
            'category' => 'Pembangunan & Sarana',
            'target_amount' => 20000000,
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(7),
            'status' => 'aktif',
        ]);

        $response = $this->get('/donasi');
        $response->assertStatus(200);
        $response->assertSee('7 Hari Lagi');
        $response->assertDontSee('-5.08');
    }

    public function test_program_with_custom_pembina_returns_custom_sangha_members_list(): void
    {
        $program = DonationProgram::create([
            'title' => 'Program Khusus Pembina',
            'slug' => 'program-khusus-pembina',
            'category' => 'Pembangunan & Sarana',
            'target_amount' => 10000000,
            'pembina' => [
                ['name' => 'Bhikkhu Subhaddho', 'title' => 'Penanggung Jawab Proyek', 'photo' => 'images/test.jpg'],
            ],
            'status' => 'aktif',
        ]);

        $this->assertCount(1, $program->sangha_members_list);
        $this->assertEquals('Bhikkhu Subhaddho', $program->sangha_members_list->first()->name);
        $this->assertEquals('Penanggung Jawab Proyek', $program->sangha_members_list->first()->title);
    }

    public function test_program_without_pembina_falls_back_to_global_sangha_members(): void
    {
        \App\Models\SanghaMember::create([
            'name' => 'Bhikkhu Global Vihara',
            'title' => 'Dewan Pembina Vihara',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $program = DonationProgram::create([
            'title' => 'Program Tanpa Pembina Khusus',
            'slug' => 'program-tanpa-pembina-khusus',
            'category' => 'Operasional Vihara',
            'target_amount' => 5000000,
            'pembina' => null,
            'status' => 'aktif',
        ]);

        $this->assertCount(1, $program->sangha_members_list);
        $this->assertEquals('Bhikkhu Global Vihara', $program->sangha_members_list->first()->name);
    }

    public function test_admin_donasi_livewire_saves_custom_pembina(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test('admin::donasi')
            ->call('openCreateModal')
            ->set('formTitle', 'Program Dāna Candi')
            ->set('formCategory', 'Pembangunan & Sarana')
            ->set('formTarget', '15000000')
            ->set('formPembina', [
                ['name' => 'Bhikkhu Subhaddho', 'title' => 'Ketua Pembangunan', 'photo' => ''],
                ['name' => 'Bhikkhu Khemanando', 'title' => 'Wakil Ketua', 'photo' => ''],
            ])
            ->call('saveProgram');

        $program = DonationProgram::where('slug', 'program-dana-candi')->first();
        $this->assertNotNull($program);
        $this->assertCount(2, $program->pembina);
        $this->assertEquals('Bhikkhu Subhaddho', $program->pembina[0]['name']);
        $this->assertEquals('Ketua Pembangunan', $program->pembina[0]['title']);
    }
}


