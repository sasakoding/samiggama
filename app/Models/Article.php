<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'author_name',
        'user_id',
        'excerpt',
        'content',
        'cover_image',
        'views_count',
        'status',
        'published_at',
    ];

    protected $casts = [
        'views_count' => 'integer',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedDateAttribute(): string
    {
        $date = $this->published_at ?? $this->created_at;
        return $date ? $date->translatedFormat('d F Y') : date('d F Y');
    }

    public function getReadTimeAttribute(): string
    {
        $wordCount = str_word_count(strip_tags($this->content ?? ''));
        $minutes = max(1, (int) ceil($wordCount / 200));
        return $minutes . ' menit baca';
    }

    public function getCoverImageUrlAttribute(): string
    {
        if ($this->cover_image && file_exists(public_path($this->cover_image))) {
            return asset($this->cover_image);
        }
        return asset($this->cover_image ?: 'images/news-baksos.jpg');
    }

    public function getCategorySlugAttribute(): string
    {
        return \Illuminate\Support\Str::slug($this->category);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}

