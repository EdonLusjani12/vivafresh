<?php
require_once __DIR__ . '/bootstrap.php';

const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
const IMAGE_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

/**
 * Validates an uploaded image and stores it in media/ under a random name.
 * Returns the site-relative path (e.g. "media/3f9a….jpg"); throws RuntimeException with a
 * message that can be shown to the admin.
 */
function save_uploaded_image(array $file): string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || ($file['size'] ?? 0) > MAX_IMAGE_BYTES) {
        throw new RuntimeException('Сликата е преголема (макс. 5 MB).');
    }
    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Сликата не можеше да се прикачи.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(IMAGE_TYPES[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Дозволени се само JPG, PNG, WEBP или GIF слики.');
    }

    if (!is_dir(MEDIA_DIR) && !@mkdir(MEDIA_DIR, 0755, true)) {
        throw new RuntimeException('Папката media/ не може да се создаде.');
    }

    $name = date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . IMAGE_TYPES[$mime];
    if (!move_uploaded_file($file['tmp_name'], MEDIA_DIR . '/' . $name)) {
        throw new RuntimeException('Сликата не можеше да се зачува.');
    }
    return 'media/' . $name;
}

/** Deletes a previously uploaded image unless another content field still uses it. */
function delete_media_if_unused(string $path): void
{
    if (!preg_match('~^media/[\w.-]+$~', $path)) {
        return;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM vf_content WHERE cvalue = ?');
    $stmt->execute([$path]);
    if ((int) $stmt->fetchColumn() === 0) {
        @unlink(APP_ROOT . '/' . $path);
    }
}
