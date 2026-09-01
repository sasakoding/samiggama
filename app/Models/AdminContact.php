<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'role',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get clean international phone digits (e.g. 628123456789).
     */
    public function getCleanPhoneAttribute(): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $this->phone);
        
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return $digits ?: '6281123456789';
    }

    /**
     * Get a random active admin WhatsApp number or fallback.
     */
    public static function getRandomActivePhone(?string $fallback = null): string
    {
        $contact = static::active()->inRandomOrder()->first();

        if ($contact) {
            return $contact->clean_phone;
        }

        if ($fallback) {
            $digits = preg_replace('/[^0-9]/', '', $fallback);
            if (str_starts_with($digits, '0')) {
                $digits = '62' . substr($digits, 1);
            }
            return $digits ?: '6281123456789';
        }

        $defaultSetting = Setting::get('social_whatsapp') ?: Setting::get('foundation_phone', '6281123456789');
        $digits = preg_replace('/[^0-9]/', '', (string) $defaultSetting);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        return $digits ?: '6281123456789';
    }

    /**
     * Get a random active admin contact model or null.
     */
    public static function getRandomActiveContact(): ?self
    {
        return static::active()->inRandomOrder()->first();
    }
}
