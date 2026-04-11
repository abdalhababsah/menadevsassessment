<?php

namespace App\Services\Storage;

use App\Contracts\Storage\MediaStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class LocalMediaStorage implements MediaStorage
{
    public function store(UploadedFile $file, string $directory = ''): string
    {
        $path = $file->store($directory, 'public');

        return $path;
    }

    public function temporaryUrl(string $path, int $expirationMinutes = 60): string
    {
        // Local public disk doesn't support short-lived signed URLs out of the box like S3 does,
        // so we return the permanent public URL. For true temporary local URLs,
        // a custom signed route would be needed.
        return Storage::disk('public')->url($path);
    }

    public function delete(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }
}
