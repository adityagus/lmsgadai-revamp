<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface FileUploadServiceInterface
{
    /**
     * Upload an uploaded file to a specified directory.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string|null $fileName
     * @return string
     */
    public function upload(UploadedFile $file, string $directory, string $fileName = null): string;

    /**
     * Delete a file from disk.
     *
     * @param string $path
     * @param string $disk
     * @return bool
     */
    public function delete(string $path, string $disk = 'aktif'): bool;

    /**
     * Move a file from one path to another.
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function move(string $from, string $to): bool;
}
