<?php
// Copy this file to mail_config.php on the server and fill it in.
// mail_config.php is ignored by Git and blocked from the web by .htaccess.
//
// spar.mk Exchange ports (all use STARTTLS and a self-signed certificate):
//   8025 - no login; only accepts mail from IP addresses IT has allowed  -> 'auth' => false
//   587  - login with username and password                              -> 'auth' => true
return [
    'host' => 'mail.spar.mk',
    'port' => 8025,
    'secure' => 'tls', // 'tls' = STARTTLS, 'ssl' = implicit TLS, '' = none
    'auth' => false,
    'username' => 'noreply@spar.mk',
    'password' => '',
    'verify_cert' => false, // the Exchange certificate is not issued for mail.spar.mk
    'from_email' => 'noreply@spar.mk',
    'from_name' => 'Viva Fresh',
    'to' => 'edon.lusjani@spar.mk',
];
