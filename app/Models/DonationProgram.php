<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonationProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'target_amount',
        'start_date',
        'end_date',
        'cover_image',
        'content',
        'status',
    ];

    protected $casts = [
        'target_amount' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function getCollectedAmountAttribute(): int
    {
        return (int) $this->donations()->where('status', 'verified')->sum('amount');
    }

    public function getDonorsCountAttribute(): int
    {
        return $this->donations()->where('status', 'verified')->count();
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return min(100, round(($this->collected_amount / $this->target_amount) * 100, 1));
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->end_date) {
            return null;
        }

        $endOfDay = $this->end_date->copy()->endOfDay();
        if ($endOfDay->isPast()) {
            return 0;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->end_date->startOfDay(), true));
    }

    public function getDaysLeftTextAttribute(): string
    {
        if ($this->status === 'selesai' || $this->status === 'nonaktif') {
            return 'Selesai & Tersalurkan';
        }

        if (!$this->end_date) {
            return 'Sedang Berjalan';
        }

        $endOfDay = $this->end_date->copy()->endOfDay();
        if ($endOfDay->isPast()) {
            return 'Selesai & Tersalurkan';
        }

        $days = (int) now()->startOfDay()->diffInDays($this->end_date->startOfDay(), true);

        if ($days <= 0) {
            return 'Hari Terakhir';
        }

        return $days . ' Hari Lagi';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'selesai');
    }
}
