<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VisitorLog extends Model
{
    protected $fillable = [
        'ip_hash',
        'session_id',
        'url',
        'page_name',
        'user_agent',
        'device_type',
        'referer',
        'visited_date',
    ];

    protected $casts = [
        'visited_date' => 'date',
    ];

    /**
     * Record a page visit seamlessly, accurately, and in real-time.
     */
    public static function recordVisit(Request $request): void
    {
        // 1. Skip non-GET requests, admin panel, livewire polling/updates, api, and assets
        if (!$request->isMethod('GET')) {
            return;
        }

        $path = '/' . ltrim($request->path(), '/');

        // Excluded routes & static assets
        if (
            str_starts_with($path, '/mimin') ||
            str_starts_with($path, '/livewire') ||
            str_starts_with($path, '/api') ||
            str_starts_with($path, '/up') ||
            str_ends_with($path, '.xml') ||
            str_ends_with($path, '.txt') ||
            str_ends_with($path, '.ico') ||
            str_ends_with($path, '.json') ||
            str_ends_with($path, '.png') ||
            str_ends_with($path, '.jpg') ||
            str_ends_with($path, '.jpeg') ||
            str_ends_with($path, '.webp') ||
            str_ends_with($path, '.svg') ||
            str_ends_with($path, '.css') ||
            str_ends_with($path, '.js') ||
            str_ends_with($path, '.woff') ||
            str_ends_with($path, '.woff2')
        ) {
            return;
        }

        // 2. Identify IP hash & session
        $ip = $request->ip() ?: '127.0.0.1';
        $ipHash = hash('sha256', $ip . config('app.key', 'samaggi-salt'));
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        $trackerId = $sessionId ?: $ipHash;
        $userAgent = substr($request->userAgent() ?? '', 0, 250);

        // 3. Debounce rapid identical hits (30 seconds per user per URL)
        $cacheKey = 'visit_debounce_' . md5($trackerId . '_' . $path);
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, now()->addSeconds(30));

        // 4. Accurate device type detection
        $deviceType = 'desktop';
        $uaLower = strtolower($userAgent);
        if (
            str_contains($uaLower, 'ipad') ||
            str_contains($uaLower, 'tablet') ||
            str_contains($uaLower, 'playbook') ||
            str_contains($uaLower, 'silk')
        ) {
            $deviceType = 'tablet';
        } elseif (
            str_contains($uaLower, 'mobile') ||
            str_contains($uaLower, 'android') ||
            str_contains($uaLower, 'iphone') ||
            str_contains($uaLower, 'ipod') ||
            str_contains($uaLower, 'blackberry') ||
            str_contains($uaLower, 'windows phone')
        ) {
            $deviceType = 'mobile';
        }

        // 5. Friendly Page Name
        $pageName = self::resolvePageName($path);

        // 6. Referer
        $referer = substr($request->headers->get('referer') ?? '', 0, 250);

        // 7. Store to database
        self::create([
            'ip_hash' => $ipHash,
            'session_id' => $sessionId,
            'url' => substr($path, 0, 250),
            'page_name' => $pageName,
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'referer' => $referer ?: null,
            'visited_date' => Carbon::today()->toDateString(),
        ]);

        // 8. Invalidate cached stats so UI updates in real-time immediately
        Cache::forget('public_footer_visitor_stats');
        Cache::forget('admin_dashboard_visitor_analytics');
    }

    /**
     * Resolve user-friendly label for page paths.
     */
    public static function resolvePageName(string $path): string
    {
        if ($path === '/' || $path === '') {
            return 'Beranda Utama';
        }
        if (str_starts_with($path, '/tentang-kami')) {
            return 'Tentang Kami';
        }
        if (str_starts_with($path, '/pengurus')) {
            return 'Pengurus & Pembina';
        }
        if (str_starts_with($path, '/kegiatan')) {
            return 'Kegiatan & Jadwal';
        }
        if (str_starts_with($path, '/galeri')) {
            return 'Galeri Suasana';
        }
        if (str_starts_with($path, '/donasi')) {
            return 'Dana Paramita';
        }
        if (str_starts_with($path, '/pengeluaran')) {
            return 'Laporan Pengeluaran';
        }
        if (str_starts_with($path, '/berita/')) {
            return 'Detail Berita / Kajian';
        }
        if (str_starts_with($path, '/berita')) {
            return 'Berita & Artikel';
        }

        return ucwords(trim(str_replace(['/', '-', '_'], ' ', $path))) ?: 'Halaman Publik';
    }

    /**
     * Get lightweight statistics for the public footer.
     */
    public static function getPublicFooterStats(): array
    {
        return Cache::remember('public_footer_visitor_stats', now()->addMinutes(1), function () {
            $today = Carbon::today()->toDateString();
            $thisMonthStart = Carbon::now()->startOfMonth()->toDateString();

            $totalHits = (int) self::count();
            $uniqueVisitors = (int) self::distinct('ip_hash')->count('ip_hash');
            $todayHits = (int) self::whereDate('visited_date', $today)->count();
            $todayUnique = (int) self::whereDate('visited_date', $today)->distinct('ip_hash')->count('ip_hash');
            $thisMonthHits = (int) self::whereDate('visited_date', '>=', $thisMonthStart)->count();

            // Optional baseline offset from settings (default 0 for 100% genuine counter)
            $baselineOffset = (int) Setting::get('visitor_counter_baseline', 0);

            return [
                'total_views' => $totalHits + $baselineOffset,
                'total_unique' => $uniqueVisitors + (int) round($baselineOffset * 0.42),
                'today_views' => $todayHits,
                'today_unique' => $todayUnique,
                'month_views' => $thisMonthHits + (int) round($baselineOffset * 0.15),
            ];
        });
    }

    /**
     * Get detailed analytics for the Admin Dashboard.
     */
    public static function getAdminDashboardStats(): array
    {
        return Cache::remember('admin_dashboard_visitor_analytics', now()->addSeconds(30), function () {
            $today = Carbon::today()->toDateString();
            $yesterday = Carbon::yesterday()->toDateString();
            $thisMonthStart = Carbon::now()->startOfMonth()->toDateString();

            // Today vs Yesterday (using whereDate for cross-database compatibility)
            $todayHits = (int) self::whereDate('visited_date', $today)->count();
            $todayUnique = (int) self::whereDate('visited_date', $today)->distinct('ip_hash')->count('ip_hash');
            $yesterdayHits = (int) self::whereDate('visited_date', $yesterday)->count();

            // All-time totals
            $totalHits = (int) self::count();
            $uniqueVisitors = (int) self::distinct('ip_hash')->count('ip_hash');
            $thisMonthHits = (int) self::whereDate('visited_date', '>=', $thisMonthStart)->count();

            // Device breakdown
            $mobileCount = (int) self::where('device_type', 'mobile')->count();
            $desktopCount = (int) self::where('device_type', 'desktop')->count();
            $tabletCount = (int) self::where('device_type', 'tablet')->count();
            $totalDevices = max(1, $mobileCount + $desktopCount + $tabletCount);

            // Last 7 Days Daily Breakdown
            $dailyStats = [];
            for ($i = 6; $i >= 0; $i--) {
                $dateObj = Carbon::today()->subDays($i);
                $dateStr = $dateObj->toDateString();
                $hits = (int) self::whereDate('visited_date', $dateStr)->count();
                $unique = (int) self::whereDate('visited_date', $dateStr)->distinct('ip_hash')->count('ip_hash');

                $dailyStats[] = [
                    'date' => $dateStr,
                    'day_name' => $dateObj->translatedFormat('D, d M'),
                    'short_day' => $dateObj->translatedFormat('D'),
                    'hits' => $hits,
                    'unique' => $unique,
                ];
            }

            // Top 5 Visited Pages
            $topPages = self::selectRaw('url, page_name, count(*) as total_views, count(distinct ip_hash) as unique_visitors')
                ->groupBy('url', 'page_name')
                ->orderByDesc('total_views')
                ->take(5)
                ->get()
                ->map(function ($p) {
                    return [
                        'url' => (string) $p->url,
                        'page_name' => (string) ($p->page_name ?: 'Halaman Publik'),
                        'total_views' => (int) $p->total_views,
                        'unique_visitors' => (int) $p->unique_visitors,
                    ];
                })
                ->values()
                ->toArray();

            return [
                'today_hits' => $todayHits,
                'today_unique' => $todayUnique,
                'yesterday_hits' => $yesterdayHits,
                'total_hits' => $totalHits,
                'total_unique' => $uniqueVisitors,
                'month_hits' => $thisMonthHits,
                'mobile_percent' => (int) round(($mobileCount / $totalDevices) * 100),
                'desktop_percent' => (int) round(($desktopCount / $totalDevices) * 100),
                'tablet_percent' => (int) round(($tabletCount / $totalDevices) * 100),
                'daily_stats' => $dailyStats,
                'top_pages' => $topPages,
            ];
        });
    }
}
