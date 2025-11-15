<?php
declare(strict_types=1);
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
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
    header('Location: /?contact=error');
    exit;
}

$stmt = $pdo->prepare('INSERT INTO contact_messages (full_name, email, phone, message) VALUES (:full_name, :email, :phone, :message)');
$stmt->execute([
    'full_name' => $fullName,
    'email' => $email,
    'phone' => $phone,
    'message' => $message,
]);

header('Location: /?contact=success');
exit;
