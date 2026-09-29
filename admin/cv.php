<?php
require __DIR__ . '/../inc/admin.php';

require_admin();

$stmt = admin_db()->prepare('SELECT cv_file, cv_name FROM vf_applications WHERE id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$row = $stmt->fetch();

$path = $row && $row['cv_file'] !== '' ? CV_DIR . '/' . basename($row['cv_file']) : '';
if ($path === '' || !is_file($path)) {
    http_response_code(404);
    exit('CV-то не е пронајдено.');
}

$types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$name = $row['cv_name'] !== '' ? $row['cv_name'] : 'CV.' . $ext;
$asciiName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);

header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header("Content-Disposition: attachment; filename=\"$asciiName\"; filename*=UTF-8''" . rawurlencode($name));
readfile($path);
