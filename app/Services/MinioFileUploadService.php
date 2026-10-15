<?php

namespace App\Services;

use App\Contracts\FileUploadServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MinioFileUploadService implements FileUploadServiceInterface
{
    /**
     * @inheritDoc
     */
    public function upload(UploadedFile $file, string $directory, string $fileName = null): string
    {
        $fileName = $fileName ?? (time() . '_' . $file->getClientOriginalName());

        // Store under the configured MinIO active-document prefix.
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

        // Existing database paths use public/ even when the bucket prefix is empty.
        $path = $this->objectPath($path);
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
        $from = $this->objectPath($from);
        $to = $this->objectPath($to);
        // Support moving files (e.g., from aktif to tidakaktif folders)
        if (Storage::exists($from)) {
            return Storage::move($from, $to);
        }
        return false;
    }

    private function objectPath(string $path): string
    {
        if (str_starts_with($path, 'public/')) {
            return ltrim(config('filesystems.minio_public_prefix', '').'/'.substr($path, 7), '/');
        }

        return $path;
    }
}
