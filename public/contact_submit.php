<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/functions.php';

$isAjax = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (isset($_POST['ajax']) && $_POST['ajax'] === '1')
);

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
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
        exit;
    }
    redirectWithStatus('error');
}

$config = require __DIR__ . '/../config/config.php';
/** @var PDO $pdo */
$pdo = require __DIR__ . '/../config/database.php';

$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF token invalid.']);
        exit;
    }
    exit('Jeton CSRF invalide.');
}

$fullName = trim($_POST['full_name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$fullName || !$email || !$message) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
        exit;
    }
    redirectWithStatus('error');
}

$stmt = $pdo->prepare('INSERT INTO contact_messages (full_name, email, phone, message, is_read) VALUES (:full_name, :email, :phone, :message, :is_read)');
$stmt->execute([
    'full_name' => $fullName,
    'email' => $email,
    'phone' => $phone,
    'message' => $message,
    'is_read' => 0,
]);

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

redirectWithStatus('success');
