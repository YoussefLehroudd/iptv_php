<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/functions.php';

$_SESSION = [];
session_destroy();
$basePath = appBasePath();
header('Location: ' . $basePath . '/abdo_admin/index.php');
exit;
