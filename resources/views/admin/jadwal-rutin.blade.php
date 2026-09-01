<?php

use App\Models\RoutineSchedule;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $searchQuery = '';
    public string $monthFilter = 'all'; // 'all', 'YYYY-MM'
    public bool $modalOpen = false;
    public ?int $editingId = null;
    public ?string $feedbackMessage = null;

    // Form Inputs
    public string $formDate = '';
    public string $formActivityName = 'Puja Bakti Umum Minggu Pagi';
    public string $formLeader1 = '';
    public string $formLeader2 = '';
    public string $formSpeaker = '';
    public string $formTopic = '';
    public string $formTimeRange = '';
    public string $formStatus = 'aktif';

    // Header Settings State
    public bool $headerModalOpen = false;
    public string $headerBadge = '';
    public string $headerTitle = '';
    public string $headerSubtitle = '';

    public function updatedMonthFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    public function openHeaderModal(): void
    {
        $this->headerBadge = \App\Models\Setting::get('routine_schedule_badge', 'Jadwal Petugas Kebaktian Rutin');
        $this->headerTitle = \App\Models\Setting::get('routine_schedule_title', 'Penugasan Petugas & Topik Dhammadesana');
        $this->headerSubtitle = \App\Models\Setting::get('routine_schedule_subtitle', 'Daftar jadwal penugasan Puja Bakti mingguan, penceramah Dhamma, dan pemimpin kebaktian di Vihara Sāmaggi Gāma.');
        $this->resetValidation();
        $this->headerModalOpen = true;
    }

    public function closeHeaderModal(): void
    {
        $this->headerModalOpen = false;
        $this->resetValidation();
    }

    public function saveHeaderSettings(): void
    {
        $this->validate([
            'headerBadge' => 'required|string|max:255',
            'headerTitle' => 'required|string|max:255',
            'headerSubtitle' => 'nullable|string|max:500',
        ], [
            'headerBadge.required' => 'Label badge atas wajib diisi.',
            'headerTitle.required' => 'Judul utama section wajib diisi.',
        ]);

        \App\Models\Setting::set('routine_schedule_badge', trim($this->headerBadge));
        \App\Models\Setting::set('routine_schedule_title', trim($this->headerTitle));
        \App\Models\Setting::set('routine_schedule_subtitle', trim($this->headerSubtitle));

        $this->feedbackMessage = 'Pengaturan judul dan deskripsi section jadwal berhasil disimpan.';
        $this->headerModalOpen = false;
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'editingId',
            'formDate',
            'formLeader1',
            'formLeader2',
            'formSpeaker',
            'formTopic',
        ]);
        $this->formDate = date('Y-m-d');
        $this->formActivityName = 'Puja Bakti Umum Minggu Pagi';
        $this->formTimeRange = '';
        $this->formStatus = 'aktif';
        $this->modalOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $sch = RoutineSchedule::find($id);
        if (!$sch) {
            return;
        }

        $this->editingId = $sch->id;
        $this->formDate = $sch->event_date ? $sch->event_date->format('Y-m-d') : '';
        $this->formActivityName = $sch->activity_name;
        $this->formLeader1 = $sch->leader_1;
        $this->formLeader2 = $sch->leader_2 ?? '';
        $this->formSpeaker = $sch->speaker;
        $this->formTopic = $sch->topic;
        $this->formTimeRange = $sch->time_range ?? '';
        $this->formStatus = $sch->status;

        $this->modalOpen = true;
    }

    public function saveSchedule(): void
    {
        $this->validate([
            'formDate' => 'required|date',
            'formActivityName' => 'required|string|max:255',
            'formLeader1' => 'required|string|max:255',
            'formLeader2' => 'nullable|string|max:255',
            'formSpeaker' => 'required|string|max:255',
            'formTopic' => 'required|string|max:255',
            'formTimeRange' => 'nullable|string|max:100',
            'formStatus' => 'required|in:aktif,selesai,dibatalkan',
        ], [
            'formDate.required' => 'Tanggal jadwal kebaktian wajib diisi.',
            'formActivityName.required' => 'Nama kegiatan wajib diisi.',
            'formLeader1.required' => 'Pemimpin Puja Bakti 1 wajib diisi.',
            'formSpeaker.required' => 'Nama penceramah / pembicara wajib diisi.',
            'formTopic.required' => 'Topik ceramah Dhamma wajib diisi.',
        ]);

        if ($this->editingId) {
            $sch = RoutineSchedule::findOrFail($this->editingId);
            $sch->update([
                'event_date' => $this->formDate,
                'activity_name' => $this->formActivityName,
                'leader_1' => $this->formLeader1,
                'leader_2' => $this->formLeader2 ?: null,
                'speaker' => $this->formSpeaker,
                'topic' => $this->formTopic,
                'time_range' => $this->formTimeRange,
                'status' => $this->formStatus,
            ]);

            $this->feedbackMessage = "Jadwal petugas kegiatan '{$this->formActivityName}' tanggal " . Carbon::parse($this->formDate)->translatedFormat('d M Y') . " berhasil diperbarui.";
        } else {
            RoutineSchedule::create([
                'event_date' => $this->formDate,
                'activity_name' => $this->formActivityName,
                'leader_1' => $this->formLeader1,
                'leader_2' => $this->formLeader2 ?: null,
                'speaker' => $this->formSpeaker,
                'topic' => $this->formTopic,
                'time_range' => $this->formTimeRange,
                'status' => $this->formStatus,
            ]);

            $this->feedbackMessage = "Jadwal petugas kegiatan '{$this->formActivityName}' tanggal " . Carbon::parse($this->formDate)->translatedFormat('d M Y') . " berhasil ditambahkan.";
        }

        $this->modalOpen = false;
    }

    public function toggleStatus(int $id): void
    {
        $sch = RoutineSchedule::find($id);
        if ($sch) {
            $sch->status = ($sch->status === 'aktif') ? 'selesai' : 'aktif';
            $sch->save();
            $this->feedbackMessage = "Status jadwal '{$sch->activity_name}' diubah menjadi {$sch->status}.";
        }
    }

    public function deleteSchedule(int $id): void
    {
        $sch = RoutineSchedule::find($id);
        if ($sch) {
            $name = $sch->activity_name;
            $date = $sch->formatted_date;
            $sch->delete();
            $this->feedbackMessage = "Jadwal petugas '{$name}' ({$date}) berhasil dihapus.";
        }
    }

    public function render()
    {
        $query = RoutineSchedule::orderBy('event_date', 'desc');

        if ($this->monthFilter !== 'all') {
            $parts = explode('-', $this->monthFilter);
            if (count($parts) === 2) {
                $query->whereYear('event_date', $parts[0])
                      ->whereMonth('event_date', $parts[1]);
            }
        }

        if (!empty($this->searchQuery)) {
            $search = '%' . strtolower($this->searchQuery) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(activity_name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(leader_1) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(leader_2) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(speaker) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(topic) LIKE ?', [$search]);
            });
        }

        $schedules = $query->paginate(15);

        $totalSchedules = RoutineSchedule::count();
        $upcomingCount = RoutineSchedule::where('event_date', '>=', now()->toDateString())->where('status', 'aktif')->count();
        $thisMonthCount = RoutineSchedule::whereMonth('event_date', now()->month)->whereYear('event_date', now()->year)->count();

        $dbMonths = RoutineSchedule::whereNotNull('event_date')
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
            $d = Carbon::create(now()->year, $m, 1);
            $yearMonths->push([
                'value' => $d->format('Y-m'),
                'label' => $d->translatedFormat('F Y'),
            ]);
        }

        $availableMonths = $dbMonths->concat($yearMonths)->unique('value')->sortBy('value')->values();

        return $this->view([
            'schedules' => $schedules,
            'totalSchedules' => $totalSchedules,
            'upcomingCount' => $upcomingCount,
            'thisMonthCount' => $thisMonthCount,
            'availableMonths' => $availableMonths,
        ])->title('Jadwal Petugas Kebaktian Rutin')->layout('layouts::admin');
    }
};
?>

<div class="space-y-6 font-sans">

    <!-- =========================================================================
         1. TOP HEADER & CONTROLS
         ========================================================================= -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    Jadwal Petugas Kebaktian Rutin
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Kelola jadwal penugasan Puja Bakti mingguan, pemimpin kebaktian, penceramah Dhamma, dan topik ceramah.
            </p>
        </div>

        <!-- Action CTA -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button 
                wire:click="openHeaderModal" 
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-white dark:bg-[#0b1f17] hover:bg-stone-100 dark:hover:bg-emerald-950 text-stone-800 dark:text-stone-200 border border-stone-300 dark:border-emerald-500/25 font-bold text-xs shadow-2xs transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Atur Judul Section</span>
            </button>

            <button 
                wire:click="openCreateModal" 
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-xs border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Jadwal</span>
            </button>
        </div>
    </div>

    <!-- Feedback Banner -->
    @if ($feedbackMessage)
        <div 
            x-data="{ show: true }" 
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-500/30 text-emerald-900 dark:text-emerald-200 text-xs font-semibold flex items-center justify-between shadow-xs"
        >
            <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button @click="show = false" type="button" class="text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 cursor-pointer p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <!-- =========================================================================
         2. STATS & METRICS
         ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-[#0D6E42] dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <div class="text-[11px] font-bold text-stone-500 uppercase tracking-wider">Jadwal Bulan Ini</div>
                <div class="text-lg font-black text-stone-900 dark:text-stone-100">{{ $thisMonthCount }} Kegiatan</div>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-amber-500/15 text-amber-700 dark:text-amber-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="text-[11px] font-bold text-stone-500 uppercase tracking-wider">Jadwal Mendatang</div>
                <div class="text-lg font-black text-stone-900 dark:text-stone-100">{{ $upcomingCount }} Kegiatan</div>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-sky-500/15 text-sky-700 dark:text-sky-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
            <div>
                <div class="text-[11px] font-bold text-stone-500 uppercase tracking-wider">Total Penugasan</div>
                <div class="text-lg font-black text-stone-900 dark:text-stone-100">{{ $totalSchedules }} Jadwal</div>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         3. TABEL DATA JADWAL PETUGAS RUTIN
         ========================================================================= -->
    <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden">
        
        <!-- Table Toolbar -->
        <div class="p-4 sm:p-5 border-b border-stone-200/80 dark:border-emerald-950 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            
            <!-- Month Filter -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-stone-500 dark:text-stone-400">Periode:</span>
                <select 
                    wire:model.live="monthFilter" 
                    class="px-3 py-1.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-800 dark:text-stone-200 font-bold focus:outline-none focus:border-amber-500"
                >
                    <option value="all">Semua Periode</option>
                    @foreach ($availableMonths as $m)
                        <option value="{{ $m['value'] }}">{{ $m['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Search Input -->
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    wire:model.live.debounce.250ms="searchQuery" 
                    type="text" 
                    placeholder="Cari kegiatan, penceramah, pemimpin..." 
                    class="w-full pl-9 pr-3.5 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-[#0D5B3A] focus:ring-1 focus:ring-[#0D5B3A] transition-all shadow-2xs"
                />
            </div>
        </div>

        <!-- Data Table (No | Tanggal | Kegiatan | Pemimpin 1 | Pemimpin 2 | Penceramah | Topik) -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-stone-50/80 dark:bg-[#071710] border-b border-stone-200/80 dark:border-emerald-950 text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3.5 px-4 text-center w-12">No</th>
                        <th scope="col" class="py-3.5 px-4 min-w-[140px]">Tanggal & Hari</th>
                        <th scope="col" class="py-3.5 px-4 min-w-[180px]">Kegiatan</th>
                        <th scope="col" class="py-3.5 px-4 min-w-[160px]">Pemimpin 1</th>
                        <th scope="col" class="py-3.5 px-4 min-w-[160px]">Pemimpin 2</th>
                        <th scope="col" class="py-3.5 px-4 min-w-[180px]">Penceramah</th>
                        <th scope="col" class="py-3.5 px-4 min-w-[200px]">Topik Dhamma</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Status</th>
                        <th scope="col" class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-emerald-950/60">
                    @forelse ($schedules as $index => $sch)
                        @php
                            $rowNumber = ($schedules->currentPage() - 1) * $schedules->perPage() + $index + 1;
                            $isPast = $sch->event_date < now()->toDateString();
                        @endphp
                        <tr class="hover:bg-stone-50/60 dark:hover:bg-emerald-950/20 transition-colors {{ $isPast ? 'opacity-70' : '' }}">
                            
                            <!-- No -->
                            <td class="py-3.5 px-4 text-center font-bold text-stone-400">
                                {{ $rowNumber }}
                            </td>

                            <!-- Tanggal & Hari -->
                            <td class="py-3.5 px-4">
                                <div class="font-extrabold text-stone-900 dark:text-stone-100">
                                    {{ $sch->event_date ? $sch->event_date->translatedFormat('d M Y') : '-' }}
                                </div>
                                <div class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold">
                                    {{ $sch->day_name }}
                                </div>
                                <div class="text-[10px] text-stone-400">
                                    {{ $sch->time_range }}
                                </div>
                            </td>

                            <!-- Nama Kegiatan -->
                            <td class="py-3.5 px-4">
                                <span class="font-extrabold text-stone-900 dark:text-stone-100 block">
                                    {{ $sch->activity_name }}
                                </span>
                            </td>

                            <!-- Pemimpin 1 -->
                            <td class="py-3.5 px-4 font-semibold text-stone-800 dark:text-stone-200">
                                {{ $sch->leader_1 }}
                            </td>

                            <!-- Pemimpin 2 -->
                            <td class="py-3.5 px-4 font-semibold text-stone-600 dark:text-stone-300">
                                {{ $sch->leader_2 ?: '-' }}
                            </td>

                            <!-- Penceramah -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-[#0D6E42] dark:text-emerald-300">
                                    {{ $sch->speaker }}
                                </div>
                            </td>

                            <!-- Topik Dhamma -->
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-stone-800 dark:text-stone-200 italic">
                                    "{{ $sch->topic }}"
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if ($sch->status === 'aktif')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border border-emerald-500/30">
                                        Aktif
                                    </span>
                                @elseif ($sch->status === 'selesai')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-stone-500/15 text-stone-600 dark:text-stone-400 border border-stone-500/30">
                                        Selesai
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-700 border border-rose-500/30">
                                        Dibatalkan
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    
                                    <!-- Edit -->
                                    <button 
                                        wire:click="openEditModal({{ $sch->id }})" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 text-amber-700 dark:text-amber-300 border border-amber-500/20 cursor-pointer shadow-2xs"
                                        title="Edit Jadwal Petugas"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <!-- Toggle Status -->
                                    <button 
                                        wire:click="toggleStatus({{ $sch->id }})" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center border shadow-2xs cursor-pointer {{ $sch->status === 'aktif' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' : 'bg-stone-100 text-stone-500' }}"
                                        title="Ubah Status"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>

                                    <!-- Hapus -->
                                    <button 
                                        wire:click="deleteSchedule({{ $sch->id }})" 
                                        wire:confirm="Apakah Anda yakin ingin menghapus jadwal ini?" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 text-rose-600 dark:text-rose-400 border border-rose-500/20 cursor-pointer shadow-2xs"
                                        title="Hapus Jadwal"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-stone-400">
                                Belum ada jadwal petugas kebaktian yang tercatat. Klik "+ Tambah Jadwal Petugas" untuk mulai menambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer Pagination -->
        <div class="px-5 py-4 bg-stone-50/80 dark:bg-[#071710] border-t border-stone-200/80 dark:border-emerald-950/70">
            {{ $schedules->onEachSide(1)->links('components.custom-pagination') }}
        </div>

    </div>

    <!-- =========================================================================
         4. MODAL TAMBAH / EDIT JADWAL PETUGAS (TELEPORTED)
         ========================================================================= -->
    <template x-teleport="body">
        <div 
            x-show="$wire.modalOpen" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/80 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            @keydown.escape.window="$wire.modalOpen = false"
        >
            <div 
                @click.away="$wire.modalOpen = false" 
                class="relative max-w-xl w-full bg-white dark:bg-[#0b1f17] rounded-3xl overflow-hidden border border-stone-300/80 dark:border-emerald-500/30 shadow-2xl flex flex-col my-auto max-h-[92vh]"
            >
                <!-- Header -->
                <div class="p-6 bg-gradient-to-r from-[#0B2117] to-[#0F3325] text-white flex items-center justify-between border-b border-emerald-900">
                    <div class="space-y-0.5">
                        <h3 class="text-lg font-extrabold text-[#FAF5ED]">
                            {{ $editingId ? 'Edit Jadwal Petugas Kebaktian' : 'Tambah Jadwal Petugas Kebaktian' }}
                        </h3>
                        <p class="text-xs text-stone-300">
                            Isi rincian tanggal, kegiatan, pemimpin puja bakti, penceramah, dan topik ceramah.
                        </p>
                    </div>
                    <button 
                        @click="$wire.modalOpen = false" 
                        type="button" 
                        class="p-2 rounded-full text-stone-400 hover:text-white bg-black/30 hover:bg-black/50 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form -->
                <form wire:submit.prevent="saveSchedule" class="p-6 space-y-4 overflow-y-auto text-xs">
                    
                    <!-- Tanggal & Jam/Waktu -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Tanggal Kebaktian <span class="text-amber-500">*</span>
                            </label>
                            <input 
                                wire:model="formDate" 
                                type="date" 
                                required 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Jam Pelaksanaan
                            </label>
                            <input 
                                wire:model="formTimeRange" 
                                type="text" 
                                placeholder="cth: 08:30 - 10:30"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>
                    </div>

                    <!-- Nama Kegiatan -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Nama Kegiatan <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="formActivityName" 
                            type="text" 
                            required 
                            list="activity-suggestions"
                            placeholder="cth: Puja Bakti Umum Minggu Pagi"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        />
                        <datalist id="activity-suggestions">
                            <option value="Puja Bakti Umum Minggu Pagi">
                            <option value="Puja Bakti Umum Minggu Sore">
                            <option value="Sekolah Minggu Buddhis (SMB) Sāmaggi Bāla">
                            <option value="Puja Bakti Remaja & Pemuda (Sāmaggi Youth)">
                            <option value="Puja Bakti Hari Uposatha (Purnama / Tilem)">
                            <option value="Kelas Meditasi Vipassanā">
                        </datalist>
                    </div>

                    <!-- Pemimpin 1 & Pemimpin 2 -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Pemimpin 1 <span class="text-amber-500">*</span>
                            </label>
                            <input 
                                wire:model="formLeader1" 
                                type="text" 
                                required 
                                placeholder="cth: Upasaka Tanoto"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Pemimpin 2 <span class="text-stone-400">(Opsional)</span>
                            </label>
                            <input 
                                wire:model="formLeader2" 
                                type="text" 
                                placeholder="cth: Upasika Lenny"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>
                    </div>

                    <!-- Penceramah / Pembicara -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Penceramah / Dhammadesaka <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="formSpeaker" 
                            type="text" 
                            required 
                            placeholder="cth: Bhikkhu Subhamitto Mahāthera / Pandita Dr. S. Widyadharma"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>

                    <!-- Topik Ceramah Dhamma -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Topik Ceramah Dhamma <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="formTopic" 
                            type="text" 
                            required 
                            placeholder="cth: Mengikis Dosa Melalui Kesabaran (Khanti)"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>

                    <!-- Status -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Status Jadwal
                        </label>
                        <select 
                            wire:model="formStatus" 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        >
                            <option value="aktif">Aktif (Akan Datang)</option>
                            <option value="selesai">Selesai Terlaksana</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="pt-4 border-t border-stone-200 dark:border-emerald-950 flex items-center justify-end gap-2.5">
                        <button 
                            @click="$wire.modalOpen = false" 
                            type="button" 
                            class="py-2.5 px-4 rounded-xl bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-700 dark:text-stone-300 font-bold text-xs transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="py-2.5 px-5 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-md border border-amber-400/30 transition-all cursor-pointer"
                        >
                            {{ $editingId ? 'Simpan Perubahan' : 'Simpan Jadwal Petugas' }}
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </template>

    <!-- =========================================================================
         5. MODAL PENGATURAN HEADER SECTION TABEL JADWAL (TELEPORTED)
         ========================================================================= -->
    <template x-teleport="body">
        <div 
            x-show="$wire.headerModalOpen" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/80 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            @keydown.escape.window="$wire.closeHeaderModal"
        >
            <div 
                @click.away="$wire.closeHeaderModal" 
                class="relative max-w-lg w-full bg-white dark:bg-[#0b1f17] rounded-3xl overflow-hidden border border-stone-300/80 dark:border-emerald-500/30 shadow-2xl flex flex-col my-auto max-h-[92vh]"
            >
                <!-- Modal Header -->
                <div class="p-6 bg-gradient-to-r from-[#0B2117] to-[#0F3325] text-white flex items-center justify-between border-b border-emerald-900">
                    <div class="space-y-0.5">
                        <h3 class="text-base sm:text-lg font-extrabold text-[#FAF5ED] flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Atur Judul & Teks Header Tabel</span>
                        </h3>
                        <p class="text-xs text-stone-300">
                            Sesuaikan label badge, judul utama, dan deskripsi section jadwal di halaman publik.
                        </p>
                    </div>
                    <button 
                        @click="$wire.closeHeaderModal" 
                        type="button" 
                        class="p-2 rounded-full text-stone-400 hover:text-white bg-black/30 hover:bg-black/50 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form Body -->
                <form wire:submit.prevent="saveHeaderSettings" class="p-6 space-y-4 overflow-y-auto text-xs custom-scrollbar">
                    
                    <!-- 1. Label Badge Atas -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Label Badge / Kategori Atas <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="headerBadge" 
                            type="text" 
                            required 
                            placeholder="cth: Jadwal Petugas Kebaktian Rutin" 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-emerald-500 font-semibold"
                        />
                        @error('headerBadge') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                        <span class="text-[10.5px] text-stone-400">Teks kecil di atas judul utama (eyebrow badge).</span>
                    </div>

                    <!-- 2. Judul Utama Section -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Judul Utama Section <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="headerTitle" 
                            type="text" 
                            required 
                            placeholder="cth: Penugasan Petugas & Topik Dhammadesana" 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-emerald-500 font-bold"
                        />
                        @error('headerTitle') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                        <span class="text-[10.5px] text-stone-400">Judul besar section tabel jadwal di halaman publik.</span>
                    </div>

                    <!-- 3. Deskripsi / Subjudul -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Deskripsi / Keterangan Singkat
                        </label>
                        <textarea 
                            wire:model="headerSubtitle" 
                            rows="3" 
                            placeholder="cth: Daftar jadwal penugasan Puja Bakti mingguan, penceramah Dhamma, dan pemimpin kebaktian di Vihara Sāmaggi Gāma." 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-emerald-500 resize-none leading-relaxed"
                        ></textarea>
                        @error('headerSubtitle') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="pt-4 border-t border-stone-200 dark:border-emerald-950 flex items-center justify-end gap-2.5">
                        <button 
                            @click="$wire.closeHeaderModal" 
                            type="button" 
                            class="py-2.5 px-4 rounded-xl bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-700 dark:text-stone-300 font-bold text-xs transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="py-2.5 px-5 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-md border border-amber-400/30 transition-all cursor-pointer flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Simpan Pengaturan Header</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </template>

</div>

