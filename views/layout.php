<?php
/**
 * Page frame around every page.
 * @var App\View $view
 * @var string $content   the page HTML
 * @var string $title
 * @var App\Auth $auth
 * @var string $hostname, $dbHost, $storage   footer info
 */
$currentUser = $auth->knownUser();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $view->e($title) ?> · MiniBox</title>
  <link rel="stylesheet" href="/css/app.css">
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="logo" href="/">
        <span class="logo-mark"><?= $view->icon('box') ?></span>
        MiniBox
      </a>
      <div class="user-box">
<?php if ($currentUser !== null): ?>
        <span class="user-email"><?= $view->e($currentUser->email) ?></span>
        <form method="post" action="/logout">
          <?= $view->csrfField() ?>
          <button type="submit" class="btn btn-header"><?= $view->icon('logout') ?> Log out</button>
        </form>
<?php else: ?>
        <a class="btn btn-header" href="/login">Log in</a>
        <a class="btn btn-header btn-header-primary" href="/register">Sign up</a>
<?php endif; ?>
      </div>
    </div>
  </header>

  <main class="container">
<?= $content ?>
  </main>

  <footer class="site-footer">
    <div class="container">
      Served by <code><?= $view->e($hostname) ?></code>
      · DB: <code><?= $view->e($dbHost) ?></code>
      · Files: <code><?= $view->e($storage) ?></code>
    </div>
  </footer>
</body>
</html>
