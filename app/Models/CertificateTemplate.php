<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category', // 'umum' (Piagam Donatur / Anumodana), 'alm' (Piagam Pelimpahan Jasa / Pattidāna / Mendiang)
        'slug',
        'orientation',
        'background_image',
        'min_amount',
        'status',
    ];

    protected $casts = [
        'min_amount' => 'integer',
    ];

    public function issuedCertificates()
    {
        return $this->hasMany(IssuedCertificate::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->category === 'alm' ? 'Pelimpahan Jasa (Alm.)' : 'Piagam Donatur (Umum)';
    }

    public function getCategoryBadgeClassAttribute(): string
    {
        return $this->category === 'alm'
            ? 'bg-amber-500/15 text-amber-800 dark:text-amber-300 border-amber-500/30'
            : 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border-emerald-500/30';
    }

    public function scopeUmum($query)
    {
        return $query->where('category', 'umum');
    }

    public function scopeAlm($query)
    {
        return $query->where('category', 'alm');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }
}
