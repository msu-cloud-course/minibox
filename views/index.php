<?php
/**
 * The only page: upload form and the list of files.
 * @var App\View $view
 * @var App\Models\File[] $files
 * @var array $totals   ['count' => int, 'bytes' => int]
 * @var int $maxUploadMb
 * @var array|null $flash   ['type' => 'success'|'error', 'message' => string]
 */
?>
<section class="hero">
  <h1>Мои файлы</h1>
  <p class="lead">
    <?= $totals['count'] ?> <?= $totals['count'] === 1 ? 'file' : 'files' ?>
    · <?= $view->e($view->bytes($totals['bytes'])) ?> in total
  </p>
</section>

<?php if ($flash): ?>
  <div class="flash flash-<?= $view->e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
    <?= $view->e($flash['message']) ?>
  </div>
<?php endif; ?>

<form class="card upload" method="post" action="/upload" enctype="multipart/form-data">
  <?= $view->csrfField() ?>
  <div class="upload-icon"><?= $view->icon('upload') ?></div>
  <div class="upload-body">
    <h2>Upload a file</h2>
    <p class="muted">Any file type, up to <?= (int) $maxUploadMb ?> MB.</p>

    <div class="upload-fields">
      <div class="field">
        <label for="file">File</label>
        <input type="file" id="file" name="file" required>
      </div>
      <div class="field">
        <label for="description">Description <span class="optional">(optional)</span></label>
        <input type="text" id="description" name="description" maxlength="255" placeholder="e.g. Lecture 3 slides">
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><?= $view->icon('upload') ?> Upload</button>
  </div>
</form>

<section class="card files" aria-labelledby="files-heading">
  <h2 id="files-heading" class="files-heading">Files</h2>

<?php if ($files === []): ?>
  <div class="empty">
    <?= $view->icon('box') ?>
    <p><strong>No files yet</strong></p>
    <p class="muted">Upload your first file with the form above.</p>
  </div>
<?php else: ?>
  <table>
    <thead>
      <tr>
        <th scope="col">Name</th>
        <th scope="col">Size</th>
        <th scope="col" class="col-date">Uploaded</th>
        <th scope="col"><span class="visually-hidden">Actions</span></th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($files as $file): ?>
      <tr>
        <td>
          <div class="file-name">
            <span class="file-icon"><?= $view->fileIcon($file->mimeType) ?></span>
            <div>
              <strong><?= $view->e($file->originalName) ?></strong>
              <span class="file-meta">
                <?= $view->e($file->typeLabel()) ?><?php if ($file->description !== null): ?> · <?= $view->e($file->description) ?><?php endif; ?>
              </span>
            </div>
          </div>
        </td>
        <td class="nowrap col-size"><?= $view->e($view->bytes($file->sizeBytes)) ?></td>
        <td class="nowrap muted col-date"><?= $view->e($view->date($file->createdAt)) ?></td>
        <td>
          <div class="row-actions">
            <a class="btn btn-small btn-secondary" href="/files/<?= $file->id ?>/download" aria-label="Download <?= $view->e($file->originalName) ?>">
              <?= $view->icon('download') ?><span class="btn-label">Download</span>
            </a>
            <form method="post" action="/files/<?= $file->id ?>/delete" onsubmit="return confirm('Delete this file?')">
              <?= $view->csrfField() ?>
              <button type="submit" class="btn btn-small btn-danger" aria-label="Delete <?= $view->e($file->originalName) ?>">
                <?= $view->icon('trash') ?><span class="btn-label">Delete</span>
              </button>
            </form>
          </div>
        </td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</section>
