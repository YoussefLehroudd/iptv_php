<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/functions.php';
/** @var PDO $pdo */
$pdo = require __DIR__ . '/../config/database.php';

$basePath = appBasePath();
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
$publicBase = $basePath;
if ($docRoot === '' || !is_dir($docRoot . $publicBase . '/assets')) {
    $publicBase = rtrim($basePath . '/public', '/');
}
$assetBase = $publicBase . '/assets';
$adminBase = $basePath . '/abdo_admin';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . $adminBase . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_email'] = $user['email'];
        header('Location: ' . $adminBase . '/dashboard.php');
        exit;
    }

    $error = 'Identifiants invalides';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABDO IPTV · Admin</title>
    <link rel="stylesheet" href="<?= $assetBase ?>/css/admin.css?v=<?= time() ?>">
</head>
<body class="admin auth">
    <div class="auth-wrapper">
        <div class="auth-illustration">
            <h1>ABDO IPTV Control Center</h1>
            <p>Gère ton contenu IPTV 2025, les offres WhatsApp et les messages clients en un seul endroit sécurisé.</p>
            <ul>
                <li>Mise à jour du hero et des sliders.</li>
                <li>Switch instantané entre 6 thèmes monochromes.</li>
                <li>Visualise les analytics visiteurs par pays.</li>
            </ul>
            <small>Support Canada · Sécurité CSRF · Cloudinary uploads</small>
        </div>
        <form class="auth-card" method="POST">
            <h1>Connexion admin</h1>
            <?php if ($error): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>
            <label>Email
                <input type="email" name="email" placeholder="admin@iptvabdo.com" required>
            </label>
            <label>Mot de passe
                <input type="password" name="password" placeholder="••••••••" required>
            </label>
            <button class="btn" type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>
