@forelse ($data as $prog)
    @php
        $isOpen = ($prog->status === 'aktif');
        $tabCondition = $isOpen ? "danaTab === 'all' || danaTab === 'open'" : "danaTab === 'all' || danaTab === 'closed'";
        $coverImg = $prog->cover_image ? asset($prog->cover_image) : asset('images/dana-kuti.jpg');
        $collected = (int) $prog->collected_amount;
        $target = (int) $prog->target_amount;
        $percent = (float) $prog->progress_percentage;
        $donorsCount = (int) $prog->donors_count;
        $progSangha = $prog->sangha_members_list;
        $progPayload = [
            'id' => $prog->id,
            'title' => $prog->title,
            'category' => $prog->category,
            'categoryName' => $prog->category,
            'content' => $prog->content ?? '',
            'coverImg' => $coverImg,
            'collected' => $collected,
            'target' => $target,
            'percent' => $percent,
            'donorsCount' => $donorsCount,
            'daysLeft' => $prog->days_left_text,
            'status' => $isOpen ? 'open' : 'closed',
            'isOpen' => $isOpen,
            'pembina' => $progSangha->map(fn($m) => [
                'name' => $m->name,
                'title' => $m->title ?: 'Bhikkhu Pembina',
                'photo_url' => $m->photo_url,
            ])->values()->all(),
            'admins' => $prog->admins_list->map(fn($a) => [
                'role' => $a->role,
                'name' => $a->name,
                'phone' => $a->phone,
            ])->values()->all(),
        ];
    @endphp
    <article 
        x-show="({{ $tabCondition }}) && ({{ $loop->index }} < visibleProgramsCount)" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-98"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        class="bg-white/95 dark:bg-[#0b1c15] rounded-3xl border {{ $isOpen ? 'border-emerald-500/25 dark:border-emerald-500/20 hover:border-emerald-500/50 shadow-md' : 'border-stone-200/80 dark:border-emerald-500/15 shadow-xs opacity-90 hover:opacity-100' }} hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between group hover:-translate-y-1.5"
    >
        <div>
            <!-- Photo with Status & Category Badges (Clickable to open Detail Modal) -->
            <div 
                @click="openProgramDetailModal(@js($progPayload))"
                class="relative aspect-[16/10] overflow-hidden bg-stone-900 cursor-pointer"
                title="Klik untuk melihat detail program lengkap"
            >
                <img 
                    src="{{ $coverImg }}" 
                    alt="{{ $prog->title }}" 
                    class="w-full h-full object-cover {{ $isOpen ? 'group-hover:scale-105' : 'grayscale-[30%] group-hover:grayscale-0 group-hover:scale-105' }} transition-all duration-700 ease-out"
                    loading="lazy"
                    decoding="async"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/25 pointer-events-none"></div>
                
                <!-- Category Tag Top Left -->
                <div class="absolute top-3.5 left-3.5 z-10">
                    <span class="px-3 py-1 rounded-full bg-black/60 backdrop-blur-md text-amber-300 text-[10.5px] font-black uppercase tracking-wider border border-white/15 shadow-xs">
                        {{ $prog->category }}
                    </span>
                </div>

                <!-- Status Badge Top Right -->
                <div class="absolute top-3.5 right-3.5 z-10">
                    @if ($isOpen)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-600/95 backdrop-blur-md text-white text-[10px] font-extrabold shadow-sm uppercase tracking-wider border border-emerald-400/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-ping"></span>
                            <span>Dibuka</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-stone-800/90 backdrop-blur-md text-amber-300 text-[10px] font-extrabold shadow-sm uppercase tracking-wider border border-amber-500/30">
                            <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span>Ditutup</span>
                        </span>
                    @endif
                </div>

                <!-- Floating Bhikkhu Sangha Endorsement Pill Bottom Left -->
                @if($progSangha->isNotEmpty())
                    <div class="absolute bottom-3 left-3.5 z-10 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-black/65 backdrop-blur-md text-white border border-white/15 shadow-sm transition-transform hover:scale-105 duration-200">
                        <div class="flex -space-x-1.5 overflow-hidden">
                            @foreach($progSangha->take(3) as $member)
                                <img 
                                    src="{{ $member->photo_url }}" 
                                    alt="{{ $member->name }}" 
                                    title="{{ $member->name }} - {{ $member->title }}" 
                                    class="inline-block w-4.5 h-4.5 rounded-full object-cover ring-1 ring-amber-400 shadow-xs" 
                                />
                            @endforeach
                        </div>
                        <span class="text-[10px] font-bold text-amber-200 tracking-wide">
                            Mengetahui
                        </span>
                    </div>
                @endif
            </div>

            <!-- Card Body -->
            <div class="p-6 space-y-3">
                <h3 
                    @click="openProgramDetailModal(@js($progPayload))"
                    class="text-base sm:text-lg font-black text-stone-900 dark:text-stone-100 leading-snug group-hover:text-[#0D6E42] dark:group-hover:text-emerald-300 transition-colors line-clamp-2 cursor-pointer"
                    title="{{ $prog->title }}"
                >
                    {{ $prog->title }}
                </h3>
                <p class="text-stone-600 dark:text-stone-300 text-xs sm:text-sm leading-relaxed line-clamp-2">
                    {{ Str::limit(strip_tags($prog->content), 110) }}
                </p>

                <!-- Read More Text Link -->
                <button 
                    @click="openProgramDetailModal(@js($progPayload))"
                    type="button" 
                    class="inline-flex items-center gap-1 text-xs font-bold text-[#0D6E42] dark:text-emerald-400 hover:text-amber-600 dark:hover:text-amber-300 hover:underline transition-colors cursor-pointer"
                >
                    <span>Baca Rincian Lengkap</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>

                <!-- Progress Bar & Analytics -->
                <div class="space-y-2 pt-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-extrabold text-[#0D6E42] dark:text-emerald-300 text-sm sm:text-base">
                            Rp {{ number_format($collected, 0, ',', '.') }}
                        </span>
                        <span class="px-2 py-0.5 rounded-md {{ $isOpen ? 'bg-emerald-50 dark:bg-emerald-950/80 text-[#0D6E42] dark:text-emerald-300 border border-emerald-500/20' : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 border border-stone-200 dark:border-stone-700' }} font-black text-[10.5px]">
                            {{ $percent }}%
                        </span>
                    </div>

                    <!-- Progress Track -->
                    <div class="w-full bg-stone-200/80 dark:bg-emerald-950/70 h-2.5 rounded-full overflow-hidden p-0.5 border border-stone-300/40 dark:border-emerald-800/40">
                        <div class="bg-gradient-to-r from-emerald-500 via-emerald-400 to-amber-400 h-full rounded-full transition-all duration-700" style="width: {{ min($percent, 100) }}%"></div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-stone-500 dark:text-stone-400 pt-0.5">
                        <span>Target: <strong class="text-stone-700 dark:text-stone-200">Rp {{ number_format($target, 0, ',', '.') }}</strong></span>
                        <span class="font-semibold text-stone-500 dark:text-stone-400">{{ $prog->days_left_text }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compact Single-Row Action Footer -->
        <div class="p-6 pt-0">
            <div class="pt-3.5 border-t border-stone-200/70 dark:border-emerald-950/60 flex items-center gap-2">
                @if ($isOpen)
                    <button 
                        @click="openDanaModal('{{ addslashes($prog->title) }}', '{{ addslashes($prog->category) }}', @js($progPayload['pembina']), @js($progPayload['admins']))"
                        type="button" 
                        class="flex-1 inline-flex items-center justify-center gap-2 bg-[#0D5B3A] hover:bg-[#09472D] dark:bg-emerald-700 dark:hover:bg-emerald-600 text-white font-extrabold py-2.5 px-3.5 rounded-xl text-xs sm:text-sm shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-300 shrink-0"><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg>
                        <span>Konfirmasi Dāna</span>
                    </button>

                    <button 
                        @click="openItemDonorsModal({id: {{ $prog->id }}, title: '{{ addslashes($prog->title) }}', categoryName: '{{ addslashes($prog->category) }}', collected: {{ $collected }}, target: {{ $target }}, percent: {{ $percent }}, donorsCount: {{ $donorsCount }}, status: 'open'})"
                        type="button" 
                        class="px-3 py-2.5 rounded-xl bg-stone-100 dark:bg-emerald-950/70 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-[#0D5B3A] dark:text-emerald-300 border border-emerald-600/20 text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5 shrink-0 shadow-2xs"
                        title="Lihat Daftar Donatur ({{ $donorsCount }} Orang)"
                    >
                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>{{ $donorsCount }}</span>
                    </button>
                @else
                    <div class="flex-1 inline-flex items-center justify-center gap-1 text-[11px] font-bold text-stone-600 dark:text-stone-300 bg-stone-100 dark:bg-emerald-950/50 py-2 px-3 rounded-xl border border-stone-200 dark:border-emerald-800/30">
                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Target Terpenuhi</span>
                    </div>

                    <button 
                        @click="openItemDonorsModal({id: {{ $prog->id }}, title: '{{ addslashes($prog->title) }}', categoryName: '{{ addslashes($prog->category) }}', collected: {{ $collected }}, target: {{ $target }}, percent: {{ $percent }}, donorsCount: {{ $donorsCount }}, status: 'closed'})"
                        type="button" 
                        class="px-3 py-2 rounded-xl bg-stone-100 dark:bg-emerald-950/70 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-[#0D5B3A] dark:text-emerald-300 border border-emerald-600/20 text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5 shrink-0"
                        title="Lihat Daftar Donatur ({{ $donorsCount }} Orang)"
                    >
                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>{{ $donorsCount }}</span>
                    </button>
                @endif
            </div>
        </div>
    </article>
@empty
    <div class="col-span-full py-16 px-6 text-center space-y-4 rounded-3xl bg-white/60 dark:bg-[#0b1c15]/60 border border-stone-200/80 dark:border-emerald-500/15 backdrop-blur-md shadow-xs">
        <div class="w-16 h-16 rounded-full bg-emerald-500/10 dark:bg-emerald-400/10 text-[#0D6E42] dark:text-emerald-300 flex items-center justify-center mx-auto shadow-inner">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="space-y-1.5 max-w-md mx-auto">
            <h3 class="text-base sm:text-lg font-black text-stone-800 dark:text-stone-200">
                Belum Ada Program Donasi Aktif
            </h3>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 leading-relaxed">
                Saat ini belum ada program kebajikan terbuka. Silakan pantau saluran resmi kami atau hubungi sekretariat vihara untuk informasi dāna rutin.
            </p>
        </div>
    </div>
@endforelse