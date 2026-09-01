<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicExpensePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_public_expense_page(): void
    {
        $response = $this->get('/pengeluaran');
        $response->assertStatus(200);
        $response->assertSee('Laporan & Rincian');
        $response->assertSee('Pengeluaran Dāna Umat');
    }

    public function test_public_expense_page_displays_expenses_and_metrics(): void
    {
        Expense::create([
            'title' => 'Pengadaan Lilin & Minyak Pelita Altar',
            'description' => 'Untuk sarana kebaktian vihara.',
            'amount' => 850000,
            'expense_date' => now(),
            'category' => 'Umum',
        ]);

        $response = $this->get('/pengeluaran');
        $response->assertStatus(200);
        $response->assertSee('Pengadaan Lilin & Minyak Pelita Altar');
        $response->assertSee('850.000');
    }

    public function test_public_expense_search(): void
    {
        Expense::create([
            'title' => 'Bakti Sosial Paket Sembako Warga',
            'amount' => 5000000,
            'expense_date' => now(),
            'category' => 'Umum',
        ]);

        Expense::create([
            'title' => 'Tagihan Listrik PLN Dhammasala',
            'amount' => 2500000,
            'expense_date' => now(),
            'category' => 'Umum',
        ]);

        Livewire::test('pages::pengeluaran')
            ->set('searchQuery', 'Sembako')
            ->assertSee('Bakti Sosial Paket Sembako Warga')
            ->assertDontSee('Tagihan Listrik PLN Dhammasala')
            ->set('searchQuery', '')
            ->assertSee('Tagihan Listrik PLN Dhammasala');
    }
}
