<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../includes/functions.php';

$config = require __DIR__ . '/../config/config.php';

/** @var PDO $pdo */
$pdo = require __DIR__ . '/../config/database.php';

logVisit($pdo);

$settings = getSettings($pdo);
$themeVars = getActiveThemeVars($settings['active_theme'] ?? 'onyx', $settings);
$brandTitleSetting = trim($settings['brand_title'] ?? '');
$brandName = $brandTitleSetting !== '' ? $brandTitleSetting : ($config['brand_name'] ?? 'ABDO IPTV CANADA');

$lang = 'en';
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'fr' ? 'fr' : 'en';
} elseif (!empty($_COOKIE['site_lang'])) {
    $lang = strtolower((string) $_COOKIE['site_lang']) === 'fr' ? 'fr' : 'en';
}
if (!isset($_COOKIE['site_lang']) || $_COOKIE['site_lang'] !== $lang) {
    setcookie('site_lang', $lang, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = appBasePath();
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
$publicBase = $basePath;
if ($docRoot === '' || !is_dir($docRoot . $publicBase . '/assets')) {
    $publicBase = rtrim($basePath . '/public', '/');
}
$assetBase = $publicBase . '/assets';
$faviconUrl = trim($settings['site_favicon'] ?? '') ?: ($assetBase . '/favicon.ico');
$homeUrl = $publicBase . '/?lang=' . urlencode($lang);

$pageTitle = $lang === 'fr' ? 'Conditions d’utilisation' : 'Terms of Service';
$subtitle = $lang === 'fr'
    ? 'Veuillez lire ces conditions avant d’utiliser nos services IPTV.'
    : 'Please read these terms before using our IPTV services.';

$sections = $lang === 'fr'
    ? [
        'Acceptation des conditions' => [
            'En utilisant nos services, vous acceptez ces conditions et les politiques associées.',
            'Les offres et fonctionnalités peuvent évoluer pour améliorer l’expérience.',
        ],
        'Utilisation du service' => [
            'Accès personnel et non transférable, conformément aux règles de votre équipement.',
            'Interdiction d’un usage frauduleux, de partage non autorisé ou de revente.',
        ],
        'Paiements & renouvellements' => [
            'Les prix et durées sont indiqués au moment de la commande.',
            'Les remboursements sont gérés au cas par cas selon la réglementation locale.',
        ],
        'Disponibilité' => [
            'Nous visons une haute disponibilité, mais des maintenances ou incidents peuvent survenir.',
            'Nous vous informerons des interruptions planifiées quand cela est possible.',
        ],
        'Responsabilité' => [
            'Le contenu et les sources tiers peuvent varier; nous ne garantissons pas la disponibilité permanente de chaque chaîne ou VOD.',
            'En cas de manquement, notre responsabilité est limitée au montant payé pour la période concernée.',
        ],
        'Contact' => [
            'Support via WhatsApp ou email pour toute question ou réclamation.',
        ],
    ]
    : [
        'Acceptance of terms' => [
            'By using our services you agree to these terms and related policies.',
            'Plans and features may evolve to improve the experience.',
        ],
        'Service use' => [
            'Personal, non-transferable access in line with your device’s rules.',
            'No fraudulent use, unauthorized sharing, or reselling is allowed.',
        ],
        'Payments & renewals' => [
            'Prices and durations are shown at checkout time.',
            'Refunds are handled case by case according to local regulations.',
        ],
        'Availability' => [
            'We aim for high uptime, but maintenance or incidents may occur.',
            'We will communicate planned interruptions whenever possible.',
        ],
        'Liability' => [
            'Third-party content and sources can change; we cannot guarantee permanent availability of every channel or VOD.',
            'If issues arise, our liability is limited to the amount paid for the affected period.',
        ],
        'Contact' => [
            'Support via WhatsApp or email for any questions or complaints.',
        ],
    ];

?><!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e($brandName) ?></title>
    <meta name="description" content="<?= e($subtitle) ?>">
    <link rel="icon" href="<?= e($faviconUrl) ?>" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e($assetBase) ?>/css/style.css?v=<?= time() ?>">
    <style>
        :root {
            <?php foreach ($themeVars as $var => $value): ?>
            <?= $var ?>: <?= e($value) ?>;
            <?php endforeach; ?>
        }
    </style>
</head>
<body data-theme="<?= e($settings['active_theme'] ?? 'onyx') ?>">
    <main class="legal">
        <div class="section-head">
            <p class="eyebrow"><?= $lang === 'fr' ? 'Informations légales' : 'Legal information' ?></p>
            <h2><?= e($pageTitle) ?></h2>
            <p class="subtitle"><?= e($subtitle) ?></p>
        </div>
        <div class="legal-body" style="text-align: left;">
            <?php foreach ($sections as $title => $items): ?>
                <h3 style="margin-top: 1rem; color: var(--text-primary);"><?= e($title) ?></h3>
                <ul style="margin: 0.4rem 0 1rem 1.2rem; padding: 0;">
                    <?php foreach ($items as $item): ?>
                        <li style="margin-bottom: 0.35rem;"><?= e($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
            <p style="margin-top: 1.2rem; color: var(--text-secondary);">
                <?= $lang === 'fr'
                    ? 'Dernière mise à jour : ' . date('F Y')
                    : 'Last updated: ' . date('F Y') ?>
            </p>
            <a class="btn" href="<?= e($homeUrl) ?>"><?= $lang === 'fr' ? '← Retour à l’accueil' : '← Back to home' ?></a>
        </div>
    </main>
</body>
</html>
