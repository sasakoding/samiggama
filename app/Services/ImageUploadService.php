<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Compress and save uploaded image to public directory.
     * Automatically converts/compresses to optimized WebP or JPEG.
     */
    public static function uploadAndCompress(UploadedFile $file, string $folder = 'uploads', int $maxWidth = 1600, int $quality = 82): ?string
    {
        try {
            $folder = trim(str_replace('\\', '/', $folder), '/');
            $destinationPath = public_path($folder);
            File::ensureDirectoryExists($destinationPath, 0755, true);

            $fileName = Str::uuid() . '.webp';
            $targetFile = $destinationPath . DIRECTORY_SEPARATOR . $fileName;

            $sourcePath = $file->getRealPath();
            $imageInfo = @getimagesize($sourcePath);
            if (!$imageInfo) {
                // Fallback direct copy if getimagesize fails
                $fileName = Str::uuid() . '.' . (strtolower($file->getClientOriginalExtension()) ?: 'jpg');
                $targetFile = $destinationPath . DIRECTORY_SEPARATOR . $fileName;
                File::copy($sourcePath, $targetFile);
                @unlink($sourcePath);
                return $folder . '/' . $fileName;
            }

            $mime = $imageInfo['mime'];
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            // Create image resource from mime
            $src = match ($mime) {
                'image/jpeg', 'image/jpg' => imagecreatefromjpeg($sourcePath),
                'image/png' => imagecreatefrompng($sourcePath),
                'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : null,
                default => null,
            };

            if (!$src) {
                $fileName = Str::uuid() . '.' . (strtolower($file->getClientOriginalExtension()) ?: 'jpg');
                $targetFile = $destinationPath . DIRECTORY_SEPARATOR . $fileName;
                File::copy($sourcePath, $targetFile);
                @unlink($sourcePath);
                return $folder . '/' . $fileName;
            }

            // Handle PNG transparency
            imagealphablending($src, true);
            imagesavealpha($src, true);

            // Calculate scaled dimensions
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = (int) round(($height / $width) * $maxWidth);

                $dst = imagecreatetruecolor($newWidth, $newHeight);
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($src);
                $src = $dst;
            }

            // Save as WebP if supported, otherwise JPEG
            if (function_exists('imagewebp')) {
                imagewebp($src, $targetFile, $quality);
            } else {
                $fileName = Str::uuid() . '.jpg';
                $targetFile = $destinationPath . DIRECTORY_SEPARATOR . $fileName;
                imagejpeg($src, $targetFile, $quality);
            }

            imagedestroy($src);

            // Clean up temporary file if still exists
            if (file_exists($sourcePath) && is_file($sourcePath)) {
                @unlink($sourcePath);
            }

            self::cleanLivewireTmp();

            return $folder . '/' . $fileName;
        } catch (\Throwable $e) {
            // Fallback: direct copy file
            $folder = trim(str_replace('\\', '/', $folder), '/');
            $destinationPath = public_path($folder);
            File::ensureDirectoryExists($destinationPath, 0755, true);

            $fileName = Str::uuid() . '.' . (strtolower($file->getClientOriginalExtension()) ?: 'jpg');
            $targetFile = $destinationPath . DIRECTORY_SEPARATOR . $fileName;

            if (file_exists($file->getRealPath())) {
                File::copy($file->getRealPath(), $targetFile);
                @unlink($file->getRealPath());
            } else {
                File::put($targetFile, $file->getContent());
            }

            self::cleanLivewireTmp();
            return $folder . '/' . $fileName;
        }
    }

    /**
     * Upload original image without any compression or resizing.
     * Used for certificate templates (Piagam) to retain 100% crystal-clear HD print quality.
     */
    public static function uploadOriginal(UploadedFile $file, string $folder = 'uploads/certificates'): string
    {
        $folder = trim(str_replace('\\', '/', $folder), '/');
        $destinationPath = public_path($folder);
        File::ensureDirectoryExists($destinationPath, 0755, true);

        $extension = strtolower($file->getClientOriginalExtension()) ?: 'png';
        $fileName = Str::uuid() . '.' . $extension;
        $targetFile = $destinationPath . DIRECTORY_SEPARATOR . $fileName;

        $sourcePath = $file->getRealPath();

        // Use File::copy instead of move() to prevent Windows file lock / cross-partition errors
        if ($sourcePath && file_exists($sourcePath) && is_file($sourcePath)) {
            File::copy($sourcePath, $targetFile);
            @unlink($sourcePath);
        } else {
            File::put($targetFile, $file->getContent());
        }

        // Clean up temporary livewire storage
        self::cleanLivewireTmp();

        return $folder . '/' . $fileName;
    }

    /**
     * Delete existing image from public folder.
     */
    public static function deleteOldImage(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        // Don't delete default assets in images/
        if (str_starts_with($path, 'images/')) {
            return;
        }

        $fullPath = public_path($path);
        if (File::exists($fullPath)) {
            @File::delete($fullPath);
        }
    }

    /**
     * Clean up livewire temporary storage.
     */
    public static function cleanLivewireTmp(): void
    {
        $tmpDirs = [
            storage_path('app/livewire-tmp'),
            storage_path('app/private/livewire-tmp'),
        ];

        foreach ($tmpDirs as $dir) {
            if (File::exists($dir)) {
                $files = File::files($dir);
                foreach ($files as $f) {
                    @File::delete($f->getRealPath());
                }
            }
        }
    }
}
