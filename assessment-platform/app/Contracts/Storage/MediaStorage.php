<?php

namespace App\Contracts\Storage;

use Illuminate\Http\UploadedFile;

interface MediaStorage
{
    /**
     * Store a file and return the storage path.
     */
    public function store(UploadedFile $file, string $directory = ''): string;

    /**
     * Retrieve a temporary URL for a stored file.
     */
    public function temporaryUrl(string $path, int $expirationMinutes = 60): string;

    /**
     * Delete a file from storage.
     */
    public function delete(string $path): bool;

    /**
     * Check if a file exists in storage.
     */
    public function exists(string $path): bool;
}
