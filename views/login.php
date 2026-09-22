<?php
/**
 * Log in page.
 * @var App\View $view
 * @var string $email
 * @var string|null $error
 */
?>
<section class="card auth-card">
  <h1>Log in</h1>
  <p class="muted">Welcome back to your files.</p>

<?php if ($error !== null): ?>
  <div class="flash flash-error" role="alert"><?= $view->e($error) ?></div>
<?php endif; ?>

  <form method="post" action="/login">
    <?= $view->csrfField() ?>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= $view->e($email) ?>" autocomplete="email" required>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Log in</button>
  </form>

  <p class="auth-switch">No account yet? <a href="/register">Sign up</a></p>
</section>
