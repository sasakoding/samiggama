<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'gallery_album_id',
        'file_path',
        'image_path',
        'caption',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function getImagePathAttribute(): ?string
    {
        return $this->attributes['file_path'] ?? ($this->attributes['image_path'] ?? null);
    }

    public function setImagePathAttribute(?string $value): void
    {
        $this->attributes['file_path'] = $value;
        $this->attributes['image_path'] = $value;
    }

    public function album()
    {
        return $this->belongsTo(GalleryAlbum::class, 'gallery_album_id');
    }
}
