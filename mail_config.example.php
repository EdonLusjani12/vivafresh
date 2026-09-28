<?php
// Copy this file to mail_config.php on the server and put the real password in it.
// mail_config.php is ignored by Git and blocked from the web by .htaccess.
return [
    'host' => 'mail.spar.mk',
    'port' => 8025,
    'secure' => 'ssl', // 'ssl' = SMTPS (implicit TLS), 'tls' = STARTTLS
    'username' => 'noreply@spar.mk',
    'password' => '',
    'from_email' => 'noreply@spar.mk',
    'from_name' => 'Viva Fresh',
    'to' => 'edon.lusjani@spar.mk',
];
