<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class StoredFileController extends Controller
{
    public function __invoke(string $path)
    {
        abort_if(str_contains($path, '\\') || str_contains($path, "\0") ||
            in_array('..', explode('/', $path), true), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        // Keep existing same-origin PDF/image URLs; the bucket stays private.
        return $disk->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
