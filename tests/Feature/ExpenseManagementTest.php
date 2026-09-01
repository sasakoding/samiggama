<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Pengurus Kas Vihara',
            'email' => 'kas@samaggigama.org',
            'password' => bcrypt('password'),
            'role' => 'Super Administrator',
            'status' => 'aktif',
        ]);
    }

    public function test_guest_cannot_access_expenses_page(): void
    {
        $response = $this->get('/mimin/pengeluaran');
        $response->assertRedirect('/mimin/login');
    }

    public function test_admin_can_view_expenses_table_and_metrics(): void
    {
        Expense::create([
            'title' => "Pembelian Minyak Pelita Altar 20 Liter\nNota No: 102/DU-2026",
            'amount' => 650000,
            'expense_date' => now(),
            'category' => 'Umum',
            'created_by' => $this->admin->id,
        ]);

        Donation::create([
            'invoice_number' => 'DN-TEST-EXP-01',
            'donor_name' => 'Donatur Teladan',
            'amount' => 5000000,
            'total_amount' => 5000000,
            'payment_method' => 'bca',
            'status' => 'verified',
        ]);

        $this->actingAs($this->admin);

        $response = $this->get('/mimin/pengeluaran');
        $response->assertStatus(200);
        $response->assertSee('Catatan Pengeluaran Dāna');
        $response->assertSee('Pembelian Minyak Pelita Altar 20 Liter');
        $response->assertSee('650.000');
    }

    public function test_admin_can_create_new_expense(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin::pengeluaran')
            ->call('openCreateModal')
            ->set('formTitle', "Pembayaran Tagihan Air PDAM Vihara\nBulan Agustus 2026")
            ->set('formAmount', '450000')
            ->call('saveExpense')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false);

        $this->assertDatabaseHas('expenses', [
            'title' => "Pembayaran Tagihan Air PDAM Vihara\nBulan Agustus 2026",
            'amount' => 450000,
        ]);
    }

    public function test_admin_can_edit_expense(): void
    {
        $expense = Expense::create([
            'title' => 'Pengadaan Bunga Segar Altar',
            'amount' => 300000,
            'expense_date' => now(),
            'category' => 'Umum',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('admin::pengeluaran')
            ->call('openEditModal', $expense->id)
            ->assertSet('formTitle', 'Pengadaan Bunga Segar Altar')
            ->assertSet('formAmount', '300000')
            ->set('formAmount', '375000')
            ->set('formTitle', 'Pengadaan Bunga Segar & Lilin Altar')
            ->call('saveExpense')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'title' => 'Pengadaan Bunga Segar & Lilin Altar',
            'amount' => 375000,
        ]);
    }

    public function test_admin_can_delete_expense(): void
    {
        $expense = Expense::create([
            'title' => 'Pengeluaran Yang Akan Dihapus',
            'amount' => 100000,
            'expense_date' => now(),
            'category' => 'Umum',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('admin::pengeluaran')
            ->call('deleteExpense', $expense->id);

        $this->assertDatabaseMissing('expenses', [
            'id' => $expense->id,
        ]);
    }

    public function test_admin_can_search_expenses(): void
    {
        Expense::create([
            'title' => 'Konsumsi Pelatihan Dhamma Remaja',
            'amount' => 850000,
            'expense_date' => now(),
            'category' => 'Umum',
            'created_by' => $this->admin->id,
        ]);

        Expense::create([
            'title' => 'Perbaikan Pompa Air Sumur',
            'amount' => 1200000,
            'expense_date' => now(),
            'category' => 'Umum',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('admin::pengeluaran')
            ->set('searchQuery', 'Pompa')
            ->assertSee('Perbaikan Pompa Air Sumur')
            ->assertDontSee('Konsumsi Pelatihan Dhamma Remaja')
            ->set('searchQuery', '')
            ->assertSee('Konsumsi Pelatihan Dhamma Remaja');
    }
}
