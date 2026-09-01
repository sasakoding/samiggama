<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new 
#[Layout('layouts.auth')]
#[Title('Panel Administrasi & Pengurus — SĀMAGGI GĀMA')]
class extends Component
{
    public string $loginIdentifier = '';
    public string $password = '';
    public bool $remember = false;
    public bool $showPassword = false;
    public bool $isLoading = false;
    public ?string $feedbackMessage = null;
    public string $feedbackType = 'info'; // 'success', 'error', 'info'

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirect(route('admin.dashboard'), navigate: true);
            return;
        }

        if (session()->has('feedbackMessage')) {
            $this->feedbackMessage = session('feedbackMessage');
            $this->feedbackType = 'info';
        }
    }

    public function fillAdminDemo(string $level): void
    {
        if ($level === 'superadmin') {
            $this->loginIdentifier = 'admin@gmail.com';
            $this->password = '12345678';
            $this->feedbackMessage = 'Kredensial Super Administrator terpasang. Klik tombol "Masuk ke Panel Administrasi" untuk melanjutkan.';
            $this->feedbackType = 'info';
        } else {
            $this->loginIdentifier = 'bendahara@gmail.com';
            $this->password = '12345678';
            $this->feedbackMessage = 'Kredensial Bendahara Yayasan terpasang. Klik tombol "Masuk ke Panel Administrasi" untuk melanjutkan.';
            $this->feedbackType = 'info';
        }
    }

    public function handleLogin(): void
    {
        $this->isLoading = true;
        $this->feedbackMessage = null;

        // 1. Validasi Input Form
        $this->validate([
            'loginIdentifier' => 'required|string',
            'password' => 'required|string',
        ], [
            'loginIdentifier.required' => 'Email atau username administrator wajib diisi.',
            'password.required' => 'Kata sandi master akun Anda wajib diisi.',
        ]);

        // 2. Proteksi Brute-Force & Rate Limiting Keamanan Tinggi (Maks. 5 percobaan / 60 detik)
        $throttleKey = Str::transliterate(Str::lower($this->loginIdentifier).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->feedbackMessage = "Terlalu banyak percobaan login gagal. Akses ditangguhkan sementara demi keamanan sistem. Silakan coba kembali dalam {$seconds} detik.";
            $this->feedbackType = 'error';
            $this->isLoading = false;
            return;
        }

        // 3. Autentikasi Fleksibel (Email atau Username)
        $isEmail = filter_var($this->loginIdentifier, FILTER_VALIDATE_EMAIL);
        $credentials = $isEmail
            ? ['email' => $this->loginIdentifier, 'password' => $this->password]
            : ['name' => $this->loginIdentifier, 'password' => $this->password];

        if (Auth::attempt($credentials, $this->remember)) {
            $user = Auth::user();

            // 4. Verifikasi Status Akun Aktif
            if ($user->status !== 'aktif') {
                Auth::logout();
                RateLimiter::hit($throttleKey, 60);
                $this->feedbackMessage = 'Akun Administrator Anda berstatus non-aktif atau dinonaktifkan. Silakan hubungi Super Administrator.';
                $this->feedbackType = 'error';
                $this->isLoading = false;
                return;
            }

            // 5. Proteksi Session Fixation & Logging Audit Keamanan
            if (request()->hasSession()) {
                request()->session()->regenerate();
            }
            RateLimiter::clear($throttleKey);

            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
            ]);

            $this->feedbackMessage = "Autentikasi Berhasil! Selamat datang kembali, {$user->name}. Mengalihkan...";
            $this->feedbackType = 'success';
            $this->isLoading = false;

            $this->redirectIntended(route('admin.dashboard'), navigate: true);
            return;
        }

        // 6. Gagal Autentikasi: Catat Hit Rate Limiter
        RateLimiter::hit($throttleKey, 60);
        $attemptsLeft = RateLimiter::remaining($throttleKey, 5);

        $this->feedbackMessage = "Kombinasi email/username atau kata sandi tidak cocok. Sisa kesempatan percobaan: {$attemptsLeft} kali.";
        $this->feedbackType = 'error';
        $this->isLoading = false;
    }

    public function render()
    {
        return $this->view();
    }
};
?>

<div 
    x-data="{ 
        showPass: @entangle('showPassword'),
        loading: @entangle('isLoading')
    }" 
    class="min-h-screen w-full flex flex-col lg:flex-row bg-[#F0E9DF] dark:bg-[#07130E] selection:bg-emerald-200 selection:text-emerald-950 font-sans"
>

    <!-- =========================================================================
         KOLOM KIRI: DEKORASI SANCTUARY, BRANDING & SACRED BUDDHIST AMBIENCE
         ========================================================================= -->
    <aside 
        class="relative hidden lg:flex lg:w-[48%] xl:w-[50%] flex-col justify-between p-10 xl:p-14 2xl:p-16 overflow-hidden bg-[#05130D] text-white shrink-0 border-r border-amber-500/20"
        aria-label="Dekorasi Visual Panel Administrasi Vihara Sāmaggi Gāma"
    >
        <!-- Background Cinematic Sanctuary Photography -->
        <img 
            src="{{ asset('images/login-sanctuary.jpg') }}" 
            alt="Suasana Senja Sakral Vihara Samaggi Gama" 
            class="absolute inset-0 w-full h-full object-cover object-center scale-105 filter brightness-90 contrast-105 transition-transform duration-1000 ease-out"
        />

        <!-- Multi-Layered Royal Dark Gradients -->
        <div class="absolute inset-0 bg-gradient-to-t from-[#04100B] via-[#071E14]/85 via-45% to-[#04100B]/60 mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#04100B]/90 via-[#071E14]/60 to-transparent"></div>
        <div class="absolute inset-0 bg-radial from-amber-500/15 via-transparent to-transparent pointer-events-none"></div>

        <!-- Ambient Golden & Emerald Lighting Orbs -->
        <div class="absolute top-10 left-10 w-80 h-80 bg-amber-500/15 rounded-full blur-3xl pointer-events-none animate-pulse-glow"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Subtle Geometric Contour Lines (SVG Overlay) -->
        <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-20 stroke-amber-400/40 fill-none" viewBox="0 0 800 1000" preserveAspectRatio="none" aria-hidden="true">
            <circle cx="400" cy="500" r="280" stroke-width="1" stroke-dasharray="4 8" />
            <circle cx="400" cy="500" r="380" stroke-width="0.8" stroke-dasharray="2 12" />
            <path d="M0,300 C200,220 600,380 800,300" stroke-width="1" />
            <path d="M0,700 C200,620 600,780 800,700" stroke-width="1" />
        </svg>

        <!-- TOP BAR: OFFICIAL EMBLEM & RESTRICTED ACCESS BADGE -->
        <div class="relative z-10 flex items-center justify-between">
            <a href="{{ route('home') }}" class="group flex items-center gap-3.5 transition-all">
                <div class="p-2 rounded-2xl bg-gradient-to-br from-amber-500/20 via-emerald-600/30 to-amber-600/20 border border-amber-500/40 shadow-md group-hover:scale-105 group-hover:border-amber-400 transition-all duration-300">
                    <img src="{{ asset(\App\Models\Setting::get('foundation_logo', 'images/logo/android-chrome-192x192.png')) }}" alt="Logo Vihara Samaggi Gama" class="w-8 h-8 object-contain drop-shadow-sm">
                </div>
                <div class="flex flex-col">
                    <span class="font-serif font-black tracking-tight text-lg text-[#FAF5ED] group-hover:text-amber-300 transition-colors">
                        SĀMAGGI GĀMA
                    </span>
                    <span class="text-[10px] font-bold tracking-[0.2em] text-amber-300/90 uppercase">
                        Sistem Administrasi Yayasan
                    </span>
                </div>
            </a>

            <!-- System Live Status Pill -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-black/40 backdrop-blur-md border border-amber-500/30 text-xs font-semibold text-amber-300 shadow-inner">
                <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span class="text-[11px] font-bold tracking-wide">Area Terbatas (Admin)</span>
            </div>
        </div>

        <!-- MIDDLE: HERO HEADLINE & SACRED BUDDHIST WISDOM -->
        <div class="relative z-10 space-y-6 my-auto max-w-xl">
            
            <!-- Category Tag -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-gradient-to-r from-amber-500/20 to-emerald-500/20 border border-amber-500/40 text-amber-300 text-xs font-black uppercase tracking-wider">
                <svg class="w-3.5 h-3.5 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 7.4h7.6l-6.2 4.5 2.4 7.4-6.2-4.5-6.2 4.5 2.4-7.4-6.2-4.5h7.6z"/></svg>
                <span>Sistem Informasi Terpadu (SAMADHI)</span>
            </div>

            <!-- Main Heading -->
            <div class="space-y-3">
                <h1 class="text-3xl xl:text-4xl 2xl:text-5xl font-serif font-black tracking-tight text-[#FAF5ED] leading-tight drop-shadow-sm">
                    Pusat Kendali Administrasi, Dāna & Dhamma.
                </h1>
                <p class="text-stone-300 text-sm xl:text-base leading-relaxed font-normal">
                    Portal terenkripsi khusus Dewan Pengurus, Bhikkhu Sangha, dan Sekretariat Resmi Yayasan Vihara Sāmaggi Gāma untuk mengelola program keumatan dan transparansi donasi.
                </p>
            </div>

            <!-- Sacred Pali Wisdom Glassmorphic Card -->
            <div class="p-5 xl:p-6 rounded-3xl bg-black/45 backdrop-blur-xl border border-amber-500/35 shadow-2xl space-y-3 transform transition-all hover:border-amber-400/60 group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-amber-400 text-xs font-bold uppercase tracking-wider">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1010 10A10 10 0 0012 2zm0 18a8 8 0 118-8 8 8 0 01-8 8zm0-14a6 6 0 106 6 6 6 0 00-6-6zm0 2a4 4 0 11-4 4 4 4 0 014-4z"/></svg>
                        <span>Dhammapada 21</span>
                    </div>
                    <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">Pali Canon</span>
                </div>

                <blockquote class="text-base xl:text-lg font-serif italic text-amber-200/95 font-semibold leading-snug">
                    “Appamādo amatapadaṁ, pamādo maccuno padaṁ.<br class="hidden sm:inline"/>
                    Appamattā na mīyanti, ye pamattā yathā matā.”
                </blockquote>

                <p class="text-xs text-stone-300 italic">
                    “Kewaspadaan adalah jalan menuju keabadian batin; kelengahan adalah jalan menuju kematian. Mereka yang waspada tidak akan mati, tetapi mereka yang lengah sama seperti orang yang telah mati.”
                </p>
            </div>

        </div>
    </aside>


    <!-- =========================================================================
         KOLOM KANAN: FORM LOGIN ADMIN SUPER PREMIUM & INTERAKTIF
         ========================================================================= -->
    <main 
        id="login-form-section" 
        class="flex-1 flex flex-col justify-between p-6 sm:p-10 lg:p-12 xl:p-16 overflow-y-auto bg-[#FAF5ED] dark:bg-[#071710] transition-colors duration-300"
    >
        
        <!-- TOP UTILITY HEADER: BACK LINK & THEME SWITCHER -->
        <div class="flex items-center justify-between w-full max-w-lg mx-auto">
            
            <!-- Back to Home Link Button -->
            <a 
                href="{{ route('home') }}" 
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white dark:bg-[#0c2218] border border-stone-300/70 dark:border-emerald-500/25 text-stone-700 dark:text-stone-300 hover:text-[#0D6E42] dark:hover:text-emerald-300 hover:border-emerald-600/40 text-xs font-bold transition-all shadow-xs group"
            >
                <svg class="w-4 h-4 text-stone-500 dark:text-stone-400 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Beranda</span>
            </a>

            <!-- Dark / Light Mode Switcher -->
            <button 
                @click="
                    isDark = !isDark; 
                    if (isDark) { 
                        document.documentElement.classList.add('dark'); 
                        localStorage.theme = 'dark'; 
                    } else { 
                        document.documentElement.classList.remove('dark'); 
                        localStorage.theme = 'light'; 
                    }
                " 
                type="button" 
                class="p-2.5 rounded-xl bg-white dark:bg-[#0c2218] border border-stone-300/70 dark:border-emerald-500/25 text-stone-700 dark:text-amber-300 hover:bg-stone-100 dark:hover:bg-emerald-900/60 transition-all shadow-xs cursor-pointer"
                :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
                :aria-label="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
            >
                <!-- Sun Icon -->
                <svg x-show="isDark" x-cloak class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <!-- Moon Icon -->
                <svg x-show="!isDark" class="w-4 h-4 text-stone-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </button>

        </div>

        <!-- CENTER FORM CARD WRAPPER -->
        <div class="w-full max-w-lg mx-auto my-auto py-8 space-y-7">
            
            <!-- Heading & Subtitle -->
            <div class="space-y-2 text-center sm:text-left">
                <!-- Mobile Brand Logo (Visible only on mobile/tablet) -->
                <div class="lg:hidden flex items-center gap-2">
                    <img src="{{ asset(\App\Models\Setting::get('foundation_logo', 'images/logo/android-chrome-192x192.png')) }}" alt="Logo" class="w-6 h-6 object-contain">
                    <span class="font-serif font-black text-sm text-[#143D2D] dark:text-[#E8F3EE]">SĀMAGGI GĀMA</span>
                </div>
                
                <h2 class="text-2xl sm:text-3xl xl:text-4xl font-serif font-black tracking-tight text-[#143D2D] dark:text-[#E8F3EE]">
                    Masuk ke Panel Pengurus
                </h2>
                
                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed">
                    Masukkan email dan password Anda untuk mengelola website Vihara Sāmaggi Gāma.
                </p>
            </div>

            <!-- Dynamic Feedback Alert Message -->
            @if ($feedbackMessage)
                <div 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="p-4 rounded-2xl border text-xs font-semibold flex items-start gap-3 shadow-xs"
                    :class="{
                        'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-500/40 text-emerald-900 dark:text-emerald-200': '{{ $feedbackType }}' === 'success',
                        'bg-rose-50 dark:bg-rose-950/50 border-rose-500/40 text-rose-900 dark:text-rose-200': '{{ $feedbackType }}' === 'error',
                        'bg-amber-50 dark:bg-amber-950/50 border-amber-500/40 text-amber-900 dark:text-amber-200': '{{ $feedbackType }}' === 'info'
                    }"
                >
                    <div class="shrink-0 mt-0.5">
                        @if ($feedbackType === 'success')
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        @elseif ($feedbackType === 'error')
                            <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        @else
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        @endif
                    </div>
                    <div class="leading-relaxed flex-1">
                        {{ $feedbackMessage }}
                    </div>
                </div>
            @endif

            <!-- MAIN ADMIN LOGIN FORM -->
            <form wire:submit.prevent="handleLogin" class="space-y-4">
                
                <!-- Input 1: Email / Username Admin -->
                <div class="space-y-1.5">
                    <label for="loginIdentifier" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                        <span>Email </span>
                        <span class="text-amber-500">*</span>
                    </label>
                    <div class="relative rounded-2xl shadow-xs">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400 dark:text-stone-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"/></svg>
                        </div>
                        <input 
                            wire:model="loginIdentifier" 
                            type="text" 
                            id="loginIdentifier" 
                            required 
                            autocomplete="username"
                            placeholder="cth: admin.utama@samaggigama.org" 
                            class="w-full pl-10 pr-4 py-3 rounded-2xl bg-white dark:bg-[#0c2218] border border-stone-300/80 dark:border-emerald-500/30 text-stone-900 dark:text-stone-100 text-xs sm:text-sm placeholder-stone-400 focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all shadow-inner"
                        />
                    </div>
                </div>

                <!-- Input 2: Kata Sandi Admin (Password with Toggle) -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            <span>Kata Sandi Master</span>
                            <span class="text-amber-500">*</span>
                        </label>
                        
                    </div>
                    <div class="relative rounded-2xl shadow-xs">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400 dark:text-stone-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <input 
                            wire:model="password" 
                            :type="showPass ? 'text' : 'password'" 
                            id="password" 
                            required 
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi master akun Anda" 
                            class="w-full pl-10 pr-11 py-3 rounded-2xl bg-white dark:bg-[#0c2218] border border-stone-300/80 dark:border-emerald-500/30 text-stone-900 dark:text-stone-100 text-xs sm:text-sm placeholder-stone-400 focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all shadow-inner"
                        />
                        <!-- Show / Hide Password Button (Strictly SVG) -->
                        <button 
                            @click="showPass = !showPass" 
                            type="button" 
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 transition-colors cursor-pointer"
                            :title="showPass ? 'Sembunyikan Kata Sandi' : 'Tampilkan Kata Sandi'"
                            :aria-label="showPass ? 'Sembunyikan Kata Sandi' : 'Tampilkan Kata Sandi'"
                        >
                            <!-- Eye Open (when hiding) -->
                            <svg x-show="showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <!-- Eye Slash (when showing) -->
                            <svg x-show="!showPass" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input 
                            wire:model="remember" 
                            type="checkbox" 
                            class="w-4 h-4 rounded-md text-[#0D5B3A] focus:ring-emerald-500 border-stone-300 dark:border-emerald-600/40 dark:bg-emerald-950 accent-[#0D5B3A]"
                        />
                        <span class="text-xs text-stone-600 dark:text-stone-300 font-medium">
                            Ingat saya
                        </span>
                    </label>

                </div>

                <!-- Main Submit Action Button -->
                <div class="pt-2">
                    <button 
                        type="submit" 
                        :disabled="loading"
                        class="w-full inline-flex items-center justify-center gap-2.5 py-3.5 px-6 rounded-2xl bg-gradient-to-r from-[#0D5B3A] via-[#143D2D] to-[#0D5B3A] hover:from-[#0F6B44] hover:to-[#174836] text-white font-bold text-sm shadow-md shadow-emerald-950/20 hover:shadow-lg hover:shadow-emerald-950/30 border border-amber-400/30 transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed"
                    >
                        <!-- Loading Spinner -->
                        <svg x-show="loading" x-cloak class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        
                        <span x-show="!loading">Masuk ke Panel</span>
                        <span x-show="loading" x-cloak>Memverifikasi Kredensial...</span>

                        <svg x-show="!loading" class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>

            </form>

            @if(env('APP_ENV') == 'local')
                <!-- QUICK DEMO PREVIEW CREDENTIAL BUTTONS (FOR EVALUATION) -->
                <div class="p-3.5 rounded-2xl bg-stone-200/60 dark:bg-[#0c2218] border border-stone-300/60 dark:border-emerald-500/20 space-y-2">
                    <div class="flex items-center justify-between text-[11px] font-bold text-stone-500 dark:text-stone-400">
                        <span>Akses Demo Pengujian:</span>
                        <span class="text-amber-600 dark:text-amber-400 font-extrabold uppercase text-[10px]">1-Klik Otomatis</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <button 
                            wire:click="fillAdminDemo('superadmin')"
                            type="button" 
                            class="py-1.5 px-2.5 rounded-xl bg-white dark:bg-emerald-950/70 hover:bg-stone-100 dark:hover:bg-emerald-900 text-stone-700 dark:text-stone-300 text-xs font-semibold border border-stone-300/50 dark:border-emerald-500/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
                        >
                            <svg class="w-3.5 h-3.5 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 7.4h7.6l-6.2 4.5 2.4 7.4-6.2-4.5-6.2 4.5 2.4-7.4-6.2-4.5h7.6z"/></svg>
                            <span>Super Administrator</span>
                        </button>

                        <button 
                            wire:click="fillAdminDemo('bendahara')"
                            type="button" 
                            class="py-1.5 px-2.5 rounded-xl bg-white dark:bg-emerald-950/70 hover:bg-stone-100 dark:hover:bg-emerald-900 text-stone-700 dark:text-stone-300 text-xs font-semibold border border-stone-300/50 dark:border-emerald-500/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
                        >
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <span>Bendahara Yayasan</span>
                        </button>
                    </div>
                </div>
            @endif

        </div>

        <!-- BOTTOM FOOTNOTE SECURITY & ENCRYPTION -->
        <div class="w-full max-w-lg mx-auto pt-4 border-t border-stone-200 dark:border-emerald-950/60 flex items-center justify-between text-[11px] text-stone-500 dark:text-stone-400">
            <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>Privasi Terlindungi</span>
            </div>
            <span>Vihara Sāmaggi Gāma</span>
        </div>

    </main>

</div>
