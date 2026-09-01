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
}
