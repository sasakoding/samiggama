<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanghaMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'photo',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Get the photo URL or fallback.
     */
    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo && (file_exists(public_path($this->photo)) || str_starts_with($this->photo, 'http'))) {
            return str_starts_with($this->photo, 'http') ? $this->photo : asset($this->photo);
        }

        return asset('images/bhikkhu-sangha.jpg');
    }
}
