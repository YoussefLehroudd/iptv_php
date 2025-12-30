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

$pageTitle = $lang === 'fr' ? 'Politique de confidentialité' : 'Privacy Policy';
$subtitle = $lang === 'fr'
    ? 'Comment nous collectons, utilisons et protégeons vos informations pour un service IPTV sécurisé.'
    : 'How we collect, use and protect your information to deliver a secure IPTV experience.';

$sections = $lang === 'fr'
    ? [
        'Données collectées' => [
            'Coordonnées de contact que vous fournissez (nom, email, WhatsApp).',
            'Informations de commande et préférences liées à votre abonnement.',
            'Données techniques basiques (IP, appareil) pour sécuriser l’accès.',
        ],
        'Utilisation des données' => [
            'Activer et gérer votre abonnement IPTV.',
            'Assurer le support client et vous informer des changements importants.',
            'Améliorer la performance et la sécurité de nos services.',
        ],
        'Partage limité' => [
            'Nous ne revendons pas vos données.',
            'Partage uniquement avec des partenaires indispensables (paiement, anti-fraude) dans le cadre du service.',
        ],
        'Sécurité & conservation' => [
            'Mesures techniques pour protéger vos informations (chiffrement, accès restreint).',
            'Conservation uniquement le temps nécessaire à la prestation et aux obligations légales.',
        ],
        'Vos choix' => [
            'Vous pouvez demander l’accès, la mise à jour ou la suppression de vos données quand cela est applicable.',
            'Pour toute question, contactez-nous via WhatsApp ou email.',
        ],
    ]
    : [
        'Data we collect' => [
            'Contact details you provide (name, email, WhatsApp).',
            'Order information and preferences related to your subscription.',
            'Basic technical data (IP, device) to keep access secure.',
        ],
        'How we use it' => [
            'To activate and manage your IPTV subscription.',
            'To provide customer support and communicate important updates.',
            'To improve performance and security of the service.',
        ],
        'Limited sharing' => [
            'We never sell your data.',
            'Sharing only with essential partners (payments, anti-fraud) to deliver the service.',
        ],
        'Security & retention' => [
            'Technical safeguards protect your information (encryption, restricted access).',
            'Data kept only as long as needed for service delivery and legal requirements.',
        ],
        'Your choices' => [
            'You may request access, updates, or deletion of your data where applicable.',
            'For any questions, reach us via WhatsApp or email.',
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
            <p><strong><?= e($brandName) ?></strong> <?= $lang === 'fr'
                ? 's’engage à protéger votre vie privée. Voici l’essentiel de notre politique de confidentialité :'
                : 'is committed to protecting your privacy. Here is the essence of our privacy policy:' ?>
            </p>
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
