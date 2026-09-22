<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Exceptions\HttpException;
use App\Exceptions\RedirectException;
use App\Models\File;
use App\Storage;
use App\View;

// Everything a logged-in user can do with their files: list, upload, download, delete.
class FileController
{
    public function __construct(
        private readonly Auth $auth,
        private readonly Storage $storage,
        private readonly View $view,
        private readonly int $maxUploadMb,
    ) {
    }

    // GET /
    public function index(): void
    {
        $user = $this->auth->requireUser();

        $this->view->render('index', [
            'title' => 'My files',
            'files' => File::allForUser($user->id),
            'totals' => File::totalsForUser($user->id),
            'maxUploadMb' => $this->maxUploadMb,
            'flash' => $this->view->takeFlash(),
        ]);
    }

    // POST /upload
    public function upload(): void
    {
        $user = $this->auth->requireUser();
        $upload = $_FILES['file'] ?? null;
        $description = trim((string) ($_POST['description'] ?? ''));

        $error = $this->validate($upload, $description);
        if ($error !== null) {
            $this->view->flash('error', $error);
            throw new RedirectException('/');
        }

        // The type is read from the file content, not trusted from the browser.
        $mimeType = mime_content_type($upload['tmp_name']) ?: 'application/octet-stream';
        $originalName = mb_substr(basename((string) $upload['name']), 0, 255);

        // 1. Put the file into the upload folder.
        $storedName = $this->storage->save($upload['tmp_name']);

        // 2. Remember it in the database.
        File::create($user->id, $originalName, $storedName, $mimeType, (int) $upload['size'], $description !== '' ? $description : null);

        $this->view->flash('success', "Uploaded \"{$originalName}\".");
        throw new RedirectException('/');
    }

    // GET /files/{id}/download
    public function download(string $id): void
    {
        $file = $this->findOr404($id);
        $this->storage->download($file->storedName, $file->originalName, $file->mimeType);
    }

    // POST /files/{id}/delete
    public function delete(string $id): void
    {
        $file = $this->findOr404($id);

        $this->storage->delete($file->storedName);
        $file->delete();

        $this->view->flash('success', "Deleted \"{$file->originalName}\".");
        throw new RedirectException('/');
    }

    /** Returns an error message, or null when the upload is fine. */
    private function validate(?array $upload, string $description): ?string
    {
        $tooLarge = "The file is larger than {$this->maxUploadMb} MB.";

        // When a request is bigger than PHP's post_max_size, PHP drops the whole form.
        if ($upload === null) {
            return (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && $_POST === [] ? $tooLarge : 'Choose a file to upload.';
        }

        $error = match ($upload['error']) {
            UPLOAD_ERR_OK => null,
            UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => $tooLarge,
            default => 'Upload failed. Please try again.',
        };
        if ($error !== null) {
            return $error;
        }

        if ($upload['size'] === 0) {
            return 'The file is empty.';
        }
        if ($upload['size'] > $this->maxUploadMb * 1024 * 1024) {
            return $tooLarge;
        }
        if (mb_strlen($description) > 255) {
            return 'The description must be at most 255 characters.';
        }
        return null;
    }

    /** The file with this id, if it belongs to the logged-in user. Other people's files are "not found". */
    private function findOr404(string $id): File
    {
        $user = $this->auth->requireUser();
        return File::findForUser((int) $id, $user->id) ?? throw new HttpException(404);
    }
}
