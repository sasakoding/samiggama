<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_berita_page_displays_articles_from_database(): void
    {
        $author = User::factory()->create([
            'name' => 'Bhadra Virya',
            'role' => 'Penulis Dhamma',
            'status' => 'aktif',
        ]);

        $article1 = Article::create([
            'title' => 'Mutiara Kebajikan di Hari Uposatha',
            'slug' => 'mutiara-kebajikan-di-hari-uposatha',
            'category' => 'Kajian Dhamma',
            'author_name' => 'Bhadra Virya',
            'user_id' => $author->id,
            'excerpt' => 'Ringkasan artikel mutiara kebajikan pada hari uposatha suci.',
            'content' => '<p>Uposatha adalah sarana pemurnian batin yang diajarkan oleh Sang Buddha.</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $article2 = Article::create([
            'title' => 'Bakti Sosial Peduli Sesama Warga Sekitar',
            'slug' => 'bakti-sosial-peduli-sesama-warga-sekitar',
            'category' => 'Warta Vihara',
            'author_name' => 'Humas Vihara',
            'excerpt' => 'Penyaluran bantuan beras dan sembako untuk masyarakat sekitar vihara.',
            'content' => '<p>Kegiatan bakti sosial berjalan lancar dan penuh kehangatan.</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/berita');
        $response->assertStatus(200);
        $response->assertSee('Mutiara Kebajikan di Hari Uposatha');
        $response->assertSee('Bakti Sosial Peduli Sesama Warga Sekitar');
        $response->assertSee('Bhadra Virya');
        $response->assertSee('Humas Vihara');
    }

    public function test_public_berita_detail_page_loads_from_database_and_increments_views(): void
    {
        $article = Article::create([
            'title' => 'Kedalaman Meditasi Samatha Bhavana',
            'slug' => 'kedalaman-meditasi-samatha-bhavana',
            'category' => 'Kajian Dhamma',
            'author_name' => 'Bhikkhu Subhamitto',
            'excerpt' => 'Pengantar mendalam mengenai ketenangan batin melalui meditasi samatha.',
            'content' => '<p>Meditasi samatha mengarahkan pikiran pada objek yang menenteramkan batin.</p>',
            'views_count' => 5,
            'status' => 'published',
            'published_at' => now()->subDays(2),
        ]);

        $response = $this->get('/berita/' . $article->slug);
        $response->assertStatus(200);
        $response->assertSee('Kedalaman Meditasi Samatha Bhavana');
        $response->assertSee('Bhikkhu Subhamitto');
        $response->assertSee('Meditasi samatha mengarahkan pikiran pada objek yang menenteramkan batin.', false);

        // Check views count increment
        $article->refresh();
        $this->assertEquals(6, $article->views_count);
    }
}
