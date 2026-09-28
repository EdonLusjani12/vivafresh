<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';
require_once __DIR__ . '/Exception.php';

function create_mailer(array $config): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->Port = (int) $config['port'];
    $mail->Timeout = 20;

    $secure = $config['secure'] ?? 'tls';
    if ($secure === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($secure === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
    }

    $mail->SMTPAuth = (bool) ($config['auth'] ?? true);
    if ($mail->SMTPAuth) {
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
    }

    if (($config['verify_cert'] ?? true) === false) {
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
    }

    $mail->setFrom($config['from_email'], $config['from_name']);

    return $mail;
}
