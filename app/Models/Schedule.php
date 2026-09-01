<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'event_date',
        'start_time',
        'end_time',
        'schedule_type',
        'location',
        'leader',
        'description',
        'activities',
        'cover_image',
        'status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'activities' => 'array',
    ];

    public function getActivitiesAttribute($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_null($value) ? [] : (array) $value;
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->event_date ? $this->event_date->translatedFormat('d M Y') : '-';
    }

    public function getDayNameAttribute(): string
    {
        return $this->event_date ? $this->event_date->translatedFormat('l') : '-';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString())->orderBy('event_date');
    }
}
