<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\HttpException;
use RuntimeException;

// Keeps uploaded files in a folder on this server's disk.
// Every file gets a random name, so two uploads with the same name never overwrite each other
// and nobody can guess a file's location. The real name is stored in the database.
class Storage
{
    public function __construct(private readonly string $dir)
    {
    }

    public function description(): string
    {
        return "local disk ({$this->dir})";
    }

    /** Move a just-uploaded file (from $_FILES) into the folder and return its new name. */
    public function save(string $uploadedTmpPath): string
    {
        $this->ensureFolderExists();
        $storedName = bin2hex(random_bytes(16));

        if (!move_uploaded_file($uploadedTmpPath, $this->path($storedName))) {
            throw new RuntimeException('Could not save the uploaded file.');
        }
        return $storedName;
    }

    /** Send the file to the browser as a download. */
    public function download(string $storedName, string $downloadName, string $mimeType): void
    {
        $path = $this->path($storedName);
        if (!is_file($path)) {
            throw new HttpException(404);
        }

        // The UTF-8 name goes into filename*, a plain ASCII copy into filename (for old clients).
        $asciiName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $downloadName);

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($path));
        header(sprintf(
            'Content-Disposition: attachment; filename="%s"; filename*=UTF-8\'\'%s',
            $asciiName,
            rawurlencode($downloadName)
        ));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
    }

    public function delete(string $storedName): void
    {
        $path = $this->path($storedName);
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function path(string $storedName): string
    {
        // basename() makes sure we never leave the upload folder.
        return $this->dir . '/' . basename($storedName);
    }

    private function ensureFolderExists(): void
    {
        if (!is_dir($this->dir) && !mkdir($this->dir, 0775, true) && !is_dir($this->dir)) {
            throw new RuntimeException("Cannot create the upload folder {$this->dir}.");
        }
    }
}
