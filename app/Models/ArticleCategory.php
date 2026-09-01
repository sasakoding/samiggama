<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ArticleCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'badge_color',
        'status',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class, 'category', 'name');
    }

    public function getArticlesCountAttribute(): int
    {
        return $this->articles()->count();
    }

    public function getPublishedArticlesCountAttribute(): int
    {
        return $this->articles()->where('status', 'published')->count();
    }

    /**
     * Helper to auto-seed initial default categories if table is empty
     */
    public static function seedDefaultsIfEmpty(): void
    {
        if (static::count() > 0) {
            return;
        }

        $defaults = [
            [
                'name' => 'Kajian Dhamma',
                'slug' => 'kajian-dhamma',
                'description' => 'Artikel ulasan filosofi ajaran Buddha, renungan Sutta, dan panduan praktik meditasi.',
                'badge_color' => 'emerald',
                'status' => 'aktif',
            ],
            [
                'name' => 'Berita Vihara',
                'slug' => 'berita-vihara',
                'description' => 'Kabar resmi, peliputan perayaan hari besar, dan warta agenda kegiatan vihara.',
                'badge_color' => 'amber',
                'status' => 'aktif',
            ],
            [
                'name' => 'Kegiatan Sosial',
                'slug' => 'kegiatan-sosial',
                'description' => 'Aksi bakti sosial, kepedulian masyarakat sekitar, dan donasi paket berkah.',
                'badge_color' => 'blue',
                'status' => 'aktif',
            ],
            [
                'name' => 'Transparansi',
                'slug' => 'transparansi',
                'description' => 'Laporan akuntabilitas keuangan, pertanggungjawaban dana, dan renovasi vihara.',
                'badge_color' => 'purple',
                'status' => 'aktif',
            ],
            [
                'name' => 'Pendidikan & SMB',
                'slug' => 'pendidikan-smb',
                'description' => 'Pembinaan karakter Sekolah Minggu Buddhis dan aktivitas pemuda Sāmaggi Youth.',
                'badge_color' => 'rose',
                'status' => 'aktif',
            ],
        ];

        foreach ($defaults as $data) {
            static::create($data);
        }

        // Also check if any existing articles have categories not in defaults
        $existingCategories = Article::pluck('category')->filter()->unique();
        foreach ($existingCategories as $catName) {
            if (!static::where('name', $catName)->exists()) {
                static::create([
                    'name' => $catName,
                    'slug' => Str::slug($catName),
                    'description' => 'Kategori artikel ' . $catName,
                    'badge_color' => 'emerald',
                    'status' => 'aktif',
                ]);
            }
        }
    }
}
