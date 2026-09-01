<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\CertificateTemplate;
use App\Models\Donation;
use App\Models\DonationProgram;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Officer;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDeleteOperationsTest extends TestCase
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

    public function test_admin_can_delete_gallery_album_and_photos(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'Dokumentasi Asadha 2568',
            'slug' => 'dokumentasi-asadha-2568',
            'category' => 'Hari Raya',
            'cover_image' => 'images/gallery-altar.jpg',
        ]);

        $photo = GalleryPhoto::create([
            'gallery_album_id' => $album->id,
            'file_path' => 'images/gallery-pradaksina.jpg',
            'image_path' => 'images/gallery-pradaksina.jpg',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::galeri')
            ->call('deleteAlbum', $album->id);

        $this->assertDatabaseMissing('gallery_albums', ['id' => $album->id]);
        $this->assertDatabaseMissing('gallery_photos', ['id' => $photo->id]);
    }

    public function test_admin_can_delete_single_gallery_photo(): void
    {
        $album = GalleryAlbum::create([
            'title' => 'Dokumentasi Kathina 2568',
            'slug' => 'dokumentasi-kathina-2568',
            'category' => 'Hari Raya',
        ]);

        $photo = GalleryPhoto::create([
            'gallery_album_id' => $album->id,
            'file_path' => 'images/gallery-pindapata.jpg',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::galeri')
            ->call('deletePhoto', $photo->id);

        $this->assertDatabaseMissing('gallery_photos', ['id' => $photo->id]);
        $this->assertDatabaseHas('gallery_albums', ['id' => $album->id]);
    }

    public function test_admin_can_delete_schedule(): void
    {
        $sch = Schedule::create([
            'title' => 'Meditasi Rutin Jumat',
            'slug' => 'meditasi-rutin-jumat',
            'category' => 'Meditasi',
            'event_date' => now()->addDays(2),
            'start_time' => '19:00',
            'end_time' => '20:30',
            'location' => 'Dhammasala',
            'status' => 'aktif',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::agenda')
            ->call('deleteSchedule', $sch->id);

        $this->assertDatabaseMissing('schedules', ['id' => $sch->id]);
    }

    public function test_admin_can_delete_article(): void
    {
        $art = Article::create([
            'title' => 'Kajian Tipitaka Mingguan',
            'slug' => 'kajian-tipitaka-mingguan',
            'category' => 'Kajian Dhamma',
            'author_name' => 'Admin',
            'content' => '<p>Konten kajian</p>',
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::berita')
            ->call('deleteArticle', $art->id);

        $this->assertDatabaseMissing('articles', ['id' => $art->id]);
    }

    public function test_admin_can_delete_donation_program(): void
    {
        $prog = DonationProgram::create([
            'title' => 'Pembangunan Kuti Sangha',
            'slug' => 'pembangunan-kuti-sangha',
            'category' => 'Sarana & Pembangunan',
            'target_amount' => 50000000,
            'status' => 'aktif',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::donasi')
            ->call('deleteProgram', $prog->id);

        $this->assertDatabaseMissing('donation_programs', ['id' => $prog->id]);
    }

    public function test_admin_can_delete_officer(): void
    {
        $off = Officer::create([
            'name' => 'Upasaka Surya',
            'title' => 'Wakil Ketua Yayasan',
            'division' => 'Pengurus Harian',
            'sort_order' => 2,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::pengurus')
            ->call('deleteOfficer', $off->id);

        $this->assertDatabaseMissing('officers', ['id' => $off->id]);
    }

    public function test_admin_can_delete_certificate_template(): void
    {
        $tpl = CertificateTemplate::create([
            'name' => 'Piagam Penghargaan Dana Kuti',
            'slug' => 'piagam-penghargaan-dana-kuti',
            'background_image' => 'images/piagam-bg.jpg',
            'status' => 'aktif',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::piagam')
            ->call('deleteTemplate', $tpl->id);

        $this->assertDatabaseMissing('certificate_templates', ['id' => $tpl->id]);
    }

    public function test_admin_can_delete_donation_record(): void
    {
        $don = Donation::create([
            'invoice_number' => 'INV-TEST-001',
            'donor_name' => 'Anonim',
            'amount' => 100000,
            'total_amount' => 100012,
            'payment_method' => 'qris',
            'status' => 'pending',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::transaksi')
            ->call('deleteDonation', $don->id);

        $this->assertDatabaseMissing('donations', ['id' => $don->id]);
    }
}
