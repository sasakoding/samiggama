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

    public function test_admin_can_save_album_with_both_cover_image_and_photos(): void
    {
        $coverFile = \Illuminate\Http\UploadedFile::fake()->image('cover.jpg', 600, 400);
        $photo1 = \Illuminate\Http\UploadedFile::fake()->image('photo1.jpg', 600, 400);
        $photo2 = \Illuminate\Http\UploadedFile::fake()->image('photo2.jpg', 600, 400);

        Livewire::actingAs($this->admin)
            ->test('admin::galeri')
            ->call('openCreateModal')
            ->set('formTitle', 'Pindapata Akbar 2568')
            ->set('formCategory', 'Puja Bakti')
            ->set('formLocation', 'Pelataran Vihara')
            ->set('formCoverImage', $coverFile)
            ->set('formPhotos', [$photo1, $photo2])
            ->call('saveAlbum')
            ->assertHasNoErrors();

        $album = GalleryAlbum::where('title', 'Pindapata Akbar 2568')->first();
        $this->assertNotNull($album);
        $this->assertStringContainsString('uploads/gallery', $album->cover_image);
        $this->assertEquals(2, $album->photos()->count());
    }

    public function test_admin_can_edit_album_and_upload_both_new_cover_and_additional_photos(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'Kathina Puja 2568',
            'slug' => 'kathina-puja-2568',
            'category' => 'Hari Raya',
            'cover_image' => 'images/gallery-altar.jpg',
        ]);

        $photoExisting = GalleryPhoto::create([
            'gallery_album_id' => $album->id,
            'file_path' => 'images/gallery-pradaksina.jpg',
            'image_path' => 'images/gallery-pradaksina.jpg',
        ]);

        $newCover = \Illuminate\Http\UploadedFile::fake()->image('new_cover.jpg', 600, 400);
        $newPhoto = \Illuminate\Http\UploadedFile::fake()->image('new_photo.jpg', 600, 400);

        Livewire::actingAs($this->admin)
            ->test('admin::galeri')
            ->call('openEditModal', $album->id)
            ->assertSet('editingId', $album->id)
            ->set('formCoverImage', $newCover)
            ->set('formPhotos', [$newPhoto])
            ->call('saveAlbum')
            ->assertHasNoErrors();

        $album->refresh();
        $this->assertStringContainsString('uploads/gallery', $album->cover_image);
        // Should have 1 existing + 1 new = 2 photos
        $this->assertEquals(2, $album->photos()->count());
    }
}

