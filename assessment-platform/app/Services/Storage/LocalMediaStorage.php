<?php

namespace App\Services\Storage;

use App\Contracts\Storage\MediaStorage;
use App\Exceptions\NotImplementedException;
use Illuminate\Http\UploadedFile;

final class LocalMediaStorage implements MediaStorage
{
    public function store(UploadedFile $file, string $directory = ''): string
    {
        // TODO: Implement local media storage
        throw new NotImplementedException('Local media storage is not yet implemented.');
    }

    public function temporaryUrl(string $path, int $expirationMinutes = 60): string
    {
        // TODO: Implement temporary URL generation
        throw new NotImplementedException('Temporary URL generation is not yet implemented.');
    }

    public function delete(string $path): bool
    {
        // TODO: Implement file deletion
        throw new NotImplementedException('File deletion is not yet implemented.');
    }

    public function exists(string $path): bool
    {
        // TODO: Implement file existence check
        throw new NotImplementedException('File existence check is not yet implemented.');
    }
}
