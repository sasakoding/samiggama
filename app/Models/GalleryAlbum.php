<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryAlbum extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'event_date',
        'location',
        'cover_image',
        'description',
        'views_count',
    ];

    protected $casts = [
        'event_date' => 'date',
        'views_count' => 'integer',
    ];

    public function photos()
    {
        return $this->hasMany(GalleryPhoto::class);
    }
}
