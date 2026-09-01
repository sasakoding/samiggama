<?php

use App\Models\Setting;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $activeTab = 'yayasan'; // 'yayasan', 'rekening', 'sosmed'
    public ?string $feedbackMessage = null;

    // Profil & Logo Yayasan
    public string $foundationName = '';
    public string $foundationLegalKemenag = '';
    public string $foundationNotary = '';
    public string $foundationAddress = '';
    public string $foundationMapUrl = '';
    public string $foundationPhone = '';
    public string $foundationEmail = '';
    public $logoImage = null;
    public ?string $existingLogoImage = null;

    // Dokumen / Izin Organisasi
    public string $foundationLegalDocTitle = '';
    public $legalDocFile = null;
    public ?string $existingLegalDocFile = null;

    // Rekening & QRIS
    public string $bankName1 = '';
    public string $bankAccount1 = '';
    public string $bankHolder1 = '';
    public string $bankName2 = '';
    public string $bankAccount2 = '';
    public string $bankHolder2 = '';
    public string $qrisMerchantId = '';
    public $qrisImage = null;
    public ?string $existingQrisImage = null;

    // Bimbingan Bhikkhu Sangha
    public $sanghaPhoto = null;
    public ?string $existingSanghaPhoto = null;
    public string $sanghaTitle = 'Mengetahui Bhikkhu Sangha';

    // Media Sosial & Saluran Komunikasi
    public string $socialYoutube = '';
    public string $socialInstagram = '';
    public string $socialFacebook = '';
    public string $socialTiktok = '';
    public string $socialWhatsapp = '';
    public string $socialSpotify = '';
    public string $socialTelegram = '';

    public function mount(): void
    {
        // 1. Yayasan & Legalitas
        $this->foundationName = Setting::get('foundation_name', 'Yayasan Vihara Sāmaggi Gāma');
        $this->foundationLegalKemenag = Setting::get('foundation_kemenag', 'Tanda Daftar Bimas Buddha No. 412/2019');
        $this->foundationNotary = Setting::get('foundation_notary', 'Akta Notaris No. 18/2019');
        $this->foundationAddress = Setting::get('foundation_address', 'Jl. Sāmaggi Raya No. 8, Candi Dhyana, Indonesia');
        $this->foundationMapUrl = Setting::get('foundation_map_url', 'https://maps.google.com/?q=Vihara+Samaggi+Gama');
        $this->foundationPhone = Setting::get('foundation_phone', '+62 811-2345-6789');
        $this->foundationEmail = Setting::get('foundation_email', 'sekretariat@samaggigama.org');
        $this->existingLogoImage = Setting::get('foundation_logo', 'images/logo/android-chrome-192x192.png');

        $this->foundationLegalDocTitle = Setting::get('foundation_legal_doc_title', 'Surat Tanda Daftar & Izin Operasional Kemenag RI');
        $this->existingLegalDocFile = Setting::get('foundation_legal_doc', null);

        // 2. Rekening & QRIS
        $this->bankName1 = Setting::get('bank_name_1', 'Bank Central Asia (BCA)');
        $this->bankAccount1 = Setting::get('bank_account_1', '088-789-2233');
        $this->bankHolder1 = Setting::get('bank_holder_1', 'Yayasan Vihara Samaggi Gama');
        $this->bankName2 = Setting::get('bank_name_2', 'Bank Mandiri');
        $this->bankAccount2 = Setting::get('bank_account_2', '123-00-9988776-5');
        $this->bankHolder2 = Setting::get('bank_holder_2', 'Yayasan Vihara Samaggi Gama');
        $this->qrisMerchantId = Setting::get('qris_merchant_id', 'ID1020039948291');
        $this->existingQrisImage = Setting::get('qris_image', 'images/qris-vihara.jpg');

        $this->existingSanghaPhoto = Setting::get('sangha_photo', 'images/bhikkhu-sangha.jpg');
        $this->sanghaTitle = Setting::get('sangha_title', 'Mengetahui Bhikkhu Sangha');

        // 3. Media Sosial
        $this->socialYoutube = Setting::get('social_youtube', 'https://youtube.com/@samaggigama');
        $this->socialInstagram = Setting::get('social_instagram', 'https://instagram.com/samaggigama');
        $this->socialFacebook = Setting::get('social_facebook', 'https://facebook.com/samaggigama');
        $this->socialTiktok = Setting::get('social_tiktok', 'https://tiktok.com/@samaggigama');
        $this->socialWhatsapp = Setting::get('social_whatsapp', 'https://wa.me/6281123456789');
        $this->socialSpotify = Setting::get('social_spotify', '');
        $this->socialTelegram = Setting::get('social_telegram', '');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function removeLogoImage(): void
    {
        $this->logoImage = null;
        $this->existingLogoImage = null;
    }

    public function removeLegalDoc(): void
    {
        $this->legalDocFile = null;
        $this->existingLegalDocFile = null;
    }

    public function removeQrisImage(): void
    {
        $this->qrisImage = null;
        $this->existingQrisImage = null;
    }

    public function removeSanghaPhoto(): void
    {
        $this->sanghaPhoto = null;
        $this->existingSanghaPhoto = null;
    }

    public function saveSettings(): void
    {
        $this->validate([
            'foundationName' => 'required|string|max:255',
            'foundationEmail' => 'nullable|email',
            'logoImage' => 'nullable|image|max:5120', // 5MB
            'legalDocFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:15360', // 15MB
            'qrisImage' => 'nullable|image|max:10240', // 10MB
            'sanghaPhoto' => 'nullable|image|max:10240', // 10MB
        ], [
            'foundationName.required' => 'Nama yayasan wajib diisi.',
            'logoImage.image' => 'Berkas logo harus berupa gambar (PNG, JPG, JPEG, WebP).',
            'logoImage.max' => 'Ukuran logo maksimal 5MB.',
            'legalDocFile.mimes' => 'Berkas izin organisasi harus berformat PDF, JPG, PNG, atau WebP.',
            'legalDocFile.max' => 'Ukuran berkas izin maksimal 15MB.',
            'qrisImage.image' => 'Berkas QRIS harus berupa gambar (JPG, PNG, JPEG, atau WebP).',
            'qrisImage.max' => 'Ukuran gambar QRIS maksimal 10MB.',
            'sanghaPhoto.image' => 'Foto Bhikkhu Sangha harus berupa gambar (JPG, PNG, JPEG, atau WebP).',
            'sanghaPhoto.max' => 'Ukuran foto Bhikkhu Sangha maksimal 10MB.',
        ]);

        // 1. Profil & Legalitas
        Setting::set('foundation_name', $this->foundationName);
        Setting::set('foundation_kemenag', $this->foundationLegalKemenag);
        Setting::set('foundation_notary', $this->foundationNotary);
        Setting::set('foundation_address', $this->foundationAddress);
        Setting::set('foundation_map_url', trim($this->foundationMapUrl));
        Setting::set('foundation_phone', $this->foundationPhone);
        Setting::set('foundation_email', $this->foundationEmail);
        Setting::set('foundation_legal_doc_title', $this->foundationLegalDocTitle);

        // Upload and save Logo
        if ($this->logoImage) {
            $logoPath = ImageUploadService::uploadOriginal($this->logoImage, 'uploads/settings');
            Setting::set('foundation_logo', $logoPath);
            $this->existingLogoImage = $logoPath;
            $this->logoImage = null;
        } elseif (empty($this->existingLogoImage)) {
            Setting::set('foundation_logo', null);
        }

        // Upload and save Izin Organisasi Document
        if ($this->legalDocFile) {
            $ext = strtolower($this->legalDocFile->getClientOriginalExtension()) ?: 'pdf';
            $destinationPath = public_path('uploads/documents');
            File::ensureDirectoryExists($destinationPath, 0755, true);
            $fileName = 'izin-organisasi-' . Str::uuid() . '.' . $ext;
            $targetFile = $destinationPath . DIRECTORY_SEPARATOR . $fileName;

            $sourcePath = $this->legalDocFile->getRealPath();
            if ($sourcePath && file_exists($sourcePath) && is_file($sourcePath)) {
                File::copy($sourcePath, $targetFile);
                @unlink($sourcePath);
            } else {
                File::put($targetFile, $this->legalDocFile->getContent());
            }
            ImageUploadService::cleanLivewireTmp();

            $legalDocPath = 'uploads/documents/' . $fileName;
            Setting::set('foundation_legal_doc', $legalDocPath);
            $this->existingLegalDocFile = $legalDocPath;
            $this->legalDocFile = null;
        } elseif (empty($this->existingLegalDocFile)) {
            Setting::set('foundation_legal_doc', null);
        }

        // 2. Save Bank Accounts & QRIS
        Setting::set('bank_name_1', $this->bankName1);
        Setting::set('bank_account_1', $this->bankAccount1);
        Setting::set('bank_holder_1', $this->bankHolder1);
        Setting::set('bank_name_2', $this->bankName2);
        Setting::set('bank_account_2', $this->bankAccount2);
        Setting::set('bank_holder_2', $this->bankHolder2);
        Setting::set('qris_merchant_id', $this->qrisMerchantId);

        // Upload and save QRIS image
        if ($this->qrisImage) {
            $qrisPath = ImageUploadService::uploadOriginal($this->qrisImage, 'uploads/settings');
            Setting::set('qris_image', $qrisPath);
            $this->existingQrisImage = $qrisPath;
            $this->qrisImage = null;
        } elseif (empty($this->existingQrisImage)) {
            Setting::set('qris_image', null);
        }

        // 3. Save Bhikkhu Sangha Endorsement
        Setting::set('sangha_title', $this->sanghaTitle);
        if ($this->sanghaPhoto) {
            $sanghaPath = ImageUploadService::uploadOriginal($this->sanghaPhoto, 'uploads/settings');
            Setting::set('sangha_photo', $sanghaPath);
            $this->existingSanghaPhoto = $sanghaPath;
            $this->sanghaPhoto = null;
        } elseif (empty($this->existingSanghaPhoto)) {
            Setting::set('sangha_photo', null);
        }

        // 4. Save Social Media Links
        Setting::set('social_youtube', $this->socialYoutube);
        Setting::set('social_instagram', $this->socialInstagram);
        Setting::set('social_facebook', $this->socialFacebook);
        Setting::set('social_tiktok', $this->socialTiktok);
        Setting::set('social_whatsapp', $this->socialWhatsapp);
        Setting::set('social_spotify', $this->socialSpotify);
        Setting::set('social_telegram', $this->socialTelegram);

        $this->feedbackMessage = 'Seluruh pengaturan identitas yayasan, rekening, dan saluran media sosial berhasil disimpan ke database.';
    }

    // ==========================================
    // MULTI-ADMIN WHATSAPP ROTATOR METHODS
    // ==========================================
    public bool $contactModalOpen = false;
    public ?int $editingContactId = null;
    public string $contactName = '';
    public string $contactPhone = '';
    public string $contactRole = 'Sekretariat & Layanan Umat';
    public bool $contactIsActive = true;
    public int $contactSortOrder = 1;

    public function openCreateContactModal(): void
    {
        $this->reset(['editingContactId', 'contactName', 'contactPhone', 'contactRole', 'contactSortOrder']);
        $this->contactRole = 'Sekretariat & Layanan Umat';
        $this->contactIsActive = true;
        $this->contactSortOrder = \App\Models\AdminContact::count() + 1;
        $this->resetValidation();
        $this->contactModalOpen = true;
    }

    public function openEditContactModal(int $id): void
    {
        $contact = \App\Models\AdminContact::find($id);
        if (!$contact) return;

        $this->editingContactId = $contact->id;
        $this->contactName = $contact->name;
        $this->contactPhone = $contact->phone;
        $this->contactRole = $contact->role ?? '';
        $this->contactIsActive = (bool) $contact->is_active;
        $this->contactSortOrder = (int) $contact->sort_order;
        $this->resetValidation();
        $this->contactModalOpen = true;
    }

    public function closeContactModal(): void
    {
        $this->contactModalOpen = false;
        $this->resetValidation();
    }

    public function saveContact(): void
    {
        $this->validate([
            'contactName' => 'required|string|max:255',
            'contactPhone' => 'required|string|max:50',
            'contactRole' => 'nullable|string|max:255',
            'contactSortOrder' => 'nullable|integer',
        ], [
            'contactName.required' => 'Nama admin / PIC wajib diisi.',
            'contactPhone.required' => 'Nomor WhatsApp wajib diisi.',
        ]);

        if ($this->editingContactId) {
            $contact = \App\Models\AdminContact::findOrFail($this->editingContactId);
            $contact->update([
                'name' => $this->contactName,
                'phone' => $this->contactPhone,
                'role' => $this->contactRole,
                'is_active' => $this->contactIsActive,
                'sort_order' => $this->contactSortOrder ?: 0,
            ]);
            $this->feedbackMessage = "Kontak WhatsApp admin '{$this->contactName}' berhasil diperbarui.";
        } else {
            \App\Models\AdminContact::create([
                'name' => $this->contactName,
                'phone' => $this->contactPhone,
                'role' => $this->contactRole,
                'is_active' => $this->contactIsActive,
                'sort_order' => $this->contactSortOrder ?: 0,
            ]);
            $this->feedbackMessage = "Kontak WhatsApp admin '{$this->contactName}' berhasil ditambahkan ke sistem rotator.";
        }

        $this->contactModalOpen = false;
    }

    public function toggleContactStatus(int $id): void
    {
        $contact = \App\Models\AdminContact::find($id);
        if ($contact) {
            $contact->is_active = !$contact->is_active;
            $contact->save();
            $statusText = $contact->is_active ? 'diaktifkan' : 'dinonaktifkan';
            $this->feedbackMessage = "Status kontak admin '{$contact->name}' berhasil {$statusText}.";
        }
    }

    public function deleteContact(int $id): void
    {
        $contact = \App\Models\AdminContact::find($id);
        if ($contact) {
            $name = $contact->name;
            $contact->delete();
            $this->feedbackMessage = "Kontak admin '{$name}' berhasil dihapus dari sistem rotator.";
        }
    }

    // ==========================================
    // DEWAN BHIKKHU SANGHA METHODS
    // ==========================================
    public bool $sanghaModalOpen = false;
    public ?int $editingSanghaId = null;
    public string $sanghaMemberName = '';
    public string $sanghaMemberTitle = '';
    public $sanghaMemberPhoto = null;
    public ?string $existingSanghaMemberPhoto = null;
    public bool $sanghaMemberIsActive = true;
    public int $sanghaMemberSortOrder = 1;

    public function openCreateSanghaModal(): void
    {
        $this->reset(['editingSanghaId', 'sanghaMemberName', 'sanghaMemberTitle', 'sanghaMemberPhoto', 'existingSanghaMemberPhoto', 'sanghaMemberSortOrder']);
        $this->sanghaMemberTitle = 'Pembina Spiritual';
        $this->sanghaMemberIsActive = true;
        $this->sanghaMemberSortOrder = \App\Models\SanghaMember::count() + 1;
        $this->resetValidation();
        $this->sanghaModalOpen = true;
    }

    public function openEditSanghaModal(int $id): void
    {
        $member = \App\Models\SanghaMember::find($id);
        if (!$member) return;

        $this->editingSanghaId = $member->id;
        $this->sanghaMemberName = $member->name;
        $this->sanghaMemberTitle = $member->title ?? '';
        $this->sanghaMemberPhoto = null;
        $this->existingSanghaMemberPhoto = $member->photo;
        $this->sanghaMemberIsActive = (bool) $member->is_active;
        $this->sanghaMemberSortOrder = (int) $member->sort_order;
        $this->resetValidation();
        $this->sanghaModalOpen = true;
    }

    public function closeSanghaModal(): void
    {
        $this->sanghaModalOpen = false;
        $this->resetValidation();
    }

    public function removeSanghaMemberPhoto(): void
    {
        $this->sanghaMemberPhoto = null;
        $this->existingSanghaMemberPhoto = null;
    }

    public function saveSanghaMember(): void
    {
        $this->validate([
            'sanghaMemberName' => 'required|string|max:255',
            'sanghaMemberTitle' => 'nullable|string|max:255',
            'sanghaMemberPhoto' => 'nullable|image|max:10240',
            'sanghaMemberSortOrder' => 'nullable|integer',
        ], [
            'sanghaMemberName.required' => 'Nama Bhikkhu wajib diisi.',
            'sanghaMemberPhoto.image' => 'Berkas foto harus berupa gambar (JPG, PNG, JPEG, atau WebP).',
            'sanghaMemberPhoto.max' => 'Ukuran foto maksimal 10MB.',
        ]);

        $photoPath = $this->existingSanghaMemberPhoto;
        if ($this->sanghaMemberPhoto) {
            $photoPath = ImageUploadService::uploadOriginal($this->sanghaMemberPhoto, 'uploads/sangha');
        }

        if ($this->editingSanghaId) {
            $member = \App\Models\SanghaMember::findOrFail($this->editingSanghaId);
            $member->update([
                'name' => $this->sanghaMemberName,
                'title' => $this->sanghaMemberTitle,
                'photo' => $photoPath,
                'is_active' => $this->sanghaMemberIsActive,
                'sort_order' => $this->sanghaMemberSortOrder ?: 0,
            ]);
            $this->feedbackMessage = "Data anggota Sangha '{$this->sanghaMemberName}' berhasil diperbarui.";
        } else {
            \App\Models\SanghaMember::create([
                'name' => $this->sanghaMemberName,
                'title' => $this->sanghaMemberTitle,
                'photo' => $photoPath ?: 'images/bhikkhu-sangha.jpg',
                'is_active' => $this->sanghaMemberIsActive,
                'sort_order' => $this->sanghaMemberSortOrder ?: 0,
            ]);
            $this->feedbackMessage = "Data Bhikkhu '{$this->sanghaMemberName}' berhasil ditambahkan.";
        }

        $this->sanghaModalOpen = false;
    }

    public function toggleSanghaStatus(int $id): void
    {
        $member = \App\Models\SanghaMember::find($id);
        if ($member) {
            $member->is_active = !$member->is_active;
            $member->save();
            $statusText = $member->is_active ? 'diaktifkan' : 'dinonaktifkan';
            $this->feedbackMessage = "Status Bhikkhu '{$member->name}' berhasil {$statusText}.";
        }
    }

    public function deleteSanghaMember(int $id): void
    {
        $member = \App\Models\SanghaMember::find($id);
        if ($member) {
            $name = $member->name;
            $member->delete();
            $this->feedbackMessage = "Data Bhikkhu '{$name}' berhasil dihapus.";
        }
    }

    public function render()
    {
        return $this->view([
            'adminContacts' => \App\Models\AdminContact::orderBy('sort_order')->orderBy('id')->get(),
            'sanghaMembers' => \App\Models\SanghaMember::orderBy('sort_order')->orderBy('id')->get(),
        ])->title('Pengaturan Sistem & Yayasan')->layout('layouts::admin');
    }
};
?>

<div 
    x-data="{
        isDraggingLogo: false,
        isDraggingDoc: false,
        isDraggingQris: false,
        handleLogoDrop(event) {
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.$wire.upload('logoImage', files[0], () => {}, () => {});
            }
        },
        handleDocDrop(event) {
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.$wire.upload('legalDocFile', files[0], () => {}, () => {});
            }
        },
        handleQrisDrop(event) {
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.$wire.upload('qrisImage', files[0], () => {}, () => {});
            }
        }
    }"
    class="space-y-6 font-sans"
>

    <!-- =========================================================================
         1. TOP HEADER
         ========================================================================= -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    Pengaturan Sistem & Yayasan
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Konfigurasi identitas resmi vihara, logo yayasan, berkas izin organisasi, rekening dāna, dan media sosial.
            </p>
        </div>

        @if (in_array($activeTab, ['yayasan', 'rekening', 'sosmed']))
            <button 
                wire:click="saveSettings" 
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-5 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-xs border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>Simpan Pengaturan</span>
            </button>
        @endif
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
         2. TABS & FORM CONTAINER
         ========================================================================= -->
    <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden">
        
        <!-- Tab Navigation Bar -->
        <div class="p-3 bg-stone-50/80 dark:bg-[#071710] border-b border-stone-200/80 dark:border-emerald-950 flex flex-wrap gap-2">
            <button 
                @click="$wire.setTab('yayasan')" 
                type="button" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 {{ $activeTab === 'yayasan' ? 'bg-[#0D5B3A] text-white shadow-xs' : 'text-stone-600 dark:text-stone-400 hover:bg-stone-200/60 dark:hover:bg-emerald-950' }}"
            >
                <svg class="w-4 h-4 {{ $activeTab === 'yayasan' ? 'text-amber-300' : 'text-stone-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Profil Organisasi</span>
            </button>
            <button 
                @click="$wire.setTab('rekening')" 
                type="button" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 {{ $activeTab === 'rekening' ? 'bg-[#0D5B3A] text-white shadow-xs' : 'text-stone-600 dark:text-stone-400 hover:bg-stone-200/60 dark:hover:bg-emerald-950' }}"
            >
                <svg class="w-4 h-4 {{ $activeTab === 'rekening' ? 'text-amber-300' : 'text-stone-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span>Rekening Bank & QRIS</span>
            </button>
            <button 
                @click="$wire.setTab('sosmed')" 
                type="button" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 {{ $activeTab === 'sosmed' ? 'bg-[#0D5B3A] text-white shadow-xs' : 'text-stone-600 dark:text-stone-400 hover:bg-stone-200/60 dark:hover:bg-emerald-950' }}"
            >
                <svg class="w-4 h-4 {{ $activeTab === 'sosmed' ? 'text-amber-300' : 'text-stone-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                <span>Media Sosial & Publikasi</span>
            </button>
            <button 
                @click="$wire.setTab('sangha')" 
                type="button" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 {{ $activeTab === 'sangha' ? 'bg-[#0D5B3A] text-white shadow-xs' : 'text-stone-600 dark:text-stone-400 hover:bg-stone-200/60 dark:hover:bg-emerald-950' }}"
            >
                <svg class="w-4 h-4 {{ $activeTab === 'sangha' ? 'text-amber-300' : 'text-stone-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Dewan Bhikkhu Sangha</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-400/20 text-amber-800 dark:text-amber-300 font-black">{{ $sanghaMembers->count() }}</span>
            </button>
            <button 
                @click="$wire.setTab('rotator')" 
                type="button" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 {{ $activeTab === 'rotator' ? 'bg-[#0D5B3A] text-white shadow-xs' : 'text-stone-600 dark:text-stone-400 hover:bg-stone-200/60 dark:hover:bg-emerald-950' }}"
            >
                <svg class="w-4 h-4 {{ $activeTab === 'rotator' ? 'text-amber-300' : 'text-stone-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>WA Admin Rotator</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-400/20 text-emerald-800 dark:text-emerald-300 font-black">{{ $adminContacts->count() }}</span>
            </button>
        </div>

        <!-- Form Body -->
        <div class="p-6 sm:p-8 space-y-6">
            
            <!-- =========================================================================
                 TAB 1: YAYASAN, LOGO & IZIN ORGANISASI
                 ========================================================================= -->
            @if ($activeTab === 'yayasan')
                <div class="space-y-6 text-xs">
                    
                    <!-- 1. UPLOAD LOGO RESMI YAYASAN (DRAG & DROP + PREVIEW) -->
                    <div class="p-5 rounded-3xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950/80 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-stone-200 dark:border-emerald-950/60 pb-3">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-extrabold text-stone-900 dark:text-stone-100 text-xs uppercase tracking-wider">
                                        Logo Resmi Vihara & Yayasan
                                    </h3>
                                    <span class="px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-800 dark:text-amber-300 font-bold text-[10px]">
                                        Identitas Web & Header
                                    </span>
                                </div>
                                <p class="text-[11px] text-stone-500 dark:text-stone-400">
                                    Logo akan tampil pada header navigasi publik, favicon, footer, dan panel administrasi.
                                </p>
                            </div>
                            
                            @if ($logoImage || $existingLogoImage)
                                <button 
                                    wire:click="removeLogoImage" 
                                    type="button" 
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 font-bold text-xs border border-rose-500/20 transition-all cursor-pointer self-start sm:self-auto"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Hapus Logo</span>
                                </button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                            <!-- Preview Box -->
                            <div class="sm:col-span-4 flex flex-col items-center">
                                <div class="relative w-full max-w-[170px] aspect-square rounded-2xl p-3 bg-white dark:bg-[#0c2218] border-2 border-dashed border-emerald-600/40 shadow-sm flex items-center justify-center overflow-hidden group">
                                    @if ($logoImage)
                                        <img 
                                            src="{{ $logoImage->temporaryUrl() }}" 
                                            alt="Pratinjau Logo Baru" 
                                            class="w-full h-full object-contain drop-shadow-sm"
                                        />
                                        <div class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-amber-500 text-stone-950 font-black text-[9.5px] uppercase tracking-wider shadow-sm">
                                            Logo Baru
                                        </div>
                                    @elseif ($existingLogoImage)
                                        <img 
                                            src="{{ asset($existingLogoImage) }}" 
                                            alt="Logo Vihara Samaggi Gama" 
                                            class="w-full h-full object-contain drop-shadow-sm"
                                        />
                                        <div class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-emerald-600 text-white font-black text-[9.5px] uppercase tracking-wider shadow-sm">
                                            Logo Aktif
                                        </div>
                                    @else
                                        <div class="text-center p-4 space-y-1 text-stone-400">
                                            <svg class="w-10 h-10 mx-auto text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="text-[11px] font-semibold">Belum ada logo</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Drag & Drop Dropzone Box -->
                            <div class="sm:col-span-8 space-y-2">
                                <div 
                                    @dragover.prevent="isDraggingLogo = true"
                                    @dragleave.prevent="isDraggingLogo = false"
                                    @drop.prevent="isDraggingLogo = false; handleLogoDrop($event)"
                                    :class="isDraggingLogo ? 'border-[#0D5B3A] bg-emerald-50/70 dark:bg-emerald-950/60 scale-[1.02]' : 'border-stone-300 dark:border-emerald-500/25 bg-white dark:bg-[#0b1f17] hover:border-emerald-500'"
                                    class="relative border-2 border-dashed rounded-2xl p-5 text-center space-y-2.5 transition-all duration-200 cursor-pointer group shadow-xs"
                                >
                                    <input 
                                        wire:model="logoImage" 
                                        type="file" 
                                        accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" 
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                                    />

                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#0D5B3A] to-amber-500 text-white flex items-center justify-center mx-auto shadow-xs group-hover:scale-105 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>

                                    <div class="space-y-0.5">
                                        <p class="text-xs font-extrabold text-stone-800 dark:text-stone-200">
                                            Tarik & Jatuhkan Berkas Logo di Sini
                                        </p>
                                        <p class="text-[11px] text-stone-500 dark:text-stone-400">
                                            atau <span class="text-[#0D5B3A] dark:text-emerald-400 font-bold underline">klik untuk memilih berkas</span>
                                        </p>
                                    </div>

                                    <p class="text-[10px] text-stone-400 dark:text-stone-500">
                                        Format: PNG Transparan, JPG, WebP, SVG (Maks. 5MB) • Rasio 1:1 direkomendasikan
                                    </p>

                                    <div wire:loading wire:target="logoImage" class="text-amber-500 text-xs font-bold pt-1">
                                        Memproses berkas logo...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. INFORMASI IDENTITAS YAYASAN -->
                    <div class="p-5 rounded-3xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950/80 space-y-4">
                        <h3 class="font-extrabold text-stone-900 dark:text-stone-100 text-xs uppercase tracking-wider border-b border-stone-200 dark:border-emerald-950/60 pb-3">
                            Informasi & Legalitas Tertulis
                        </h3>

                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                    Nama Resmi Yayasan / Vihara
                                </label>
                                <input 
                                    wire:model="foundationName" 
                                    type="text" 
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-semibold"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                        Nomor Tanda Daftar Kemenag RI
                                    </label>
                                    <input 
                                        wire:model="foundationLegalKemenag" 
                                        type="text" 
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                        Akta Notaris Yayasan
                                    </label>
                                    <input 
                                        wire:model="foundationNotary" 
                                        type="text" 
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                                    />
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                    Alamat Lengkap Vihara
                                </label>
                                <textarea 
                                    wire:model="foundationAddress" 
                                    rows="2"
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                                ></textarea>
                            </div>

                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                        Tautan / URL Google Maps (Gmaps)
                                    </label>
                                    @if (!empty($foundationMapUrl))
                                        <a 
                                            href="{{ $foundationMapUrl }}" 
                                            target="_blank" 
                                            rel="noopener noreferrer" 
                                            class="text-[11px] font-bold text-[#0D6E42] dark:text-emerald-400 hover:underline inline-flex items-center gap-1 cursor-pointer"
                                        >
                                            <span>Buka Titik Maps</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @endif
                                </div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                    <input 
                                        wire:model="foundationMapUrl" 
                                        type="url" 
                                        placeholder="cth: https://maps.app.goo.gl/xxx atau https://goo.gl/maps/xxx"
                                        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                                    />
                                </div>
                                <p class="text-[10.5px] text-stone-500 dark:text-stone-400">
                                    Tautan lokasi Google Maps yang akan terbuka ketika pengunjung mengklik alamat vihara untuk panduan rute.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                        Nomor Telepon / WhatsApp Sekretariat
                                    </label>
                                    <input 
                                        wire:model="foundationPhone" 
                                        type="text" 
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                        Email Resmi
                                    </label>
                                    <input 
                                        wire:model="foundationEmail" 
                                        type="email" 
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. UPLOAD BERKAS IZIN ORGANISASI / SK RESMI (DRAG & DROP + DOWNLOAD / PREVIEW) -->
                    <div class="p-5 rounded-3xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950/80 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-stone-200 dark:border-emerald-950/60 pb-3">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-extrabold text-stone-900 dark:text-stone-100 text-xs uppercase tracking-wider">
                                        Berkas Surat Izin Organisasi & SK Legalitas
                                    </h3>
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-[#0D6E42] dark:text-emerald-300 font-bold text-[10px]">
                                        Dokumen Sah
                                    </span>
                                </div>
                                <p class="text-[11px] text-stone-500 dark:text-stone-400">
                                    Unggah salinan sertifikat izin Kemenag, SK Kemenkumham, atau Akta Pendirian resmi Yayasan.
                                </p>
                            </div>

                            @if ($legalDocFile || $existingLegalDocFile)
                                <button 
                                    wire:click="removeLegalDoc" 
                                    type="button" 
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 font-bold text-xs border border-rose-500/20 transition-all cursor-pointer self-start sm:self-auto"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Hapus Berkas</span>
                                </button>
                            @endif
                        </div>

                        <!-- Document Title Input -->
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Judul / Nama Dokumen Izin
                            </label>
                            <input 
                                wire:model="foundationLegalDocTitle" 
                                type="text" 
                                placeholder="cth: Surat Keputusan Izin Operasional Kemenag RI" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>

                        <!-- Existing / Newly Uploaded Document Card -->
                        @if ($existingLegalDocFile || $legalDocFile)
                            <div class="p-3.5 rounded-2xl bg-white dark:bg-[#0c2218] border border-stone-200 dark:border-emerald-500/25 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div class="space-y-0.5 min-w-0">
                                        <div class="font-extrabold text-stone-800 dark:text-stone-200 text-xs truncate">
                                            {{ $foundationLegalDocTitle ?: 'Dokumen Izin Organisasi' }}
                                        </div>
                                        <div class="text-[10.5px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>{{ $legalDocFile ? 'Berkas baru siap disimpan' : 'Berkas izin tersimpan di server' }}</span>
                                        </div>
                                    </div>
                                </div>

                                @if ($existingLegalDocFile)
                                    <a 
                                        href="{{ asset($existingLegalDocFile) }}" 
                                        target="_blank"
                                        download
                                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white text-xs font-bold transition-all shadow-xs shrink-0"
                                    >
                                        <svg class="w-3.5 h-3.5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        <span>Unduh / Buka Dokumen</span>
                                    </a>
                                @endif
                            </div>
                        @endif

                        <!-- Drag & Drop Dropzone for Document -->
                        <div 
                            @dragover.prevent="isDraggingDoc = true"
                            @dragleave.prevent="isDraggingDoc = false"
                            @drop.prevent="isDraggingDoc = false; handleDocDrop($event)"
                            :class="isDraggingDoc ? 'border-[#0D5B3A] bg-emerald-50/70 dark:bg-emerald-950/60 scale-[1.02]' : 'border-stone-300 dark:border-emerald-500/25 bg-white dark:bg-[#0b1f17] hover:border-emerald-500'"
                            class="relative border-2 border-dashed rounded-2xl p-5 text-center space-y-2.5 transition-all duration-200 cursor-pointer group shadow-xs"
                        >
                            <input 
                                wire:model="legalDocFile" 
                                type="file" 
                                accept="application/pdf,image/png,image/jpeg,image/jpg,image/webp" 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                            />

                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#0D5B3A] to-amber-500 text-white flex items-center justify-center mx-auto shadow-xs group-hover:scale-105 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>

                            <div class="space-y-0.5">
                                <p class="text-xs font-extrabold text-stone-800 dark:text-stone-200">
                                    Tarik & Jatuhkan Berkas Izin Organisasi di Sini
                                </p>
                                <p class="text-[11px] text-stone-500 dark:text-stone-400">
                                    atau <span class="text-[#0D5B3A] dark:text-emerald-400 font-bold underline">klik untuk memilih berkas</span>
                                </p>
                            </div>

                            <p class="text-[10px] text-stone-400 dark:text-stone-500">
                                Format: PDF, JPG, PNG, WebP (Maksimal 15MB)
                            </p>

                            <div wire:loading wire:target="legalDocFile" class="text-amber-500 text-xs font-bold pt-1">
                                Mengunggah dokumen izin...
                            </div>
                        </div>
                    </div>

                </div>
            @endif

            <!-- =========================================================================
                 TAB 2: REKENING & QRIS
                 ========================================================================= -->
            @if ($activeTab === 'rekening')
                <div class="space-y-6 text-xs">
                    
                    <!-- Rekening 1 -->
                    <div class="p-4 rounded-2xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950/80 space-y-3">
                        <h3 class="font-extrabold text-stone-900 dark:text-stone-100 text-xs uppercase tracking-wider">
                            Rekening Bank Utama
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="space-y-1">
                                <label class="block font-bold text-stone-600 dark:text-stone-400">Nama Bank</label>
                                <input wire:model="bankName1" type="text" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100" />
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-stone-600 dark:text-stone-400">Nomor Rekening</label>
                                <input wire:model="bankAccount1" type="text" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 font-bold" />
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-stone-600 dark:text-stone-400">Atas Nama</label>
                                <input wire:model="bankHolder1" type="text" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100" />
                            </div>
                        </div>
                    </div>

                    <!-- Rekening 2 -->
                    <div class="p-4 rounded-2xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950/80 space-y-3">
                        <h3 class="font-extrabold text-stone-900 dark:text-stone-100 text-xs uppercase tracking-wider">
                            Rekening Bank Sekunder
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="space-y-1">
                                <label class="block font-bold text-stone-600 dark:text-stone-400">Nama Bank</label>
                                <input wire:model="bankName2" type="text" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100" />
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-stone-600 dark:text-stone-400">Nomor Rekening</label>
                                <input wire:model="bankAccount2" type="text" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 font-bold" />
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-stone-600 dark:text-stone-400">Atas Nama</label>
                                <input wire:model="bankHolder2" type="text" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100" />
                            </div>
                        </div>
                    </div>

                    <!-- QRIS STANDAR NASIONAL -->
                    <div class="p-5 rounded-3xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950/80 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-stone-200 dark:border-emerald-950/60 pb-3">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-extrabold text-stone-900 dark:text-stone-100 text-xs uppercase tracking-wider">
                                        Gambar QRIS Standar Nasional
                                    </h3>
                                </div>
                                <p class="text-[11px] text-stone-500 dark:text-stone-400">
                                    Upload gambar barcode QRIS resmi vihara untuk ditampilkan pada pop-up dāna umat.
                                </p>
                            </div>
                            
                            @if ($qrisImage || $existingQrisImage)
                                <button 
                                    wire:click="removeQrisImage" 
                                    type="button" 
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 font-bold text-xs border border-rose-500/20 transition-all cursor-pointer self-start sm:self-auto"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Hapus Gambar</span>
                                </button>
                            @endif
                        </div>

                        <!-- Live Preview Card -->
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                            
                            <!-- Left: Image Preview Box -->
                            <div class="sm:col-span-5 flex flex-col items-center">
                                <div class="relative w-full max-w-[220px] aspect-square rounded-2xl p-2.5 bg-white dark:bg-[#0c2218] border-2 border-dashed border-emerald-600/40 shadow-sm flex items-center justify-center overflow-hidden group">
                                    @if ($qrisImage)
                                        <img 
                                            src="{{ $qrisImage->temporaryUrl() }}" 
                                            alt="Pratinjau QRIS Baru" 
                                            class="w-full h-full object-contain rounded-xl"
                                        />
                                        <div class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-amber-500 text-stone-950 font-black text-[9.5px] uppercase tracking-wider shadow-sm">
                                            Berkas Baru
                                        </div>
                                    @elseif ($existingQrisImage)
                                        <img 
                                            src="{{ asset($existingQrisImage) }}" 
                                            alt="QRIS Vihara Samaggi Gama" 
                                            class="w-full h-full object-contain rounded-xl"
                                        />
                                        <div class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-emerald-600 text-white font-black text-[9.5px] uppercase tracking-wider shadow-sm">
                                            QRIS Aktif
                                        </div>
                                    @else
                                        <div class="text-center p-4 space-y-1 text-stone-400">
                                            <svg class="w-10 h-10 mx-auto text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="text-[11px] font-semibold">Belum ada gambar QRIS</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Right: Drag & Drop Dropzone Input -->
                            <div class="sm:col-span-7 space-y-3">
                                <div 
                                    @dragover.prevent="isDraggingQris = true"
                                    @dragleave.prevent="isDraggingQris = false"
                                    @drop.prevent="isDraggingQris = false; handleQrisDrop($event)"
                                    :class="isDraggingQris ? 'border-[#0D5B3A] bg-emerald-50/70 dark:bg-emerald-950/60 scale-[1.02]' : 'border-stone-300 dark:border-emerald-500/25 bg-white dark:bg-[#0b1f17] hover:border-emerald-500'"
                                    class="relative border-2 border-dashed rounded-2xl p-5 text-center space-y-2.5 transition-all duration-200 cursor-pointer group shadow-xs"
                                >
                                    <input 
                                        wire:model="qrisImage" 
                                        type="file" 
                                        accept="image/png,image/jpeg,image/jpg,image/webp" 
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                                    />

                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#0D5B3A] to-amber-500 text-white flex items-center justify-center mx-auto shadow-xs group-hover:scale-105 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    </div>

                                    <div class="space-y-0.5">
                                        <p class="text-xs font-extrabold text-stone-800 dark:text-stone-200">
                                            Tarik & Jatuhkan Gambar QRIS di Sini
                                        </p>
                                        <p class="text-[11px] text-stone-500 dark:text-stone-400">
                                            atau <span class="text-[#0D5B3A] dark:text-emerald-400 font-bold underline">klik untuk memilih berkas</span>
                                        </p>
                                    </div>

                                    <p class="text-[10px] text-stone-400 dark:text-stone-500">
                                        Format: PNG, JPG, JPEG, WebP (Maks. 10MB) • Resolusi tajam otomatis
                                    </p>

                                    <div wire:loading wire:target="qrisImage" class="text-amber-500 text-xs font-bold pt-1">
                                        Memproses gambar QRIS...
                                    </div>
                                </div>

                                <!-- NMID Input Field -->
                                <div class="space-y-1">
                                    <label class="block font-bold text-stone-600 dark:text-stone-400 text-[11px]">
                                        Merchant NMID (Opsional untuk fitur salin donatur)
                                    </label>
                                    <input 
                                        wire:model="qrisMerchantId" 
                                        type="text" 
                                        placeholder="cth: ID1020039948291"
                                        class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 font-bold focus:outline-none focus:border-amber-500" 
                                    />
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            @endif

            <!-- =========================================================================
                 TAB 3: MEDIA SOSIAL & PUBLIKASI (NEW DEDICATED TAB)
                 ========================================================================= -->
            @if ($activeTab === 'sosmed')
                <div class="space-y-6 text-xs">
                    
                    <div class="p-5 rounded-3xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950/80 space-y-4">
                        <div class="border-b border-stone-200 dark:border-emerald-950/60 pb-3 space-y-0.5">
                            <div class="flex items-center gap-2">
                                <h3 class="font-extrabold text-stone-900 dark:text-stone-100 text-xs uppercase tracking-wider">
                                    Tautan Media Sosial & Saluran Publikasi Resmi
                                </h3>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-[#0D6E42] dark:text-emerald-300 font-bold text-[10px]">
                                    Terhubung ke Footer & Publik
                                </span>
                            </div>
                            <p class="text-[11px] text-stone-500 dark:text-stone-400">
                                Tautan yang diatur di bawah ini akan otomatis menjadi tautan aktif pada footer website publik, kartu kontak, dan tombol sosial media vihara.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            
                            <!-- 1. YouTube -->
                            <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200 dark:border-emerald-500/20 space-y-2 shadow-2xs">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 font-bold text-stone-800 dark:text-stone-200">
                                        <span class="w-6 h-6 rounded-lg bg-[#FF0000]/15 text-[#FF0000] flex items-center justify-center font-bold">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                        </span>
                                        <span>YouTube Channel</span>
                                    </label>
                                    @if ($socialYoutube)
                                        <a href="{{ $socialYoutube }}" target="_blank" class="inline-flex items-center gap-1 text-[10.5px] text-red-600 hover:underline font-bold">
                                            <span>Uji Link</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @endif
                                </div>
                                <input 
                                    wire:model="socialYoutube" 
                                    type="url" 
                                    placeholder="https://youtube.com/@samaggigama" 
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 focus:outline-none focus:border-red-500"
                                />
                                <span class="text-[10px] text-stone-400 block">Link channel atau siaran streaming Dhammadesana.</span>
                            </div>

                            <!-- 2. Instagram -->
                            <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200 dark:border-emerald-500/20 space-y-2 shadow-2xs">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 font-bold text-stone-800 dark:text-stone-200">
                                        <span class="w-6 h-6 rounded-lg bg-pink-500/15 text-pink-600 flex items-center justify-center font-bold">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                        </span>
                                        <span>Instagram Akun</span>
                                    </label>
                                    @if ($socialInstagram)
                                        <a href="{{ $socialInstagram }}" target="_blank" class="inline-flex items-center gap-1 text-[10.5px] text-pink-600 hover:underline font-bold">
                                            <span>Uji Link</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @endif
                                </div>
                                <input 
                                    wire:model="socialInstagram" 
                                    type="url" 
                                    placeholder="https://instagram.com/samaggigama" 
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 focus:outline-none focus:border-pink-500"
                                />
                                <span class="text-[10px] text-stone-400 block">Link profil Instagram untuk galeri kegiatan dan pengumuman.</span>
                            </div>

                            <!-- 3. Facebook -->
                            <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200 dark:border-emerald-500/20 space-y-2 shadow-2xs">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 font-bold text-stone-800 dark:text-stone-200">
                                        <span class="w-6 h-6 rounded-lg bg-[#1877F2]/15 text-[#1877F2] flex items-center justify-center font-bold">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        </span>
                                        <span>Facebook Page / Group</span>
                                    </label>
                                    @if ($socialFacebook)
                                        <a href="{{ $socialFacebook }}" target="_blank" class="inline-flex items-center gap-1 text-[10.5px] text-blue-600 hover:underline font-bold">
                                            <span>Uji Link</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @endif
                                </div>
                                <input 
                                    wire:model="socialFacebook" 
                                    type="url" 
                                    placeholder="https://facebook.com/samaggigama" 
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 focus:outline-none focus:border-blue-600"
                                />
                                <span class="text-[10px] text-stone-400 block">Halaman resmi Facebook komunitas umat vihara.</span>
                            </div>

                            <!-- 4. TikTok -->
                            <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200 dark:border-emerald-500/20 space-y-2 shadow-2xs">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 font-bold text-stone-800 dark:text-stone-200">
                                        <span class="w-6 h-6 rounded-lg bg-stone-800/15 dark:bg-stone-200/15 text-stone-900 dark:text-stone-100 flex items-center justify-center font-bold">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                                        </span>
                                        <span>TikTok Akun</span>
                                    </label>
                                    @if ($socialTiktok)
                                        <a href="{{ $socialTiktok }}" target="_blank" class="inline-flex items-center gap-1 text-[10.5px] text-stone-700 dark:text-stone-300 hover:underline font-bold">
                                            <span>Uji Link</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @endif
                                </div>
                                <input 
                                    wire:model="socialTiktok" 
                                    type="url" 
                                    placeholder="https://tiktok.com/@samaggigama" 
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 focus:outline-none focus:border-stone-800"
                                />
                                <span class="text-[10px] text-stone-400 block">Link video inspirasi singkat dan mutiara Dhamma di TikTok.</span>
                            </div>

                            <!-- 5. WhatsApp Hotline -->
                            <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200 dark:border-emerald-500/20 space-y-2 shadow-2xs">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 font-bold text-stone-800 dark:text-stone-200">
                                        <span class="w-6 h-6 rounded-lg bg-[#25D366]/15 text-[#25D366] flex items-center justify-center font-bold">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        </span>
                                        <span>WhatsApp Hotline / Channel</span>
                                    </label>
                                    @if ($socialWhatsapp)
                                        <a href="{{ $socialWhatsapp }}" target="_blank" class="inline-flex items-center gap-1 text-[10.5px] text-emerald-600 hover:underline font-bold">
                                            <span>Uji Link</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @endif
                                </div>
                                <input 
                                    wire:model="socialWhatsapp" 
                                    type="text" 
                                    placeholder="https://wa.me/6281123456789" 
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 focus:outline-none focus:border-emerald-500"
                                />
                                <span class="text-[10px] text-stone-400 block">Link direct chat atau saluran informasi WhatsApp resmi.</span>
                            </div>

                            <!-- 6. Spotify / Podcast Dhamma -->
                            <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200 dark:border-emerald-500/20 space-y-2 shadow-2xs">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 font-bold text-stone-800 dark:text-stone-200">
                                        <span class="w-6 h-6 rounded-lg bg-[#1DB954]/15 text-[#1DB954] flex items-center justify-center font-bold">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.48.66.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>
                                        </span>
                                        <span>Spotify / Podcast Dhamma</span>
                                    </label>
                                    @if ($socialSpotify)
                                        <a href="{{ $socialSpotify }}" target="_blank" class="inline-flex items-center gap-1 text-[10.5px] text-green-600 hover:underline font-bold">
                                            <span>Uji Link</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @endif
                                </div>
                                <input 
                                    wire:model="socialSpotify" 
                                    type="url" 
                                    placeholder="https://open.spotify.com/show/..." 
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 focus:outline-none focus:border-green-500"
                                />
                                <span class="text-[10px] text-stone-400 block">Link podcast rekaman ceramah Dhamma di Spotify (opsional).</span>
                            </div>

                        </div>
                    </div>

                </div>
            @endif

            <!-- =========================================================================
                 TAB 4: DEWAN BHIKKHU SANGHA (SPITIRUAL ADVISORY & BLESSINGS)
                 ========================================================================= -->
            @if ($activeTab === 'sangha')
                <div class="space-y-6 text-xs">
                    
                    <!-- Hero Header & Action Banner -->
                    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#092218] via-[#0E3324] to-[#071710] border border-amber-500/30 p-6 sm:p-8 text-white shadow-xl">
                        
                        <!-- Background Ambient Glow -->
                        <div class="absolute -top-12 -right-12 w-64 h-64 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="absolute -bottom-12 -left-12 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
                        
                        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            
                            <!-- Left: Title & Explanatory Narrative -->
                            <div class="space-y-3 max-w-2xl">
                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/15 border border-amber-400/30 text-amber-300 font-extrabold text-[10.5px] uppercase tracking-wider shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                                    <span>Bimbingan Spiritual & Sangha Sasana</span>
                                </div>
                                
                                <div>
                                    <h3 class="text-xl sm:text-2xl font-black tracking-tight text-[#FAF5ED] flex items-center gap-2.5">
                                        <span>Dewan Bhikkhu Sangha Vihara Sāmaggi Gāma</span>
                                    </h3>
                                    <p class="text-stone-300 text-xs sm:text-sm leading-relaxed mt-1.5">
                                        Kelola daftar Yang Mulia Bhikkhu Sangha yang menaungi pembinaan spiritual dan penyaluran dana umat vihara.
                                    </p>
                                </div>
                            </div>

                            <!-- Right: Action Add Button -->
                            <div class="shrink-0">
                                <button 
                                    wire:click="openCreateSanghaModal" 
                                    type="button" 
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 py-3 px-5 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-stone-950 font-black text-xs shadow-lg hover:shadow-amber-500/25 transition-all duration-200 cursor-pointer transform hover:-translate-y-0.5 active:scale-95"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.8" d="M12 4v16m8-8H4"/></svg>
                                    <span>Bhikkhu</span>
                                </button>
                            </div>

                        </div>
                    </div>

                    <!-- Sangha Members Table / Card Grid -->
                    <div class="p-5 rounded-3xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950/80 space-y-4">
                        <div class="flex items-center justify-between border-b border-stone-200 dark:border-emerald-950/60 pb-3">
                            <div class="space-y-0.5">
                                <h3 class="font-extrabold text-stone-900 dark:text-stone-100 text-xs uppercase tracking-wider">
                                    Daftar Anggota Dewan Bhikkhu Sangha ({{ $sanghaMembers->count() }})
                                </h3>
                                <p class="text-[11px] text-stone-500 dark:text-stone-400">
                                    Urutan tampil diatur berdasarkan nomor urut terkecil ke terbesar.
                                </p>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-stone-200 dark:border-emerald-950/60 shadow-xs">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-stone-100 dark:bg-[#0b1f17] border-b border-stone-200 dark:border-emerald-950 text-stone-700 dark:text-stone-300 font-extrabold text-[11px] uppercase tracking-wider">
                                        <th class="py-3 px-4 w-12 text-center">Urut</th>
                                        <th class="py-3 px-4">Foto & Nama Bhikkhu</th>
                                        <th class="py-3 px-4">Gelar / Peran Spiritual</th>
                                        <th class="py-3 px-4 text-center">Status Tampil</th>
                                        <th class="py-3 px-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-stone-200/80 dark:divide-emerald-950/60 bg-white dark:bg-[#071710]">
                                    @forelse ($sanghaMembers as $member)
                                        <tr class="hover:bg-amber-50/50 dark:hover:bg-emerald-950/30 transition-colors">
                                            
                                            <!-- Sort Order -->
                                            <td class="py-3.5 px-4 text-center font-bold text-stone-500">
                                                <span class="w-6 h-6 rounded-lg bg-stone-100 dark:bg-emerald-950/80 inline-flex items-center justify-center font-mono font-bold text-xs">
                                                    {{ $member->sort_order }}
                                                </span>
                                            </td>

                                            <!-- Foto & Nama -->
                                            <td class="py-3.5 px-4">
                                                <div class="flex items-center gap-3">
                                                    <img 
                                                        src="{{ $member->photo_url }}" 
                                                        alt="{{ $member->name }}" 
                                                        class="w-10 h-10 rounded-full object-cover border-2 border-amber-500/60 shadow-xs ring-1 ring-amber-400/25 shrink-0" 
                                                    />
                                                    <div>
                                                        <div class="font-extrabold text-stone-900 dark:text-stone-100 text-xs sm:text-sm">
                                                            {{ $member->name }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Gelar / Peran -->
                                            <td class="py-3.5 px-4 text-stone-600 dark:text-stone-400">
                                                <span class="text-amber-900 dark:text-amber-300 font-bold text-[11px]">
                                                    {{ $member->title ?: 'Pembina Spiritual' }}
                                                </span>
                                            </td>

                                            <!-- Status Toggle -->
                                            <td class="py-3.5 px-4 text-center">
                                                <button 
                                                    wire:click="toggleSanghaStatus({{ $member->id }})" 
                                                    type="button" 
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold transition-all cursor-pointer {{ $member->is_active ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-500/30' : 'bg-stone-200/80 dark:bg-stone-800 text-stone-600 dark:text-stone-400 border border-stone-300 dark:border-stone-700' }}"
                                                >
                                                    <span class="w-1.5 h-1.5 rounded-full {{ $member->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-stone-400' }}"></span>
                                                    <span>{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                                </button>
                                            </td>

                                            <!-- Actions -->
                                            <td class="py-3.5 px-4 text-right">
                                                <div class="inline-flex items-center gap-1.5">
                                                    <button 
                                                        wire:click="openEditSanghaModal({{ $member->id }})" 
                                                        type="button" 
                                                        class="p-2 rounded-xl bg-stone-100 dark:bg-emerald-950/60 hover:bg-emerald-600 hover:text-white text-stone-700 dark:text-stone-300 transition-colors cursor-pointer"
                                                        title="Edit Data Bhikkhu"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </button>
                                                    <button 
                                                        wire:click="deleteSanghaMember({{ $member->id }})" 
                                                        wire:confirm="Apakah Anda yakin ingin menghapus data Bhikkhu '{{ $member->name }}'?"
                                                        type="button" 
                                                        class="p-2 rounded-xl bg-rose-500/10 hover:bg-rose-600 hover:text-white text-rose-600 dark:text-rose-400 transition-colors cursor-pointer"
                                                        title="Hapus Data Bhikkhu"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </td>

                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-14 text-center text-stone-400 space-y-3">
                                                <div class="w-12 h-12 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto">
                                                    <svg class="w-6 h-6 fill-none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="text-sm font-extrabold text-stone-700 dark:text-stone-300">Belum ada anggota Bhikkhu Sangha</div>
                                                    <p class="text-xs text-stone-400 max-w-sm mx-auto">Klik tombol <strong>+ Tambah Anggota Bhikkhu</strong> di atas untuk mendaftarkan Yang Mulia Bhikkhu Sangha.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            @endif

            <!-- =========================================================================
                 TAB 5: WA ADMIN ROTATOR (LOAD BALANCING CONTACTS)
                 ========================================================================= -->
            @if ($activeTab === 'rotator')
                <div class="space-y-6 text-xs">
                    
                    <!-- Premium Hero Header & Action Banner -->
                    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#092218] via-[#0E3324] to-[#071710] border border-emerald-500/25 p-6 sm:p-8 text-white shadow-xl">
                        
                        <!-- Background Ambient Glow & Sacred Geometry Aura -->
                        <div class="absolute -top-12 -right-12 w-64 h-64 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="absolute -bottom-12 -left-12 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
                        
                        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            
                            <!-- Left: Title & Explanatory Narrative -->
                            <div class="space-y-3 max-w-2xl">
                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-400/10 border border-emerald-400/25 text-emerald-300 font-extrabold text-[10.5px] uppercase tracking-wider shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>WhatsApp Load Balancer Engine</span>
                                </div>
                                
                                <div>
                                    <h3 class="text-xl sm:text-2xl font-black tracking-tight text-[#FAF5ED] flex items-center gap-2.5">
                                        <span>Rotator WhatsApp Multi-Admin</span>
                                    </h3>
                                    <p class="text-stone-300 text-xs sm:text-sm leading-relaxed mt-1.5">
                                        Kelola daftar kontak WhatsApp pengurus vihara tanpa batasan jumlah. Sistem secara otomatis membagi pesan dari tombol <strong>Konfirmasi Donasi</strong>, <strong>Hubungi Sekretariat</strong>, dan konsultasi secara acak ke admin yang berstatus aktif.
                                    </p>
                                </div>

                                <!-- Key Mini Badges -->
                                <div class="flex flex-wrap items-center gap-3 pt-1 text-[11px] text-stone-300">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>Distribusi Pesan Fair Balancing</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>1-Click Toggle On/Off</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>Fallback Otomatis ke Nomor Utama</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Quick Stat Chips & CTA Button -->
                            <div class="flex flex-col sm:flex-row lg:flex-col items-start sm:items-center lg:items-end justify-between gap-4 shrink-0">
                                <!-- Action Add Button -->
                                <button 
                                    wire:click="openCreateContactModal" 
                                    type="button" 
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 py-3 px-5 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-stone-950 font-black text-xs shadow-lg hover:shadow-amber-500/25 transition-all duration-200 cursor-pointer transform hover:-translate-y-0.5 active:scale-95 shrink-0"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.8" d="M12 4v16m8-8H4"/></svg>
                                    <span>Kontak Admin</span>
                                </button>

                            </div>

                        </div>
                    </div>

                    <!-- Table of Admin Contacts -->
                    <div class="rounded-3xl bg-white dark:bg-[#071710] border border-stone-200/90 dark:border-emerald-950/80 shadow-md overflow-hidden">
                        
                        <div class="p-4 bg-stone-50/70 dark:bg-[#05140d] border-b border-stone-200/80 dark:border-emerald-950 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                            <div class="font-extrabold text-stone-900 dark:text-stone-100 text-xs flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span>Daftar Kontak Admin Penerima Pesan Rotator</span>
                            </div>
                            <div class="text-[11px] text-stone-500 dark:text-stone-400 font-semibold">
                                Total: <strong class="text-stone-800 dark:text-stone-200">{{ $adminContacts->count() }}</strong> admin terdaftar
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-stone-100/60 dark:bg-[#040e09] border-b border-stone-200 dark:border-emerald-950/80 text-[10px] font-black text-stone-500 dark:text-stone-400 uppercase tracking-widest">
                                    <tr>
                                        <th scope="col" class="py-3.5 px-4 text-center w-12">No</th>
                                        <th scope="col" class="py-3.5 px-5 min-w-[220px]">Nama Admin / PIC</th>
                                        <th scope="col" class="py-3.5 px-5 min-w-[200px]">Nomor WhatsApp</th>
                                        <th scope="col" class="py-3.5 px-5 min-w-[180px]">Tugas / Divisi</th>
                                        <th scope="col" class="py-3.5 px-4 text-center min-w-[160px]">Status Rotator</th>
                                        <th scope="col" class="py-3.5 px-4 text-center w-28">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-stone-200/70 dark:divide-emerald-950/50 font-sans">
                                    @forelse ($adminContacts as $index => $contact)
                                        <tr class="hover:bg-emerald-50/40 dark:hover:bg-emerald-950/20 transition-colors">
                                            
                                            <!-- No -->
                                            <td class="py-4 px-4 text-center font-bold text-stone-400">{{ $index + 1 }}</td>

                                            <!-- PIC Info -->
                                            <td class="py-4 px-5">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-[#0D5B3A] to-emerald-600 text-white font-black text-xs flex items-center justify-center shrink-0 shadow-sm border border-emerald-400/30">
                                                        {{ strtoupper(substr($contact->name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <span class="font-extrabold text-stone-900 dark:text-stone-100 block text-xs sm:text-sm leading-snug">{{ $contact->name }}</span>
                                                        <span class="text-[10.5px] text-stone-400 font-medium">Prioritas Urutan: #{{ $contact->sort_order }}</span>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Phone & Direct Chat Link -->
                                            <td class="py-4 px-5">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono font-bold text-stone-900 dark:text-stone-100 text-xs sm:text-sm">
                                                        {{ $contact->phone }}
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- Role / Task -->
                                            <td class="py-4 px-5">
                                                <span class="inline-block text-stone-700 dark:text-stone-300 font-semibold text-xs">
                                                    {{ $contact->role ?: 'Sekretariat & Layanan' }}
                                                </span>
                                            </td>

                                            <!-- Status Toggle Pill -->
                                            <td class="py-4 px-4 text-center">
                                                <button 
                                                    wire:click="toggleContactStatus({{ $contact->id }})" 
                                                    type="button" 
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10.5px] font-extrabold transition-all cursor-pointer shadow-2xs {{ $contact->is_active ? 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border border-emerald-500/40 hover:bg-emerald-500/25' : 'bg-stone-200/80 dark:bg-stone-800 text-stone-500 border border-stone-300 dark:border-stone-700 hover:bg-stone-300' }}"
                                                    title="Klik untuk mengubah status aktif/nonaktif"
                                                >
                                                    <span class="w-2 h-2 rounded-full {{ $contact->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-stone-400' }}"></span>
                                                    <span>{{ $contact->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                                </button>
                                            </td>

                                            <!-- Action Buttons -->
                                            <td class="py-4 px-4 text-center">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <button 
                                                        wire:click="openEditContactModal({{ $contact->id }})" 
                                                        type="button" 
                                                        class="p-2 rounded-xl text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-emerald-950 border border-stone-200/80 dark:border-emerald-900/40 transition-all cursor-pointer shadow-2xs"
                                                        title="Edit Data Kontak"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </button>
                                                    <button 
                                                        wire:click="deleteContact({{ $contact->id }})" 
                                                        wire:confirm="Apakah Anda yakin ingin menghapus kontak admin ini dari sistem rotator?" 
                                                        type="button" 
                                                        class="p-2 rounded-xl text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200/80 dark:border-rose-900/40 transition-all cursor-pointer shadow-2xs"
                                                        title="Hapus Kontak dari Rotator"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </td>

                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-14 text-center text-stone-400 space-y-3">
                                                <div class="w-12 h-12 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto">
                                                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="text-sm font-extrabold text-stone-700 dark:text-stone-300">Belum ada kontak admin terdaftar</div>
                                                    <p class="text-xs text-stone-400 max-w-sm mx-auto">Klik tombol <strong>+ Tambah Kontak Admin WA</strong> di atas untuk mendaftarkan kontak pengurus.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            @endif

            <!-- Bottom Save CTA (Hanya untuk Tab yang Memiliki Form Pengaturan) -->
            @if (in_array($activeTab, ['yayasan', 'rekening', 'sosmed']))
                <div class="pt-4 border-t border-stone-200 dark:border-emerald-950 flex items-center justify-between">
                    <span class="text-[11px] text-stone-400">
                        Perubahan akan langsung aktif secara global di seluruh halaman website.
                    </span>
                    <button 
                        wire:click="saveSettings" 
                        type="button" 
                        class="py-2.5 px-6 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-md border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            @endif

        </div>

    </div>

    <!-- =========================================================================
         3. MODAL TAMBAH / EDIT KONTAK ADMIN WHATSAPP (TELEPORTED)
         ========================================================================= -->
    <template x-teleport="body">
        <div 
            x-show="$wire.contactModalOpen" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/80 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            @keydown.escape.window="$wire.closeContactModal"
        >
            <div 
                @click.away="$wire.closeContactModal" 
                class="relative max-w-lg w-full bg-white dark:bg-[#0b1f17] rounded-3xl overflow-hidden border border-stone-300/80 dark:border-emerald-500/30 shadow-2xl flex flex-col my-auto max-h-[92vh]"
            >
                <!-- Modal Header -->
                <div class="p-6 bg-gradient-to-r from-[#0B2117] to-[#0F3325] text-white flex items-center justify-between border-b border-emerald-900">
                    <div class="space-y-0.5">
                        <h3 class="text-base sm:text-lg font-extrabold text-[#FAF5ED] flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            <span>{{ $editingContactId ? 'Edit Kontak Admin WhatsApp' : 'Tambah Kontak Admin WhatsApp Baru' }}</span>
                        </h3>
                        <p class="text-xs text-stone-300">
                            Masukkan data kontak admin untuk didaftarkan ke sistem rotator otomatis.
                        </p>
                    </div>
                    <button 
                        @click="$wire.closeContactModal" 
                        type="button" 
                        class="p-2 rounded-full text-stone-400 hover:text-white bg-black/30 hover:bg-black/50 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form Body -->
                <form wire:submit.prevent="saveContact" class="p-6 space-y-4 overflow-y-auto text-xs custom-scrollbar">
                    
                    <!-- 1. Nama Admin / PIC -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Nama Admin / PIC <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="contactName" 
                            type="text" 
                            required 
                            placeholder="cth: Admin Upasaka Budi / Sdri. Ratna Dewi" 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-emerald-500 font-semibold"
                        />
                        @error('contactName') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                    </div>

                    <!-- 2. Nomor WhatsApp -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Nomor WhatsApp <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="contactPhone" 
                            type="text" 
                            required 
                            placeholder="cth: 081234567890 / +62 812-3456-7890" 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-emerald-500 font-mono font-bold"
                        />
                        @error('contactPhone') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                        <span class="text-[10.5px] text-stone-400">Bisa menggunakan awalan 08 atau +62.</span>
                    </div>

                    <!-- 3. Tugas / Divisi -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Tugas / Divisi Admin
                        </label>
                        <input 
                            wire:model="contactRole" 
                            type="text" 
                            placeholder="cth: Konfirmasi Dāna & Keuangan / Sekretariat Umum" 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-emerald-500"
                        />
                        @error('contactRole') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                    </div>

                    <!-- 4. Urutan & Status Aktif -->
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Nomor Urut
                            </label>
                            <input 
                                wire:model="contactSortOrder" 
                                type="number" 
                                min="0" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-emerald-500"
                            />
                        </div>

                        <div class="space-y-1.5 flex flex-col justify-end">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 cursor-pointer">
                                <input 
                                    wire:model="contactIsActive" 
                                    type="checkbox" 
                                    class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500"
                                />
                                <span class="font-bold text-stone-800 dark:text-stone-200 text-xs">Status Aktif</span>
                            </label>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="pt-4 border-t border-stone-200 dark:border-emerald-950 flex items-center justify-end gap-2.5">
                        <button 
                            @click="$wire.closeContactModal" 
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
                            <span>{{ $editingContactId ? 'Simpan Perubahan Kontak' : 'Simpan Kontak Admin' }}</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </template>

    <!-- =========================================================================
         4. MODAL TAMBAH / EDIT ANGGOTA BHIKKHU SANGHA (TELEPORTED)
         ========================================================================= -->
    <template x-teleport="body">
        <div 
            x-show="$wire.sanghaModalOpen" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/80 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            @keydown.escape.window="$wire.closeSanghaModal"
        >
            <div 
                @click.away="$wire.closeSanghaModal" 
                class="relative max-w-lg w-full bg-white dark:bg-[#0b1f17] rounded-3xl overflow-hidden border border-amber-500/30 shadow-2xl flex flex-col my-auto max-h-[92vh]"
            >
                <!-- Modal Header -->
                <div class="p-6 bg-gradient-to-r from-[#092218] via-[#0E3324] to-[#071710] text-white flex items-center justify-between border-b border-amber-500/30">
                    <div class="space-y-0.5">
                        <h3 class="text-base sm:text-lg font-extrabold text-[#FAF5ED] flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>{{ $editingSanghaId ? 'Edit Data Bhikkhu Sangha' : 'Tambah Anggota Bhikkhu Sangha' }}</span>
                        </h3>
                        <p class="text-xs text-stone-300">
                            Masukkan informasi profil Yang Mulia Bhikkhu Sangha.
                        </p>
                    </div>
                    <button 
                        @click="$wire.closeSanghaModal" 
                        type="button" 
                        class="p-2 rounded-full text-stone-400 hover:text-white bg-black/30 hover:bg-black/50 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form Body -->
                <form wire:submit.prevent="saveSanghaMember" class="p-6 space-y-4 overflow-y-auto text-xs custom-scrollbar">
                    
                    <!-- Foto Bhikkhu Sangha -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Foto Potret Bhikkhu Sangha
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="relative w-20 h-20 rounded-full border-2 border-dashed border-amber-500/50 p-0.5 bg-stone-100 dark:bg-emerald-950/80 overflow-hidden flex items-center justify-center shrink-0">
                                @if ($sanghaMemberPhoto)
                                    <img src="{{ $sanghaMemberPhoto->temporaryUrl() }}" class="w-full h-full object-cover rounded-full" />
                                @elseif ($existingSanghaMemberPhoto)
                                    <img src="{{ asset($existingSanghaMemberPhoto) }}" class="w-full h-full object-cover rounded-full" />
                                @else
                                    <svg class="w-8 h-8 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                @endif
                            </div>
                            <div class="flex-1 space-y-1.5">
                                <input 
                                    wire:model="sanghaMemberPhoto" 
                                    type="file" 
                                    accept="image/png,image/jpeg,image/jpg,image/webp" 
                                    class="w-full text-xs text-stone-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-500/15 file:text-amber-900 dark:file:text-amber-300 hover:file:bg-amber-500/25 file:cursor-pointer cursor-pointer"
                                />
                                <span class="text-[10.5px] text-stone-400 block">Format: JPG, PNG, WebP (Maks. 10MB) • Rasio 1:1 bulat</span>
                                @error('sanghaMemberPhoto') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Nama Lengkap Bhikkhu -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Nama Lengkap & Gelar Kebikkuan <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="sanghaMemberName" 
                            type="text" 
                            required 
                            placeholder="cth: Bhikkhu Uttamo Mahāthera" 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-semibold"
                        />
                        @error('sanghaMemberName') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Peran / Jabatan Spiritual -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Gelar / Peran Spiritual
                        </label>
                        <input 
                            wire:model="sanghaMemberTitle" 
                            type="text" 
                            placeholder="cth: Pembina Spiritual & Sanghanāyaka / Kepala Vihara" 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-semibold"
                        />
                        @error('sanghaMemberTitle') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Urutan & Status Aktif -->
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Nomor Urut
                            </label>
                            <input 
                                wire:model="sanghaMemberSortOrder" 
                                type="number" 
                                min="0" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-bold"
                            />
                        </div>

                        <div class="space-y-1.5 flex flex-col justify-end">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 cursor-pointer">
                                <input 
                                    wire:model="sanghaMemberIsActive" 
                                    type="checkbox" 
                                    class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500"
                                />
                                <span class="font-bold text-stone-800 dark:text-stone-200 text-xs">Tampilkan di Publik</span>
                            </label>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="pt-4 border-t border-stone-200 dark:border-emerald-950 flex items-center justify-end gap-2.5">
                        <button 
                            @click="$wire.closeSanghaModal" 
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
                            <span>{{ $editingSanghaId ? 'Simpan Perubahan Data' : 'Simpan Anggota Bhikkhu' }}</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </template>

</div>

