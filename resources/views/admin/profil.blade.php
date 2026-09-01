<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

new class extends Component
{
    public string $activeTab = 'profil'; // 'profil', 'password'
    public ?string $feedbackMessage = null;
    public ?string $errorMessage = null;

    // Profil Info
    public string $name = '';
    public string $email = '';
    public string $role = '';

    // Password Form
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        if ($user) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->role;
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->feedbackMessage = null;
        $this->errorMessage = null;
    }

    public function updateProfile(): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ], [
            'name.required' => 'Nama administrator wajib diisi.',
            'email.required' => 'Email administrator wajib diisi.',
            'email.unique' => 'Email ini telah digunakan oleh akun lain.',
        ]);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);
        $this->feedbackMessage = 'Informasi profil administrator Anda berhasil diperbarui.';
    }

    public function updatePassword(): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $this->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|min:6',
            'newPasswordConfirmation' => 'required|same:newPassword',
        ], [
            'currentPassword.required' => 'Masukkan kata sandi akun saat ini.',
            'newPassword.required' => 'Masukkan kata sandi baru.',
            'newPassword.min' => 'Kata sandi baru minimal 6 karakter.',
            'newPasswordConfirmation.same' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        if (!Hash::check($this->currentPassword, $user->password)) {
            $this->errorMessage = 'Kata sandi saat ini tidak sesuai.';
            return;
        }

        $user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->feedbackMessage = 'Kata sandi akun Anda berhasil diperbarui.';
        $this->errorMessage = null;
    }

    public function render()
    {
        return $this->view()->title('Profil & Ubah Sandi')->layout('layouts::admin');
    }
};
?>

<div class="space-y-6 font-sans">

    <!-- =========================================================================
         1. TOP HEADER
         ========================================================================= -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    Profil & Keamanan Akun
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Kelola informasi akun administrator dan perbarui kata sandi akun Anda.
            </p>
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

    @if ($errorMessage)
        <div 
            x-data="{ show: true }" 
            x-show="show"
            class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-500/30 text-rose-900 dark:text-rose-200 text-xs font-semibold flex items-center justify-between shadow-xs"
        >
            <span>{{ $errorMessage }}</span>
            <button @click="show = false" type="button" class="text-rose-700 dark:text-rose-300 hover:text-rose-900 cursor-pointer p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <!-- =========================================================================
         2. TABS & FORM CONTAINER
         ========================================================================= -->
    <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden">
        
        <!-- Tab Navigation Bar -->
        <div class="p-3 bg-stone-50/80 dark:bg-[#071710] border-b border-stone-200/80 dark:border-emerald-950 flex flex-wrap gap-2">
            <button 
                @click="$wire.setTab('profil')" 
                type="button" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $activeTab === 'profil' ? 'bg-[#0D5B3A] text-white shadow-xs' : 'text-stone-600 dark:text-stone-400 hover:bg-stone-200/60 dark:hover:bg-emerald-950' }}"
            >
                Informasi Profil
            </button>
            <button 
                @click="$wire.setTab('password')" 
                type="button" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $activeTab === 'password' ? 'bg-[#0D5B3A] text-white shadow-xs' : 'text-stone-600 dark:text-stone-400 hover:bg-stone-200/60 dark:hover:bg-emerald-950' }}"
            >
                Ubah Kata Sandi
            </button>
        </div>

        <div class="p-6 sm:p-8">
            
            <!-- TAB 1: PROFIL -->
            @if ($activeTab === 'profil')
                <form wire:submit.prevent="updateProfile" class="space-y-4 max-w-xl text-xs">
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Nama Lengkap & Gelar
                        </label>
                        <input 
                            wire:model="name" 
                            type="text" 
                            required 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-semibold"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Alamat Email (Login)
                        </label>
                        <input 
                            wire:model="email" 
                            type="email" 
                            required 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Peran / Hak Akses
                        </label>
                        <input 
                            value="{{ $role }}" 
                            disabled 
                            type="text" 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-100 dark:bg-[#040e09] border border-stone-200 dark:border-emerald-950 text-stone-500 text-xs cursor-not-allowed font-semibold"
                        />
                    </div>

                    <div class="pt-4 border-t border-stone-200 dark:border-emerald-950">
                        <button 
                            type="submit" 
                            class="py-2.5 px-6 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-md border border-amber-400/30 transition-all cursor-pointer"
                        >
                            Simpan Perubahan Profil
                        </button>
                    </div>
                </form>
            @endif

            <!-- TAB 2: UBAH SANDI -->
            @if ($activeTab === 'password')
                <form wire:submit.prevent="updatePassword" class="space-y-4 max-w-xl text-xs">
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Kata Sandi Saat Ini <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="currentPassword" 
                            type="password" 
                            required 
                            placeholder="••••••••"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Kata Sandi Baru <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="newPassword" 
                            type="password" 
                            required 
                            minlength="6"
                            placeholder="Minimal 6 karakter"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Konfirmasi Kata Sandi Baru <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="newPasswordConfirmation" 
                            type="password" 
                            required 
                            minlength="6"
                            placeholder="Ketik ulang kata sandi baru"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>

                    <div class="pt-4 border-t border-stone-200 dark:border-emerald-950">
                        <button 
                            type="submit" 
                            class="py-2.5 px-6 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-md border border-amber-400/30 transition-all cursor-pointer"
                        >
                            Ubah Kata Sandi
                        </button>
                    </div>
                </form>
            @endif

        </div>

    </div>

</div>
