<?php
require __DIR__ . '/../inc/admin.php';

require_post_csrf();
$_SESSION = [];
session_regenerate_id(true);
session_destroy();
redirect('/admin/login.php');
