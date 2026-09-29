<?php
require __DIR__ . '/../inc/admin.php';

$admin = require_admin();
$pdo = admin_db();

const MIN_PASSWORD_LENGTH = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $currentPassword = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['new'] ?? '');

    $stmt = $pdo->prepare('SELECT password_hash FROM vf_admins WHERE id = ?');
    $stmt->execute([$admin['id']]);
    $hash = (string) $stmt->fetchColumn();

    if (!password_verify($currentPassword, $hash)) {
        flash('Моменталната лозинка не е точна.', 'error');
    } elseif (mb_strlen($new) < MIN_PASSWORD_LENGTH) {
        flash('Новата лозинка мора да има најмалку ' . MIN_PASSWORD_LENGTH . ' знаци.', 'error');
    } elseif ($new !== (string) ($_POST['confirm'] ?? '')) {
        flash('Новите лозинки не се совпаѓаат.', 'error');
    } else {
        $pdo->prepare('UPDATE vf_admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
        session_regenerate_id(true);
        flash('Лозинката е сменета.');
    }
    redirect('/admin/password.php');
}

admin_header('Лозинка', 'password');
?>
    <h1>Промена на лозинка</h1>
    <form class="panel narrow" method="post">
      <?= csrf_field() ?>
      <div class="field">
        <label for="current">Моментална лозинка</label>
        <input id="current" type="password" name="current" autocomplete="current-password" required>
      </div>
      <div class="field">
        <label for="new">Нова лозинка</label>
        <input id="new" type="password" name="new" autocomplete="new-password" minlength="<?= MIN_PASSWORD_LENGTH ?>" required>
        <small>Најмалку <?= MIN_PASSWORD_LENGTH ?> знаци.</small>
      </div>
      <div class="field">
        <label for="confirm">Повтори нова лозинка</label>
        <input id="confirm" type="password" name="confirm" autocomplete="new-password" minlength="<?= MIN_PASSWORD_LENGTH ?>" required>
      </div>
      <div class="form-foot">
        <button class="button primary" type="submit">Смени лозинка</button>
      </div>
    </form>
<?php
admin_footer();
