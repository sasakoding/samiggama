<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutineSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_date',
        'activity_name',
        'leader_1',
        'leader_2',
        'speaker',
        'topic',
        'time_range',
        'status',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function getFormattedDateAttribute(): string
    {
        return $this->event_date ? $this->event_date->translatedFormat('d F Y') : '-';
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
        return $query->where('event_date', '>=', now()->toDateString())->orderBy('event_date', 'asc');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('event_date', now()->month)
            ->whereYear('event_date', now()->year);
    }
}
