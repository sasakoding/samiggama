<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin Vihara',
            'role' => 'Super Admin',
            'status' => 'aktif',
        ]);
    }

    public function test_guest_cannot_access_article_categories_page(): void
    {
        $response = $this->get('/mimin/berita/kategori');
        $response->assertRedirect('/mimin/login');
    }

    public function test_admin_can_access_article_categories_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/mimin/berita/kategori');
        $response->assertStatus(200);
        $response->assertSee('Kelola Kategori Berita');
    }

    public function test_admin_can_create_new_category(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin::berita-kategori')
            ->set('formName', 'Pendidikan Moralitas')
            ->set('formDescription', 'Kajian mengenai etika dan moralitas anak muda')
            ->set('formBadgeColor', 'rose')
            ->set('formStatus', 'aktif')
            ->call('saveCategory')
            ->assertSet('modalOpen', false);

        $this->assertDatabaseHas('article_categories', [
            'name' => 'Pendidikan Moralitas',
            'slug' => 'pendidikan-moralitas',
            'badge_color' => 'rose',
            'status' => 'aktif',
        ]);
    }

    public function test_admin_can_edit_category_and_sync_existing_articles(): void
    {
        $cat = ArticleCategory::create([
            'name' => 'Warta Lama',
            'slug' => 'warta-lama',
            'description' => 'Kategori lama',
            'badge_color' => 'amber',
            'status' => 'aktif',
        ]);

        $article = Article::create([
            'title' => 'Kegiatan Hari Ini',
            'slug' => 'kegiatan-hari-ini',
            'category' => 'Warta Lama',
            'author_name' => 'Admin',
            'content' => '<p>Konten berita</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::berita-kategori')
            ->call('openEditModal', $cat->id)
            ->set('formName', 'Warta Terkini')
            ->set('formSlug', 'warta-terkini')
            ->call('saveCategory');

        $this->assertDatabaseHas('article_categories', [
            'id' => $cat->id,
            'name' => 'Warta Terkini',
            'slug' => 'warta-terkini',
        ]);

        $article->refresh();
        $this->assertEquals('Warta Terkini', $article->category);
    }

    public function test_admin_can_toggle_category_status(): void
    {
        $cat = ArticleCategory::create([
            'name' => 'Kategori Sementara',
            'slug' => 'kategori-sementara',
            'status' => 'aktif',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::berita-kategori')
            ->call('toggleStatus', $cat->id);

        $cat->refresh();
        $this->assertEquals('nonaktif', $cat->status);
    }

    public function test_admin_can_delete_category(): void
    {
        $cat = ArticleCategory::create([
            'name' => 'Kategori Hapus',
            'slug' => 'kategori-hapus',
            'status' => 'aktif',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::berita-kategori')
            ->call('deleteCategory', $cat->id);

        $this->assertDatabaseMissing('article_categories', [
            'id' => $cat->id,
        ]);
    }
}
