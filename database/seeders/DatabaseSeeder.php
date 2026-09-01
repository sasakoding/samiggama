<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\CertificateTemplate;
use App\Models\Donation;
use App\Models\DonationProgram;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\IssuedCertificate;
use App\Models\Officer;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Ganteng',
                'password' => Hash::make('12345678'),
                'role' => 'Super Administrator',
                'status' => 'aktif',
                'last_login_at' => now(),
                'last_login_ip' => '127.0.0.1',
            ]
        );

        User::updateOrCreate(
            ['email' => 'bendahara@gmail.com'],
            [
                'name' => 'Bendahara Cantik',
                'password' => Hash::make('12345678'),
                'role' => 'Bendahara',
                'status' => 'aktif',
                'last_login_at' => now()->subHours(3),
                'last_login_ip' => '182.253.12.10',
            ]
        );
        
        // 10. Settings
        Setting::set('foundation_name', 'Vihara Sāmaggi Gāma');
        Setting::set('foundation_kemenag_id', 'BA.01.03/F.IX/748/2019');
        Setting::set('foundation_notary_id', 'Akta Notaris No. 18 / 24 Mei 2019');
        Setting::set('foundation_address', 'Jl. Samaggi Gāma No. 108, Jakarta');
        Setting::set('foundation_email', 'sekretariat@samaggigama.org');
        Setting::set('foundation_phone', '+62 812-3456-7890');
        Setting::set('bank_bca_norek', '883-092-8811');
        Setting::set('bank_bca_holder', 'Vihara Samaggi Gama');
        Setting::set('qris_nmid', 'ID1020038891024');

        // Social Media Settings
        Setting::set('social_youtube', 'https://youtube.com/@samaggigama');
        Setting::set('social_instagram', 'https://instagram.com/samaggigama');
        Setting::set('social_facebook', 'https://facebook.com/samaggigama');
        Setting::set('social_tiktok', 'https://tiktok.com/@samaggigama');
        Setting::set('social_whatsapp', 'https://wa.me/6281123456789');

        // Routine Schedule Header Settings
        Setting::set('routine_schedule_badge', 'Jadwal Petugas Kebaktian Rutin');
        Setting::set('routine_schedule_title', 'Penugasan Petugas & Topik Dhammadesana');
        Setting::set('routine_schedule_subtitle', 'Daftar jadwal penugasan Puja Bakti mingguan, penceramah Dhamma, dan pemimpin kebaktian di Vihara Sāmaggi Gāma.');

        // 11. Multi-Admin WhatsApp Contacts (Rotator)
        \App\Models\AdminContact::updateOrCreate(
            ['name' => 'Admin Upasaka Budi (Sekretariat)'],
            [
                'phone' => '081234567890',
                'role' => 'Layanan Informasi & Sekretariat',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
        \App\Models\AdminContact::updateOrCreate(
            ['name' => 'Admin Upasika Ratna Dewi (Konfirmasi Dāna)'],
            [
                'phone' => '081298765432',
                'role' => 'Konfirmasi Dāna & Keuangan',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );
        \App\Models\AdminContact::updateOrCreate(
            ['name' => 'Admin Layanan Umat & Puja Bakti'],
            [
                'phone' => '081377889900',
                'role' => 'Jadwal Kebaktian & Konsultasi',
                'is_active' => true,
                'sort_order' => 3,
            ]
        );

    }
}
