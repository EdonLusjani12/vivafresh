<?php
// Run from cPanel Terminal inside public_html:  php mail_test.php you@example.com
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/mail_setup.php';

$configFile = __DIR__ . '/mail_config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "mail_config.php is missing next to this script.\n");
    exit(1);
}
$config = require $configFile;
$to = $argv[1] ?? $config['to'];

$mail = null;

try {
    $mail = create_mailer($config);
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = 'echo';
    $mail->addAddress($to);
    $mail->Subject = 'Viva Fresh – тест порака';
    $mail->Body = "Тест од vivafresh.mk (mail_test.php).\nАко ова го читате, праќањето работи.";
    $mail->send();
    echo "\nOK: test email sent to $to\n";
} catch (Exception $e) {
    echo "\nFAILED: " . ($mail ? $mail->ErrorInfo : $e->getMessage()) . "\n";
    exit(1);
}
