<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

new class extends Component
{
    public string $searchQuery = '';
    public bool $modalOpen = false;
    public bool $resetModalOpen = false;
    public ?int $editingId = null;
    public ?User $selectedUser = null;
    public ?string $feedbackMessage = null;

    // Form inputs
    public string $formName = '';
    public string $formEmail = '';
    public string $formRole = 'Super Administrator';
    public string $formStatus = 'aktif';
    public string $formPassword = '';
    public string $formNewPassword = '';

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'formName', 'formEmail', 'formPassword']);
        $this->formRole = 'Admin';
        $this->formStatus = 'aktif';
        $this->modalOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $user = User::find($id);
        if (!$user) {
            return;
        }

        $this->editingId = $user->id;
        $this->formName = $user->name;
        $this->formEmail = $user->email;
        $this->formRole = $user->isBendahara() ? 'Bendahara' : 'Admin';
        $this->formStatus = $user->status ?? 'aktif';
        $this->formPassword = '';

        $this->modalOpen = true;
    }

    public function openResetModal(int $id): void
    {
        $this->selectedUser = User::find($id);
        if ($this->selectedUser) {
            $this->formNewPassword = '';
            $this->resetModalOpen = true;
        }
    }

    public function saveUser(): void
    {
        $rules = [
            'formName' => 'required|string|max:255',
            'formRole' => 'required|string',
            'formStatus' => 'required|in:aktif,nonaktif',
        ];

        if ($this->editingId) {
            $rules['formEmail'] = 'required|email|unique:users,email,' . $this->editingId;
        } else {
            $rules['formEmail'] = 'required|email|unique:users,email';
            $rules['formPassword'] = 'required|min:6';
        }

        $this->validate($rules);

        if ($this->editingId) {
            $user = User::find($this->editingId);
            if ($user) {
                $data = [
                    'name' => $this->formName,
                    'email' => $this->formEmail,
                    'role' => $this->formRole,
                    'status' => $this->formStatus,
                ];
                if (!empty($this->formPassword)) {
                    $data['password'] = Hash::make($this->formPassword);
                }
                $user->update($data);
                $this->feedbackMessage = "Data pengguna '{$this->formName}' berhasil diperbarui.";
            }
        } else {
            User::create([
                'name' => $this->formName,
                'email' => $this->formEmail,
                'role' => $this->formRole,
                'status' => $this->formStatus,
                'password' => Hash::make($this->formPassword),
            ]);
            $this->feedbackMessage = "Pengguna baru '{$this->formName}' berhasil didaftarkan.";
        }

        $this->modalOpen = false;
    }

    public function resetPassword(): void
    {
        $this->validate([
            'formNewPassword' => 'required|min:6',
        ]);

        if ($this->selectedUser) {
            $this->selectedUser->update([
                'password' => Hash::make($this->formNewPassword),
            ]);
            $this->feedbackMessage = "Kata sandi untuk '{$this->selectedUser->name}' berhasil direset.";
            $this->resetModalOpen = false;
        }
    }

    public function toggleStatus(int $id): void
    {
        $user = User::find($id);
        if ($user) {
            $user->status = ($user->status === 'aktif') ? 'nonaktif' : 'aktif';
            $user->save();
            $this->feedbackMessage = "Status akun '{$user->name}' diubah menjadi {$user->status}.";
        }
    }

    public function deleteUser(int $id): void
    {
        $user = User::find($id);
        if ($user) {
            if (User::count() <= 1) {
                $this->feedbackMessage = "Tidak dapat menghapus satu-satunya akun administrator!";
                return;
            }
            $name = $user->name;
            $user->delete();
            $this->feedbackMessage = "Pengguna '{$name}' berhasil dihapus.";
        }
    }

    public function render()
    {
        $query = User::latest();

        if (!empty($this->searchQuery)) {
            $search = '%' . strtolower($this->searchQuery) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(email) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(role) LIKE ?', [$search]);
            });
        }

        $users = $query->get();

        return $this->view([
            'users' => $users,
        ])->title('Kelola Pengguna & Hak Akses')->layout('layouts::admin');
    }
};
?>

<div class="space-y-6 font-sans">

    <!-- =========================================================================
         1. TOP HEADER & ACTION CTA
         ========================================================================= -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    Kelola Pengguna
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Kelola akun administrator, sekretariat, tim keuangan, dan editor portal.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button 
                wire:click="openCreateModal"
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-xs border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>Pengguna</span>
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
         2. TABEL PENGGUNA
         ========================================================================= -->
    <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden">
        
        <!-- Table Toolbar -->
        <div class="p-5 border-b border-stone-200/80 dark:border-emerald-950 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold text-stone-900 dark:text-stone-100">
                    Daftar Pengguna Sistem
                </h2>
                <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                    Akun staf dan hak akses pengelola portal vihara.
                </p>
            </div>

            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    wire:model.live.debounce.250ms="searchQuery" 
                    type="text" 
                    placeholder="Cari nama, email" 
                    class="w-full pl-9 pr-3.5 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-[#0D5B3A] focus:ring-1 focus:ring-[#0D5B3A] transition-all shadow-2xs"
                />
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-stone-50/80 dark:bg-[#071710] border-b border-stone-200/80 dark:border-emerald-950 text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3.5 px-5">Nama & Email</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Hak Akses / Peran</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Status</th>
                        <th scope="col" class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-emerald-950/60">
                    @forelse ($users as $u)
                        <tr class="hover:bg-stone-50/60 dark:hover:bg-emerald-950/20 transition-colors">
                            
                            <!-- Nama & Email -->
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-300 font-bold text-xs flex items-center justify-center shrink-0 border border-amber-500/20">
                                        {{ strtoupper(substr($u->name, 0, 2)) }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-extrabold text-stone-900 dark:text-stone-100 text-xs sm:text-sm truncate">
                                            {{ $u->name }}
                                        </span>
                                        <span class="text-[11px] text-stone-400 mt-0.5">
                                            {{ $u->email }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Hak Akses / Role -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if ($u->isBendahara())
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] font-extrabold bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Bendahara</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] font-extrabold bg-emerald-500/15 text-[#0D6E42] dark:text-emerald-300 border border-emerald-500/30 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Admin (Akses Penuh)</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <button 
                                    wire:click="toggleStatus({{ $u->id }})" 
                                    type="button" 
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition-colors {{ $u->status === 'aktif' ? 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-400 border border-rose-500/20' }}"
                                    title="Klik untuk mengubah status akun"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full {{ $u->status === 'aktif' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span>{{ ucfirst($u->status ?? 'aktif') }}</span>
                                </button>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    
                                    <!-- Reset Password -->
                                    <button 
                                        wire:click="openResetModal({{ $u->id }})" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-600 dark:text-stone-300 border border-stone-200 dark:border-emerald-500/20 cursor-pointer shadow-2xs"
                                        title="Reset Kata Sandi"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    </button>

                                    <!-- Edit -->
                                    <button 
                                        wire:click="openEditModal({{ $u->id }})" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-600 dark:text-stone-300 border border-stone-200 dark:border-emerald-500/20 cursor-pointer shadow-2xs"
                                        title="Edit Pengguna"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <!-- Hapus -->
                                    <button 
                                        wire:click="deleteUser({{ $u->id }})" 
                                        wire:confirm="Apakah Anda yakin ingin menghapus akun pengguna ini?" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 text-rose-600 dark:text-rose-400 border border-rose-500/20 cursor-pointer shadow-2xs"
                                        title="Hapus Pengguna"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-stone-400">
                                Belum ada data pengguna.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    <!-- =========================================================================
         3. MODAL TAMBAH / EDIT PENGGUNA (TELEPORTED)
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
                            {{ $editingId ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru' }}
                        </h3>
                        <p class="text-xs text-stone-300">Konfigurasi akun dan hak akses pengelola sistem.</p>
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
                <form wire:submit.prevent="saveUser" class="p-6 space-y-4 overflow-y-auto text-xs">
                    
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Nama Lengkap <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="formName" 
                            type="text" 
                            required 
                            placeholder="cth: Hendra Wijaya, S.E."
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Email <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="formEmail" 
                            type="email" 
                            required 
                            placeholder="cth: hendra.wijaya@samaggigama.org"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>

                    <!-- Role / Hak Akses Select (Admin vs Bendahara) -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Hak Akses / Peran <span class="text-amber-500">*</span>
                        </label>
                        <select 
                            wire:model="formRole" 
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-medium"
                        >
                            <option value="Admin">Admin (Akses Penuh Seluruh Menu & Fitur)</option>
                            <option value="Bendahara">Bendahara (Khusus Transaksi & Pengeluaran Kas)</option>
                        </select>
                        <p class="text-[10.5px] text-stone-500 dark:text-stone-400">
                            Bendahara hanya dapat mengakses Dashboard Keuangan, Transaksi Dāna Masuk, dan Catatan Pengeluaran Kas.
                        </p>
                    </div>

                    <!-- Status Akun -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Status Akun <span class="text-amber-500">*</span>
                        </label>
                        <select 
                            wire:model="formStatus" 
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-medium"
                        >
                            <option value="aktif">Aktif (Dapat Login)</option>
                            <option value="nonaktif">Nonaktif (Diblokir dari Sistem)</option>
                        </select>
                    </div>

                    @if (!$editingId)
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Kata Sandi (Minimal 6 Karakter) <span class="text-amber-500">*</span>
                            </label>
                            <input 
                                wire:model="formPassword" 
                                type="password" 
                                required 
                                placeholder="••••••••"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>
                    @endif

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
                            {{ $editingId ? 'Simpan Perubahan' : 'Buat Pengguna' }}
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </template>

    <!-- =========================================================================
         4. MODAL RESET KATA SANDI (TELEPORTED)
         ========================================================================= -->
    @if ($selectedUser)
        <template x-teleport="body">
            <div 
                x-show="$wire.resetModalOpen" 
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/80 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
                @keydown.escape.window="$wire.resetModalOpen = false"
            >
                <div 
                    @click.away="$wire.resetModalOpen = false" 
                    class="relative max-w-md w-full bg-white dark:bg-[#0b1f17] rounded-3xl overflow-hidden border border-stone-300/80 dark:border-emerald-500/30 shadow-2xl flex flex-col my-auto"
                >
                    <div class="p-6 bg-[#0B2117] text-white flex items-center justify-between border-b border-emerald-900">
                        <div>
                            <h3 class="text-base font-extrabold text-[#FAF5ED]">Reset Kata Sandi</h3>
                            <p class="text-xs text-stone-300">Untuk akun: {{ $selectedUser->name }}</p>
                        </div>
                        <button 
                            @click="$wire.resetModalOpen = false" 
                            type="button" 
                            class="p-1.5 rounded-full text-stone-400 hover:text-white bg-black/30 hover:bg-black/50 transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="resetPassword" class="p-6 space-y-4 text-xs">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Kata Sandi Baru <span class="text-amber-500">*</span>
                            </label>
                            <input 
                                wire:model="formNewPassword" 
                                type="password" 
                                required 
                                minlength="6"
                                placeholder="Minimal 6 karakter"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>

                        <div class="pt-3 flex items-center justify-end gap-2.5">
                            <button 
                                @click="$wire.resetModalOpen = false" 
                                type="button" 
                                class="py-2.5 px-4 rounded-xl bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-700 dark:text-stone-300 font-bold text-xs cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                class="py-2.5 px-5 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-md border border-amber-400/30 cursor-pointer"
                            >
                                Simpan Sandi Baru
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    @endif

</div>
