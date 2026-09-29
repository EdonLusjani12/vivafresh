<?php
require_once __DIR__ . '/bootstrap.php';

const SESSION_IDLE_SECONDS = 2 * 3600;
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW_MINUTES = 15;

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

function https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

session_name('vf_admin');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/admin/',
    'secure' => https(),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function admin_db(): PDO
{
    $pdo = db();
    if (!$pdo) {
        http_response_code(500);
        exit('Базата на податоци не е достапна. Проверете db_config.php.');
    }
    return $pdo;
}

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    if (time() - ($_SESSION['last_seen'] ?? 0) > SESSION_IDLE_SECONDS) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }
    $_SESSION['last_seen'] = time();
    return ['id' => (int) $_SESSION['admin_id'], 'username' => $_SESSION['admin_username']];
}

function require_admin(): array
{
    $admin = current_admin();
    if (!$admin) {
        header('Location: /admin/login.php');
        exit;
    }
    return $admin;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Every POST in the admin must carry the session's CSRF token. */
function require_post_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Невалидно барање. Освежете ја страницата и обидете се повторно.');
    }
}

function flash(string $message, string $type = 'ok'): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function login_blocked(PDO $pdo): bool
{
    $since = date('Y-m-d H:i:s', time() - LOGIN_WINDOW_MINUTES * 60);
    $pdo->prepare('DELETE FROM vf_login_attempts WHERE attempted_at < ?')->execute([$since]);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM vf_login_attempts WHERE ip = ?');
    $stmt->execute([client_ip()]);
    return (int) $stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS;
}

function attempt_login(string $username, string $password): string
{
    $pdo = admin_db();
    if (login_blocked($pdo)) {
        return 'Премногу неуспешни обиди. Обидете се повторно за ' . LOGIN_WINDOW_MINUTES . ' минути.';
    }

    $stmt = $pdo->prepare('SELECT * FROM vf_admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        $pdo->prepare('INSERT INTO vf_login_attempts (ip, attempted_at) VALUES (?, ?)')->execute([client_ip(), now()]);
        return 'Погрешно корисничко име или лозинка.';
    }

    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
        $pdo->prepare('UPDATE vf_admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
    }
    $pdo->prepare('DELETE FROM vf_login_attempts WHERE ip = ?')->execute([client_ip()]);
    $pdo->prepare('UPDATE vf_admins SET last_login_at = ? WHERE id = ?')->execute([now(), $admin['id']]);

    session_regenerate_id(true);
    $_SESSION = [
        'admin_id' => (int) $admin['id'],
        'admin_username' => $admin['username'],
        'last_seen' => time(),
    ];
    return '';
}

const ADMIN_NAV = [
    'dashboard' => ['/admin/', 'fa-gauge', 'Преглед'],
    'content' => ['/admin/content.php', 'fa-pen-to-square', 'Содржина и слики'],
    'positions' => ['/admin/positions.php', 'fa-briefcase', 'Позиции'],
    'applications' => ['/admin/applications.php', 'fa-inbox', 'Апликации'],
    'markets' => ['/admin/markets.php', 'fa-store', 'Маркети'],
    'password' => ['/admin/password.php', 'fa-key', 'Лозинка'],
];

function admin_header(string $title, string $active = ''): void
{
    $admin = current_admin();
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    ?>
<!DOCTYPE html>
<html lang="mk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> - Админ</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= e(asset('admin/admin.css')) ?>">
</head>
<body class="<?= $admin ? 'has-sidebar' : 'is-login' ?>">
<?php if ($admin): ?>
  <aside class="sidebar">
    <a class="side-brand" href="/admin/"><i class="fas fa-leaf"></i> Viva Fresh <small>Админ</small></a>
    <nav>
<?php foreach (ADMIN_NAV as $id => [$href, $icon, $label]): ?>
      <a class="<?= $id === $active ? 'active' : '' ?>" href="<?= e($href) ?>"><i class="fas <?= e($icon) ?>"></i> <?= e($label) ?></a>
<?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <a href="/" target="_blank"><i class="fas fa-arrow-up-right-from-square"></i> Отвори сајт</a>
      <form method="post" action="/admin/logout.php">
        <?= csrf_field() ?>
        <button type="submit"><i class="fas fa-right-from-bracket"></i> Одјава (<?= e($admin['username']) ?>)</button>
      </form>
    </div>
  </aside>
<?php endif; ?>
  <main class="admin-main">
<?php foreach ($messages as [$type, $text]): ?>
    <div class="alert alert-<?= e($type) ?>"><?= e($text) ?></div>
<?php endforeach; ?>
<?php
}

function admin_footer(): void
{
    ?>
  </main>
</body>
</html>
<?php
}
