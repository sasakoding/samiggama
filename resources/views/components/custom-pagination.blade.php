@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav role="navigation" aria-label="Pagination Navigation" class="w-full flex flex-col md:flex-row items-center justify-between gap-4 py-1">
        
        <!-- Left: Summary Info Pill -->
        <div class="flex flex-wrap items-center gap-2.5 text-xs text-stone-600 dark:text-stone-300 w-full md:w-auto justify-between md:justify-start">
            <div class="inline-flex items-center gap-2">
                <span class="font-medium text-stone-600 dark:text-stone-300">
                    Menampilkan 
                    <span class="font-extrabold text-stone-900 dark:text-stone-100">{{ $paginator->firstItem() ?? 0 }}</span>
                    -
                    <span class="font-extrabold text-stone-900 dark:text-stone-100">{{ $paginator->lastItem() ?? 0 }}</span>
                    dari 
                    <span class="font-black text-[#0D6E42] dark:text-emerald-400">{{ $paginator->total() }}</span> 
                    data
                </span>
            </div>
        </div>

        <!-- Right: Pagination Buttons Suite -->
        @if ($paginator->hasPages())
            <div class="flex items-center gap-1 sm:gap-1.5 w-full md:w-auto justify-center">
                
                <!-- 2. Prev Button (< Prev) -->
                @if ($paginator->onFirstPage())
                    <span class="px-2.5 sm:px-3 h-8 sm:h-9 rounded-xl flex items-center gap-1 text-stone-300 dark:text-stone-600 text-xs font-bold cursor-not-allowed opacity-50 select-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        <span class="hidden sm:inline">Prev</span>
                    </span>
                @else
                    <button 
                        wire:click="previousPage('{{ $paginator->getPageName() }}')" 
                        wire:loading.attr="disabled"
                        type="button" 
                        class="px-2.5 sm:px-3 h-8 sm:h-9 rounded-xl bg-stone-50 dark:bg-[#0b1f17] hover:bg-emerald-50 dark:hover:bg-emerald-950/80 text-stone-700 dark:text-stone-200 hover:text-[#0D6E42] dark:hover:text-emerald-300 border border-stone-200/80 dark:border-emerald-500/20 shadow-2xs flex items-center gap-1 text-xs font-bold transition-all cursor-pointer hover:scale-105 active:scale-95"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        <span class="hidden sm:inline">Prev</span>
                    </button>
                @endif

                <!-- 3. Page Numbers -->
                <div class="flex items-center gap-1 px-1">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="w-6 sm:w-7 h-8 sm:h-9 flex items-center justify-center text-stone-400 dark:text-stone-500 font-extrabold tracking-widest text-xs select-none">
                                ···
                            </span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <button 
                                        type="button" 
                                        aria-current="page" 
                                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gradient-to-r from-[#0D5B3A] to-[#127249] text-amber-200 font-black text-xs sm:text-sm shadow-md shadow-emerald-950/30 border border-amber-400/40 flex items-center justify-center cursor-default transform scale-105 select-none"
                                    >
                                        {{ $page }}
                                    </button>
                                @else
                                    <button 
                                        wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" 
                                        wire:loading.attr="disabled"
                                        type="button" 
                                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-stone-50 dark:bg-[#0b1f17] hover:bg-emerald-50 dark:hover:bg-emerald-950/80 text-stone-700 dark:text-stone-200 hover:text-[#0D6E42] dark:hover:text-emerald-300 border border-stone-200/80 dark:border-emerald-500/20 shadow-2xs font-bold text-xs sm:text-sm flex items-center justify-center transition-all cursor-pointer hover:scale-105 active:scale-95"
                                    >
                                        {{ $page }}
                                    </button>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>

                <!-- 4. Next Button (Next >) -->
                @if ($paginator->hasMorePages())
                    <button 
                        wire:click="nextPage('{{ $paginator->getPageName() }}')" 
                        wire:loading.attr="disabled"
                        type="button" 
                        class="px-2.5 sm:px-3 h-8 sm:h-9 rounded-xl bg-stone-50 dark:bg-[#0b1f17] hover:bg-emerald-50 dark:hover:bg-emerald-950/80 text-stone-700 dark:text-stone-200 hover:text-[#0D6E42] dark:hover:text-emerald-300 border border-stone-200/80 dark:border-emerald-500/20 shadow-2xs flex items-center gap-1 text-xs font-bold transition-all cursor-pointer hover:scale-105 active:scale-95"
                    >
                        <span class="hidden sm:inline">Next</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @else
                    <span class="px-2.5 sm:px-3 h-8 sm:h-9 rounded-xl flex items-center gap-1 text-stone-300 dark:text-stone-600 text-xs font-bold cursor-not-allowed opacity-50 select-none">
                        <span class="hidden sm:inline">Next</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif

            </div>
        @endif

    </nav>
@endif
