<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Upload new photo to the cloud and return its path 
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, 's3');
    }

    /**
     * Delete photo from the cloud by its path
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk('s3')->exists($path)) {
            Storage::disk('s3')->delete($path);
        }
    }
}