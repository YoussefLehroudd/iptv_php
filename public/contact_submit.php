<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/functions.php';

function redirectWithStatus(string $status): void
{
    $target = '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';

    if ($referer) {
        $parts = parse_url($referer);
        if ($parts && (empty($parts['host']) || $parts['host'] === $currentHost)) {
            $scheme = $parts['scheme'] ?? '';
            $host = $parts['host'] ?? '';
            $path = $parts['path'] ?? '';
            $query = $parts['query'] ?? '';
            $target = ($scheme ? $scheme . '://' : '') . ($host ? $host : '') . $path;
            if ($query) {
                $target .= '?' . $query;
            }
        }
    }

    if ($target === '') {
        $basePath = appBasePath();
        $target = $basePath === '' ? '/' : $basePath . '/';
    }

    $target = preg_replace('/#.*$/', '', $target);
    $target .= (str_contains($target, '?') ? '&' : '?') . 'contact=' . $status . '#support';
    header('Location: ' . $target);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithStatus('error');
}

$config = require __DIR__ . '/../config/config.php';
/** @var PDO $pdo */
$pdo = require __DIR__ . '/../config/database.php';

$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Jeton CSRF invalide.');
}

$fullName = trim($_POST['full_name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$fullName || !$email || !$message) {
    redirectWithStatus('error');
}

$stmt = $pdo->prepare('INSERT INTO contact_messages (full_name, email, phone, message) VALUES (:full_name, :email, :phone, :message)');
$stmt->execute([
    'full_name' => $fullName,
    'email' => $email,
    'phone' => $phone,
    'message' => $message,
]);

redirectWithStatus('success');
