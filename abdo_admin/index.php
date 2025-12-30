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
                <div class="password-input">
                    <input type="password" name="password" id="password" placeholder="••••••••" autocomplete="current-password" required>
                    <button type="button" class="toggle-password" data-toggle-password aria-pressed="false" aria-label="Afficher le mot de passe">
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <path class="icon-eye" d="M12 5C6 5 1.7 10 1 12c.7 2 5 7 11 7s10.3-5 11-7c-.7-2-5-7-11-7zm0 12c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6zm0-3a3 3 0 100-6 3 3 0 000 6z"/>
                            <path class="icon-eye-off" d="M4.3 3 3 4.3l3 3A11 11 0 001 12c.7 2 5 7 11 7 1.7 0 3.3-.4 4.7-1.1l2 2L20 19.7 4.3 3zM12 17c-3.3 0-6-2.7-6-6 0-.8.1-1.5.4-2.1l1.8 1.8a3 3 0 003.9 3.9l2.1 2.1c-.7.2-1.4.3-2.2.3zm8-5c-.4.9-1.5 2.3-3 3.4l-1.5-1.5a5 5 0 001.5-3 5 5 0 00-.5-2.2l1.6-1.6C19.7 8 21.1 9.9 21.6 12zM9.4 10.4 13.6 14.6A3 3 0 009.4 10.4zm4.8-4.8-1.3 1.3a3 3 0 00-3.6 3.6L8 11.9a5 5 0 014.9-6.3c.4 0 .9.1 1.3.2z"/>
                        </svg>
                    </button>
                </div>
            </label>
            <button class="btn" type="submit">Se connecter</button>
        </form>
    </div>
    <script>
        (function () {
            const passwordInput = document.querySelector('#password');
            const toggleButton = document.querySelector('[data-toggle-password]');
            if (!passwordInput || !toggleButton) {
                return;
            }

            toggleButton.addEventListener('click', function () {
                const isText = passwordInput.getAttribute('type') === 'text';
                passwordInput.setAttribute('type', isText ? 'password' : 'text');
                toggleButton.classList.toggle('is-visible', !isText);
                toggleButton.setAttribute('aria-pressed', String(!isText));
                toggleButton.setAttribute('aria-label', isText ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
            });
        }());
    </script>
</body>
</html>
