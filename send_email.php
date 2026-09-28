<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer.php';
require __DIR__ . '/SMTP.php';
require __DIR__ . '/Exception.php';

const MAX_CV_BYTES = 5 * 1024 * 1024;
const ALLOWED_CV_TYPES = ['pdf', 'doc', 'docx'];
const CONTACT_PHONE = '071 350 288';

// Outside public_html, so saved CVs can never be opened from the web.
$cvDir = dirname(__DIR__) . '/cv_uploads';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: vrabotuvanje.html');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function field(string $name, int $maxLength = 120): string
{
    return mb_substr(trim((string) ($_POST[$name] ?? '')), 0, $maxLength);
}

function render(bool $ok, string $title, string $message): void
{
    http_response_code($ok ? 200 : 400);
    $icon = $ok ? 'fa-circle-check' : 'fa-circle-exclamation';
    $kicker = $ok ? 'Испратено' : 'Грешка';
    $action = $ok
        ? '<a class="btn btn-primary" href="index.html">Назад на почетна</a>'
        : '<a class="btn btn-primary" href="vrabotuvanje.html#application" onclick="history.back();return false;">Обиди се повторно</a>';
    $title = e($title);
    $message = e($message);

    echo <<<HTML
<!DOCTYPE html>
<html lang="mk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title} - Viva Fresh Store MK</title>
  <link rel="icon" href="Logo.png" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="css/site.css">
</head>
<body>
  <header class="site-header">
    <div class="wrap header-inner">
      <a class="brand" href="index.html">
        <img src="Logo.png" alt="Viva Fresh">
        <span>Viva Fresh<span class="brand-mk"> MK</span></span>
      </a>
      <button class="nav-toggle" id="navToggle" aria-label="Мени"><i class="fas fa-bars"></i></button>
      <nav class="nav" id="nav">
        <a href="index.html">Дома</a>
        <a href="katalog.html">Каталог</a>
        <a href="cenovnik.html">Ценовници</a>
        <a href="kontakt.html">Контакт</a>
        <a class="active" href="vrabotuvanje.html">Вработување</a>
        <a href="toogoodtogo1.php">Too Good To Go</a>
      </nav>
    </div>
  </header>
  <main class="wrap">
    <section class="page-hero">
      <span class="kicker"><i class="fas {$icon}"></i> {$kicker}</span>
      <h1>{$title}</h1>
      <p>{$message}</p>
      <div class="actions" style="justify-content:center">{$action}</div>
    </section>
  </main>
  <footer class="site-footer">
    <div class="wrap copy">© 2026 Viva Fresh Store MK</div>
  </footer>
  <script src="js/site.js"></script>
</body>
</html>
HTML;
    exit;
}

$firstName = field('first_name');
$lastName = field('last_name');
$city = field('city');
$position = field('position');
$phone = field('phone', 40);
$email = field('email', 190);

$successTitle = 'Ви благодариме!';
$successMessage = 'Вашата апликација е испратена. Ќе ве контактираме кога ќе има соодветна позиција.';
$failMessage = 'Се појави техничка грешка. Обидете се повторно подоцна или јавете се на ' . CONTACT_PHONE . '.';

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

$configFile = __DIR__ . '/mail_config.php';
if (!is_file($configFile)) {
    error_log('send_email.php: mail_config.php is missing');
    render(false, 'Апликацијата не е испратена', $failMessage);
}
$config = require $configFile;

// Keep a copy of the CV in case the email fails; if saving fails, still attach it from the temp upload.
$attachPath = $cv['tmp_name'];
if (is_dir($cvDir) || @mkdir($cvDir, 0750, true)) {
    $savedPath = $cvDir . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (move_uploaded_file($cv['tmp_name'], $savedPath)) {
        $attachPath = $savedPath;
    } else {
        error_log('send_email.php: could not save CV to ' . $cvDir);
    }
} else {
    error_log('send_email.php: could not create ' . $cvDir);
}

$fullName = "$firstName $lastName";
$safeName = trim(preg_replace('/[^\p{L}\p{N}]+/u', '_', $fullName), '_');
$attachmentName = 'CV_' . ($safeName !== '' ? $safeName : 'kandidat') . '.' . $ext;

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

$mail = new PHPMailer(true);

try {
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = ($config['secure'] ?? 'ssl') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = (int) $config['port'];

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to']);
    $mail->addReplyTo($email, $fullName);

    $mail->isHTML(true);
    $mail->Subject = "Нова апликација за работа: $fullName ($position)";
    $mail->Body = '<h3>Нова апликација за работа</h3>' . $htmlRows . '<p>CV-то е во прилог.</p>';
    $mail->AltBody = "Нова апликација за работа\n\n" . $textRows . "\nCV-то е во прилог.";
    $mail->addAttachment($attachPath, $attachmentName);

    $mail->send();
} catch (Exception $e) {
    error_log('send_email.php: ' . $mail->ErrorInfo);
    render(false, 'Апликацијата не е испратена', $failMessage);
}

render(true, $successTitle, $successMessage);
