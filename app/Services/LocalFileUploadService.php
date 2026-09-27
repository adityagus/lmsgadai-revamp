<?php

namespace App\Services;

use App\Contracts\FileUploadServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LocalFileUploadService implements FileUploadServiceInterface
{
    /**
     * @inheritDoc
     */
    public function upload(UploadedFile $file, string $directory, string $fileName = null): string
    {
        $fileName = $fileName ?? (time() . '_' . $file->getClientOriginalName());
        
        // Store in the 'aktif' disk (mapped to storage/app/public/aktif)
        return $file->storeAs($directory, $fileName, 'aktif');
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path, string $disk = 'aktif'): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }
        
        // Fallback to default disk if path contains 'public/' prefix
        if (Storage::exists($path)) {
            return Storage::delete($path);
        }
        
        return false;
    }

    /**
     * @inheritDoc
     */
    public function move(string $from, string $to): bool
    {
        // Support moving files (e.g., from aktif to tidakaktif folders)
        if (Storage::exists($from)) {
            // Ensure destination folder exists
            $dir = dirname($to);
            if (!Storage::exists($dir)) {
                Storage::makeDirectory($dir);
            }
            return Storage::move($from, $to);
        }
        return false;
    }
}
