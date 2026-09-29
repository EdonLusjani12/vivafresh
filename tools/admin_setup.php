<?php
/*
 * Creates the admin database tables and an admin account (or resets its password).
 * Run from the server terminal as the site user:
 *   su -s /bin/bash vivafre1 -c "cd ~/public_html && php tools/admin_setup.php admin"
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../inc/schema.php';

$username = trim($argv[1] ?? '');
if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
    fwrite(STDERR, "Usage: php tools/admin_setup.php <username>\n(3-60 characters: letters, digits, . _ -)\n");
    exit(1);
}

$pdo = db();
if (!$pdo) {
    fwrite(STDERR, "Cannot connect to the database. Check db_config.php.\n");
    exit(1);
}

install_schema($pdo);
echo "Database tables are ready.\n";

function ask_hidden(string $prompt): string
{
    echo $prompt;
    $hide = DIRECTORY_SEPARATOR === '/' && stream_isatty(STDIN);
    if ($hide) {
        shell_exec('stty -echo');
    }
    $value = rtrim((string) fgets(STDIN), "\r\n");
    if ($hide) {
        shell_exec('stty echo');
        echo "\n";
    }
    return $value;
}

$password = ask_hidden("Password for '$username' (min. 10 characters): ");
if (mb_strlen($password) < 10) {
    fwrite(STDERR, "Password must be at least 10 characters.\n");
    exit(1);
}
if (ask_hidden('Repeat password: ') !== $password) {
    fwrite(STDERR, "Passwords do not match.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('SELECT id FROM vf_admins WHERE username = ?');
$stmt->execute([$username]);

if ($id = $stmt->fetchColumn()) {
    $pdo->prepare('UPDATE vf_admins SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
    echo "Password for '$username' has been reset.\n";
} else {
    $pdo->prepare('INSERT INTO vf_admins (username, password_hash, created_at) VALUES (?, ?, ?)')->execute([$username, $hash, now()]);
    echo "Admin '$username' created. Log in at /admin/\n";
}
