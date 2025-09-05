<?php
require_once __DIR__ . '/auth.php';

session_start();
$_SESSION['logout_success'] = true;
$auth->logout();
header('Location: /login');
exit;
