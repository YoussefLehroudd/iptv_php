<?php

declare(strict_types=1);

session_start();



$config = require __DIR__ . '/../config/config.php';

/** @var PDO $pdo */

$pdo = require __DIR__ . '/../config/database.php';



logVisit($pdo);



$settings = getSettings($pdo);
$themeVars = getActiveThemeVars($settings['active_theme'] ?? 'onyx');
$brandTitleSetting = trim($settings['brand_title'] ?? '');
$brandName = $brandTitleSetting !== '' ? $brandTitleSetting : ($config['brand_name'] ?? 'ABDO IPTV CANADA');
$brandTaglineSetting = trim($settings['brand_tagline'] ?? '');
$brandTagline = $brandTaglineSetting !== '' ? $brandTaglineSetting : 'Ultra IPTV for Canada';
$brandLogoDesktop = trim($settings['brand_logo_desktop'] ?? '');
$brandLogoMobile = trim($settings['brand_logo_mobile'] ?? '');
if ($brandLogoMobile === '' && $brandLogoDesktop !== '') {
    $brandLogoMobile = $brandLogoDesktop;
}
$supportWhatsappNumber = trim($settings['support_whatsapp_number'] ?? '') ?: ($config['whatsapp_number'] ?? '');

$sliders = fetchAllAssoc($pdo, 'SELECT * FROM sliders ORDER BY created_at DESC');

$offers = fetchAllAssoc($pdo, 'SELECT * FROM offers ORDER BY is_featured DESC, price ASC');

$providers = fetchAllAssoc($pdo, 'SELECT * FROM providers ORDER BY created_at DESC');
$video = getPrimaryVideo($pdo);
$visitStats = getVisitStats($pdo);
$songs = getSongs($pdo);
$songDefaultVolume = (int) ($settings['song_default_volume'] ?? 40);
$songDefaultMuted = ($settings['song_default_muted'] ?? '1') === '1';
$contactSuccess = isset($_GET['contact']) && $_GET['contact'] === 'success';
$lang = (isset($_GET['lang']) && $_GET['lang'] === 'fr') ? 'fr' : 'en';


if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));

}



$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$baseUrl = $scheme . '://' . $host;

$basePath = appBasePath();
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
$publicBase = $basePath;
if ($docRoot === '' || !is_dir($docRoot . $publicBase . '/assets')) {
    $publicBase = rtrim($basePath . '/public', '/');
}
$assetBase = $publicBase . '/assets';

$mediaBase = $assetBase . '/images/demo';



$seoTitle = $settings['seo_title'] ?? 'ABDO IPTV Canada | Premium IPTV Accounts 2025';

$seoDescription = $settings['seo_description'] ?? 'Blazing-fast IPTV for Canada, secure WhatsApp payment, and 24/7 support.';

$structuredData = [

    '@context' => 'https://schema.org',

    '@type' => 'Product',

    'name' => $brandName,

    'description' => $seoDescription,

    'brand' => $brandName,

    'url' => $baseUrl,

    'offers' => array_map(static function (array $offer) use ($supportWhatsappNumber): array {

        return [

            '@type' => 'Offer',

            'price' => (float) $offer['price'],

            'priceCurrency' => 'CAD',

            'name' => $offer['name'],

            'availability' => 'https://schema.org/InStock',

            'url' => getWhatsappLink($supportWhatsappNumber, $offer['name'], (float) $offer['price'], $offer['duration']),

        ];

    }, $offers),

];



$defaultMoviePosters = [

    ['title' => 'Kung Fu Panda 4', 'image_url' => $mediaBase . '/kfp4.webp', 'category_label' => 'Movies & TV Shows'],

    ['title' => 'The Beekeeper', 'image_url' => $mediaBase . '/beekeeper.webp', 'category_label' => 'Movies & TV Shows'],

    ['title' => 'Kingdom of the Planet of the Apes', 'image_url' => $mediaBase . '/apes.webp', 'category_label' => 'Movies & TV Shows'],

    ['title' => 'Furiosa', 'image_url' => $mediaBase . '/furiosa.webp', 'category_label' => 'Movies & TV Shows'],

    ['title' => 'The Queen\'s Gambit', 'image_url' => $mediaBase . '/queens.webp', 'category_label' => 'Movies & TV Shows'],

];



$defaultSportEvents = [

    ['title' => 'Formula 1', 'image_url' => $mediaBase . '/f1.webp'],

    ['title' => 'LaLiga', 'image_url' => $mediaBase . '/laliga.webp'],

    ['title' => 'NBA Playoffs', 'image_url' => $mediaBase . '/nba.webp'],

    ['title' => 'Bundesliga', 'image_url' => $mediaBase . '/bundesliga.webp'],

    ['title' => 'Euro 2024', 'image_url' => $mediaBase . '/euro.webp'],

];



$defaultTestimonials = [
    ['name' => 'Omar - Montreal', 'message' => 'Fast support, zero freeze during NHL games. Thanks!', 'capture_url' => $mediaBase . '/wa-1.webp'],
    ['name' => 'Nadia - Ottawa', 'message' => 'WhatsApp support always there, I renewed for 12 months right away.', 'capture_url' => $mediaBase . '/wa-2.webp'],
    ['name' => 'Youssef - Quebec', 'message' => 'VOD updated every day. Netflix, Apple TV+, everything is there.', 'capture_url' => $mediaBase . '/wa-3.webp'],
];



$posterCategories = fetchAllAssoc($pdo, 'SELECT id, label, slug, headline FROM poster_categories ORDER BY label ASC');
$moviePosterRows = fetchAllAssoc($pdo, 'SELECT id, title, image_url, category_id FROM movie_posters ORDER BY created_at DESC');

if (!$posterCategories) {

    $posterCategories = [

        ['id' => 0, 'label' => 'Movies & TV Shows', 'slug' => 'movies', 'headline' => 'Latest blockbuster posters'],

    ];

}

$moviePosterGroups = [];
foreach ($posterCategories as $category) {
    $moviePosterGroups[(int) $category['id']] = [
        'label' => $category['label'],
        'slug' => $category['slug'],
        'headline' => $category['headline'] ?? 'Latest blockbuster posters',
        'posters' => [],
    ];
}

if ($moviePosterRows) {
    $fallbackCategoryId = (int) ($posterCategories[0]['id'] ?? 0);
    foreach ($moviePosterRows as $poster) {
        $categoryId = (int) ($poster['category_id'] ?? $fallbackCategoryId);
        if (!isset($moviePosterGroups[$categoryId])) {
            $categoryId = $fallbackCategoryId;
        }
        $moviePosterGroups[$categoryId]['posters'][] = $poster;
    }
} else {
    $firstCategoryId = (int) ($posterCategories[0]['id'] ?? 0);
    $moviePosterGroups[$firstCategoryId]['posters'] = $defaultMoviePosters;
}

$moviePosterGroups = array_values(array_filter($moviePosterGroups, static fn(array $group): bool => !empty($group['posters'])));

$sportEvents = fetchAllAssoc($pdo, 'SELECT id, title, image_url FROM sport_events ORDER BY created_at DESC');

if (!$sportEvents) {

    $sportEvents = $defaultSportEvents;

}

$testimonials = fetchAllAssoc($pdo, 'SELECT id, name, message, capture_url FROM testimonials ORDER BY created_at DESC');

if (!$testimonials) {

    $testimonials = $defaultTestimonials;

}



$deviceBadges = [

    'Android',

    'iOS',

    'Windows',

    'macOS',

    'Fire TV',

    'MAG Box',

    'Smart TV',

    'LG',

    'Chrome',

    'Apple TV',

];



$welcomeRotator = [
    'CA - Hey Canada! Welcome to ABDO IPTV.',
    'EN - Welcome! Premium IPTV built for every Canadian province.',
    'FR - Fast support 24/7 in English, French, and Arabic.',
    'ES - Reliable IPTV for our Canadian Latin community.',
];



$faqs = [
    [
        'key' => 'faq-1',
        'question' => 'Which devices are supported?',
        'answer' => 'All Smart TVs, Android/Apple, FireStick, MAG, PC/Mac and even Chromecast.',
    ],
    [
        'key' => 'faq-2',
        'question' => 'How long to activate my account?',
        'answer' => 'Between 5 and 7 minutes after your WhatsApp payment is validated.',
    ],
    [
        'key' => 'faq-3',
        'question' => 'Can I test before buying?',
        'answer' => 'Yes, ask for a 24h test directly via the WhatsApp button.',
    ],
    [
        'key' => 'faq-4',
        'question' => 'How many simultaneous connections?',
        'answer' => 'Each plan includes 1 connection; multi-screen is available on request.',
    ],
    [
        'key' => 'faq-5',
        'question' => 'Which payment methods?',
        'answer' => 'Interac, bank transfer, USDT crypto, or PayPal depending on availability.',
    ],
];



?>

<!DOCTYPE html>

<html lang="<?= e($lang) ?>">

<head>

    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="<?= e($seoDescription) ?>">

    <meta name="keywords" content="IPTV Canada, IPTV streaming, IPTV WhatsApp, IPTV 2025, Premium IPTV">

    <meta name="author" content="<?= e($brandName) ?>">



    <meta property="og:title" content="<?= e($seoTitle) ?>">

    <meta property="og:description" content="<?= e($seoDescription) ?>">

    <meta property="og:type" content="website">

    <meta property="og:image" content="<?= e($sliders[0]['media_url'] ?? 'https://res.cloudinary.com/dziwz75h6/image/upload/e_background_removal/f_png/v1763174007/t%C3%A9l%C3%A9chargement_1_ruskkt.jpg') ?>">

    <meta property="og:url" content="<?= e($baseUrl) ?>">



    <meta name="twitter:card" content="summary_large_image">

    <meta name="twitter:title" content="<?= e($seoTitle) ?>">

    <meta name="twitter:description" content="<?= e($seoDescription) ?>">



    <title><?= e($seoTitle) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= $assetBase ?>/css/style.css?v=<?= time() ?>">

    <style>

        :root {

            <?php foreach ($themeVars as $var => $value): ?>

            <?= $var ?>: <?= e($value) ?>;

            <?php endforeach; ?>

        }

        .lang-switch {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: 1rem;
        }

        .lang-switch button {
            padding: 0.35rem 0.65rem;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: transparent;
            color: #f6f6f6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .lang-switch button.active {
            background: var(--accent-500, #7c3aed);
            color: #fff;
            border-color: var(--accent-500, #7c3aed);
            box-shadow: 0 0 0 1px rgba(124, 58, 237, 0.25);
        }

        .lang-switch button:focus-visible {
            outline: 2px solid var(--accent-500, #7c3aed);
            outline-offset: 2px;
        }

    </style>

    <script type="application/ld+json">

        <?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>

    </script>

    <script>
        // Keep index pinned to top when arriving from other pages (even with BFCache)
        (function () {
            if ('scrollRestoration' in window.history) {
                window.history.scrollRestoration = 'manual';
            }
            const resetTop = () => window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
            window.addEventListener('pageshow', resetTop);
            window.addEventListener('load', resetTop);
        })();
    </script>

</head>

<body>

    <div class="noise"></div>

    <header class="site-header" id="top">

        <div class="logo">

            <?php if ($brandLogoDesktop || $brandLogoMobile): ?>

                <picture class="logo-picture">

                    <?php if ($brandLogoMobile): ?>

                        <source srcset="<?= e($brandLogoMobile) ?>" media="(max-width: 720px)">

                    <?php endif; ?>

                    <img src="<?= e($brandLogoDesktop ?: $brandLogoMobile) ?>" alt="<?= e($brandName) ?>">

                </picture>

            <?php else: ?>

                <span class="logo-icon">IPTV</span>

            <?php endif; ?>

            <div>

                <strong><?= e($brandName) ?></strong>

                <small><?= e($brandTagline) ?></small>

            </div>

        </div>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="siteNav" data-menu-toggle>

            <span class="sr-only">Open menu</span>

            <span></span>

            <span></span>

            <span></span>

        </button>

        <div class="nav-wrapper" data-menu-panel>

            <nav id="siteNav" class="site-nav">

                <a href="#top" data-i18n-key="nav-home" data-i18n-default="Home">Home</a>

                <a href="#offres" data-i18n-key="nav-pricing" data-i18n-default="Pricing">Pricing</a>

                <a href="#movies" data-i18n-key="nav-movies" data-i18n-default="Movies">Movies</a>

                <a href="#faq" data-i18n-key="nav-faq" data-i18n-default="FAQ">FAQ</a>

                <a href="#support" data-i18n-key="nav-contact" data-i18n-default="Contact">Contact</a>

            </nav>

            <div class="lang-switch" aria-label="Language">
                <button type="button" data-lang-switch="en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</button>
                <button type="button" data-lang-switch="fr" class="<?= $lang === 'fr' ? 'active' : '' ?>">FR</button>
            </div>

            <a class="btn primary header-cta" href="<?= e(getWhatsappLink($supportWhatsappNumber, '')) ?>" target="_blank" rel="noopener" data-i18n-key="btn-free-trial" data-i18n-default="Free Trial">Free Trial</a>

        </div>

    </header>

    <div class="mobile-nav-backdrop" data-menu-backdrop hidden></div>



    <main>

        <section class="hero" data-animate>

            <div class="hero-content">

                <p class="eyebrow" data-i18n-key="hero-eyebrow" data-i18n-default="Secure IPTV · Instant WhatsApp payment">Secure IPTV · Instant WhatsApp payment</p>

                <h1 data-i18n-key="hero-title" data-i18n-default="<?= e($settings['hero_title'] ?? 'Best IPTV Service at an Affordable Price') ?>"><?= e($settings['hero_title'] ?? 'Best IPTV Service at an Affordable Price') ?></h1>

                <p class="subtitle" data-i18n-key="hero-subtitle" data-i18n-default="<?= e($settings['hero_subtitle'] ?? 'Experience breathtaking 4K visuals, +40K channels & 54K VOD across Canada.') ?>"><?= e($settings['hero_subtitle'] ?? 'Experience breathtaking 4K visuals, +40K channels & 54K VOD across Canada.') ?></p>

                <?php if ($welcomeRotator): ?>

                    <div class="greeting-rotator" data-rotator>

                        <?php foreach ($welcomeRotator as $index => $greeting): ?>

                            <span class="rotator-line<?= $index === 0 ? ' active' : '' ?>" data-rotator-line><?= e($greeting) ?></span>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <div class="hero-cta">

                    <a class="btn primary" href="#offres" data-i18n-key="hero-cta-primary" data-i18n-default="<?= e($settings['hero_cta'] ?? 'See plans') ?>"><?= e($settings['hero_cta'] ?? 'See plans') ?></a>

                    <a class="btn outline" href="<?= e(getWhatsappLink($supportWhatsappNumber, 'I want a 24h test')) ?>" target="_blank" rel="noopener" data-i18n-key="hero-cta-test" data-i18n-default="Test 24h">Test 24h</a>

                </div>

                <ul class="device-icons">
                    <?php foreach (['Smart TV', 'Laptops/PC', 'Android', 'iOS', 'Windows'] as $device): ?>
                        <li><?= e($device) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($songs): ?>
                    <div class="music-player" data-music-player data-default-muted="<?= $songDefaultMuted ? 'true' : 'false' ?>" data-default-volume="<?= (int) $songDefaultVolume ?>">
                        <div class="music-player__cover">
                            <div class="music-player__thumb" data-music-cover></div>
                            <div class="music-player__meta">
                                <strong data-music-title></strong>
                                <span data-music-artist></span>
                            </div>
                            <div class="music-player__controls">
                                <button type="button" class="icon-btn" data-music-prev title="Previous track">⏮</button>
                                <button type="button" class="icon-btn primary" data-music-play title="Play / Pause">▶</button>
                                <button type="button" class="icon-btn" data-music-next title="Next track">⏭</button>
                            </div>
                        </div>
                        <div class="music-player__progress">
                            <span data-music-current>0:00</span>
                            <input type="range" min="0" max="100" value="0" data-music-progress>
                            <span data-music-duration>0:00</span>
                        </div>
                        <div class="music-player__volume">
                            <input type="range" min="0" max="100" value="<?= (int) $songDefaultVolume ?>" data-music-volume>
                            <button type="button" class="icon-btn" data-music-mute title="Couper le son">🔇</button>
                        </div>
                        <div data-music-youtube></div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="hero-visual">

                <div class="screen-frame">

                    <div class="slider" data-slider="hero">

                        <div class="slider-track">

                            <?php foreach ($sliders as $slider): ?>

                                <article class="slide">

                                    <div class="media">

                                        <?php if ($slider['media_type'] === 'video'): ?>

                                            <div class="video-frame">

                                                <video autoplay muted playsinline controlslist="nodownload noremoteplayback" disablepictureinpicture>

                                                    <source src="<?= e($slider['media_url']) ?>" type="video/mp4">

                                                </video>

                                                <button class="video-audio-toggle" type="button" aria-pressed="false" data-video-toggle>

                                                    <span class="sr-only">Activer le son</span>

                                                    <svg class="icon-sound" viewBox="0 0 24 24" aria-hidden="true">

                                                        <path fill="currentColor" d="M5 9v6h3l4 4V5L8 9H5zm10.5 3a3 3 0 0 0-1.5-2.6v5.2a3 3 0 0 0 1.5-2.6zm-1.5-6.3v2.1a5 5 0 0 1 0 8.4v2.1a7 7 0 0 0 0-12.6z"/>

                                                    </svg>

                                                    <svg class="icon-muted" viewBox="0 0 24 24" aria-hidden="true">

                                                        <path fill="currentColor" d="M16.5 12a3 3 0 0 1-.9 2.1l1.4 1.4A4.98 4.98 0 0 0 18.5 12a4.98 4.98 0 0 0-1.5-3.5l-1.4 1.4c.6.5.9 1.2.9 2.1zm3.5 0a7 7 0 0 0-2-5l-1.4 1.4A4.99 4.99 0 0 1 20 12a4.99 4.99 0 0 1-1.4 3.6L20 17a6.99 6.99 0 0 0 0-10zm-2.3 9.7L3.3 7.8 4.7 6.4 10 11h2V5l4 4h3v6h-2.2l2.5 2.5-1.4 1.4zM5 9v6h3l4 4v-6.2l5.2 5.2-1.4 1.4L12 19l-4 4H5v-6H2V9h3z"/>

                                                    </svg>

                                                </button>

                                            </div>

                                        <?php else: ?>

                                            <img src="<?= e($slider['media_url']) ?>" alt="<?= e($slider['title']) ?>">

                                        <?php endif; ?>

                                    </div>

                                    <div class="copy">

                                        <h3><?= e($slider['title']) ?></h3>

                                        <p><?= e($slider['subtitle']) ?></p>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    </div>

                    <div class="slider-nav" data-slider-nav="hero">

                        <button type="button" data-slider-target="hero" data-direction="prev">‹</button>

                        <button type="button" data-slider-target="hero" data-direction="next">›</button>

                    </div>

                </div>

                <div class="hero-stats">

                    <div>

                        <span><?= e(number_format($visitStats['total'])) ?>+</span>

                        <p data-i18n-key="stat-clients" data-i18n-default="Active clients">Clients actifs</p>

                    </div>

                    <div>

                        <span>+40K</span>

                        <p data-i18n-key="stat-vod" data-i18n-default="Channels & VOD">Chaînes &amp; VOD</p>

                    </div>

                    <div>

                        <span>99.9%</span>

                        <p data-i18n-key="stat-uptime" data-i18n-default="Uptime guaranteed">Uptime garanti</p>

                    </div>

                </div>

            </div>

        </section>



        <section class="logo-strip" data-animate>

            <div class="provider-carousel" data-provider-carousel>

                <button class="provider-nav prev" type="button" aria-label="Previous" data-provider-nav="prev">‹</button>

                <div class="provider-window">

                    <div class="provider-track" data-provider-track>

                        <?php foreach ($providers as $provider): ?>

                            <div class="provider-logo">

                                <img src="<?= e($provider['logo_url']) ?>" alt="<?= e($provider['name']) ?>">

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

                <button class="provider-nav next" type="button" aria-label="Next" data-provider-nav="next">›</button>

            </div>

        </section>



        <section class="offers" id="offres" data-animate>

            <div class="section-head">

                <p class="eyebrow" data-i18n-key="offers-eyebrow" data-i18n-default="Choose your plan">Choose your plan</p>

                <h2 data-i18n-key="offers-title" data-i18n-default="Choose Your <span>IPTV Plan</span>" data-i18n-html="true">Choose Your <span>IPTV Plan</span></h2>

                <p data-i18n-key="offers-subtitle" data-i18n-default="Activation in 5-7 minutes · Support EN/FR/AR 24/7">Activation in 5-7 minutes · Support EN/FR/AR 24/7</p>

            </div>

            <div class="offers-grid">

                <?php foreach ($offers as $offer): ?>

                    <article class="offer-card <?= $offer['is_featured'] ? 'featured' : '' ?>">

                        <header>

                            <p><?= e($offer['duration']) ?></p>

                            <h3><span class="currency">$</span><?= e(formatCurrency((float) $offer['price'])) ?></h3>

                        </header>

                        <ul>

                            <?php foreach (splitFeatures($offer['features']) as $feature): ?>

                                <li><?= e($feature) ?></li>

                            <?php endforeach; ?>

                        </ul>

                        <a class="btn primary" href="<?= $basePath ?>/checkout?offer=<?= (int) $offer['id'] ?>" data-i18n-key="offers-buy" data-i18n-default="Buy now">Buy now</a>

                        <small data-i18n-key="offers-ready" data-i18n-default="Ready in 5-7 min · WhatsApp">Ready in 5-7 min · WhatsApp</small>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>



        <section class="features" data-animate>

            <?php

            $benefits = [

                ['key' => 'benefit-1', 'title' => 'Fast Reliable Servers', 'desc' => '10Gb Montreal servers + anti-freeze AI.'],

                ['key' => 'benefit-2', 'title' => '4K / FHD Streaming', 'desc' => 'Compatible MAG, Android, Enigma, Apple TV, FireStick.'],

                ['key' => 'benefit-3', 'title' => 'Money Back Guarantee', 'desc' => 'Refund within 10 days if you are not satisfied.'],

                ['key' => 'benefit-4', 'title' => 'Support 24/7', 'desc' => 'WhatsApp + email EN / FR / AR at any time.'],

            ];

            foreach ($benefits as $benefit): ?>

                <article>

                    <h3 data-i18n-key="<?= e($benefit['key']) ?>-title" data-i18n-default="<?= e($benefit['title']) ?>"><?= e($benefit['title']) ?></h3>

                    <p data-i18n-key="<?= e($benefit['key']) ?>-desc" data-i18n-default="<?= e($benefit['desc']) ?>"><?= e($benefit['desc']) ?></p>

                </article>

            <?php endforeach; ?>

        </section>



        <?php foreach ($moviePosterGroups as $index => $group): ?>

            <?php
            $sectionId = $index === 0 ? 'movies' : 'movies-' . ($index + 1);
            $sliderId = $sectionId;
            $eyebrowKey = $index === 0 ? 'media-eyebrow' : null;
            $titleKey = $index === 0 ? 'media-title' : null;
            ?>

            <section class="media-section" id="<?= e($sectionId) ?>" data-animate>

                <div class="section-head">

                    <p class="eyebrow"<?= $eyebrowKey ? ' data-i18n-key="' . e($eyebrowKey) . '" data-i18n-default="' . e($group['label']) . '"' : '' ?>><?= e($group['label']) ?></p>

                    <h2<?= $titleKey ? ' data-i18n-key="' . e($titleKey) . '" data-i18n-default="' . e($group['headline'] ?? 'Latest blockbuster posters') . '"' : '' ?>><?= e($group['headline'] ?? 'Latest blockbuster posters') ?></h2>

                </div>

                <div class="media-carousel">

                    <div class="slider" data-slider="<?= e($sliderId) ?>" data-visible="4" data-infinite="true">

                        <div class="slider-track">

                            <?php foreach ($group['posters'] as $poster): ?>

                                <article class="slide poster">

                                    <img src="<?= e($poster['image_url']) ?>" alt="<?= e($poster['title']) ?>">

                                </article>

                            <?php endforeach; ?>

                        </div>

                    </div>

                    <div class="slider-nav" data-slider-nav="<?= e($sliderId) ?>">

                        <button type="button" data-slider-target="<?= e($sliderId) ?>" data-direction="prev">&lsaquo;</button>

                        <button type="button" data-slider-target="<?= e($sliderId) ?>" data-direction="next">&rsaquo;</button>

                    </div>

                </div>

            </section>

        <?php endforeach; ?>



        <section class="media-section sports" data-animate>

            <div class="section-head">

                <p class="eyebrow" data-i18n-key="sports-eyebrow" data-i18n-default="All Sports Events">All Sports Events</p>

                <h2 data-i18n-key="sports-title" data-i18n-default="Football · NBA · F1 · UFC">Football · NBA · F1 · UFC</h2>

            </div>

            <div class="media-carousel">

                <div class="slider" data-slider="sports" data-visible="4" data-infinite="true">

                    <div class="slider-track">

                        <?php foreach ($sportEvents as $event): ?>

                            <article class="slide poster">

                                <img src="<?= e($event['image_url']) ?>" alt="<?= e($event['title']) ?>">

                            </article>

                        <?php endforeach; ?>

                    </div>

                </div>

                <div class="slider-nav" data-slider-nav="sports">

                    <button type="button" data-slider-target="sports" data-direction="prev">‹</button>

                    <button type="button" data-slider-target="sports" data-direction="next">›</button>

                </div>

            </div>

        </section>



        <section class="devices" data-animate>

            <div class="section-head">

                <p class="eyebrow" data-i18n-key="devices-eyebrow" data-i18n-default="Supported Devices">Supported Devices</p>

                <h2 data-i18n-key="devices-title" data-i18n-default="Compatible everywhere">Compatible partout</h2>

            </div>

            <div class="device-badges">

                <?php foreach ($deviceBadges as $badge): ?>

                    <span><?= e($badge) ?></span>

                <?php endforeach; ?>

            </div>

        </section>



        <section class="faq" id="faq" data-animate>

            <div class="section-head">

                <p class="eyebrow" data-i18n-key="faq-eyebrow" data-i18n-default="FAQ">FAQ</p>

                <h2 data-i18n-key="faq-title" data-i18n-default="Frequently Asked Questions">Frequently Asked Questions</h2>

            </div>

            <div class="faq-list">

                <?php foreach ($faqs as $index => $faq): ?>

                    <article class="faq-item">

                        <button type="button" class="faq-question" data-faq="<?= (int) $index ?>">

                            <span data-i18n-key="<?= e($faq['key']) ?>-q" data-i18n-default="<?= e($faq['question']) ?>"><?= e($faq['question']) ?></span>

                            <span>›</span>

                        </button>

                        <div class="faq-answer" data-faq-panel="<?= (int) $index ?>">

                            <p data-i18n-key="<?= e($faq['key']) ?>-a" data-i18n-default="<?= e($faq['answer']) ?>"><?= e($faq['answer']) ?></p>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>



        <section class="testimonials" data-animate>

            <div class="section-head">

                <p class="eyebrow" data-i18n-key="testimonials-eyebrow" data-i18n-default="Customer reviews">Customer reviews</p>

                <h2 data-i18n-key="testimonials-title" data-i18n-default="Hear from our satisfied customers">Hear from our satisfied customers</h2>

            </div>

            <div class="media-carousel">

                <div class="slider" data-slider="testimonials" data-visible="4">

                    <div class="slider-track">

                        <?php foreach ($testimonials as $testimonial): ?>

                            <article class="slide testimonial">

                                <img src="<?= e($testimonial['capture_url']) ?>" alt="Testimonial <?= e($testimonial['name']) ?>">

                            </article>

                        <?php endforeach; ?>

                    </div>

                </div>

                <div class="slider-nav" data-slider-nav="testimonials">

                    <button type="button" data-slider-target="testimonials" data-direction="prev"><</button>

                    <button type="button" data-slider-target="testimonials" data-direction="next">></button>

                </div>

            </div>

        </section>



        <section class="contact" id="support" data-animate>

            <div class="contact-card">

                <div>

                    <p class="eyebrow" data-i18n-key="contact-eyebrow" data-i18n-default="Fast support">Fast support</p>

                    <h2 data-i18n-key="contact-title" data-i18n-default="Need help? Contact us">Need help? Contact us</h2>

                    <p data-i18n-key="contact-copy" data-i18n-default="Reach us on WhatsApp or through this form for a quick reply.">Reach us on WhatsApp or through this form for a quick reply.</p>

                    <?php if ($contactSuccess): ?>

                        <div class="alert success" data-flash>Thanks! Message received.</div>

                    <?php endif; ?>

                    <form action="<?= $publicBase ?>/contact_submit.php" method="POST" class="contact-form">

                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                        <label data-i18n-key="contact-fullname" data-i18n-default="Full name">Full name<input type="text" name="full_name" required></label>

                        <label data-i18n-key="contact-email" data-i18n-default="Email">Email<input type="email" name="email" required></label>

                        <label data-i18n-key="contact-phone" data-i18n-default="Phone">Phone<input type="text" name="phone"></label>

                        <label data-i18n-key="contact-message" data-i18n-default="Message">Message<textarea name="message" rows="4" required></textarea></label>

                        <button type="submit" class="btn primary" data-i18n-key="contact-submit" data-i18n-default="Send">Send</button>

                    </form>

                </div>



            </div>

        </section>

    </main>



<footer data-animate>

        <p data-i18n-key="footer-note" data-i18n-default="© {year} {brand} · Secure IPTV Canada · All rights reserved." data-i18n-year="<?= date('Y') ?>" data-i18n-brand="<?= e($brandName) ?>">© <?= date('Y') ?> <?= e($brandName) ?> · Secure IPTV Canada · All rights reserved.</p>

        <div class="footer-links">

            <a href="#offres" data-i18n-key="footer-pricing" data-i18n-default="Pricing Plans">Pricing Plans</a>

            <a href="#faq" data-i18n-key="footer-faq" data-i18n-default="FAQ">FAQ</a>

            <a href="<?= e(getWhatsappLink($supportWhatsappNumber, '')) ?>" target="_blank" rel="noopener" data-i18n-key="footer-support" data-i18n-default="Support WhatsApp">Support WhatsApp</a>

        </div>

    </footer>



    <a class="whatsapp-float whatsapp-float--chat" href="<?= e(getWhatsappLink($supportWhatsappNumber, '')) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">

        <svg viewBox="0 0 24 24" role="img" aria-hidden="true">

            <path fill="currentColor" d="M12 2a10 10 0 0 0-8.94 14.5L2 22l5.65-1.48A10 10 0 1 0 12 2zm0 1.8a8.2 8.2 0 0 1 6.69 12.85 8.2 8.2 0 0 1-9.34 2.59l-.27-.1-3.38.88.9-3.34-.17-.28A8.2 8.2 0 0 1 12 3.8zm3.66 5.04c-.2-.005-.49-.01-.77.48-.27.49-.9 1.4-.98 1.5-.08.1-.18.15-.32.08-.14-.07-.6-.22-1.14-.56-.84-.48-1.37-1.08-1.53-1.26-.16-.18-.02-.28.12-.41.12-.12.3-.32.42-.48.14-.16.18-.28.26-.46.08-.18.04-.35-.02-.48-.07-.13-.6-1.46-.82-2-.22-.54-.46-.48-.63-.48h-.54c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.6.12.16 1.78 2.72 4.3 3.9.6.27 1.07.43 1.44.55.6.19 1.14.16 1.57.1.48-.07 1.48-.61 1.69-1.21.21-.6.21-1.12.15-1.21-.06-.09-.22-.14-.42-.15z"/>

        </svg>

    </a>

    <button class="whatsapp-float whatsapp-float--top" type="button" aria-label="Back to top" data-scroll-top>
        <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
            <path fill="currentColor" d="M5 15.5 12 8l7 7.5-1.4 1.4L12 10.8l-5.6 6.1z"/>
        </svg>
    </button>



    <?php if ($songs): ?>
        <script>
            window.musicPlayerConfig = <?= json_encode([
                'songs' => array_map(static function (array $song): array {
                    return [
                        'id' => (int) $song['id'],
                        'title' => $song['title'],
                        'artist' => $song['artist'] ?: '',
                        'type' => $song['source_type'],
                        'source' => $song['source_url'],
                        'thumbnail' => $song['thumbnail_url'] ?? '',
                    ];
                }, $songs),
                'settings' => [
                    'defaultVolume' => max(0, min(100, $songDefaultVolume)),
                    'defaultMuted' => $songDefaultMuted,
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        </script>
    <?php endif; ?>
    <script>

        window.APP_THEME = <?= json_encode($settings['active_theme'] ?? 'onyx') ?>;

    </script>

    <script>
        (function () {
            const serverLang = <?= json_encode($lang) ?>;
            const urlLang = new URLSearchParams(window.location.search).get('lang');
            const storedLang = localStorage.getItem('site-lang');
            const initialLang = urlLang === 'fr' ? 'fr' : urlLang === 'en' ? 'en' : storedLang === 'fr' ? 'fr' : storedLang === 'en' ? 'en' : serverLang;

            const translations = {
                en: {
                    'nav-home': 'Home',
                    'nav-pricing': 'Pricing',
                    'nav-movies': 'Movies',
                    'nav-faq': 'FAQ',
                    'nav-contact': 'Contact',
                    'btn-free-trial': 'Free Trial',
                    'hero-eyebrow': 'Secure IPTV · Instant WhatsApp payment',
                    'hero-title': 'Best IPTV Service at an Affordable Price',
                    'hero-subtitle': 'Experience breathtaking 4K visuals, +40K channels & 54K VOD across Canada.',
                    'hero-cta-primary': 'See plans',
                    'hero-cta-test': 'Test 24h',
                    'stat-clients': 'Active clients',
                    'stat-vod': 'Channels & VOD',
                    'stat-uptime': 'Uptime guaranteed',
                    'offers-eyebrow': 'Choose your plan',
                    'offers-title': 'Choose Your <span>IPTV Plan</span>',
                    'offers-subtitle': 'Activation in 5-7 minutes · Support EN/FR/AR 24/7',
                    'offers-buy': 'Buy now',
                    'offers-ready': 'Ready in 5-7 min · WhatsApp',
                    'devices-eyebrow': 'Supported Devices',
                    'devices-title': 'Compatible everywhere',
                    'faq-eyebrow': 'FAQ',
                    'faq-title': 'Frequently Asked Questions',
                    'testimonials-eyebrow': 'Customer reviews',
                    'testimonials-title': 'Hear from our satisfied customers',
                    'benefit-1-title': 'Fast Reliable Servers',
                    'benefit-1-desc': '10Gb Montreal servers + anti-freeze AI.',
                    'benefit-2-title': '4K / FHD Streaming',
                    'benefit-2-desc': 'Compatible MAG, Android, Enigma, Apple TV, FireStick.',
                    'benefit-3-title': 'Money Back Guarantee',
                    'benefit-3-desc': 'Refund within 10 days if you are not satisfied.',
                    'benefit-4-title': 'Support 24/7',
                    'benefit-4-desc': 'WhatsApp + email EN / FR / AR at any time.',
                    'contact-eyebrow': 'Fast support',
                    'contact-title': 'Need help? Contact us',
                    'contact-copy': 'Reach us on WhatsApp or through this form for a quick reply.',
                    'media-eyebrow': 'Movies & TV Shows',
                    'media-title': 'Latest blockbuster posters',
                    'sports-eyebrow': 'All Sports Events',
                    'sports-title': 'Football · NBA · F1 · UFC',
                    'contact-subject': 'Subject',
                    'contact-fullname': 'Full name',
                    'contact-email': 'Email',
                    'contact-phone': 'Phone'
                    'contact-whatsapp': 'WhatsApp'
                    'contact-city': 'City',
                    'contact-message': 'Message',
                    'contact-submit': 'Send',
                    'footer-note': '© {year} {brand} · Secure IPTV Canada · All rights reserved.',
                    'footer-pricing': 'Pricing Plans',
                    'footer-faq': 'FAQ',
                    'footer-support': 'Support WhatsApp',
                    'faq-1-q': 'Which devices are supported?',
                    'faq-1-a': 'All Smart TVs, Android/Apple, FireStick, MAG, PC/Mac and even Chromecast.',
                    'faq-2-q': 'How long to activate my account?',
                    'faq-2-a': 'Between 5 and 7 minutes after your WhatsApp payment is validated.',
                    'faq-3-q': 'Can I test before buying?',
                    'faq-3-a': 'Yes, ask for a 24h test directly via the WhatsApp button.',
                    'faq-4-q': 'How many simultaneous connections?',
                    'faq-4-a': 'Each plan includes 1 connection; multi-screen is available on request.',
                    'faq-5-q': 'Which payment methods?',
                    'faq-5-a': 'Interac, bank transfer, USDT crypto, or PayPal depending on availability.',
                },
                fr: {
                    'nav-home': 'Accueil',
                    'nav-pricing': 'Tarifs',
                    'nav-movies': 'Films et séries',
                    'nav-faq': 'FAQ',
                    'nav-contact': 'Contact',
                    'btn-free-trial': 'Essai gratuit',
                    'hero-eyebrow': 'IPTV sécurisée · Paiement WhatsApp instantané',
                    'hero-title': 'Meilleur service IPTV à prix abordable',
                    'hero-subtitle': 'Profitez d\'une image 4K, +40K chaînes & 54K VOD partout au Canada.',
                    'hero-cta-primary': 'Voir les offres',
                    'hero-cta-test': 'Test 24h',
                    'stat-clients': 'Clients actifs',
                    'stat-vod': 'Chaînes & VOD',
                    'stat-uptime': 'Uptime garanti',
                    'offers-eyebrow': 'Choisissez votre offre',
                    'offers-title': 'Choisissez votre <span>offre IPTV</span>',
                    'offers-subtitle': 'Activation en 5 à 7 minutes · Support EN/FR/AR 24/7',
                    'offers-buy': 'Acheter',
                    'offers-ready': 'Prêt en 5-7 min · WhatsApp',
                    'devices-eyebrow': 'Appareils supportés',
                    'devices-title': 'Compatible partout',
                    'faq-eyebrow': 'FAQ',
                    'faq-title': 'Questions fréquentes',
                    'testimonials-eyebrow': 'Avis clients',
                    'testimonials-title': 'Ils nous font confiance',
                    'benefit-1-title': 'Serveurs rapides et fiables',
                    'benefit-1-desc': 'Serveurs 10Gb Montréal + anti-freeze AI.',
                    'benefit-2-title': 'Streaming 4K / FHD',
                    'benefit-2-desc': 'Compatible MAG, Android, Enigma, Apple TV, FireStick.',
                    'benefit-3-title': 'Garantie satisfait ou remboursé',
                    'benefit-3-desc': 'Remboursement sous 10 jours si vous n\'êtes pas satisfait.',
                    'benefit-4-title': 'Support 24/7',
                    'benefit-4-desc': 'WhatsApp + email EN / FR / AR à tout moment.',
                    'contact-eyebrow': 'Support rapide',
                    'contact-title': 'Besoin d\'aide ? Contactez-nous',
                    'contact-copy': 'Écrivez-nous sur WhatsApp ou via le formulaire pour une réponse rapide.',
                    'media-eyebrow': 'Films & séries',
                    'media-title': 'Les dernières affiches blockbuster',
                    'sports-eyebrow': 'Tous les événements sportifs',
                    'sports-title': 'Football · NBA · F1 · UFC',
                    'contact-subject': 'Sujet',
                    'contact-fullname': 'Nom complet',
                    'contact-email': 'Email',
                    'contact-phone': 'Téléphone',
                    'contact-message': 'Message',
                    'contact-submit': 'Envoyer',
                    'footer-note': '© {year} {brand} · IPTV sécurisée Canada · Tous droits réservés.',
                    'footer-pricing': 'Offres',
                    'footer-faq': 'FAQ',
                    'footer-support': 'Support WhatsApp',
                    'faq-1-q': 'Quels appareils sont supportés ?',
                    'faq-1-a': 'Toutes les Smart TV, Android/Apple, FireStick, MAG, PC/Mac et même Chromecast.',
                    'faq-2-q': 'Combien de temps pour activer mon compte ?',
                    'faq-2-a': 'Entre 5 et 7 minutes après validation de votre paiement WhatsApp.',
                    'faq-3-q': 'Puis-je tester avant d\'acheter ?',
                    'faq-3-a': 'Oui, demande un test 24h directement via le bouton WhatsApp.',
                    'faq-4-q': 'Combien de connexions simultanées ?',
                    'faq-4-a': 'Chaque plan inclut 1 connexion ; le multi-écran est disponible sur demande.',
                    'faq-5-q': 'Quels modes de paiement ?',
                    'faq-5-a': 'Interac, virement bancaire, crypto USDT ou PayPal selon disponibilité.',
                
                },
            };

            const els = Array.from(document.querySelectorAll('[data-i18n-key]'));
            const applyLang = (target) => {
                const pack = translations[target] || translations[serverLang] || translations.en;
                els.forEach((el) => {
                    const key = el.dataset.i18nKey;
                    const fallback = el.dataset.i18nDefault || el.textContent;
                    let text = pack[key] ?? fallback;
                    if (!text) return;
                    text = text
                        .replace('{year}', el.dataset.i18nYear || new Date().getFullYear())
                        .replace('{brand}', el.dataset.i18nBrand || 'ABDO IPTV');
                    if (el.dataset.i18nHtml === 'true') {
                        el.innerHTML = text;
                    } else {
                        el.textContent = text;
                    }
                });
                document.querySelectorAll('[data-lang-switch]').forEach((btn) => {
                    btn.classList.toggle('active', btn.dataset.langSwitch === target);
                });
                document.documentElement.setAttribute('lang', target);
                localStorage.setItem('site-lang', target);
                const url = new URL(window.location.href);
                url.searchParams.set('lang', target);
                window.history.replaceState({}, '', url.toString());
            };

            document.querySelectorAll('[data-lang-switch]').forEach((btn) => {
                btn.addEventListener('click', (evt) => {
                    evt.preventDefault();
                    const target = btn.dataset.langSwitch === 'fr' ? 'fr' : 'en';
                    applyLang(target);
                });
            });

            applyLang(initialLang || 'en');
        })();

    </script>

    <script src="<?= $assetBase ?>/js/main.js?v=<?= time() ?>" defer></script>

</body>

</html>


