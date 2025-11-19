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

$brandTagline = $brandTaglineSetting !== '' ? $brandTaglineSetting : 'Ultra IPTV · Canada';

$brandLogoDesktop = trim($settings['brand_logo_desktop'] ?? '');

$brandLogoMobile = trim($settings['brand_logo_mobile'] ?? '');

if ($brandLogoMobile === '' && $brandLogoDesktop !== '') {

    $brandLogoMobile = $brandLogoDesktop;

}

$sliders = fetchAllAssoc($pdo, 'SELECT * FROM sliders ORDER BY created_at DESC');

$offers = fetchAllAssoc($pdo, 'SELECT * FROM offers ORDER BY is_featured DESC, price ASC');

$providers = fetchAllAssoc($pdo, 'SELECT * FROM providers ORDER BY created_at DESC');
$video = getPrimaryVideo($pdo);
$visitStats = getVisitStats($pdo);
$songs = getSongs($pdo);
$songDefaultVolume = (int) ($settings['song_default_volume'] ?? 40);
$songDefaultMuted = ($settings['song_default_muted'] ?? '1') === '1';
$contactSuccess = isset($_GET['contact']) && $_GET['contact'] === 'success';


if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));

}



$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$baseUrl = $scheme . '://' . $host;

$basePath = appBasePath();

$mediaBase = $basePath . '/assets/images/demo';



$seoTitle = $settings['seo_title'] ?? 'ABDO IPTV Canada | Premium IPTV Accounts 2025';

$seoDescription = $settings['seo_description'] ?? 'Serveurs IPTV ultra rapides pour Canada, paiement WhatsApp sécurisé et support 24/7.';

$structuredData = [

    '@context' => 'https://schema.org',

    '@type' => 'Product',

    'name' => $brandName,

    'description' => $seoDescription,

    'brand' => $brandName,

    'url' => $baseUrl,

    'offers' => array_map(static function (array $offer) use ($config): array {

        return [

            '@type' => 'Offer',

            'price' => (float) $offer['price'],

            'priceCurrency' => 'CAD',

            'name' => $offer['name'],

            'availability' => 'https://schema.org/InStock',

            'url' => getWhatsappLink($config['whatsapp_number'], $offer['name'], (float) $offer['price'], $offer['duration']),

        ];

    }, $offers),

];



$defaultMoviePosters = [

    ['title' => 'Kung Fu Panda 4', 'image_url' => $mediaBase . '/kfp4.webp'],

    ['title' => 'The Beekeeper', 'image_url' => $mediaBase . '/beekeeper.webp'],

    ['title' => 'Kingdom of the Planet of the Apes', 'image_url' => $mediaBase . '/apes.webp'],

    ['title' => 'Furiosa', 'image_url' => $mediaBase . '/furiosa.webp'],

    ['title' => 'The Queen\'s Gambit', 'image_url' => $mediaBase . '/queens.webp'],

];



$defaultSportEvents = [

    ['title' => 'Formula 1', 'image_url' => $mediaBase . '/f1.webp'],

    ['title' => 'LaLiga', 'image_url' => $mediaBase . '/laliga.webp'],

    ['title' => 'NBA Playoffs', 'image_url' => $mediaBase . '/nba.webp'],

    ['title' => 'Bundesliga', 'image_url' => $mediaBase . '/bundesliga.webp'],

    ['title' => 'Euro 2024', 'image_url' => $mediaBase . '/euro.webp'],

];



$defaultTestimonials = [

    ['name' => 'Omar - Montréal', 'message' => 'Service rapide, zéro freeze pendant les matchs de NHL. Merci !', 'capture_url' => $mediaBase . '/wa-1.webp'],

    ['name' => 'Nadia - Ottawa', 'message' => 'Support WhatsApp toujours présent, j\'ai renouvelé pour 12 mois direct.', 'capture_url' => $mediaBase . '/wa-2.webp'],

    ['name' => 'Youssef - Québec', 'message' => 'Les VOD sont mis é jour tous les jours. Netflix, Apple TV+, tout y est.', 'capture_url' => $mediaBase . '/wa-3.webp'],

];



$moviePosters = fetchAllAssoc($pdo, 'SELECT id, title, image_url FROM movie_posters ORDER BY created_at DESC');

if (!$moviePosters) {

    $moviePosters = $defaultMoviePosters;

}

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

    'CA · Salut Canada! Bienvenue chez ABDO IPTV.',

    'EN · Welcome! Premium IPTV made for every Canadian province.',

    'FR · Assistance rapide 24/7 en francais et en arabe.',

    'ES · IPTV estable para nuestra comunidad latina en Canada.',

];



$faqs = [

    ['question' => 'Quels appareils sont supportés ?', 'answer' => 'Toutes les Smart TV, Android/Apple, FireStick, MAG, PC/Mac et même ChromeCast.'],

    ['question' => 'Combien de temps pour activer mon compte ?', 'answer' => 'Entre 5 et 7 minutes après validation de votre paiement WhatsApp.'],

    ['question' => 'Puis-je tester avant d’acheter ?', 'answer' => 'Oui, demande un test 24h directement via le bouton WhatsApp.'],

    ['question' => 'Combien de connexions simultanées ?', 'answer' => 'Chaque offre inclut 1 connexion, option multi-écrans disponible sur demande.'],

    ['question' => 'Quels modes de paiement ?', 'answer' => 'Interac, virement bancaire, crypto USDT ou PayPal selon disponibilité.'],

];



?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="<?= e($seoDescription) ?>">

    <meta name="keywords" content="IPTV Canada, IPTV Maroc, IPTV 2025, IPTV WhatsApp, IPTV Hostinger">

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

    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css?v=<?= time() ?>">

    <style>

        :root {

            <?php foreach ($themeVars as $var => $value): ?>

            <?= $var ?>: <?= e($value) ?>;

            <?php endforeach; ?>

        }

    </style>

    <script type="application/ld+json">

        <?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>

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

            <span class="sr-only">Ouvrir le menu</span>

            <span></span>

            <span></span>

            <span></span>

        </button>

        <div class="nav-wrapper" data-menu-panel>

            <nav id="siteNav" class="site-nav">

                <a href="#top">Accueil</a>

                <a href="#offres">Pricing</a>

                <a href="#movies">Films</a>

                <a href="#faq">FAQ</a>

                <a href="#support">Contact</a>

            </nav>

            <a class="btn primary header-cta" href="<?= e(getWhatsappLink($config['whatsapp_number'], 'Je veux un test')) ?>" target="_blank" rel="noopener">Free Trial</a>

        </div>

    </header>

    <div class="mobile-nav-backdrop" data-menu-backdrop hidden></div>



    <main>

        <section class="hero" data-animate>

            <div class="hero-content">

                <p class="eyebrow">IPTV sécurisé · Paiement WhatsApp instantané</p>

                <h1><?= e($settings['hero_title'] ?? 'Best IPTV Service at an Affordable Price') ?></h1>

                <p class="subtitle"><?= e($settings['hero_subtitle'] ?? 'Experience breathtaking 4K visuals, +40K chaînes & 54K VOD partout au Canada.') ?></p>

                <?php if ($welcomeRotator): ?>

                    <div class="greeting-rotator" data-rotator>

                        <?php foreach ($welcomeRotator as $index => $greeting): ?>

                            <span class="rotator-line<?= $index === 0 ? ' active' : '' ?>" data-rotator-line><?= e($greeting) ?></span>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <div class="hero-cta">

                    <a class="btn primary" href="#offres"><?= e($settings['hero_cta'] ?? 'Voir les offres') ?></a>

                    <a class="btn outline" href="<?= e(getWhatsappLink($config['whatsapp_number'], 'Je veux tester 24h')) ?>" target="_blank" rel="noopener">Tester 24h</a>

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
                                <button type="button" class="icon-btn" data-music-prev title="Piste précédente">⏮</button>
                                <button type="button" class="icon-btn primary" data-music-play title="Lecture / Pause">▶</button>
                                <button type="button" class="icon-btn" data-music-next title="Piste suivante">⏭</button>
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

                        <p>Clients actifs</p>

                    </div>

                    <div>

                        <span>+40K</span>

                        <p>Chaînes & VOD</p>

                    </div>

                    <div>

                        <span>99.9%</span>

                        <p>Uptime garanti</p>

                    </div>

                </div>

            </div>

        </section>



        <section class="logo-strip" data-animate>

            <div class="provider-carousel" data-provider-carousel>

                <button class="provider-nav prev" type="button" aria-label="Précédent" data-provider-nav="prev">‹</button>

                <div class="provider-window">

                    <div class="provider-track" data-provider-track>

                        <?php foreach ($providers as $provider): ?>

                            <div class="provider-logo">

                                <img src="<?= e($provider['logo_url']) ?>" alt="<?= e($provider['name']) ?>">

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

                <button class="provider-nav next" type="button" aria-label="Suivant" data-provider-nav="next">›</button>

            </div>

        </section>



        <section class="offers" id="offres" data-animate>

            <div class="section-head">

                <p class="eyebrow">Choisis ton plan</p>

                <h2>Choose Your <span>IPTV Plan</span></h2>

                <p>Activation en 5-7 minutes · Support FR/AR/EN 24h/24</p>

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

                        <button class="btn primary" data-offer='<?= json_encode($offer, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>' data-whatsapp="<?= e(getWhatsappLink($config['whatsapp_number'], $offer['name'], (float) $offer['price'], $offer['duration'])) ?>">Acheter</button>

                        <small>Prêt en 5-7 min · WhatsApp</small>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>



        <section class="features" data-animate>

            <?php

            $benefits = [

                ['title' => 'Fast Reliable Servers', 'desc' => 'Serveurs 10Gb Montréal + anti-freeze AI.'],

                ['title' => '4K / FHD Streaming', 'desc' => 'Compatible MAG, Android, Enigma, Apple TV, FireStick.'],

                ['title' => 'Money Back Guarantee', 'desc' => 'Remboursé sous 10 jours si non satisfait.'],

                ['title' => 'Support 24/7', 'desc' => 'WhatsApp + email FR / AR / EN à toute heure.'],

            ];

            foreach ($benefits as $benefit): ?>

                <article>

                    <h3><?= e($benefit['title']) ?></h3>

                    <p><?= e($benefit['desc']) ?></p>

                </article>

            <?php endforeach; ?>

        </section>



        <section class="media-section" id="movies" data-animate>

            <div class="section-head">

                <p class="eyebrow">Movies & TV Shows</p>

                <h2>Latest blockbuster posters</h2>

            </div>

            <div class="media-carousel">

                <div class="slider" data-slider="movies" data-visible="4" data-infinite="true">

                    <div class="slider-track">

                        <?php foreach ($moviePosters as $poster): ?>

                            <article class="slide poster">

                                <img src="<?= e($poster['image_url']) ?>" alt="<?= e($poster['title']) ?>">

                            </article>

                        <?php endforeach; ?>

                    </div>

                </div>

                <div class="slider-nav" data-slider-nav="movies">

                    <button type="button" data-slider-target="movies" data-direction="prev">‹</button>

                    <button type="button" data-slider-target="movies" data-direction="next">›</button>

                </div>

            </div>

        </section>



        <section class="media-section sports" data-animate>

            <div class="section-head">

                <p class="eyebrow">All Sports Events</p>

                <h2>Football · NBA · F1 · UFC</h2>

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

                <p class="eyebrow">Supported Devices</p>

                <h2>Compatible partout</h2>

            </div>

            <div class="device-badges">

                <?php foreach ($deviceBadges as $badge): ?>

                    <span><?= e($badge) ?></span>

                <?php endforeach; ?>

            </div>

        </section>



        <section class="faq" id="faq" data-animate>

            <div class="section-head">

                <p class="eyebrow">FAQ</p>

                <h2>Questions fréquentes</h2>

            </div>

            <div class="faq-list">

                <?php foreach ($faqs as $index => $faq): ?>

                    <article class="faq-item">

                        <button type="button" class="faq-question" data-faq="<?= (int) $index ?>">

                            <span><?= e($faq['question']) ?></span>

                            <span>›</span>

                        </button>

                        <div class="faq-answer" data-faq-panel="<?= (int) $index ?>">

                            <p><?= e($faq['answer']) ?></p>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>



        <section class="testimonials" data-animate>

            <div class="section-head">

                <p class="eyebrow">Avis clients</p>

                <h2>Hear from our satisfied customers</h2>

            </div>

            <div class="media-carousel">

                <div class="slider" data-slider="testimonials" data-visible="4">

                    <div class="slider-track">

                        <?php foreach ($testimonials as $testimonial): ?>

                            <article class="slide testimonial">

                                <img src="<?= e($testimonial['capture_url']) ?>" alt="Temoignage <?= e($testimonial['name']) ?>">

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

                    <p class="eyebrow">Support express</p>

                    <h2>Need help? Contact us</h2>

                    <p>WhatsApp direct ou via ce formulaire, réponse ultra rapide.</p>

                    <?php if ($contactSuccess): ?>

                        <div class="alert success" data-flash>Merci ! Message bien reçu.</div>

                    <?php endif; ?>

                    <form action="<?= $basePath ?>/contact_submit.php" method="POST" class="contact-form">

                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                        <label>Nom complet<input type="text" name="full_name" required></label>

                        <label>Email<input type="email" name="email" required></label>

                        <label>Téléphone<input type="text" name="phone"></label>

                        <label>Message<textarea name="message" rows="4" required></textarea></label>

                        <button type="submit" class="btn primary">Envoyer</button>

                    </form>

                </div>



            </div>

        </section>

    </main>



    <footer data-animate>

        <p>© <?= date('Y') ?> <?= e($brandName) ?> · IPTV sécurisé Canada · All rights reserved.</p>

        <div class="footer-links">

            <a href="#offres">Pricing Plans</a>

            <a href="#faq">FAQ</a>

            <a href="<?= e(getWhatsappLink($config['whatsapp_number'], 'Support rapide')) ?>" target="_blank" rel="noopener">Support WhatsApp</a>

        </div>

    </footer>



    <a class="whatsapp-float whatsapp-float--chat" href="<?= e(getWhatsappLink($config['whatsapp_number'], 'Salut ABDO, besoin info IPTV')) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">

        <svg viewBox="0 0 24 24" role="img" aria-hidden="true">

            <path fill="currentColor" d="M12 2a10 10 0 0 0-8.94 14.5L2 22l5.65-1.48A10 10 0 1 0 12 2zm0 1.8a8.2 8.2 0 0 1 6.69 12.85 8.2 8.2 0 0 1-9.34 2.59l-.27-.1-3.38.88.9-3.34-.17-.28A8.2 8.2 0 0 1 12 3.8zm3.66 5.04c-.2-.005-.49-.01-.77.48-.27.49-.9 1.4-.98 1.5-.08.1-.18.15-.32.08-.14-.07-.6-.22-1.14-.56-.84-.48-1.37-1.08-1.53-1.26-.16-.18-.02-.28.12-.41.12-.12.3-.32.42-.48.14-.16.18-.28.26-.46.08-.18.04-.35-.02-.48-.07-.13-.6-1.46-.82-2-.22-.54-.46-.48-.63-.48h-.54c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.6.12.16 1.78 2.72 4.3 3.9.6.27 1.07.43 1.44.55.6.19 1.14.16 1.57.1.48-.07 1.48-.61 1.69-1.21.21-.6.21-1.12.15-1.21-.06-.09-.22-.14-.42-.15z"/>

        </svg>

    </a>

    <button class="whatsapp-float whatsapp-float--top" type="button" aria-label="Revenir en haut" data-scroll-top>
        <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
            <path fill="currentColor" d="M5 15.5 12 8l7 7.5-1.4 1.4L12 10.8l-5.6 6.1z"/>
        </svg>
    </button>



    <div class="modal" id="offerModal" hidden>

        <div class="modal-content">

            <button class="modal-close" type="button" aria-label="Fermer">×</button>

            <h3 id="modalTitle"></h3>

            <p class="modal-duration"></p>

            <p class="modal-description"></p>

            <ul class="modal-features"></ul>

            <a class="btn primary" id="modalCta" target="_blank" rel="noopener">Acheter sur WhatsApp</a>

        </div>

    </div>



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

    <script src="<?= $basePath ?>/assets/js/main.js?v=<?= time() ?>" defer></script>

</body>

</html>











