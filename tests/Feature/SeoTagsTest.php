<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_contains_rich_seo_tags_and_json_ld(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<meta name="keywords"', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:image"', false);
        $response->assertSee('<meta name="twitter:card"', false);
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('BuddhistTemple', false);
    }

    public function test_sitemap_xml_returns_valid_xml_response(): void
    {
        Article::create([
            'title' => 'Perayaan Magha Puja 2026',
            'slug' => 'perayaan-magha-puja-2026',
            'author_name' => 'Redaksi Samaggi Gama',
            'category' => 'Kegiatan',
            'content' => 'Perayaan Magha Puja berjalan dengan khidmat di Dhammasala.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('urlset', false);
        $response->assertSee('perayaan-magha-puja-2026', false);
        $response->assertSee('/tentang-kami', false);
        $response->assertSee('/kegiatan', false);
    }

    public function test_article_detail_page_pushes_news_article_json_ld(): void
    {
        $article = Article::create([
            'title' => 'Kajian Tipitaka Mingguan Bersama Bhikkhu Sangha',
            'slug' => 'kajian-tipitaka-mingguan',
            'author_name' => 'Redaksi Samaggi Gama',
            'category' => 'Dhamma',
            'content' => 'Kajian Tipitaka membahas Sutta Pitaka mendalam.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/berita/' . $article->slug);

        $response->assertStatus(200);
        $response->assertSee('NewsArticle', false);
        $response->assertSee('Kajian Tipitaka Mingguan Bersama Bhikkhu Sangha', false);
    }
}
