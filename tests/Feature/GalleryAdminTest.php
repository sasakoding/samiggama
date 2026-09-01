<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GalleryAdminTest extends TestCase
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

    public function test_admin_can_access_gallery_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/mimin/galeri');
        $response->assertStatus(200);
        $response->assertSee('Galeri Foto & Dokumentasi');
    }

    public function test_admin_can_create_and_delete_album(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'Dokumentasi Waisak 2568',
            'slug' => 'dokumentasi-waisak-2568',
            'category' => 'Hari Raya',
            'cover_image' => 'images/gallery-altar.jpg',
        ]);

        $photo = GalleryPhoto::create([
            'gallery_album_id' => $album->id,
            'image_path' => 'images/gallery-pradaksina.jpg',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::galeri')
            ->call('deleteAlbum', $album->id);

        $this->assertDatabaseMissing('gallery_albums', [
            'id' => $album->id,
        ]);

        $this->assertDatabaseMissing('gallery_photos', [
            'id' => $photo->id,
        ]);
    }
}
