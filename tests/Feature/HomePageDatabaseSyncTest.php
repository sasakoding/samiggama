<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Donation;
use App\Models\DonationProgram;
use App\Models\GalleryAlbum;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomePageDatabaseSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_displays_synced_data_from_database(): void
    {
        // 1. Set Custom About Us in Settings
        Setting::set('about_title', 'Pusat Meditasi & Persaudaraan Damai');
        Setting::set('about_badge', 'Profil Resmi Vihara');

        // 2. Create Custom Gallery Album
        $album = GalleryAlbum::create([
            'title' => 'Dokumentasi Khidmat Meditasi Malam',
            'slug' => 'dokumentasi-khidmat-meditasi-malam',
            'category' => 'Taman Meditasi',
            'location' => 'Taman Kolam Teratai',
            'event_date' => now(),
            'description' => 'Momen keheningan batin para praktisi meditasi.',
            'cover_image' => 'images/gallery-meditation.jpg',
        ]);

        // 3. Create Custom Donation Program
        $prog = DonationProgram::create([
            'title' => 'Dāna Peremajaan Lantai Dhammasala',
            'slug' => 'dana-peremajaan-lantai-dhammasala',
            'category' => 'Renovasi Sarana',
            'target_amount' => 80000000,
            'status' => 'aktif',
            'cover_image' => 'images/gallery-dhammasala.jpg',
            'content' => 'Program peremajaan lantai marmer untuk kenyamanan puja bakti.',
        ]);

        // 4. Create Verified Donation
        Donation::create([
            'invoice_number' => 'DN-SYNC-0099',
            'donation_program_id' => $prog->id,
            'donor_name' => 'Upasaka Dharmawan',
            'amount' => 5000000,
            'total_amount' => 5000000,
            'payment_method' => 'bca',
            'status' => 'verified',
        ]);

        // 4.1 Create Expense
        \App\Models\Expense::create([
            'title' => 'Pembelian Lilin Altar',
            'amount' => 1500000,
            'expense_date' => now(),
            'category' => 'Perlengkapan Puja Bakti & Altar',
        ]);

        // 5. Create Custom Article
        $article = Article::create([
            'title' => 'Pentingnya Menjaga Kesadaran Penuh dalam Keseharian',
            'slug' => 'pentingnya-menjaga-kesadaran-penuh',
            'category' => 'Kajian Dhamma',
            'author_name' => 'Bhikkhu Samaggi',
            'excerpt' => 'Artikel mengenai penerapan mindfulness di setiap hembusan nafas.',
            'content' => '<p>Konten lengkap mindfulness...</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // 6. Test Home Livewire Component
        $component = Livewire::test('pages::home');

        $component->assertSee('Pusat Meditasi & Persaudaraan Damai')
            ->assertSee('Profil Resmi Vihara')
            ->assertSee('Dokumentasi Khidmat Meditasi Malam')
            ->assertSee('Dāna Peremajaan Lantai Dhammasala')
            ->assertSee('Pentingnya Menjaga Kesadaran Penuh dalam Keseharian')
            ->assertStatus(200);

        // 7. Test HTTP GET to Home route
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Pusat Meditasi & Persaudaraan Damai');
        $response->assertSee('Dokumentasi Khidmat Meditasi Malam');
        $response->assertSee('Dāna Peremajaan Lantai Dhammasala');
        $response->assertSee('Pentingnya Menjaga Kesadaran Penuh dalam Keseharian');
        $response->assertSee('Total Dāna Terkumpul');
        $response->assertSee('5.000.000');
        $response->assertSee('Total Pengeluaran Dāna');
        $response->assertSee('1.500.000');
    }
}
