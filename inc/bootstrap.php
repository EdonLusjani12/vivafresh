<?php
date_default_timezone_set('Europe/Skopje');
mb_internal_encoding('UTF-8');

define('APP_ROOT', dirname(__DIR__));
define('MEDIA_DIR', APP_ROOT . '/media');
// Outside public_html, so saved CVs can never be opened from the web.
define('CV_DIR', dirname(APP_ROOT) . '/cv_uploads');

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/**
 * db_config.local.php (SQLite, for local preview) wins over the server's db_config.php (MySQL).
 * Returns null when no database is reachable, so public pages can fall back to defaults.
 */
function db(): ?PDO
{
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }
    $pdo = null;

    $file = is_file(APP_ROOT . '/db_config.local.php') ? APP_ROOT . '/db_config.local.php' : APP_ROOT . '/db_config.php';
    if (!is_file($file)) {
        return null;
    }
    $c = require $file;

    try {
        if (($c['driver'] ?? 'mysql') === 'sqlite') {
            $pdo = new PDO('sqlite:' . $c['path']);
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $c['host'],
                $c['port'] ?? 3306,
                $c['db'],
                $c['charset'] ?? 'utf8mb4'
            );
            $pdo = new PDO($dsn, $c['user'], $c['pass']);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        error_log('db: ' . $ex->getMessage());
        $pdo = null;
    }
    return $pdo;
}

function content_schema(): array
{
    static $schema = null;
    return $schema ??= require __DIR__ . '/content_schema.php';
}

function content_all(bool $reload = false): array
{
    static $values = null;
    if ($values !== null && !$reload) {
        return $values;
    }

    $values = [];
    foreach (content_schema() as $section) {
        foreach ($section['fields'] as $key => $field) {
            $values[$key] = $field['default'];
        }
    }

    if ($pdo = db()) {
        try {
            foreach ($pdo->query('SELECT ckey, cvalue FROM vf_content') as $row) {
                $values[$row['ckey']] = $row['cvalue'];
            }
        } catch (PDOException $ex) {
            error_log('content: ' . $ex->getMessage());
        }
    }
    return $values;
}

/** Raw content value; escape it before output. */
function c(string $key): string
{
    return (string) (content_all()[$key] ?? '');
}

/** Escaped content value. */
function t(string $key): string
{
    return e(c($key));
}

/** Escaped content value with line breaks kept. */
function tn(string $key): string
{
    return nl2br(e(c($key)), false);
}

function content_save(string $key, string $value): void
{
    $stmt = db()->prepare('REPLACE INTO vf_content (ckey, cvalue, updated_at) VALUES (?, ?, ?)');
    $stmt->execute([$key, $value, now()]);
}

function content_delete(string $key): void
{
    db()->prepare('DELETE FROM vf_content WHERE ckey = ?')->execute([$key]);
}

const DEFAULT_MARKETS = [
    ['number' => 1, 'name' => 'Вива Фреш', 'type' => 'super', 'city' => 'Скопје'],
    ['number' => 2, 'name' => 'Вива Фреш', 'type' => 'regular', 'city' => 'Скопје'],
];

function markets(): array
{
    $list = json_decode(c('cenovnik.markets'), true);
    return is_array($list) ? $list : DEFAULT_MARKETS;
}

function open_positions(): array
{
    if (!$pdo = db()) {
        return [];
    }
    try {
        return $pdo->query('SELECT * FROM vf_positions WHERE is_open = 1 ORDER BY sort_order, id DESC')->fetchAll();
    } catch (PDOException $ex) {
        error_log('positions: ' . $ex->getMessage());
        return [];
    }
}

function phone_digits(): string
{
    return preg_replace('/\D+/', '', c('site.phone'));
}

function phone_intl(): string
{
    return preg_replace('/\D+/', '', c('site.phone_intl'));
}

function youtube_id(string $value): string
{
    if (preg_match('~^[\w-]{11}$~', $value)) {
        return $value;
    }
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:embed/|watch\?(?:.*&)?v=|shorts/|live/))([\w-]{11})~', $value, $m)) {
        return $m[1];
    }
    return '';
}

/** Root-relative URL for a local file, with a cache-busting version. */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = APP_ROOT . '/' . $path;
    return '/' . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}
