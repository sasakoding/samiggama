<?php

namespace Tests\Feature;

use App\Models\Officer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicPengurusTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pengurus_page_can_be_rendered(): void
    {
        Officer::create([
            'name' => 'Bhante Dhammiko Mahāthera',
            'title' => 'Ketua Dewan Pembina & Sangha Nayaka',
            'division' => 'Dewan Pembina',
            'sort_order' => 1,
            'status' => 'aktif',
        ]);

        Officer::create([
            'name' => 'Hendra Wijaya, S.E.',
            'title' => 'Ketua Umum Pengurus Yayasan',
            'division' => 'Pengurus Harian',
            'sort_order' => 2,
            'status' => 'aktif',
        ]);

        $response = $this->get('/pengurus');

        $response->assertStatus(200);
        $response->assertSee('Susunan Pengurus');
        $response->assertSee('Bhante Dhammiko Mahāthera');
        $response->assertSee('Hendra Wijaya, S.E.');
    }

    public function test_public_pengurus_search_filter(): void
    {
        Officer::create([
            'name' => 'Ratna Dewi, S.Kom.',
            'title' => 'Sekretaris Jenderal',
            'division' => 'Pengurus Harian',
            'sort_order' => 1,
            'status' => 'aktif',
        ]);

        Officer::create([
            'name' => 'Budi Santoso, B.Sc.',
            'title' => 'Bendahara Umum',
            'division' => 'Pengurus Harian',
            'sort_order' => 2,
            'status' => 'aktif',
        ]);

        Livewire::test('pages::pengurus')
            ->set('searchQuery', 'Ratna')
            ->assertSee('Ratna Dewi, S.Kom.')
            ->assertDontSee('Budi Santoso, B.Sc.');
    }
}
