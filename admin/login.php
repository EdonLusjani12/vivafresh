<?php
require __DIR__ . '/../inc/admin.php';

if (current_admin()) {
    redirect('/admin/');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $error = attempt_login($username, (string) ($_POST['password'] ?? ''));
    if ($error === '') {
        redirect('/admin/');
    }
}

admin_header('Најава');
?>
    <form class="panel login-card" method="post">
      <div class="login-logo"><i class="fas fa-leaf"></i></div>
      <h1>Viva Fresh Админ</h1>
<?php if ($error !== ''): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
      <?= csrf_field() ?>
      <label>Корисничко име
        <input type="text" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
      </label>
      <label>Лозинка
        <input type="password" name="password" autocomplete="current-password" required>
      </label>
      <button class="button primary" type="submit">Најави се</button>
    </form>
<?php
admin_footer();
