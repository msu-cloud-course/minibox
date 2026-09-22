<?php
/**
 * Error page (404 / 500).
 * @var App\View $view
 * @var int $code
 * @var string $heading, $text
 * @var string|null $details   only filled when APP_DEBUG=true
 */
?>
<section class="card error-page">
  <p class="error-code"><?= (int) $code ?></p>
  <h1><?= $view->e($heading) ?></h1>
  <p class="muted"><?= $view->e($text) ?></p>
<?php if ($details !== null): ?>
  <pre><?= $view->e($details) ?></pre>
<?php endif; ?>
  <a class="btn btn-secondary" href="/">Back to files</a>
</section>
