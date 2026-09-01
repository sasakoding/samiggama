<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public Pages
Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/tentang-kami', 'pages::tentang-kami')->name('tentang-kami');
Route::livewire('/pengurus', 'pages::pengurus')->name('pengurus');
Route::livewire('/kegiatan', 'pages::kegiatan')->name('kegiatan');
Route::livewire('/galeri', 'pages::galeri')->name('galeri');
Route::livewire('/berita', 'pages::berita')->name('berita');
Route::livewire('/berita/{slug}', 'pages::berita-detail')->name('berita.detail');
Route::livewire('/donasi', 'pages::donasi')->name('donasi');
Route::livewire('/pengeluaran', 'pages::pengeluaran')->name('pengeluaran.public');
Route::get('/kalender', function () {
    return redirect()->route('kegiatan', ['tab' => 'kalender']);
});
Route::get('/donatur', function () {
    return redirect()->to(route('donasi') . '#donatur');
});

// Dynamic XML Sitemap for Search Engines (Google, Bing, Yahoo)
Route::get('/sitemap.xml', function () {
    $articles = \App\Models\Article::where('status', 'published')->orderByDesc('published_at')->get();
    
    $staticPages = [
        ['url' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
        ['url' => route('tentang-kami'), 'priority' => '0.8', 'changefreq' => 'monthly'],
        ['url' => route('pengurus'), 'priority' => '0.8', 'changefreq' => 'monthly'],
        ['url' => route('kegiatan'), 'priority' => '0.9', 'changefreq' => 'daily'],
        ['url' => route('donasi'), 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['url' => route('pengeluaran.public'), 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['url' => route('berita'), 'priority' => '0.9', 'changefreq' => 'daily'],
        ['url' => route('galeri'), 'priority' => '0.7', 'changefreq' => 'weekly'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

    foreach ($staticPages as $page) {
        $xml .= '<url>';
        $xml .= '<loc>' . htmlspecialchars($page['url']) . '</loc>';
        $xml .= '<lastmod>' . date('Y-m-d') . '</lastmod>';
        $xml .= '<changefreq>' . $page['changefreq'] . '</changefreq>';
        $xml .= '<priority>' . $page['priority'] . '</priority>';
        $xml .= '</url>';
    }

    foreach ($articles as $art) {
        $xml .= '<url>';
        $xml .= '<loc>' . htmlspecialchars(route('berita.detail', ['slug' => $art->slug])) . '</loc>';
        $xml .= '<lastmod>' . ($art->updated_at ? $art->updated_at->format('Y-m-d') : date('Y-m-d')) . '</lastmod>';
        $xml .= '<changefreq>weekly</changefreq>';
        $xml .= '<priority>0.8</priority>';
        if ($art->cover_image) {
            $xml .= '<image:image>';
            $xml .= '<image:loc>' . htmlspecialchars(asset($art->cover_image)) . '</image:loc>';
            $xml .= '<image:title>' . htmlspecialchars($art->title) . '</image:title>';
            $xml .= '</image:image>';
        }
        $xml .= '</url>';
    }

    $xml .= '</urlset>';

    return response($xml, 200, [
        'Content-Type' => 'application/xml',
    ]);
});

// Authentication Routes
Route::livewire('/mimin/login', 'pages::login')->name('login')->middleware('guest');
Route::post('/mimin/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login')->with('feedbackMessage', 'Anda telah berhasil keluar dari sesi sistem.');
})->name('logout');

// Redirect /mimin to dashboard
Route::get('/mimin', function () {
    return redirect()->route('admin.dashboard');
});

// Protected Admin Panel Routes (High Security: Requires Active Authentication)
Route::middleware(['auth'])->prefix('mimin')->name('admin.')->group(function () {
    // Routes accessible by both Admin and Bendahara (Keuangan & Dashboard)
    Route::middleware(['role:admin,bendahara'])->group(function () {
        Route::livewire('/dashboard', 'admin::dashboard')->name('dashboard');
        Route::livewire('/transaksi', 'admin::transaksi')->name('transaksi');
        Route::livewire('/pengeluaran', 'admin::pengeluaran')->name('pengeluaran');
        Route::livewire('/profil', 'admin::profil')->name('profil');
    });

    // Routes strictly restricted to Admin (Full Access)
    Route::middleware(['role:admin'])->group(function () {
        Route::livewire('/donasi', 'admin::donasi')->name('donasi');
        Route::livewire('/piagam', 'admin::piagam')->name('piagam');
        Route::livewire('/pengurus', 'admin::pengurus')->name('pengurus');
        Route::livewire('/jadwal-rutin', 'admin::jadwal-rutin')->name('jadwal-rutin');
        Route::livewire('/agenda', 'admin::agenda')->name('agenda');
        Route::livewire('/berita', 'admin::berita')->name('berita');
        Route::livewire('/berita/kategori', 'admin::berita-kategori')->name('berita.kategori');
        Route::livewire('/berita/tambah', 'admin::berita-editor')->name('berita.create');
        Route::livewire('/berita/{id}/edit', 'admin::berita-editor')->name('berita.edit');
        Route::livewire('/galeri', 'admin::galeri')->name('galeri');
        Route::livewire('/tentang-kami', 'admin::tentang-kami')->name('tentang-kami');
        Route::livewire('/users', 'admin::users')->name('users');
        Route::livewire('/pengaturan', 'admin::pengaturan')->name('pengaturan');
    });
});

