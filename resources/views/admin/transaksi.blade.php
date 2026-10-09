<?php

use App\Models\CertificateTemplate;
use App\Models\Donation;
use App\Models\DonationProgram;
use App\Models\IssuedCertificate;
use App\Services\DonationImportService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component
{
    use WithFileUploads, WithPagination;

    public string $searchQuery = '';
    public string $activeStatusTab = 'semua'; // 'semua', 'verified', 'pending', 'rejected'
    public bool $createModalOpen = false;
    public bool $detailModalOpen = false;
    public bool $importModalOpen = false;
    public bool $printModalOpen = false;
    public string $printProgramId = '';
    public string $printStatus = 'verified';
    public $importFile = null;
    public ?array $importSummary = null;
    public ?Donation $selectedDonation = null;
    public ?string $feedbackMessage = null;

    // Form inputs for recording new donation
    public ?int $editingDonationId = null;
    public ?int $formProgramId = null;
    public string $formDonorName = '';
    public string $formPhone = '';
    public string $formCategory = 'umum'; // 'umum' (Donatur Umum), 'alm' (Pelimpahan Jasa Alm.)
    public ?int $formCertificateTemplateId = null;
    public string $formAmount = '';

    public function setTab(string $tab): void
    {
        $this->activeStatusTab = $tab;
        $this->resetPage();
    }

    public function updatedFormCategory(string $value): void
    {
        $defaultTpl = CertificateTemplate::where('status', 'aktif')
            ->where('category', $value)
            ->first();
        $this->formCertificateTemplateId = $defaultTpl?->id;
    }

    public function updatedFormProgramId(?int $value): void
    {
        if ($value && $this->formCategory === 'umum') {
            $prog = DonationProgram::find($value);
            if ($prog && $prog->certificate_template_id) {
                $this->formCertificateTemplateId = $prog->certificate_template_id;
            }
        }
    }

    public function openCreateModal(): void
    {
        $this->reset(['formDonorName', 'formPhone', 'formAmount']);
        $this->editingDonationId = null;
        $this->formCategory = 'umum';
        $this->formCertificateTemplateId = null;
        
        $firstProgram = DonationProgram::where('status', 'aktif')->first();
        if ($firstProgram) {
            $this->formProgramId = $firstProgram->id;
            if ($firstProgram->certificate_template_id) {
                $this->formCertificateTemplateId = $firstProgram->certificate_template_id;
            }
        }

        if (!$this->formCertificateTemplateId) {
            $defaultTpl = CertificateTemplate::where('status', 'aktif')
                ->where('category', 'umum')
                ->first();
            $this->formCertificateTemplateId = $defaultTpl?->id;
        }

        $this->createModalOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $donation = Donation::with(['donationProgram', 'issuedCertificate.certificateTemplate'])->find($id);
        if (!$donation) return;

        $this->editingDonationId = $donation->id;
        $this->formProgramId = $donation->donation_program_id;
        $this->formDonorName = $donation->donor_name;
        $this->formPhone = $donation->phone ?? '';
        $isAlm = ($donation->donor_message && str_contains(strtolower($donation->donor_message), 'pattid')) || str_contains(strtolower($donation->donor_name), 'alm');
        $this->formCategory = $isAlm ? 'alm' : 'umum';
        $this->formCertificateTemplateId = $donation->issuedCertificate?->certificate_template_id;
        $this->formAmount = (string) $donation->amount;

        $this->detailModalOpen = false;
        $this->createModalOpen = true;
    }

    public function openDetailModal(int $id): void
    {
        $this->selectedDonation = Donation::with(['donationProgram', 'issuedCertificate.certificateTemplate'])->find($id);
        if ($this->selectedDonation) {
            $this->detailModalOpen = true;
        }
    }

    public function openImportModal(): void
    {
        $this->reset(['importFile', 'importSummary']);
        $this->importModalOpen = true;
    }

    public function openPrintModal(): void
    {
        $this->printProgramId = '';
        $this->printStatus = $this->activeStatusTab === 'semua' ? 'semua' : $this->activeStatusTab;
        $this->printModalOpen = true;
    }

    public function importExcel(DonationImportService $service): void
    {
        $this->validate([
            'importFile' => 'required|file|max:20480',
        ], [
            'importFile.required' => 'Pilih file Excel (.xlsx) atau CSV terlebih dahulu.',
            'importFile.max' => 'Ukuran file maksimal 20MB.',
        ]);

        $path = $this->importFile->getRealPath();
        $result = $service->import($path);
        $this->importSummary = $result;

        if ($result['imported'] > 0) {
            $progCount = count($result['created_programs']);
            $msg = "Berhasil mengimpor {$result['imported']} data donasi.";
            if ($progCount > 0) {
                $msg .= " ({$progCount} program baru otomatis dibuat).";
            }
            $this->feedbackMessage = $msg;
        } else {
            $this->feedbackMessage = "Tidak ada data yang berhasil diimpor. Periksa format file Anda.";
        }

        $this->importFile = null;
        $this->resetPage();
    }

    public function saveDonation(): void
    {
        $this->validate([
            'formProgramId' => 'required|exists:donation_programs,id',
            'formDonorName' => 'required|string|max:255',
            'formCategory' => 'required|in:umum,alm',
            'formAmount' => 'required|numeric|min:1000',
            'formCertificateTemplateId' => 'nullable|exists:certificate_templates,id',
        ], [
            'formProgramId.required' => 'Program donasi wajib dipilih.',
            'formDonorName.required' => 'Nama donatur wajib diisi.',
            'formAmount.required' => 'Nominal dana wajib diisi.',
            'formAmount.min' => 'Nominal dana minimal Rp 1.000.',
        ]);

        $cleanAmount = (int) $this->formAmount;
        $categoryLabel = $this->formCategory === 'alm' ? 'Pelimpahan Jasa (Alm.)' : 'Donatur Umum';

        if ($this->editingDonationId) {
            $donation = Donation::find($this->editingDonationId);
            if ($donation) {
                $donation->update([
                    'donation_program_id' => $this->formProgramId,
                    'donor_name' => $this->formDonorName,
                    'phone' => $this->formPhone ?: null,
                    'amount' => $cleanAmount,
                    'total_amount' => $cleanAmount,
                    'donor_message' => $this->formCategory === 'alm' ? 'Pelimpahan Jasa (Pattidāna / Alm.)' : null,
                ]);

                // Sync issued certificate
                if ($this->formCertificateTemplateId) {
                    if ($donation->issuedCertificate) {
                        $donation->issuedCertificate->update([
                            'certificate_template_id' => $this->formCertificateTemplateId,
                            'recipient_name' => $donation->donor_name,
                            'amount' => $cleanAmount,
                        ]);
                    } else {
                        $certNumber = 'PA/' . date('Y/m') . '/' . str_pad($donation->id, 4, '0', STR_PAD_LEFT);
                        IssuedCertificate::create([
                            'certificate_number' => $certNumber,
                            'donation_id' => $donation->id,
                            'certificate_template_id' => $this->formCertificateTemplateId,
                            'recipient_name' => $donation->donor_name,
                            'amount' => $cleanAmount,
                            'issued_date' => now(),
                        ]);
                    }
                } else {
                    $donation->issuedCertificate()?->delete();
                }

                $this->createModalOpen = false;
                $this->feedbackMessage = "Data donasi {$donation->invoice_number} berhasil diperbarui.";
                $this->editingDonationId = null;
                return;
            }
        }

        $datePrefix = date('Ymd');
        $countToday = Donation::whereDate('created_at', today())->count() + 1;
        $invoiceNumber = 'DN-' . $datePrefix . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

        $donation = Donation::create([
            'invoice_number' => $invoiceNumber,
            'donation_program_id' => $this->formProgramId,
            'donor_name' => $this->formDonorName,
            'phone' => $this->formPhone ?: null,
            'amount' => $cleanAmount,
            'total_amount' => $cleanAmount,
            'payment_method' => 'transfer',
            'donor_message' => $this->formCategory === 'alm' ? 'Pelimpahan Jasa (Pattidāna / Alm.)' : null,
            'status' => 'verified',
        ]);

        // Auto create issued certificate if template selected
        if ($this->formCertificateTemplateId) {
            $certNumber = 'PA/' . date('Y/m') . '/' . str_pad($donation->id, 4, '0', STR_PAD_LEFT);
            IssuedCertificate::create([
                'certificate_number' => $certNumber,
                'donation_id' => $donation->id,
                'certificate_template_id' => $this->formCertificateTemplateId,
                'recipient_name' => $donation->donor_name,
                'amount' => $cleanAmount,
                'issued_date' => now(),
            ]);
        }

        $this->createModalOpen = false;
        $this->feedbackMessage = "Pencatatan donasi {$categoryLabel} dari '{$this->formDonorName}' sebesar Rp " . number_format($cleanAmount, 0, ',', '.') . " berhasil disimpan ({$invoiceNumber}).";
    }

    public function verifyDonation(int $id): void
    {
        $donation = Donation::find($id);
        if ($donation) {
            $donation->update(['status' => 'verified']);
            $this->feedbackMessage = "Donasi invoice {$donation->invoice_number} berhasil diverifikasi.";
            if ($this->selectedDonation && $this->selectedDonation->id === $id) {
                $this->selectedDonation->refresh();
            }
        }
    }

    public function deleteDonation(int $id): void
    {
        $donation = Donation::find($id);
        if ($donation) {
            $inv = $donation->invoice_number;
            $donation->issuedCertificate()?->delete();
            $donation->delete();
            $this->feedbackMessage = "Data donasi {$inv} berhasil dihapus.";
            if ($this->selectedDonation && $this->selectedDonation->id === $id) {
                $this->detailModalOpen = false;
            }
        }
    }

    public function render()
    {
        $query = Donation::with(['donationProgram', 'issuedCertificate.certificateTemplate'])->latest();

        if ($this->activeStatusTab !== 'semua') {
            $query->where('status', $this->activeStatusTab);
        }

        if (!empty($this->searchQuery)) {
            $search = '%' . strtolower($this->searchQuery) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(invoice_number) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(donor_name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(phone) LIKE ?', [$search])
                  ->orWhereHas('donationProgram', function ($sub) use ($search) {
                      $sub->whereRaw('LOWER(title) LIKE ?', [$search]);
                  });
            });
        }

        $donations = $query->paginate(15);
        $programs = DonationProgram::orderBy('title')->get();

        // Available certificate templates for the selected category in form
        $availableTemplates = CertificateTemplate::where('status', 'aktif')
            ->where('category', $this->formCategory)
            ->get();

        // Summary metrics
        $totalDanaMasuk = Donation::where('status', 'verified')->sum('amount');
        $totalDonatur = Donation::where('status', 'verified')->count();
        $totalAlm = Donation::where('status', 'verified')->where('donor_message', 'like', '%Pattid%')->count();

        // Print query & data
        $printDonations = collect();
        $printTotalNominal = 0;
        if ($this->printModalOpen) {
            $printQuery = Donation::with('donationProgram')->latest();
            if ($this->printStatus !== 'semua') {
                $printQuery->where('status', $this->printStatus);
            }
            if (!empty($this->printProgramId)) {
                $printQuery->where('donation_program_id', $this->printProgramId);
            }
            if (!empty($this->searchQuery)) {
                $search = '%' . strtolower($this->searchQuery) . '%';
                $printQuery->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(invoice_number) LIKE ?', [$search])
                      ->orWhereRaw('LOWER(donor_name) LIKE ?', [$search])
                      ->orWhereRaw('LOWER(phone) LIKE ?', [$search])
                      ->orWhereHas('donationProgram', function ($sub) use ($search) {
                          $sub->whereRaw('LOWER(title) LIKE ?', [$search]);
                      });
                });
            }
            $printDonations = $printQuery->get();
            $printTotalNominal = $printDonations->sum('amount');
        }

        return $this->view([
            'donations' => $donations,
            'programs' => $programs,
            'availableTemplates' => $availableTemplates,
            'totalDanaMasuk' => $totalDanaMasuk,
            'totalDonatur' => $totalDonatur,
            'totalAlm' => $totalAlm,
            'printDonations' => $printDonations,
            'printTotalNominal' => $printTotalNominal,
        ])->title('Pencatatan Donatur & Transaksi Dāna')->layout('layouts::admin');
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
                    Transaksi & Riwayat Donatur
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Pencatatan donatur masuk, penerbitan piagam anumodana/pattidāna, dan riwayat dāna per program.
            </p>
        </div>

        <!-- CTA Input Donatur & Import & Cetak -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button 
                wire:click="openPrintModal" 
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-white dark:bg-emerald-950/70 hover:bg-stone-50 dark:hover:bg-emerald-900/80 text-stone-800 dark:text-stone-200 font-bold text-xs shadow-xs border border-stone-200 dark:border-emerald-500/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Laporan</span>
            </button>

            <button 
                wire:click="openImportModal" 
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-white dark:bg-emerald-950/70 hover:bg-stone-50 dark:hover:bg-emerald-900/80 text-stone-800 dark:text-stone-200 font-bold text-xs shadow-xs border border-stone-200 dark:border-emerald-500/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <span>Import Excel / CSV</span>
            </button>

            <button 
                wire:click="openCreateModal" 
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-xs border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Transaksi</span>
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
         2. TABEL DATA TRANSAKSI & DONATUR MASUK
         ========================================================================= -->
    <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden">
        
        <!-- Table Toolbar: Search Input -->
        <div class="p-4 sm:p-5 border-b border-stone-200/80 dark:border-emerald-950 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            
            <div class="flex items-center gap-3">
                <span class="text-xs font-extrabold text-[#0D6E42] dark:text-emerald-300 uppercase tracking-wider">
                    Daftar Donatur Terverifikasi ({{ $totalDonatur }})
                </span>
                <span class="text-stone-300 dark:text-stone-700">•</span>
                <span class="text-xs text-stone-500 dark:text-stone-400">
                    Total Dāna: <strong class="text-stone-800 dark:text-stone-100">Rp {{ number_format($totalDanaMasuk, 0, ',', '.') }}</strong>
                </span>
            </div>

            <!-- Search Input -->
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    wire:model.live.debounce.250ms="searchQuery" 
                    type="text" 
                    placeholder="Cari nomor invoice, donatur, WhatsApp..." 
                    class="w-full pl-9 pr-3.5 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-[#0D5B3A] focus:ring-1 focus:ring-[#0D5B3A] transition-all shadow-2xs"
                />
            </div>
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-stone-50/80 dark:bg-[#071710] border-b border-stone-200/80 dark:border-emerald-950 text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3.5 px-5">Invoice & Tanggal</th>
                        <th scope="col" class="py-3.5 px-4">Nama Donatur / WhatsApp</th>
                        <th scope="col" class="py-3.5 px-4">Program Kebajikan</th>
                        <th scope="col" class="py-3.5 px-4">Nominal Dāna</th>
                        <th scope="col" class="py-3.5 px-4">Desain Piagam</th>
                        <th scope="col" class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-emerald-950/60">
                    @forelse ($donations as $don)
                        @php
                            $isAlm = ($don->donor_message && str_contains(strtolower($don->donor_message), 'pattid')) || str_contains(strtolower($don->donor_name), 'alm');
                            $cert = $don->issuedCertificate;
                        @endphp
                        <tr class="hover:bg-stone-50/60 dark:hover:bg-emerald-950/20 transition-colors">
                            
                            <!-- Invoice & Tanggal -->
                            <td class="py-3.5 px-5">
                                <div class="flex flex-col">
                                    <span class="font-extrabold text-stone-900 dark:text-stone-100 text-xs">
                                        {{ $don->invoice_number }}
                                    </span>
                                    <span class="text-[11px] text-stone-400 mt-0.5">
                                        {{ $don->created_at->format('d M Y, H:i') }}
                                    </span>
                                </div>
                            </td>

                            <!-- Donatur & WhatsApp -->
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="font-extrabold text-stone-900 dark:text-stone-100 text-xs">
                                            {{ $don->donor_name }}
                                        </span>
                                        @if ($isAlm)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 text-[9.5px] font-black">
                                                <svg class="w-3 h-3 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                                                <span>Alm.</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border border-emerald-500/30 text-[9.5px] font-black">
                                                <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                                <span>Umum</span>
                                            </span>
                                        @endif
                                    </div>
                                    @if ($don->phone)
                                        <div class="flex items-center gap-1 text-[11px] text-stone-500 dark:text-stone-400">
                                            <span>WA:</span>
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $don->phone) }}" target="_blank" class="text-emerald-700 dark:text-emerald-400 hover:underline font-semibold">
                                                {{ $don->phone }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Program Donasi -->
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-xs text-stone-800 dark:text-stone-200 block" title="{{ $don->donationProgram?->title ?? 'Dāna Umum Vihara' }}">
                                    {{ $don->donationProgram?->title ?? 'Dāna Umum Vihara' }}
                                </span>
                                <span class="text-[10px] text-stone-400">
                                    {{ $don->donationProgram?->category ?? 'Sarana & Operasional' }}
                                </span>
                            </td>

                            <!-- Nominal -->
                            <td class="py-3.5 px-4">
                                <span class="font-black text-[#0D5B3A] dark:text-emerald-400 tabular-nums text-sm">
                                    Rp {{ number_format($don->amount, 0, ',', '.') }}
                                </span>
                            </td>

                            <!-- Desain Piagam -->
                            <td class="py-3.5 px-4">
                                @if ($cert)
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-xs text-stone-800 dark:text-stone-200 block truncate max-w-[180px]">
                                            {{ $cert->certificateTemplate?->name ?? 'Piagam Terbit' }}
                                        </span>
                                        <span class="text-[10px] text-amber-700 dark:text-amber-300 font-semibold block">
                                            {{ $cert->certificate_number }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-stone-400 text-[11px] italic">Tanpa Piagam</span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    
                                    <!-- Rincian Donasi -->
                                    <button 
                                        wire:click="openDetailModal({{ $don->id }})" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-600 dark:text-stone-300 transition-all cursor-pointer border border-stone-200/80 dark:border-emerald-500/20 shadow-2xs"
                                        title="Lihat Rincian & Kuitansi"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>

                                    <!-- Edit Donasi -->
                                    <button 
                                        wire:click="openEditModal({{ $don->id }})" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-900/60 text-amber-700 dark:text-amber-400 transition-all cursor-pointer border border-amber-500/20 shadow-2xs"
                                        title="Edit Transaksi & Donatur"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <!-- Hapus -->
                                    <button 
                                        wire:click="deleteDonation({{ $don->id }})" 
                                        wire:confirm="Apakah Anda yakin ingin menghapus data donasi ini?" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 transition-all cursor-pointer border border-rose-500/20 shadow-2xs"
                                        title="Hapus Transaksi"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-stone-400">
                                Tidak ada transaksi donasi yang sesuai dengan pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer Pagination -->
        <div class="px-5 py-4 bg-stone-50/80 dark:bg-[#071710] border-t border-stone-200/80 dark:border-emerald-950/70">
            {{ $donations->onEachSide(1)->links('components.custom-pagination') }}
        </div>

    </div>

    <!-- =========================================================================
         3. MODAL INPUT DONATUR MASUK (TELEPORTED)
         ========================================================================= -->
    <template x-teleport="body">
        <div 
            x-show="$wire.createModalOpen" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/80 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            @keydown.escape.window="$wire.createModalOpen = false"
        >
            <div 
                @click.away="$wire.createModalOpen = false" 
                class="relative max-w-xl w-full bg-white dark:bg-[#0b1f17] rounded-3xl overflow-hidden border border-stone-300/80 dark:border-emerald-500/30 shadow-2xl flex flex-col my-auto max-h-[92vh]"
            >
                <!-- Header -->
                <div class="p-6 bg-gradient-to-r from-[#0B2117] to-[#0F3325] text-white flex items-center justify-between border-b border-emerald-900">
                    <div class="space-y-0.5">
                        <h3 class="text-lg font-extrabold text-[#FAF5ED]">
                            {{ $editingDonationId ? 'Edit Data Donasi & Donatur' : 'Input Donatur & Dāna Masuk' }}
                        </h3>
                        <p class="text-xs text-stone-300">
                            {{ $editingDonationId ? 'Perbarui informasi donatur, program kebajikan, nominal, atau desain piagam.' : 'Catat penerimaan dāna donatur dan terbitkan piagam penghargaan.' }}
                        </p>
                    </div>
                    <button 
                        @click="$wire.createModalOpen = false" 
                        type="button" 
                        class="p-2 rounded-full text-stone-400 hover:text-white bg-black/30 hover:bg-black/50 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form -->
                <form wire:submit.prevent="saveDonation" class="p-6 space-y-4 overflow-y-auto text-xs">
                    
                    <!-- Program Tujuan -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Program Donasi Tujuan <span class="text-amber-500">*</span>
                        </label>
                        <select 
                            wire:model="formProgramId" 
                            required 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        >
                            @foreach ($programs as $prog)
                                <option value="{{ $prog->id }}">{{ $prog->title }} ({{ $prog->category }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Nama Donatur & Nomor WhatsApp -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Nama Donatur / Keluarga <span class="text-amber-500">*</span>
                            </label>
                            <input 
                                wire:model="formDonorName" 
                                type="text" 
                                required 
                                placeholder="{{ $formCategory === 'alm' ? 'cth: Alm. Hendra Tanujaya' : 'cth: Keluarga Bpk. Hendra Tanujaya' }}"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Nomor WhatsApp
                            </label>
                            <input 
                                wire:model="formPhone" 
                                type="text" 
                                placeholder="cth: 081234567890"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10"
                            />
                        </div>
                    </div>

                    <!-- PILIHAN OPSI JENIS PIAGAM -->
                    <div class="space-y-2">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Jenis / Peruntukan Piagam <span class="text-amber-500">*</span>
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- Option 1: Piagam Donatur (Umum) -->
                            <label 
                                class="relative p-3 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between space-y-1.5 {{ $formCategory === 'umum' ? 'bg-emerald-50/70 dark:bg-emerald-950/50 border-[#0D5B3A] dark:border-emerald-400 shadow-xs' : 'bg-stone-50 dark:bg-[#071710] border-stone-200 dark:border-emerald-500/20 hover:border-stone-300' }}"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg {{ $formCategory === 'umum' ? 'bg-emerald-600 text-white' : 'bg-stone-200 dark:bg-emerald-950 text-stone-600 dark:text-stone-300' }} flex items-center justify-center font-bold text-xs">
                                            <svg class="w-4 h-4 {{ $formCategory === 'umum' ? 'text-amber-300' : 'text-stone-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-stone-900 dark:text-stone-100 text-xs">Piagam Donatur</div>
                                            <div class="text-[10px] text-emerald-700 dark:text-emerald-400 font-semibold">Umum / Hidup</div>
                                        </div>
                                    </div>
                                    <input 
                                        type="radio" 
                                        wire:model.live="formCategory" 
                                        value="umum" 
                                        class="text-[#0D5B3A] focus:ring-emerald-500"
                                    />
                                </div>
                            </label>

                            <!-- Option 2: Pelimpahan Jasa (Alm.) -->
                            <label 
                                class="relative p-3 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between space-y-1.5 {{ $formCategory === 'alm' ? 'bg-amber-50/70 dark:bg-amber-950/50 border-amber-600 dark:border-amber-400 shadow-xs' : 'bg-stone-50 dark:bg-[#071710] border-stone-200 dark:border-emerald-500/20 hover:border-stone-300' }}"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg {{ $formCategory === 'alm' ? 'bg-amber-600 text-white' : 'bg-stone-200 dark:bg-emerald-950 text-stone-600 dark:text-stone-300' }} flex items-center justify-center font-bold text-xs">
                                            <svg class="w-4 h-4 {{ $formCategory === 'alm' ? 'text-amber-200' : 'text-stone-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-stone-900 dark:text-stone-100 text-xs">Pelimpahan Jasa</div>
                                            <div class="text-[10px] text-amber-700 dark:text-amber-400 font-semibold">Pattidāna / Alm.</div>
                                        </div>
                                    </div>
                                    <input 
                                        type="radio" 
                                        wire:model.live="formCategory" 
                                        value="alm" 
                                        class="text-amber-600 focus:ring-amber-500"
                                    />
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- PILIHAN DESAIN TEMPLATE PIAGAM (OTOMATIS + PILIHAN DESAIN) -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Pilihan Desain Template Piagam
                            </label>
                            <span class="text-[10.5px] text-stone-400">
                                (Otomatis terpilih sesuai jenis)
                            </span>
                        </div>

                        @if ($availableTemplates->count() > 0)
                            <select 
                                wire:model="formCertificateTemplateId" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            >
                                @foreach ($availableTemplates as $t)
                                    <option value="{{ $t->id }}">
                                        {{ $t->name }} ({{ ucfirst($t->orientation) }})
                                    </option>
                                @endforeach
                            </select>

                            @php
                                $currentTpl = $availableTemplates->firstWhere('id', $formCertificateTemplateId);
                            @endphp
                            @if ($currentTpl)
                                <div class="p-2.5 rounded-xl bg-stone-100/70 dark:bg-emerald-950/40 border border-stone-200/80 dark:border-emerald-500/20 flex items-center justify-between text-[11px]">
                                    <span class="text-stone-600 dark:text-stone-300 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Desain Aktif:</span>
                                        <strong class="text-stone-800 dark:text-stone-100">{{ $currentTpl->name }}</strong>
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md bg-stone-200 dark:bg-emerald-900/60 text-stone-700 dark:text-stone-300 font-bold uppercase text-[9px]">
                                        {{ $currentTpl->orientation }}
                                    </span>
                                </div>
                            @endif
                        @else
                            <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs">
                                Belum ada template piagam aktif untuk kategori ini. Piagam akan diterbitkan tanpa latar belakang khusus atau dapat diunggah di menu <a href="{{ route('admin.piagam') }}" class="underline font-bold">Desain Piagam</a>.
                            </div>
                        @endif
                    </div>

                    <!-- Nominal Dāna -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Nominal Dāna (Rp) <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model.live="formAmount" 
                            type="text" 
                            required 
                            placeholder="cth: 1000000"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10"
                        />
                        @if ($formAmount && is_numeric($formAmount))
                            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">
                                Nominal: Rp {{ number_format((int)$formAmount, 0, ',', '.') }}
                            </div>
                        @endif
                    </div>

                    <!-- Footer Buttons -->
                    <div class="pt-4 border-t border-stone-200 dark:border-emerald-950 flex items-center justify-end gap-2.5">
                        <button 
                            @click="$wire.createModalOpen = false" 
                            type="button" 
                            class="py-2.5 px-4 rounded-xl bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-700 dark:text-stone-300 font-bold text-xs transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="py-2.5 px-5 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-md border border-amber-400/30 transition-all cursor-pointer"
                        >
                            {{ $editingDonationId ? 'Simpan Perubahan' : 'Simpan Donatur Masuk' }}
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </template>

    <!-- =========================================================================
         4. MODAL DETAIL TRANSAKSI & KUITANSI (TELEPORTED)
         ========================================================================= -->
    @if ($selectedDonation)
        <template x-teleport="body">
            <div 
                x-show="$wire.detailModalOpen" 
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/80 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
                @keydown.escape.window="$wire.detailModalOpen = false"
            >
                <div 
                    @click.away="$wire.detailModalOpen = false" 
                    class="relative max-w-lg w-full bg-white dark:bg-[#0b1f17] rounded-3xl overflow-hidden border border-stone-300/80 dark:border-emerald-500/30 shadow-2xl flex flex-col my-auto max-h-[92vh]"
                >
                    <!-- Header -->
                    <div class="p-6 bg-[#0B2117] text-white flex items-center justify-between border-b border-emerald-900">
                        <div class="space-y-0.5">
                            <span class="text-xs text-amber-300 font-bold uppercase tracking-wider">Bukti Tanda Terima Dāna</span>
                            <h3 class="text-base font-extrabold text-[#FAF5ED]">{{ $selectedDonation->invoice_number }}</h3>
                        </div>
                        <button 
                            @click="$wire.detailModalOpen = false" 
                            type="button" 
                            class="p-2 rounded-full text-stone-400 hover:text-white bg-black/30 hover:bg-black/50 transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="p-6 space-y-4 text-xs overflow-y-auto">
                        <div class="p-4 rounded-2xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950/80 space-y-3">
                            <div class="flex justify-between items-center pb-2 border-b border-stone-200/60 dark:border-emerald-900/40">
                                <span class="text-stone-500">Nama Donatur</span>
                                <span class="font-extrabold text-stone-900 dark:text-stone-100">{{ $selectedDonation->donor_name }}</span>
                            </div>
                            @if ($selectedDonation->phone)
                                <div class="flex justify-between items-center pb-2 border-b border-stone-200/60 dark:border-emerald-900/40">
                                    <span class="text-stone-500">Nomor WhatsApp</span>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $selectedDonation->phone) }}" target="_blank" class="font-bold text-emerald-700 dark:text-emerald-400 hover:underline">
                                        {{ $selectedDonation->phone }}
                                    </a>
                                </div>
                            @endif
                            <div class="flex justify-between items-center pb-2 border-b border-stone-200/60 dark:border-emerald-900/40">
                                <span class="text-stone-500">Program Tujuan</span>
                                <span class="font-bold text-stone-800 dark:text-stone-200">{{ $selectedDonation->donationProgram?->title ?? 'Dāna Umum Vihara' }}</span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-stone-200/60 dark:border-emerald-900/40">
                                <span class="text-stone-500">Nominal Dāna</span>
                                <span class="font-black text-amber-600 dark:text-amber-400 text-sm">Rp {{ number_format($selectedDonation->amount, 0, ',', '.') }}</span>
                            </div>
                            @if ($selectedDonation->issuedCertificate)
                                <div class="flex justify-between items-center pb-2 border-b border-stone-200/60 dark:border-emerald-900/40">
                                    <span class="text-stone-500">Nomor Piagam</span>
                                    <span class="font-extrabold text-amber-700 dark:text-amber-300">{{ $selectedDonation->issuedCertificate->certificate_number }}</span>
                                </div>
                                <div class="flex justify-between items-center pb-2 border-b border-stone-200/60 dark:border-emerald-900/40">
                                    <span class="text-stone-500">Desain Template</span>
                                    <span class="font-bold text-stone-800 dark:text-stone-200">{{ $selectedDonation->issuedCertificate->certificateTemplate?->name ?? 'Piagam Resmi' }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between items-center">
                                <span class="text-stone-500">Status Transaksi</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                    Terverifikasi
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons in Modal -->
                        <div class="pt-3 flex flex-wrap items-center justify-end gap-2">
                            @if ($selectedDonation->phone)
                                <a 
                                    href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $selectedDonation->phone) }}?text={{ urlencode('Namo Buddhaya, terima kasih atas dāna yang telah disalurkan kepada Vihara Sāmaggi Gāma sebesar Rp ' . number_format($selectedDonation->amount, 0, ',', '.') . ' (No: ' . $selectedDonation->invoice_number . '). Anumodana.') }}"
                                    target="_blank" 
                                    class="py-2 px-4 rounded-xl bg-[#25D366] hover:bg-[#1EBE5B] text-white font-bold text-xs transition-all cursor-pointer flex items-center gap-1.5"
                                >
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                    <span>Kirim Konfirmasi WA</span>
                                </a>
                            @endif

                            <button 
                                wire:click="openEditModal({{ $selectedDonation->id }})" 
                                type="button" 
                                class="py-2 px-4 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-800 dark:text-amber-300 border border-amber-500/30 font-bold text-xs transition-all cursor-pointer flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Edit Data</span>
                            </button>

                            <button 
                                @click="window.print()" 
                                type="button" 
                                class="py-2 px-4 rounded-xl bg-stone-100 dark:bg-emerald-950 text-stone-700 dark:text-stone-300 font-bold text-xs hover:bg-stone-200 transition-all cursor-pointer"
                            >
                                Cetak Kuitansi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif

    <!-- =========================================================================
         5. MODAL IMPORT EXCEL / CSV
         ========================================================================= -->
    @if ($importModalOpen)
        <template x-teleport="body">
            <div 
                x-data 
                class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
                @keydown.escape.window="$wire.set('importModalOpen', false)"
            >
                <div 
                    @click.away="$wire.set('importModalOpen', false)" 
                    class="relative w-full max-w-xl rounded-3xl bg-white dark:bg-[#081b13] border border-stone-200 dark:border-emerald-500/20 shadow-2xl p-6 sm:p-8 space-y-6 animate-scale-up"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-stone-200/80 dark:border-emerald-950 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-black text-stone-900 dark:text-stone-100">
                                    Import Data Donasi dari Excel / CSV
                                </h3>
                                <p class="text-xs text-stone-500 dark:text-stone-400">
                                    Unggah file .xlsx atau .csv sesuai format kolom.
                                </p>
                            </div>
                        </div>

                        <button 
                            wire:click="$set('importModalOpen', false)" 
                            type="button" 
                            class="p-2 text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 rounded-xl hover:bg-stone-100 dark:hover:bg-emerald-950 transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Format Guide Callout with Table preview matching user image -->
                    <div class="p-4 rounded-2xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-500/20 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Format Kolom Yang Dikenali:
                            </span>
                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                Otomatis Buat Program Baru
                            </span>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-stone-200 dark:border-emerald-900/60 shadow-2xs">
                            <table class="w-full text-left text-[11px]">
                                <thead class="bg-[#103E73] text-white font-bold">
                                    <tr>
                                        <th class="px-3 py-1.5 border-r border-blue-400/30">KegiatanID</th>
                                        <th class="px-3 py-1.5 border-r border-blue-400/30">Nama</th>
                                        <th class="px-3 py-1.5 text-right">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-200 divide-y divide-stone-100 dark:divide-emerald-950">
                                    <tr>
                                        <td class="px-3 py-1.5 border-r border-stone-100 dark:border-emerald-950/60 font-semibold text-stone-700 dark:text-stone-300">Thavara Dana Tahap I Pembebasan Lahan</td>
                                        <td class="px-3 py-1.5 border-r border-stone-100 dark:border-emerald-950/60 font-bold">LENI</td>
                                        <td class="px-3 py-1.5 text-right font-mono text-emerald-600 dark:text-emerald-400">1000000</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-1.5 border-r border-stone-100 dark:border-emerald-950/60 font-semibold text-stone-700 dark:text-stone-300">Thavara Dana Tahap I Pembebasan Lahan</td>
                                        <td class="px-3 py-1.5 border-r border-stone-100 dark:border-emerald-950/60 font-bold">BPK. LIAUW ENG LU</td>
                                        <td class="px-3 py-1.5 text-right font-mono text-emerald-600 dark:text-emerald-400">200000</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <ul class="text-[11px] text-stone-600 dark:text-stone-400 space-y-1 pl-1">
                            <li class="flex items-start gap-1.5">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">✓</span>
                                <span>Kolom <strong>KegiatanID</strong>: Mencari program yang ada. Jika belum ada di sistem, <strong>program baru akan otomatis dibuatkan</strong>.</span>
                            </li>
                            <li class="flex items-start gap-1.5">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">✓</span>
                                <span>Kolom <strong>Nama</strong>: Nama donatur yang tercatat.</span>
                            </li>
                            <li class="flex items-start gap-1.5">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">✓</span>
                                <span>Kolom <strong>Jumlah</strong>: Nominal rupiah dāna kebajikan.</span>
                            </li>
                        </ul>
                    </div>

                    <!-- File Upload Input -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Pilih File Excel (.xlsx) / CSV
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-stone-300 dark:border-emerald-500/30 border-dashed rounded-2xl cursor-pointer bg-stone-50/50 dark:bg-emerald-950/20 hover:bg-stone-100 dark:hover:bg-emerald-950/40 transition-colors">
                                <div class="flex flex-col items-center justify-center pt-4 pb-4">
                                    <svg class="w-7 h-7 mb-1.5 text-stone-400 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <p class="text-xs text-stone-600 dark:text-stone-400 font-medium">
                                        <span class="font-bold text-[#0D5B3A] dark:text-emerald-400">Klik untuk unggah</span> file Excel / CSV
                                    </p>
                                    <p class="text-[10px] text-stone-400 dark:text-stone-500">
                                        Mendukung format .xlsx, .xls, .csv (Maks. 20MB)
                                    </p>
                                </div>
                                <input wire:model="importFile" type="file" accept=".xlsx,.xls,.csv" class="hidden" />
                            </label>
                        </div>
                        @error('importFile') 
                            <p class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold">{{ $message }}</p> 
                        @enderror

                        @if ($importFile)
                            <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-500/30 text-xs">
                                <div class="flex items-center gap-2 truncate">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="font-bold text-emerald-900 dark:text-emerald-200 truncate">{{ $importFile->getClientOriginalName() }}</span>
                                </div>
                                <span class="text-[10px] text-stone-400 font-mono">{{ number_format($importFile->getSize() / 1024, 1) }} KB</span>
                            </div>
                        @endif
                    </div>

                    <!-- Import Summary (if available) -->
                    @if ($importSummary)
                        <div class="p-4 rounded-2xl {{ $importSummary['imported'] > 0 ? 'bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-500/30 text-emerald-900 dark:text-emerald-200' : 'bg-rose-50 dark:bg-rose-950/60 border border-rose-500/30 text-rose-900 dark:text-rose-200' }} text-xs space-y-2">
                            <div class="font-extrabold flex items-center gap-1.5">
                                @if ($importSummary['imported'] > 0)
                                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    <span>Hasil Import: Berhasil memasukkan {{ $importSummary['imported'] }} data donasi!</span>
                                @else
                                    <svg class="w-4 h-4 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                    <span>Gagal mengimpor data.</span>
                                @endif
                            </div>

                            @if (!empty($importSummary['created_programs']))
                                <div class="pt-1 border-t border-emerald-500/20 text-[11px] space-y-1">
                                    <span class="font-bold">Program baru otomatis dibuat ({{ count($importSummary['created_programs']) }}):</span>
                                    <ul class="list-disc list-inside space-y-0.5 text-stone-700 dark:text-stone-300">
                                        @foreach ($importSummary['created_programs'] as $pTitle)
                                            <li>{{ $pTitle }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (!empty($importSummary['errors']))
                                <div class="pt-1 border-t border-rose-500/20 text-[11px] space-y-0.5 text-rose-700 dark:text-rose-300">
                                    @foreach (array_slice($importSummary['errors'], 0, 5) as $err)
                                        <p>• {{ $err }}</p>
                                    @endforeach
                                    @if (count($importSummary['errors']) > 5)
                                        <p class="italic text-[10px]">...dan {{ count($importSummary['errors']) - 5 }} baris lainnya.</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Modal Action Footer -->
                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-stone-200/80 dark:border-emerald-950">
                        <button 
                            wire:click="$set('importModalOpen', false)" 
                            type="button" 
                            class="px-4 py-2.5 rounded-xl text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white font-bold text-xs cursor-pointer transition-colors"
                        >
                            {{ $importSummary && $importSummary['imported'] > 0 ? 'Selesai' : 'Batal' }}
                        </button>

                        <button 
                            wire:click="importExcel" 
                            wire:loading.attr="disabled"
                            type="button" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#0D5B3A] hover:bg-[#09472D] text-white font-extrabold text-xs shadow-md transition-all cursor-pointer disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="importExcel">Proses Import Data</span>
                            <span wire:loading wire:target="importExcel">Memproses Data...</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    @endif

    <!-- =========================================================================
         MODAL CETAK LAPORAN TRANSAKSI (NO, NAMA DONATUR, NAMA PROGRAM, NOMINAL)
         ========================================================================= -->
    @if ($printModalOpen)
        <template x-teleport="body">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
                <div 
                    wire:click="$set('printModalOpen', false)" 
                    class="fixed inset-0 bg-stone-950/70 backdrop-blur-xs transition-opacity"
                ></div>

                <div class="relative w-full max-w-4xl rounded-3xl bg-white dark:bg-[#071710] border border-stone-200 dark:border-emerald-500/25 shadow-2xl p-6 sm:p-7 space-y-4 z-10 my-auto text-stone-900 dark:text-stone-100 max-h-[90vh] flex flex-col">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-stone-200/80 dark:border-emerald-950 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-bold">Cetak Laporan Transaksi Donasi</h3>
                                <p class="text-xs text-stone-500 dark:text-stone-400">Pratinjau data rekapitulasi sebelum dicetak ke printer / PDF.</p>
                            </div>
                        </div>
                        <button 
                            wire:click="$set('printModalOpen', false)" 
                            type="button" 
                            class="p-2 rounded-xl text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-emerald-950/60 cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Filter Bar -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 rounded-2xl bg-stone-50 dark:bg-[#0b1f17] border border-stone-200/80 dark:border-emerald-500/20 shrink-0">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400 mb-1">Filter Program</label>
                            <select 
                                wire:model.live="printProgramId" 
                                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/30 text-xs font-semibold focus:outline-none focus:border-emerald-600"
                            >
                                <option value="">Semua Program Donasi</option>
                                @foreach ($programs as $prog)
                                    <option value="{{ $prog->id }}">{{ $prog->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400 mb-1">Filter Status</label>
                            <select 
                                wire:model.live="printStatus" 
                                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/30 text-xs font-semibold focus:outline-none focus:border-emerald-600"
                            >
                                <option value="semua">Semua Status</option>
                                <option value="verified">Terverifikasi (Verified)</option>
                                <option value="pending">Menunggu Konfirmasi (Pending)</option>
                                <option value="rejected">Ditolak (Rejected)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Ringkasan Info -->
                    <div class="flex items-center justify-between text-xs px-1 text-stone-600 dark:text-stone-300 shrink-0">
                        <span>Data Terpilih: <strong class="text-stone-900 dark:text-white">{{ $printDonations->count() }}</strong> transaksi</span>
                        <span>Total Nominal: <strong class="text-emerald-700 dark:text-emerald-400 font-bold">Rp {{ number_format($printTotalNominal, 0, ',', '.') }}</strong></span>
                    </div>

                    <!-- Live Preview Table (No, Nama Donatur, Nama Program, Nominal) -->
                    <div class="overflow-y-auto flex-1 min-h-[220px] rounded-2xl border border-stone-200/80 dark:border-emerald-950/80">
                        <table class="w-full text-left text-xs">
                            <thead class="sticky top-0 bg-stone-100 dark:bg-[#0b1f17] border-b border-stone-200 dark:border-emerald-950 text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                                <tr>
                                    <th class="py-2.5 px-3.5 text-center w-12">No</th>
                                    <th class="py-2.5 px-3.5">Nama Donatur</th>
                                    <th class="py-2.5 px-3.5">Nama Program</th>
                                    <th class="py-2.5 px-3.5 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-emerald-950/40">
                                @forelse ($printDonations as $idx => $item)
                                    <tr class="hover:bg-stone-50/60 dark:hover:bg-white/[0.02]">
                                        <td class="py-2.5 px-3.5 text-center text-stone-400">{{ $idx + 1 }}</td>
                                        <td class="py-2.5 px-3.5 font-bold text-stone-900 dark:text-stone-100">{{ $item->donor_name }}</td>
                                        <td class="py-2.5 px-3.5 text-stone-600 dark:text-stone-300">{{ $item->donationProgram?->title ?? '-' }}</td>
                                        <td class="py-2.5 px-3.5 text-right font-extrabold text-emerald-800 dark:text-emerald-300">
                                            Rp {{ number_format($item->amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-stone-400 italic">
                                            Tidak ada data transaksi yang cocok dengan kriteria filter saat ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-between pt-3 border-t border-stone-200/80 dark:border-emerald-950 shrink-0">
                        <button 
                            wire:click="$set('printModalOpen', false)" 
                            type="button" 
                            class="px-4 py-2.5 rounded-xl text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white font-bold text-xs cursor-pointer transition-colors"
                        >
                            Tutup
                        </button>

                        <button 
                            @click="window.print()" 
                            type="button" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#0D5B3A] hover:bg-[#09472D] text-white font-extrabold text-xs shadow-md transition-all cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Cetak Sekarang (Print)</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- =========================================================================
             DOKUMEN CETAK RESMI (HANYA MUNCUL SAAT WINDOW.PRINT)
             ========================================================================= -->
        <div id="printable-transaksi" class="hidden">
            <!-- Kop Surat / Header Laporan -->
            <div class="text-center pb-4 mb-5 border-b-2 border-stone-900">
                <h1 class="text-xl font-bold tracking-wider uppercase text-black">VIHĀRA SĀMAGGI GĀMA</h1>
                <p class="text-xs text-stone-600 mt-0.5">Laporan Rekapitulasi Transaksi Donasi Donatur</p>
                <div class="text-[11px] text-stone-700 mt-3 flex justify-between items-center border-t border-stone-300 pt-2">
                    <div><strong>Program:</strong> {{ !empty($printProgramId) ? ($programs->firstWhere('id', $printProgramId)?->title ?? 'Semua Program') : 'Semua Program' }}</div>
                    <div><strong>Status:</strong> {{ ucfirst($printStatus) }}</div>
                    <div><strong>Dicetak:</strong> {{ now()->translatedFormat('d F Y, H:i') }} WIB</div>
                </div>
            </div>

            <!-- Tabel Data: No, Nama Donatur, Nama Program, Nominal -->
            <table class="w-full text-left text-xs border-collapse border border-stone-800">
                <thead>
                    <tr class="bg-stone-100 border-b border-stone-800">
                        <th class="py-2 px-3 text-center w-12 font-bold text-black border border-stone-400">No</th>
                        <th class="py-2 px-3 font-bold text-black border border-stone-400">Nama Donatur</th>
                        <th class="py-2 px-3 font-bold text-black border border-stone-400">Nama Program</th>
                        <th class="py-2 px-3 text-right font-bold text-black border border-stone-400">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($printDonations as $index => $item)
                        <tr class="border-b border-stone-300">
                            <td class="py-2 px-3 text-center text-stone-800 border border-stone-300">{{ $index + 1 }}</td>
                            <td class="py-2 px-3 font-semibold text-black border border-stone-300">{{ $item->donor_name }}</td>
                            <td class="py-2 px-3 text-stone-700 border border-stone-300">{{ $item->donationProgram?->title ?? 'Umum / Operasional' }}</td>
                            <td class="py-2 px-3 text-right font-bold text-black border border-stone-300">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-stone-500 italic border border-stone-300">
                                Tidak ada data donasi yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($printDonations->isNotEmpty())
                    <tfoot>
                        <tr class="bg-stone-100 font-bold border-t-2 border-stone-800">
                            <td colspan="3" class="py-2.5 px-3 text-right uppercase tracking-wider text-black border border-stone-400">Total Nominal:</td>
                            <td class="py-2.5 px-3 text-right text-black font-extrabold border border-stone-400">Rp {{ number_format($printTotalNominal, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>

            <!-- Tanda Tangan / Pengesahan -->
            <div class="mt-10 flex justify-between items-start text-xs text-stone-800 avoid-break">
                <div>
                    <p class="text-stone-500 text-[11px]">Catatan:</p>
                    <p class="italic text-[10px] text-stone-500">Dokumen ini diterbitkan secara otomatis oleh Sistem Informasi Vihāra Sāmaggi Gāma.</p>
                </div>
                <div class="text-center min-w-[200px]">
                    <p>Disahkan oleh,</p>
                    <p class="font-semibold mt-0.5">Pengurus / Admin Vihāra</p>
                    <div class="h-16"></div>
                    <p class="font-bold underline text-black">( ............................................ )</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Style Khusus Cetak (Print Media) -->
    <style>
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 10mm !important;
            }
            body * {
                visibility: hidden !important;
            }
            #printable-transaksi, #printable-transaksi * {
                visibility: visible !important;
            }
            #printable-transaksi {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                display: block !important;
                background: #ffffff !important;
                color: #000000 !important;
            }
            .avoid-break {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>

</div>
