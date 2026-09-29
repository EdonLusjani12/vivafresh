<?php
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/inc/layout.php';
require __DIR__ . '/mail_setup.php';

const MAX_CV_BYTES = 5 * 1024 * 1024;
const ALLOWED_CV_TYPES = ['pdf', 'doc', 'docx'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /vrabotuvanje.php');
    exit;
}

function field(string $name, int $maxLength = 120): string
{
    return mb_substr(trim((string) ($_POST[$name] ?? '')), 0, $maxLength);
}

function render(bool $ok, string $title, string $message): void
{
    http_response_code($ok ? 200 : 400);
    site_header($title, 'vrabotuvanje');
    ?>
  <main class="wrap">
    <section class="page-hero">
      <span class="kicker"><i class="fas <?= $ok ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i> <?= $ok ? 'Испратено' : 'Грешка' ?></span>
      <h1><?= e($title) ?></h1>
      <p><?= e($message) ?></p>
      <div class="actions" style="justify-content:center">
<?php if ($ok): ?>
        <a class="btn btn-primary" href="/">Назад на почетна</a>
<?php else: ?>
        <a class="btn btn-primary" href="/vrabotuvanje.php#application" onclick="history.back();return false;">Обиди се повторно</a>
<?php endif; ?>
      </div>
    </section>
  </main>
<?php
    site_footer();
    exit;
}

$firstName = field('first_name');
$lastName = field('last_name');
$city = field('city');
$position = field('position', 150);
$phone = field('phone', 40);
$email = field('email', 190);

$successTitle = 'Ви благодариме!';
$successMessage = 'Вашата апликација е испратена. Ќе ве контактираме кога ќе има соодветна позиција.';
$failMessage = 'Се појави техничка грешка. Обидете се повторно подоцна или јавете се на ' . c('site.phone') . '.';

// Hidden honeypot field: real visitors never fill it in, bots usually do.
if (field('website') !== '') {
    render(true, $successTitle, $successMessage);
}

if (in_array('', [$firstName, $lastName, $city, $position, $phone, $email], true)) {
    render(false, 'Недостасуваат податоци', 'Ве молиме пополнете ги сите полиња.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    render(false, 'Невалидна е-пошта', 'Ве молиме внесете точна е-маил адреса.');
}

$cv = $_FILES['cv'] ?? null;
$uploadError = $cv['error'] ?? UPLOAD_ERR_NO_FILE;

if ($uploadError === UPLOAD_ERR_NO_FILE) {
    render(false, 'Недостасува CV', 'Ве молиме прикачете го вашето CV.');
}
if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE || $cv['size'] > MAX_CV_BYTES) {
    render(false, 'CV-то е преголемо', 'Максималната големина на датотеката е 5 MB.');
}
if ($uploadError !== UPLOAD_ERR_OK || !is_uploaded_file($cv['tmp_name'])) {
    render(false, 'Прикачувањето не успеа', 'CV-то не можеше да се прикачи. Обидете се повторно.');
}

$ext = strtolower(pathinfo($cv['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_CV_TYPES, true)) {
    render(false, 'Неподдржан формат', 'Прифаќаме само PDF, DOC или DOCX датотеки.');
}

$fullName = "$firstName $lastName";
$safeName = trim(preg_replace('/[^\p{L}\p{N}]+/u', '_', $fullName), '_');
$attachmentName = 'CV_' . ($safeName !== '' ? $safeName : 'kandidat') . '.' . $ext;

// Keep a copy of the CV for the admin panel; if saving fails, still attach it from the temp upload.
$attachPath = $cv['tmp_name'];
$savedFile = '';
if (is_dir(CV_DIR) || @mkdir(CV_DIR, 0750, true)) {
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (move_uploaded_file($cv['tmp_name'], CV_DIR . '/' . $name)) {
        $attachPath = CV_DIR . '/' . $name;
        $savedFile = $name;
    } else {
        error_log('send_email.php: could not save CV to ' . CV_DIR);
    }
} else {
    error_log('send_email.php: could not create ' . CV_DIR);
}

$applicationId = null;
if ($pdo = db()) {
    try {
        $pdo->prepare(
            'INSERT INTO vf_applications (first_name, last_name, city, position, phone, email, cv_file, cv_name, mail_sent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?)'
        )->execute([$firstName, $lastName, $city, $position, $phone, $email, $savedFile, $attachmentName, now()]);
        $applicationId = (int) $pdo->lastInsertId();
    } catch (PDOException $ex) {
        error_log('send_email.php: could not save application: ' . $ex->getMessage());
    }
}

$rows = [
    'Име и презиме' => $fullName,
    'Град' => $city,
    'Позиција' => $position,
    'Телефон' => $phone,
    'Е-маил' => $email,
];
$htmlRows = '';
$textRows = '';
foreach ($rows as $label => $value) {
    $htmlRows .= '<p><strong>' . e($label) . ':</strong> ' . e($value) . '</p>';
    $textRows .= "$label: $value\n";
}

$configFile = __DIR__ . '/mail_config.php';
$mailSent = false;
$mail = null;

if (!is_file($configFile)) {
    error_log('send_email.php: mail_config.php is missing');
} else {
    try {
        $config = require $configFile;
        $mail = create_mailer($config);
        $mail->addAddress($config['to']);
        $mail->addReplyTo($email, $fullName);

        $mail->isHTML(true);
        $mail->Subject = "Нова апликација за работа: $fullName ($position)";
        $mail->Body = '<h3>Нова апликација за работа</h3>' . $htmlRows . '<p>CV-то е во прилог.</p>';
        $mail->AltBody = "Нова апликација за работа\n\n" . $textRows . "\nCV-то е во прилог.";
        $mail->addAttachment($attachPath, $attachmentName);

        $mail->send();
        $mailSent = true;
    } catch (Exception $e) {
        error_log('send_email.php: ' . ($mail ? $mail->ErrorInfo : $e->getMessage()));
    }
}

if ($mailSent && $applicationId) {
    db()->prepare('UPDATE vf_applications SET mail_sent = 1 WHERE id = ?')->execute([$applicationId]);
}

// The application is not lost if it reached the admin panel, even when the e-mail failed.
if ($mailSent || $applicationId) {
    render(true, $successTitle, $successMessage);
}
render(false, 'Апликацијата не е испратена', $failMessage);
