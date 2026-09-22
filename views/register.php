<?php
/**
 * Sign up page.
 * @var App\View $view
 * @var string $email
 * @var array<string, string> $errors   field name => message
 */

// Prints the error of one field (if any) and returns the matching HTML attributes.
$fieldError = function (string $field) use ($errors, $view): string {
    return isset($errors[$field])
        ? '<p class="field-error" id="' . $field . '-error">' . $view->e($errors[$field]) . '</p>'
        : '';
};
$describedBy = fn (string $field): string => isset($errors[$field])
    ? ' aria-invalid="true" aria-describedby="' . $field . '-error"'
    : '';
?>
<section class="card auth-card">
  <h1>Sign up</h1>
  <p class="muted">Create an account to keep your files.</p>

  <form method="post" action="/register" novalidate>
    <?= $view->csrfField() ?>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= $view->e($email) ?>" autocomplete="email" required<?= $describedBy('email') ?>>
      <?= $fieldError('email') ?>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required<?= $describedBy('password') ?>>
      <?= $fieldError('password') ?>
    </div>
    <div class="field">
      <label for="password_confirmation">Repeat password</label>
      <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required<?= $describedBy('password_confirmation') ?>>
      <?= $fieldError('password_confirmation') ?>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Create account</button>
  </form>

  <p class="auth-switch">Already have an account? <a href="/login">Log in</a></p>
</section>
