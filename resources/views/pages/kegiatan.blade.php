<?php

use App\Models\RoutineSchedule;
use App\Models\Schedule;
use App\Services\BuddhistCalendarService;
use Carbon\Carbon;
use Livewire\Component;

new class extends Component
{
    public function render()
    {
        // 1. Special & Annual Events (Khusus Kegiatan Buddhis & Vihara, tidak termasuk Hari Libur Nasional Umum)
        $specialEvents = Schedule::where('status', 'aktif')
            ->where('category', '!=', 'Hari Libur Nasional')
            ->where('event_date', '>=', now()->subDays(30)->toDateString())
            ->orderBy('event_date', 'asc')
            ->get();

        if ($specialEvents->isEmpty()) {
            $specialEvents = Schedule::where('status', 'aktif')
                ->where('category', '!=', 'Hari Libur Nasional')
                ->orderByDesc('event_date')
                ->take(6)
                ->get();
        }

        // Spotlight Featured Event (Closest upcoming event or first available)
        $upcomingEvents = $specialEvents->filter(fn($e) => $e->event_date && $e->event_date->toDateString() >= now()->toDateString());
        $featuredEvent = $upcomingEvents->first() ?? $specialEvents->first();

        // 2. Routine Duty Schedules (Format: No | Tanggal | Kegiatan | Pemimpin 1 | Pemimpin 2 | Penceramah | Topik)
        $routineSchedules = RoutineSchedule::where('status', 'aktif')
            ->where('event_date', '>=', now()->subDays(14)->toDateString())
            ->orderBy('event_date', 'asc')
            ->get()
            ->map(function ($sch, $index) {
                return [
                    'id' => $sch->id,
                    'no' => $index + 1,
                    'date' => $sch->event_date ? $sch->event_date->translatedFormat('d M Y') : '-',
                    'rawDate' => $sch->event_date ? $sch->event_date->format('Y-m-d') : '',
                    'monthYear' => $sch->event_date ? $sch->event_date->format('Y-m') : '',
                    'dayName' => $sch->day_name,
                    'timeRange' => $sch->time_range ?? '08:30 - 10:30',
                    'activity' => $sch->activity_name,
                    'leader1' => $sch->leader_1,
                    'leader2' => $sch->leader_2 ?: '-',
                    'speaker' => $sch->speaker,
                    'topic' => $sch->topic,
                    'isUpcoming' => $sch->event_date ? ($sch->event_date->toDateString() >= now()->toDateString()) : false,
                ];
            });

        // 2b. Dynamic Available Months List (Database Records + Current Year)
        $dbMonths = RoutineSchedule::where('status', 'aktif')
            ->whereNotNull('event_date')
            ->orderBy('event_date', 'asc')
            ->get()
            ->map(function ($sch) {
                return [
                    'value' => $sch->event_date->format('Y-m'),
                    'label' => $sch->event_date->translatedFormat('F Y'),
                ];
            })
            ->unique('value');

        $yearMonths = collect();
        for ($m = 1; $m <= 12; $m++) {
            $d = \Carbon\Carbon::create(now()->year, $m, 1);
            $yearMonths->push([
                'value' => $d->format('Y-m'),
                'label' => $d->translatedFormat('F Y'),
            ]);
        }

        $availableMonths = $dbMonths->concat($yearMonths)->unique('value')->sortBy('value')->values();

        // 3. Consolidated Calendar Events (Hari Raya Buddhis + Uposatha + Libur Nasional + Event Vihara + Jadwal Rutin)
        $calendarEvents = [];

        // 3a. Add Events, Buddhist Holidays, Uposatha, and National Holidays from Database (Schedule)
        $allSpecialDb = Schedule::where('status', 'aktif')->orderBy('event_date', 'asc')->get();
        foreach ($allSpecialDb as $se) {
            if ($se->event_date) {
                $category = $se->category;
                $type = 'special_event';
                $badge = $se->schedule_type ?: 'Event Khusus';
                $color = 'emerald';
                $isMajor = true;

                if (in_array($category, ['Hari Raya Buddhis', 'Hari Raya'])) {
                    $type = 'holiday';
                    $badge = 'Hari Raya Buddhis';
                    $color = 'amber';
                    $isMajor = true;
                } elseif ($category === 'Hari Uposatha') {
                    $type = 'uposatha';
                    $badge = 'Hari Uposatha';
                    $color = 'purple';
                    $isMajor = false;
                } elseif ($category === 'Hari Libur Nasional') {
                    $type = 'national_holiday';
                    $badge = 'Libur Nasional';
                    $color = 'rose';
                    $isMajor = false;
                } elseif ($category === 'Meditasi & Retreat') {
                    $type = 'special_event';
                    $badge = 'Meditasi';
                    $color = 'teal';
                    $isMajor = true;
                } elseif ($category === 'Sekolah Minggu Buddhis') {
                    $type = 'special_event';
                    $badge = 'SMB & Pemuda';
                    $color = 'sky';
                    $isMajor = false;
                } elseif ($category === 'Bakti Sosial') {
                    $type = 'special_event';
                    $badge = 'Bakti Sosial';
                    $color = 'orange';
                    $isMajor = true;
                }

                $calendarEvents[] = [
                    'id' => 'evt-' . $se->id,
                    'date' => $se->event_date->format('Y-m-d'),
                    'title' => $se->title,
                    'type' => $type,
                    'category' => $category,
                    'badge' => $badge,
                    'time' => substr($se->start_time, 0, 5) . ($se->end_time ? ' - ' . substr($se->end_time, 0, 5) : ''),
                    'location' => $se->location ?: 'Dhammasala Utama',
                    'leader' => $se->leader,
                    'description' => $se->description,
                    'is_major' => $isMajor,
                    'color' => $color,
                    'image' => $se->cover_image ? asset($se->cover_image) : null,
                    'activities' => $se->activities,
                ];

                // If event has sub-activities/sessions on different dates, register them in calendar
                if (!empty($se->activities) && is_array($se->activities)) {
                    foreach ($se->activities as $actIdx => $act) {
                        if (!empty($act['date']) && $act['date'] !== $se->event_date->format('Y-m-d')) {
                            $calendarEvents[] = [
                                'id' => 'evt-' . $se->id . '-act-' . $actIdx,
                                'date' => $act['date'],
                                'title' => $se->title . ': ' . ($act['activity'] ?: 'Sesi Acara'),
                                'type' => $type,
                                'category' => $category,
                                'badge' => $badge,
                                'time' => $act['time'] ?? '08:30 - 10:30',
                                'location' => $se->location ?: 'Dhammasala Utama',
                                'leader' => !empty($act['speaker']) ? ('Penceramah: ' . $act['speaker']) : $se->leader,
                                'description' => 'Sesi: ' . ($act['activity'] ?: '-') . 
                                    (!empty($act['topic']) ? (' | Topik: “' . $act['topic'] . '”') : '') .
                                    (!empty($act['speaker']) ? (' | Penceramah: ' . $act['speaker']) : '') .
                                    (!empty($act['leader_1']) ? (' | Pemimpin: ' . $act['leader_1']) : ''),
                                'is_major' => false,
                                'color' => $color,
                                'image' => $se->cover_image ? asset($se->cover_image) : null,
                                'activities' => $se->activities,
                            ];
                        }
                    }
                }
            }
        }

        // 3b. Add Routine Schedules (from DB)
        $allRoutineDb = RoutineSchedule::where('status', 'aktif')->get();
        foreach ($allRoutineDb as $rs) {
            if ($rs->event_date) {
                $calendarEvents[] = [
                    'id' => 'rou-' . $rs->id,
                    'date' => $rs->event_date->format('Y-m-d'),
                    'title' => $rs->activity_name . ($rs->topic ? ' — “' . $rs->topic . '”' : ''),
                    'type' => 'routine',
                    'category' => 'Puja Bakti Rutin',
                    'badge' => 'Kebaktian',
                    'time' => $rs->time_range ?: '08:30 - 10:30',
                    'location' => 'Dhammasala Vihara',
                    'leader' => 'Penceramah: ' . $rs->speaker . ' | Pemimpin: ' . $rs->leader_1,
                    'description' => 'Topik: ' . $rs->topic . '. Pemimpin Puja: ' . $rs->leader_1 . ($rs->leader_2 ? ' & ' . $rs->leader_2 : '') . '. Penceramah: ' . $rs->speaker . '.',
                    'is_major' => false,
                    'color' => 'teal',
                    'image' => null,
                ];
            }
        }

        // 4. Custom Section Header Settings
        $routineBadge = \App\Models\Setting::get('routine_schedule_badge', 'Jadwal Petugas Kebaktian Rutin');
        $routineTitle = \App\Models\Setting::get('routine_schedule_title', 'Penugasan Petugas & Topik Dhammadesana');
        $routineSubtitle = \App\Models\Setting::get('routine_schedule_subtitle', 'Daftar jadwal penugasan Puja Bakti mingguan, penceramah Dhamma, dan pemimpin kebaktian di Vihara Sāmaggi Gāma.');

        return $this->view([
            'featuredEvent' => $featuredEvent,
            'specialEvents' => $specialEvents,
            'routineSchedules' => $routineSchedules,
            'availableMonths' => $availableMonths,
            'calendarEvents' => $calendarEvents,
            'routineBadge' => $routineBadge,
            'routineTitle' => $routineTitle,
            'routineSubtitle' => $routineSubtitle,
        ])->title('Kalender Hari Besar Buddhis, Event & Jadwal Kegiatan — Vihara Sāmaggi Gāma');
    }
};
?>

<main 
    id="main-content" 
    x-data="{
        currentTab: (new URLSearchParams(window.location.search).get('tab') || 'kegiatan'),
        selectedEvent: null,
        openEventModal(eventData) {
            this.selectedEvent = eventData;
        },
        closeEventModal() {
            this.selectedEvent = null;
        }
    }"
    class="min-h-screen bg-[#F0E9DF] dark:bg-[#07130E] text-stone-800 dark:text-stone-100 font-sans selection:bg-emerald-200 selection:text-emerald-950"
>

    <!-- ==========================================
         SECTION 1: HERO HEADER WITH SACRED TOPOGRAPHY
         ========================================== -->
    <header class="w-full bg-[#FAF5ED] dark:bg-[#091711] border-b border-emerald-950/10 dark:border-emerald-500/15 relative overflow-hidden pt-12 sm:pt-16 pb-14 sm:pb-16 px-6 sm:px-12 lg:px-16">
        
        <!-- Background Contour Lines -->
        <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-15 dark:opacity-10 stroke-emerald-900 dark:stroke-emerald-400 fill-none" viewBox="0 0 1440 600" preserveAspectRatio="none" aria-hidden="true">
            <path d="M-50,120 C300,40 450,220 800,90 C1100,0 1300,140 1500,60" stroke-width="1.2" />
            <path d="M-50,180 C280,110 420,280 780,160 C1120,60 1280,210 1500,130" stroke-width="1.2" />
            <path d="M-50,250 C250,180 390,340 760,220 C1100,130 1260,280 1500,190" stroke-width="1.2" />
            <path d="M-50,320 C220,240 360,400 740,290 C1080,190 1240,340 1500,260" stroke-width="1.2" />
        </svg>

        <!-- Ambient Glow Aura -->
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-amber-500/10 dark:bg-emerald-500/10 rounded-full blur-3xl pointer-events-none animate-pulse-glow"></div>
        <div class="absolute bottom-0 left-1/4 w-96 h-96 bg-emerald-600/10 dark:bg-amber-400/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-5xl mx-auto text-center space-y-6 relative z-10">
            
            <!-- Breadcrumbs -->
            <nav class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-stone-200/60 dark:bg-emerald-950/60 border border-stone-300/40 dark:border-emerald-500/20 text-xs font-semibold text-stone-600 dark:text-stone-300" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-[#0D6E42] dark:hover:text-emerald-400 transition-colors">Beranda</a>
                <span class="text-stone-400">/</span>
                <span class="text-[#0D6E42] dark:text-emerald-300 font-bold">Kalender & Kegiatan</span>
            </nav>

            <!-- Eyebrow Pill Badge -->
            <div>
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-900/5 dark:bg-emerald-400/10 border border-emerald-800/15 dark:border-emerald-400/20 text-[#0D6E42] dark:text-emerald-300 text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Pusat Informasi Hari Besar Buddhis & Penjadwalan Kegiatan</span>
                </div>
            </div>

            <!-- Page Title -->
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-tight">
                Kalender Hari Besar & <br class="hidden sm:inline" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#0D5B3A] via-[#8C6B1F] to-[#0D5B3A] dark:from-emerald-300 dark:via-amber-300 dark:to-emerald-300">
                    Agenda Kegiatan Vihara
                </span>
            </h1>

            <!-- Narrative Paragraph -->
            <p class="max-w-3xl mx-auto text-stone-600 dark:text-stone-300 text-sm sm:text-base lg:text-lg leading-relaxed">
                Informasi perayaan hari besar suci Buddhis (Waisak, Asadha, Kathina, Magha Puja, Uposatha), agenda kegiatan tematik, dan jadwal penugasan petugas Puja Bakti.
            </p>

            <!-- Main Tab Switcher Bar (Kalender vs List Kegiatan) -->
            <div class="pt-2 flex items-center justify-center">
                <div class="inline-flex p-1.5 rounded-2xl bg-stone-200/80 dark:bg-[#071811] border border-stone-300/80 dark:border-emerald-500/25 shadow-inner">
                    <button 
                        type="button" 
                        @click="currentTab = 'kegiatan'; history.replaceState(null, '', '?tab=kegiatan')"
                        :class="currentTab === 'kegiatan' ? 'bg-[#0D5B3A] text-white shadow-md ring-1 ring-amber-400/30' : 'text-stone-700 dark:text-stone-300 hover:text-emerald-800 dark:hover:text-emerald-300'"
                        class="px-5 sm:px-8 py-3 rounded-xl font-black text-xs sm:text-sm transition-all duration-200 flex items-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span>Agenda & Jadwal Kegiatan</span>
                    </button>
                    
                    <button 
                        type="button" 
                        @click="currentTab = 'kalender'; history.replaceState(null, '', '?tab=kalender')"
                        :class="currentTab === 'kalender' ? 'bg-[#0D5B3A] text-white shadow-md ring-1 ring-amber-400/30' : 'text-stone-700 dark:text-stone-300 hover:text-emerald-800 dark:hover:text-emerald-300'"
                        class="px-5 sm:px-8 py-3 rounded-xl font-black text-xs sm:text-sm transition-all duration-200 flex items-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Kalender Hari Besar & Uposatha</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-[1440px] mx-auto px-6 sm:px-10 lg:px-16 py-10 sm:py-14">

        <!-- =========================================================================
             TAB VIEW 1: KALENDER HARI BESAR & UPOSATHA BUDDHIS
             ========================================================================= -->
        <div 
            x-show="currentTab === 'kalender'" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-3"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="space-y-12"
        >
            <!-- SECTION 2: KALENDER HARI BESAR & AGENDA INTERAKTIF (ALPINE.JS) -->
            <section 
                id="kalender-interaktif" 
                aria-labelledby="kalender-heading"
                x-data="{
                    currYear: 2026,
                    currMonth: 7, // 0-indexed: 7 is August (0=Jan, 7=Aug)
                selectedDateStr: '2026-08-26',
                filterType: 'all', // 'all', 'holiday', 'special_event', 'routine'
                allEvents: @js($calendarEvents),
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                dayNamesShort: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],

                init() {
                    const today = new Date();
                    // Set default to current actual date or fallback to August 2026
                    this.currYear = today.getFullYear();
                    this.currMonth = today.getMonth();
                    const pad = (n) => String(n).padStart(2, '0');
                    this.selectedDateStr = `${this.currYear}-${pad(this.currMonth + 1)}-${pad(today.getDate())}`;
                },

                get daysInMonth() {
                    return new Date(this.currYear, this.currMonth + 1, 0).getDate();
                },

                get firstDayOfWeek() {
                    return new Date(this.currYear, this.currMonth, 1).getDay();
                },

                prevMonth() {
                    if (this.currMonth === 0) {
                        this.currMonth = 11;
                        this.currYear--;
                    } else {
                        this.currMonth--;
                    }
                },

                nextMonth() {
                    if (this.currMonth === 11) {
                        this.currMonth = 0;
                        this.currYear++;
                    } else {
                        this.currMonth++;
                    }
                },

                goToToday() {
                    const today = new Date();
                    this.currYear = today.getFullYear();
                    this.currMonth = today.getMonth();
                    const pad = (n) => String(n).padStart(2, '0');
                    this.selectedDateStr = `${this.currYear}-${pad(this.currMonth + 1)}-${pad(today.getDate())}`;
                },

                formatDateKey(day) {
                    const pad = (n) => String(n).padStart(2, '0');
                    return `${this.currYear}-${pad(this.currMonth + 1)}-${pad(day)}`;
                },

                getEventsForDate(dateStr) {
                    return this.allEvents.filter(e => {
                        const matchDate = e.date === dateStr;
                        const matchFilter = (this.filterType === 'all') || 
                            (this.filterType === 'holiday' && (e.type === 'holiday' || e.type === 'uposatha')) ||
                            (this.filterType === e.type);
                        return matchDate && matchFilter;
                    });
                },

                getEventsForCurrentMonth() {
                    const pad = (n) => String(n).padStart(2, '0');
                    const monthKey = `${this.currYear}-${pad(this.currMonth + 1)}`;
                    return this.allEvents.filter(e => {
                        const matchMonth = e.date.startsWith(monthKey);
                        const matchFilter = (this.filterType === 'all') || 
                            (this.filterType === 'holiday' && (e.type === 'holiday' || e.type === 'uposatha')) ||
                            (this.filterType === e.type);
                        return matchMonth && matchFilter;
                    }).sort((a, b) => a.date.localeCompare(b.date));
                },

                isToday(day) {
                    const today = new Date();
                    return this.currYear === today.getFullYear() && 
                           this.currMonth === today.getMonth() && 
                           day === today.getDate();
                },

                isSelected(day) {
                    return this.selectedDateStr === this.formatDateKey(day);
                },

                selectDay(day) {
                    this.selectedDateStr = this.formatDateKey(day);
                },

                get formattedSelectedDateHeader() {
                    if (!this.selectedDateStr) return '';
                    const parts = this.selectedDateStr.split('-');
                    const d = new Date(parts[0], parts[1] - 1, parts[2]);
                    const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    return `${dayNames[d.getDay()]}, ${parts[2]} ${this.monthNames[d.getMonth()]} ${parts[0]}`;
                }
            }"
            class="space-y-8"
        >
            
            <!-- Section Header & Controls -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-stone-300/60 dark:border-emerald-500/20">
                <div class="space-y-2 max-w-2xl">
                    <div class="inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-widest text-[#0D6E42] dark:text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span>Kalender Buddhis & Penjadwalan Interaktif</span>
                    </div>
                    <h2 id="kalender-heading" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                        Kalender Hari Besar & Agenda Bulanan
                    </h2>
                    <p class="text-stone-600 dark:text-stone-300 text-xs sm:text-sm leading-relaxed">
                        Pilih tanggal untuk melihat rincian hari suci, makna spiritual peristiwa agung, serta agenda kebajikan yang terselenggara di vihara.
                    </p>
                    
                    <!-- Category Filter Pills -->
                    <div class="flex flex-wrap items-center gap-2">
                        <button 
                            type="button"
                            @click="filterType = 'all'"
                            :class="filterType === 'all' ? 'bg-[#0D5B3A] text-white border-transparent shadow-xs' : 'bg-white dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border-stone-300 dark:border-emerald-500/20'"
                            class="px-3.5 py-1.5 rounded-full border text-xs font-bold transition-all cursor-pointer"
                        >
                            Semua Agenda & Libur
                        </button>
                        <button 
                            type="button"
                            @click="filterType = 'holiday'"
                            :class="filterType === 'holiday' ? 'bg-amber-600 text-white border-transparent shadow-xs' : 'bg-white dark:bg-[#0b1c15] text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-500/30'"
                            class="px-3.5 py-1.5 rounded-full border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Hari Raya Buddhis</span>
                        </button>
                        <button 
                            type="button"
                            @click="filterType = 'uposatha'"
                            :class="filterType === 'uposatha' ? 'bg-purple-600 text-white border-transparent shadow-xs' : 'bg-white dark:bg-[#0b1c15] text-purple-800 dark:text-purple-300 border-purple-300 dark:border-purple-500/30'"
                            class="px-3.5 py-1.5 rounded-full border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            <span>Hari Uposatha</span>
                        </button>
                        <button 
                            type="button"
                            @click="filterType = 'national_holiday'"
                            :class="filterType === 'national_holiday' ? 'bg-rose-600 text-white border-transparent shadow-xs' : 'bg-white dark:bg-[#0b1c15] text-rose-800 dark:text-rose-300 border-rose-300 dark:border-rose-500/30'"
                            class="px-3.5 py-1.5 rounded-full border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                            <span>Hari Libur Nasional</span>
                        </button>
                        <button 
                            type="button"
                            @click="filterType = 'special_event'"
                            :class="filterType === 'special_event' ? 'bg-emerald-700 text-white border-transparent shadow-xs' : 'bg-white dark:bg-[#0b1c15] text-emerald-800 dark:text-emerald-300 border-emerald-300 dark:border-emerald-500/30'"
                            class="px-3.5 py-1.5 rounded-full border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Event Vihara</span>
                        </button>
                        <button 
                            type="button"
                            @click="filterType = 'routine'"
                            :class="filterType === 'routine' ? 'bg-teal-700 text-white border-transparent shadow-xs' : 'bg-white dark:bg-[#0b1c15] text-teal-800 dark:text-teal-300 border-teal-300 dark:border-teal-500/30'"
                            class="px-3.5 py-1.5 rounded-full border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            <span>Puja Bakti Rutin</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Layout: Left Calendar Grid (~60%) + Right Selected Date Detail (~40%) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left: Calendar Widget Card -->
                <div class="lg:col-span-7 bg-[#FAF5ED] dark:bg-[#0b1c15] rounded-3xl p-5 sm:p-7 border border-amber-500/20 dark:border-emerald-500/20 shadow-lg space-y-6">
                    
                    <!-- Month Navigation Header -->
                    <div class="flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <h3 class="text-xl sm:text-2xl font-black text-[#143D2D] dark:text-[#E8F3EE]" x-text="monthNames[currMonth] + ' ' + currYear"></h3>
                            <p class="text-[11px] text-stone-500 dark:text-stone-400 font-semibold" x-text="getEventsForCurrentMonth().length + ' agenda & hari libur terdaftar'"></p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button 
                                @click="goToToday()" 
                                type="button" 
                                class="px-3 py-1.5 rounded-xl bg-stone-200/80 dark:bg-emerald-950/70 hover:bg-stone-300 dark:hover:bg-emerald-900 text-stone-700 dark:text-stone-300 text-xs font-bold transition-colors cursor-pointer"
                            >
                                Hari Ini
                            </button>
                            <div class="flex items-center gap-1">
                                <button 
                                    @click="prevMonth()" 
                                    type="button" 
                                    class="p-2 rounded-xl bg-stone-200/80 dark:bg-emerald-950/70 hover:bg-stone-300 dark:hover:bg-emerald-900 text-stone-700 dark:text-stone-300 transition-colors cursor-pointer"
                                    title="Bulan Sebelumnya"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button 
                                    @click="nextMonth()" 
                                    type="button" 
                                    class="p-2 rounded-xl bg-stone-200/80 dark:bg-emerald-950/70 hover:bg-stone-300 dark:hover:bg-emerald-900 text-stone-700 dark:text-stone-300 transition-colors cursor-pointer"
                                    title="Bulan Berikutnya"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Days of Week Header -->
                    <div class="grid grid-cols-7 gap-1 sm:gap-2 text-center text-[11px] font-black text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                        <template x-for="(dayName, index) in dayNamesShort" :key="'dname-' + index">
                            <div 
                                :class="{ 'text-rose-600 dark:text-rose-400': index === 0, 'text-amber-700 dark:text-amber-400': index === 6 }"
                                class="py-2" 
                                x-text="dayName"
                            ></div>
                        </template>
                    </div>

                    <!-- Calendar Day Cells Grid -->
                    <div class="grid grid-cols-7 gap-1 sm:gap-2">
                        
                        <!-- Empty Lead In Cells -->
                        <template x-for="emptyDay in firstDayOfWeek" :key="'empty-' + emptyDay">
                            <div class="aspect-square rounded-2xl bg-stone-100/40 dark:bg-emerald-950/20 border border-transparent"></div>
                        </template>

                        <!-- Days of Month -->
                        <template x-for="day in daysInMonth" :key="'day-' + day">
                            <button 
                                type="button"
                                @click="selectDay(day)"
                                :class="{
                                    'ring-2 ring-emerald-600 dark:ring-emerald-400 bg-emerald-100/90 dark:bg-emerald-950/90 font-black scale-105 shadow-md z-10': isSelected(day),
                                    'bg-white dark:bg-[#071710] border border-stone-200/80 dark:border-emerald-950/60 hover:bg-emerald-50/70 dark:hover:bg-emerald-950/50': !isSelected(day),
                                    'border-amber-500/60 dark:border-amber-400/50': isToday(day) && !isSelected(day)
                                }"
                                class="aspect-square rounded-2xl p-1 sm:p-1.5 flex flex-col justify-between transition-all duration-200 cursor-pointer relative group text-left"
                            >
                                <!-- Day Number & Today Tag -->
                                <div class="flex items-center justify-between w-full">
                                    <span 
                                        :class="{
                                            'text-emerald-900 dark:text-emerald-200 font-black': isSelected(day),
                                            'text-amber-600 dark:text-amber-400 font-bold': isToday(day) && !isSelected(day),
                                            'text-stone-800 dark:text-stone-200 font-semibold': !isToday(day) && !isSelected(day)
                                        }"
                                        class="text-xs sm:text-sm"
                                        x-text="day"
                                    ></span>

                                    <template x-if="isToday(day)">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0" title="Hari Ini"></span>
                                    </template>
                                </div>

                                <!-- Event Indicator Dots / Badges -->
                                <div class="flex items-center gap-1 flex-wrap mt-auto">
                                    <template x-for="ev in getEventsForDate(formatDateKey(day)).slice(0, 3)" :key="ev.id">
                                        <span 
                                            :class="{
                                                'bg-amber-500': ev.type === 'holiday',
                                                'bg-purple-500': ev.type === 'uposatha',
                                                'bg-rose-500': ev.type === 'national_holiday',
                                                'bg-emerald-500': ev.type === 'special_event',
                                                'bg-teal-500': ev.type === 'routine'
                                            }"
                                            class="w-1.5 sm:w-2 h-1.5 sm:h-2 rounded-full shadow-2xs"
                                            :title="ev.title"
                                        ></span>
                                    </template>
                                    <template x-if="getEventsForDate(formatDateKey(day)).length > 3">
                                        <span class="text-[9px] text-stone-400 font-bold leading-none">+</span>
                                    </template>
                                </div>
                            </button>
                        </template>

                    </div>

                    <!-- Legend -->
                    <div class="pt-4 border-t border-stone-200 dark:border-emerald-950 flex flex-wrap items-center justify-between gap-3 text-[11px] text-stone-500 dark:text-stone-400 font-semibold">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-2xs"></span>
                            <span>Hari Raya Buddhis</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shadow-2xs"></span>
                            <span>Hari Uposatha</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-2xs"></span>
                            <span>Libur Nasional</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-2xs"></span>
                            <span>Event Vihara</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-500 shadow-2xs"></span>
                            <span>Puja Bakti Rutin</span>
                        </div>
                    </div>

                </div>

                <!-- Right: Selected Date Agenda Details & Month Highlights -->
                <div class="lg:col-span-5 space-y-6">
                    
                    <!-- Selected Date Header Card -->
                    <div class="bg-[#FAF5ED] dark:bg-[#0b1c15] rounded-3xl p-6 border border-amber-500/20 dark:border-emerald-500/20 shadow-lg space-y-4">
                        
                        <div class="flex items-center justify-between border-b border-stone-200 dark:border-emerald-950 pb-3">
                            <div>
                                <span class="text-[10.5px] uppercase font-black text-amber-700 dark:text-amber-400 tracking-wider">Rincian Agenda</span>
                                <h4 class="text-base sm:text-lg font-extrabold text-[#143D2D] dark:text-[#E8F3EE]" x-text="formattedSelectedDateHeader"></h4>
                            </div>
                            <div class="px-2.5 py-1 rounded-full bg-emerald-950/10 dark:bg-emerald-400/10 text-emerald-800 dark:text-emerald-300 font-bold text-xs" x-text="getEventsForDate(selectedDateStr).length + ' Agenda'"></div>
                        </div>

                        <!-- Events List on Selected Date -->
                        <div class="space-y-3">
                            <template x-for="item in getEventsForDate(selectedDateStr)" :key="item.id">
                                <div 
                                    :class="{
                                        'border-amber-500/40 bg-amber-50/60 dark:bg-amber-950/20': item.type === 'holiday',
                                        'border-purple-500/40 bg-purple-50/60 dark:bg-purple-950/20': item.type === 'uposatha',
                                        'border-rose-500/40 bg-rose-50/60 dark:bg-rose-950/20': item.type === 'national_holiday',
                                        'border-emerald-500/40 bg-emerald-50/60 dark:bg-emerald-950/20': item.type === 'special_event',
                                        'border-teal-500/40 bg-teal-50/60 dark:bg-teal-950/20': item.type === 'routine'
                                    }"
                                    class="p-4 rounded-2xl border space-y-2 transition-all hover:shadow-md"
                                >
                                    <!-- Badge & Time -->
                                    <div class="flex items-center justify-between gap-2">
                                        <span 
                                            :class="{
                                                'bg-amber-500 text-stone-950': item.type === 'holiday',
                                                'bg-purple-600 text-white': item.type === 'uposatha',
                                                'bg-rose-600 text-white': item.type === 'national_holiday',
                                                'bg-emerald-700 text-white': item.type === 'special_event',
                                                'bg-teal-700 text-white': item.type === 'routine'
                                            }"
                                            class="px-2.5 py-0.5 rounded-md text-[10.5px] font-black uppercase tracking-wider shadow-2xs" 
                                            x-text="item.badge"
                                        ></span>

                                        <span class="text-xs font-bold text-stone-500 dark:text-stone-400 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span x-text="item.time"></span>
                                        </span>
                                    </div>

                                    <!-- Event Title -->
                                    <h5 class="font-extrabold text-stone-900 dark:text-stone-100 text-sm leading-snug" x-text="item.title"></h5>

                                    <!-- Description / Note -->
                                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed" x-text="item.description"></p>

                                    <!-- Location & Leader -->
                                    <div class="pt-2 border-t border-stone-200/60 dark:border-emerald-950/60 text-[11px] text-stone-500 dark:text-stone-400 space-y-1">
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span x-text="item.location"></span>
                                        </div>
                                        <template x-if="item.leader">
                                            <div class="flex items-center gap-1.5 font-medium text-stone-800 dark:text-stone-200">
                                                <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                <span x-text="item.leader"></span>
                                            </div>
                                        </template>
                                    </div>

                                </div>
                            </template>

                            <!-- Empty State on Selected Date -->
                            <template x-if="getEventsForDate(selectedDateStr).length === 0">
                                <div class="py-8 text-center text-stone-400 space-y-2">
                                    <div class="w-10 h-10 rounded-full bg-stone-100 dark:bg-emerald-950/50 flex items-center justify-center mx-auto text-stone-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div class="text-xs font-bold text-stone-600 dark:text-stone-300">Tidak ada agenda khusus pada tanggal ini</div>
                                    <p class="text-[11px]">Silakan klik tanggal yang memiliki titik warna pada kalender.</p>
                                </div>
                            </template>
                        </div>

                    </div>

                    <!-- Highlights of the Month Card -->
                    <div class="bg-[#FAF5ED] dark:bg-[#0b1c15] rounded-3xl p-6 border border-stone-300/60 dark:border-emerald-500/20 shadow-sm space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-stone-900 dark:text-stone-100 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span>Agenda Penting Bulan <span x-text="monthNames[currMonth]"></span></span>
                            </span>
                            <span class="text-[11px] font-bold text-stone-500" x-text="getEventsForCurrentMonth().length + ' item'"></span>
                        </div>

                        <div class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar pr-1">
                            <template x-for="item in getEventsForCurrentMonth()" :key="'mo-' + item.id">
                                <button 
                                    type="button"
                                    @click="selectedDateStr = item.date"
                                    class="w-full text-left p-2.5 rounded-xl bg-white dark:bg-[#071710] hover:bg-emerald-50 dark:hover:bg-emerald-950 border border-stone-200/80 dark:border-emerald-950/60 transition-colors flex items-center justify-between gap-2 group cursor-pointer"
                                >
                                    <div class="space-y-0.5 min-w-0">
                                        <div class="text-[10px] font-bold text-amber-700 dark:text-amber-400" x-text="item.date"></div>
                                        <div class="text-xs font-extrabold text-stone-900 dark:text-stone-100 truncate group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors" x-text="item.title"></div>
                                    </div>
                                    <span 
                                        :class="{
                                            'bg-amber-100 dark:bg-amber-950 text-amber-900 dark:text-amber-300': item.type === 'holiday',
                                            'bg-purple-100 dark:bg-purple-950 text-purple-900 dark:text-purple-300': item.type === 'uposatha',
                                            'bg-rose-100 dark:bg-rose-950 text-rose-900 dark:text-rose-300': item.type === 'national_holiday',
                                            'bg-emerald-100 dark:bg-emerald-950 text-emerald-900 dark:text-emerald-300': item.type === 'special_event',
                                            'bg-teal-100 dark:bg-teal-950 text-teal-900 dark:text-teal-300': item.type === 'routine'
                                        }"
                                        class="px-2 py-0.5 rounded text-[9.5px] font-bold shrink-0" 
                                        x-text="item.badge"
                                    ></span>
                                </button>
                            </template>

                            <template x-if="getEventsForCurrentMonth().length === 0">
                                <div class="py-4 text-center text-xs text-stone-400">
                                    Tidak ada agenda pada bulan ini.
                                </div>
                            </template>
                        </div>
                    </div>

                </div>

            </div>

        </section>
        </div>

        <!-- =========================================================================
             TAB VIEW 2: DAFTAR EVENT & PENUGASAN PETUGAS KEBAKTIAN
             ========================================================================= -->
        <div 
            x-show="currentTab === 'kegiatan'" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-3"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="space-y-16 sm:space-y-20"
        >
            <!-- SECTION 3: SPOTLIGHT UPCOMING EVENT (FEATURED HERO CARD) -->
            @if ($featuredEvent)
            <section aria-labelledby="featured-event-heading" class="relative rounded-[2.5rem] overflow-hidden bg-[#FAF5ED] dark:bg-[#0b1c15] border border-amber-500/30 dark:border-emerald-500/25 shadow-xl reveal-on-scroll">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-0 items-stretch">
                    
                    <!-- Image Side -->
                    <div class="lg:col-span-6 relative aspect-[16/10] lg:aspect-auto min-h-[320px] lg:min-h-[440px] overflow-hidden bg-stone-900 group">
                        <img 
                            src="{{ asset($featuredEvent->cover_image ?: 'images/kegiatan-chanting.jpg') }}" 
                            alt="{{ $featuredEvent->title }}" 
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t lg:bg-gradient-to-r from-black/80 via-black/30 to-transparent pointer-events-none"></div>
                        
                        <!-- Spotlight Ribbon Badge -->
                        <div class="absolute top-5 left-5 z-10 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-emerald-600/95 backdrop-blur-md text-white text-xs font-black shadow-lg uppercase tracking-wider border border-emerald-400/40">
                                <span class="w-2 h-2 rounded-full bg-emerald-300 animate-ping"></span>
                                <span>Event Utama Mendatang</span>
                            </span>
                        </div>

                        <!-- Photo Overlay Info on Mobile -->
                        <div class="absolute bottom-5 left-5 right-5 text-white lg:hidden">
                            <span class="px-2.5 py-0.5 rounded-md bg-amber-500/90 text-stone-950 text-[11px] font-black uppercase tracking-wider">
                                {{ $featuredEvent->category }}
                            </span>
                        </div>
                    </div>

                    <!-- Info Side -->
                    <div class="lg:col-span-6 p-8 sm:p-10 lg:p-12 flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <div class="hidden lg:flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-900 dark:text-amber-300 text-xs font-extrabold uppercase tracking-wider border border-amber-500/20">
                                    {{ $featuredEvent->category }}
                                </span>
                                <span class="text-xs text-stone-500 dark:text-stone-400 font-semibold">• {{ $featuredEvent->schedule_type }}</span>
                            </div>

                            <h2 id="featured-event-heading" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-snug">
                                {{ $featuredEvent->title }}
                            </h2>

                            <p class="text-stone-600 dark:text-stone-300 text-sm sm:text-base leading-relaxed line-clamp-3">
                                {{ $featuredEvent->description }}
                            </p>

                            <!-- Key Event Metadata Chips -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                <div class="flex items-center gap-3 p-3 rounded-2xl bg-stone-100/80 dark:bg-[#081610] border border-stone-200 dark:border-emerald-900/40">
                                    <div class="w-9 h-9 rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-[10.5px] font-bold text-stone-500 dark:text-stone-400 uppercase">Hari & Tanggal</div>
                                        <div class="text-xs sm:text-sm font-extrabold text-[#143D2D] dark:text-[#E8F3EE]">
                                            {{ $featuredEvent->event_date ? $featuredEvent->event_date->translatedFormat('l, d F Y') : '-' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-3 rounded-2xl bg-stone-100/80 dark:bg-[#081610] border border-stone-200 dark:border-emerald-900/40">
                                    <div class="w-9 h-9 rounded-xl bg-emerald-600/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-[10.5px] font-bold text-stone-500 dark:text-stone-400 uppercase">Waktu</div>
                                        <div class="text-xs sm:text-sm font-extrabold text-[#143D2D] dark:text-[#E8F3EE]">
                                            {{ substr($featuredEvent->start_time, 0, 5) }}{{ $featuredEvent->end_time ? ' - ' . substr($featuredEvent->end_time, 0, 5) : '' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-3 rounded-2xl bg-stone-100/80 dark:bg-[#081610] border border-stone-200 dark:border-emerald-900/40 sm:col-span-2">
                                    <div class="w-9 h-9 rounded-xl bg-purple-600/15 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-[10.5px] font-bold text-stone-500 dark:text-stone-400 uppercase">Lokasi Ruangan</div>
                                        <div class="text-xs sm:text-sm font-extrabold text-[#143D2D] dark:text-[#E8F3EE]">{{ $featuredEvent->location }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="pt-2">
                            <button 
                                type="button"
                                @click="openEventModal({{ json_encode([
                                    'title' => $featuredEvent->title,
                                    'category' => $featuredEvent->category,
                                    'schedule_type' => $featuredEvent->schedule_type,
                                    'date' => $featuredEvent->event_date ? $featuredEvent->event_date->translatedFormat('l, d F Y') : '-',
                                    'time' => substr($featuredEvent->start_time, 0, 5) . ($featuredEvent->end_time ? ' - ' . substr($featuredEvent->end_time, 0, 5) : ''),
                                    'location' => $featuredEvent->location,
                                    'leader' => $featuredEvent->leader,
                                    'description' => $featuredEvent->description,
                                    'image' => asset($featuredEvent->cover_image ?: 'images/kegiatan-chanting.jpg'),
                                    'activities' => $featuredEvent->activities,
                                ]) }})"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-[#0D5B3A] hover:bg-emerald-800 text-white font-extrabold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all cursor-pointer transform hover:-translate-y-0.5"
                            >
                                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Lihat Rincian & Poster Lengkap</span>
                            </button>
                        </div>

                    </div>
                </div>
            </section>
        @endif

        <!-- ==========================================
             SECTION 5: TABEL JADWAL PETUGAS KEBAKTIAN RUTIN
             Format: No | Tanggal | Kegiatan | Pemimpin 1 | Pemimpin 2 | Penceramah | Topik
             ========================================== -->
        <section 
            id="jadwal-petugas" 
            aria-labelledby="jadwal-petugas-heading" 
            x-data="{
                searchQuery: '',
                monthFilter: 'all',
                visibleCount: 5,
                schedules: @js($routineSchedules),
                get filteredSchedules() {
                    return this.schedules.filter(item => this.matches(item));
                },
                matches(item) {
                    const matchMonth = (this.monthFilter === 'all') || (item.monthYear === this.monthFilter);
                    const q = this.searchQuery.toLowerCase().trim();
                    const matchSearch = (q === '') || 
                        item.activity.toLowerCase().includes(q) ||
                        item.speaker.toLowerCase().includes(q) ||
                        item.leader1.toLowerCase().includes(q) ||
                        item.leader2.toLowerCase().includes(q) ||
                        item.topic.toLowerCase().includes(q) ||
                        item.date.toLowerCase().includes(q);
                    return matchMonth && matchSearch;
                },
                loadMore() {
                    this.visibleCount += 5;
                }
            }"
            class="space-y-8"
        >
            
            <!-- Section Header & Filter Bar -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-stone-300/60 dark:border-emerald-500/20">
                <div class="space-y-2 max-w-2xl">
                    <div class="inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-widest text-[#0D6E42] dark:text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span>{{ $routineBadge }}</span>
                    </div>
                    <h2 id="jadwal-petugas-heading" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                        {{ $routineTitle }}
                    </h2>
                    @if ($routineSubtitle)
                        <p class="text-stone-600 dark:text-stone-300 text-xs sm:text-sm leading-relaxed">
                            {{ $routineSubtitle }}
                        </p>
                    @endif
                </div>

                <!-- Search & Month Selector -->
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <select 
                        x-model="monthFilter" 
                        @change="visibleCount = 6"
                        class="w-full sm:w-auto px-4 py-2.5 rounded-2xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 text-xs font-bold text-stone-800 dark:text-stone-100 focus:outline-none focus:border-emerald-600 dark:focus:border-emerald-400 cursor-pointer"
                    >
                        <option value="all">Semua Bulan</option>
                        @foreach ($availableMonths as $m)
                            <option value="{{ $m['value'] }}">{{ $m['label'] }}</option>
                        @endforeach
                    </select>

                    <div class="relative w-full sm:w-64">
                        <input 
                            type="text" 
                            x-model="searchQuery" 
                            @input="visibleCount = 6"
                            placeholder="Cari penceramah / topik..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 text-xs text-stone-800 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-emerald-600 dark:focus:border-emerald-400 transition-colors shadow-inner"
                        />
                        <svg class="w-4 h-4 text-stone-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Tabel Data Interaktif: No | Tanggal | Kegiatan | Pemimpin 1 | Pemimpin 2 | Penceramah | Topik -->
            <div class="rounded-3xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/80 dark:border-emerald-500/20 shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F0E9DF]/80 dark:bg-[#071710] border-b border-stone-300/60 dark:border-emerald-950 text-[11px] font-black text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                            <tr>
                                <th scope="col" class="py-4 px-4 text-center w-12">No</th>
                                <th scope="col" class="py-4 px-5 min-w-[150px]">Tanggal & Hari</th>
                                <th scope="col" class="py-4 px-5 min-w-[180px]">Kegiatan</th>
                                <th scope="col" class="py-4 px-4 min-w-[150px]">Pemimpin 1</th>
                                <th scope="col" class="py-4 px-4 min-w-[150px]">Pemimpin 2</th>
                                <th scope="col" class="py-4 px-5 min-w-[180px]">Penceramah</th>
                                <th scope="col" class="py-4 px-5 min-w-[220px]">Topik Dhamma</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200/80 dark:divide-emerald-950/60 font-sans">
                            <template x-for="(item, idx) in filteredSchedules.slice(0, visibleCount)" :key="item.id">
                                <tr class="hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30 transition-colors">
                                    
                                    <!-- No -->
                                    <td class="py-4 px-4 text-center font-bold text-stone-400 dark:text-stone-500" x-text="idx + 1"></td>

                                    <!-- Tanggal & Hari -->
                                    <td class="py-4 px-5">
                                        <div class="font-extrabold text-[#143D2D] dark:text-[#E8F3EE] text-xs sm:text-sm" x-text="item.date"></div>
                                        <div class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold" x-text="item.dayName + ' • ' + item.timeRange"></div>
                                    </td>

                                    <!-- Kegiatan -->
                                    <td class="py-4 px-5">
                                        <span class="font-extrabold text-stone-900 dark:text-stone-100 block" x-text="item.activity"></span>
                                        <template x-if="item.isUpcoming">
                                            <span class="inline-flex items-center gap-1 mt-1 text-[#0D6E42] dark:text-emerald-300 text-[10px] font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                <span>Akan Datang</span>
                                            </span>
                                        </template>
                                    </td>

                                    <!-- Pemimpin 1 -->
                                    <td class="py-4 px-4 font-bold text-stone-800 dark:text-stone-200" x-text="item.leader1"></td>

                                    <!-- Pemimpin 2 -->
                                    <td class="py-4 px-4 font-semibold text-stone-600 dark:text-stone-300" x-text="item.leader2"></td>

                                    <!-- Penceramah -->
                                    <td class="py-4 px-5">
                                        <div class="font-black text-[#0D5B3A] dark:text-emerald-300 text-xs sm:text-sm" x-text="item.speaker"></div>
                                    </td>

                                    <!-- Topik Dhamma -->
                                    <td class="py-4 px-5">
                                        <div class="font-semibold text-stone-800 dark:text-stone-200 italic leading-relaxed" x-text="'“' + item.topic + '”'"></div>
                                    </td>

                                </tr>
                            </template>

                            <!-- Empty Search State -->
                            <template x-if="filteredSchedules.length === 0">
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-stone-400 space-y-2">
                                        <div class="text-base font-bold text-stone-600 dark:text-stone-300">Tidak ada jadwal yang sesuai</div>
                                        <div class="text-xs">Silakan ubah filter bulan atau kata kunci pencarian.</div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Load More / Read More Button -->
            <template x-if="visibleCount < filteredSchedules.length">
                <div class="pt-2 flex flex-col items-center justify-center gap-3">
                    <button 
                        type="button" 
                        @click="loadMore()" 
                        class="px-8 py-3.5 rounded-2xl bg-white dark:bg-[#071811] hover:bg-[#0D5B3A] hover:text-white dark:hover:bg-[#0D5B3A] text-stone-800 dark:text-stone-200 border border-stone-300 dark:border-emerald-500/30 text-xs sm:text-sm font-extrabold shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2.5 cursor-pointer group"
                    >
                        <svg class="w-4 h-4 text-amber-500 group-hover:text-amber-300 transition-transform duration-300 group-hover:translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        <span>Tampilkan Jadwal Lainnya</span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] bg-stone-100 dark:bg-emerald-950 text-stone-600 dark:text-stone-300 group-hover:bg-black/20 group-hover:text-white font-bold" x-text="'+' + Math.min(5, filteredSchedules.length - visibleCount)"></span>
                    </button>
                    <p class="text-[11px] text-stone-500 dark:text-stone-400 font-semibold" x-text="'Menampilkan ' + Math.min(visibleCount, filteredSchedules.length) + ' dari ' + filteredSchedules.length + ' penugasan kebaktian'"></p>
                </div>
            </template>

        </section>
        </div>

    </div>

    <!-- ==========================================
         INTERACTIVE EVENT DETAIL MODAL (TELEPORTED)
         ========================================== -->
    <div 
        x-show="selectedEvent !== null" 
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-md flex items-center justify-center p-4 sm:p-6"
        @keydown.escape.window="closeEventModal()"
    >
        <div 
            @click.away="closeEventModal()"
            class="relative max-w-3xl w-full bg-[#FAF5ED] dark:bg-[#0b1c15] rounded-[2rem] overflow-hidden border border-amber-500/30 dark:border-emerald-500/30 shadow-2xl space-y-0 my-auto max-h-[92vh] flex flex-col"
        >
            <!-- Poster Header Image -->
            <div class="relative aspect-[16/9] sm:aspect-[21/9] bg-stone-950 overflow-hidden shrink-0">
                <img 
                    :src="selectedEvent?.image" 
                    :alt="selectedEvent?.title" 
                    class="w-full h-full object-cover"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

                <!-- Close Button -->
                <button 
                    @click="closeEventModal()" 
                    type="button" 
                    class="absolute top-4 right-4 w-9 h-9 rounded-full bg-black/50 hover:bg-black/80 text-white flex items-center justify-center transition-colors cursor-pointer z-10"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <!-- Category Badge -->
                <div class="absolute bottom-4 left-5 flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full bg-amber-500 text-stone-950 text-xs font-black uppercase tracking-wider shadow-sm" x-text="selectedEvent?.category"></span>
                    <span class="px-2.5 py-1 rounded-full bg-black/60 text-white text-xs font-bold backdrop-blur-sm" x-text="selectedEvent?.schedule_type"></span>
                </div>
            </div>

            <!-- Modal Content Body -->
            <div class="p-6 sm:p-8 space-y-5 overflow-y-auto custom-scrollbar">
                
                <h3 class="text-xl sm:text-2xl font-black text-[#143D2D] dark:text-[#E8F3EE] leading-snug" x-text="selectedEvent?.title"></h3>

                <!-- Key Meta Badges -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="p-3 rounded-2xl bg-white dark:bg-[#071710] border border-stone-200 dark:border-emerald-950/80 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-stone-400">Hari & Tanggal</div>
                            <div class="text-xs font-extrabold text-stone-900 dark:text-stone-100" x-text="selectedEvent?.date"></div>
                        </div>
                    </div>

                    <div class="p-3 rounded-2xl bg-white dark:bg-[#071710] border border-stone-200 dark:border-emerald-950/80 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-600/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-stone-400">Waktu Acara</div>
                            <div class="text-xs font-extrabold text-stone-900 dark:text-stone-100" x-text="selectedEvent?.time"></div>
                        </div>
                    </div>

                    <div class="p-3 rounded-2xl bg-white dark:bg-[#071710] border border-stone-200 dark:border-emerald-950/80 flex items-center gap-3 sm:col-span-2">
                        <div class="w-8 h-8 rounded-xl bg-purple-600/15 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-stone-400">Lokasi / Tempat</div>
                            <div class="text-xs font-extrabold text-stone-900 dark:text-stone-100" x-text="selectedEvent?.location"></div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="space-y-1.5" x-show="selectedEvent?.description">
                    <div class="text-[10.5px] uppercase font-bold text-stone-400 tracking-wider">Keterangan / Pengantar Acara</div>
                    <p class="text-xs sm:text-sm text-stone-700 dark:text-stone-300 leading-relaxed whitespace-pre-line" x-text="selectedEvent?.description"></p>
                </div>

                <!-- =========================================================================
                     RANGKAIAN JADWAL KEGIATAN BERTANGGAL (STRUCTURED ACTIVITIES TABLE)
                     ========================================================================= -->
                <template x-if="selectedEvent?.activities && selectedEvent.activities.length > 0">
                    <div class="space-y-3 pt-3 border-t border-stone-200 dark:border-emerald-950/80">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#0D6E42] dark:text-emerald-400 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Rangkaian Jadwal Kegiatan</span>
                            </h4>
                        </div>

                        <div class="rounded-2xl border border-stone-300/80 dark:border-emerald-500/20 bg-white/70 dark:bg-[#071710]/70 overflow-hidden shadow-sm">
                            <div class="overflow-x-auto custom-scrollbar">
                                <table class="w-full text-left text-xs min-w-[580px]">
                                    <thead class="bg-stone-100 dark:bg-[#06140e] border-b border-stone-200 dark:border-emerald-950 text-[10.5px] font-black text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                        <tr>
                                            <th scope="col" class="py-3 px-3 text-center w-10">No</th>
                                            <th scope="col" class="py-3 px-4 min-w-[130px]">Tanggal & Waktu</th>
                                            <th scope="col" class="py-3 px-4 min-w-[150px]">Kegiatan</th>
                                            <th scope="col" class="py-3 px-3 min-w-[120px]">Pemimpin</th>
                                            <th scope="col" class="py-3 px-4 min-w-[140px]">Penceramah</th>
                                            <th scope="col" class="py-3 px-4 min-w-[160px]">Topik Dhamma</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-stone-200/80 dark:divide-emerald-950/60 font-sans">
                                        <template x-for="(act, idx) in selectedEvent.activities" :key="idx">
                                            <tr class="hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30 transition-colors">
                                                <td class="py-3 px-3 text-center font-bold text-stone-400" x-text="idx + 1"></td>
                                                <td class="py-3 px-4">
                                                    <div class="font-extrabold text-stone-900 dark:text-stone-100 text-xs" x-text="act.date"></div>
                                                    <div class="text-[10.5px] text-amber-700 dark:text-amber-400 font-semibold" x-text="act.time"></div>
                                                </td>
                                                <td class="py-3 px-4 font-black text-[#143D2D] dark:text-[#E8F3EE]" x-text="act.activity || '-'"></td>
                                                <td class="py-3 px-3 text-[11px] text-stone-700 dark:text-stone-300">
                                                    <div class="font-bold" x-text="act.leader_1 || '-'"></div>
                                                    <template x-if="act.leader_2">
                                                        <div class="text-[10px] text-stone-500 dark:text-stone-400" x-text="'& ' + act.leader_2"></div>
                                                    </template>
                                                </td>
                                                <td class="py-3 px-4 font-black text-[#0D5B3A] dark:text-emerald-300" x-text="act.speaker || '-'"></td>
                                                <td class="py-3 px-4 italic text-stone-700 dark:text-stone-300" x-text="act.topic ? ('“' + act.topic + '”') : '-'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Action / Share button -->
                <div class="pt-4 border-t border-stone-200 dark:border-emerald-950/80 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <a 
                        :href="'https://wa.me/?text=' + encodeURIComponent('Mari hadiri event ' + selectedEvent?.title + ' di Vihara Sāmaggi Gāma pada ' + selectedEvent?.date + ' pukul ' + selectedEvent?.time + '. Info selengkapnya: ' + window.location.href)" 
                        target="_blank" 
                        rel="noopener noreferrer" 
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-sm cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.584 1.961.949 2.796.949 3.179 0 5.765-2.587 5.767-5.766.001-3.187-2.578-5.836-5.767-5.836zm3.385 8.213c-.144.405-.837.774-1.17.824-.312.045-.698.072-2.115-.515-1.705-.705-2.825-2.42-2.91-2.533-.085-.114-.687-.914-.687-1.743 0-.829.434-1.238.587-1.408.153-.17.339-.213.453-.213.114 0 .228.001.328.006.105.006.246-.04.385.293.144.345.492 1.2.535 1.288.043.088.072.191.014.306-.058.114-.088.186-.174.286-.086.101-.18.225-.257.303-.086.086-.176.18-.076.352.1.171.444.733.953 1.186.656.584 1.209.764 1.38.85.171.085.285.128.328.2.043.072.043.419-.101.824z"/></svg>
                        <span>Bagikan ke WhatsApp</span>
                    </a>

                    <button 
                        @click="closeEventModal()" 
                        type="button" 
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-stone-200 dark:bg-emerald-950 hover:bg-stone-300 dark:hover:bg-emerald-900 text-stone-700 dark:text-stone-300 font-bold text-xs transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    </div>

</main>