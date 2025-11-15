<?php
declare(strict_types=1);
session_start();

$config = require __DIR__ . '/../config/config.php';
/** @var PDO $pdo */
$pdo = require __DIR__ . '/../config/database.php';

logVisit($pdo);

$settings = getSettings($pdo);
$themeVars = getActiveThemeVars($settings['active_theme'] ?? 'onyx');
$brandName = $config['brand_name'] ?? 'ABDO IPTV CANADA';
$sliders = fetchAllAssoc($pdo, 'SELECT * FROM sliders ORDER BY created_at DESC');
$offers = fetchAllAssoc($pdo, 'SELECT * FROM offers ORDER BY is_featured DESC, price ASC');
$providers = fetchAllAssoc($pdo, 'SELECT * FROM providers ORDER BY created_at DESC');
$video = getPrimaryVideo($pdo);
$visitStats = getVisitStats($pdo);
$contactSuccess = isset($_GET['contact']) && $_GET['contact'] === 'success';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $scheme . '://' . $host;
$basePath = appBasePath();

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

$moviePosters = [
    ['title' => 'Kung Fu Panda 4', 'image' => 'https://res.cloudinary.com/demo/image/upload/v1700001001/iptv/kfp4.webp'],
    ['title' => 'The Beekeeper', 'image' => 'https://res.cloudinary.com/demo/image/upload/v1700001002/iptv/beekeeper.webp'],
    ['title' => 'Kingdom of the Planet of the Apes', 'image' => 'https://res.cloudinary.com/demo/image/upload/v1700001003/iptv/apes.webp'],
    ['title' => 'Furiosa', 'image' => 'https://res.cloudinary.com/demo/image/upload/v1700001004/iptv/furiosa.webp'],
    ['title' => 'The Queen\'s Gambit', 'image' => 'https://res.cloudinary.com/demo/image/upload/v1700001005/iptv/queens.webp'],
];

$sportEvents = [
    ['title' => 'Formula 1', 'image' => 'https://res.cloudinary.com/demo/image/upload/v1700001010/iptv/f1.webp'],
    ['title' => 'LaLiga', 'image' => 'https://res.cloudinary.com/demo/image/upload/v1700001011/iptv/laliga.webp'],
    ['title' => 'NBA Playoffs', 'image' => 'https://res.cloudinary.com/demo/image/upload/v1700001012/iptv/nba.webp'],
    ['title' => 'Bundesliga', 'image' => 'https://res.cloudinary.com/demo/image/upload/v1700001013/iptv/bundesliga.webp'],
    ['title' => 'Euro 2024', 'image' => 'https://res.cloudinary.com/demo/image/upload/v1700001014/iptv/euro.webp'],
];

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

$faqs = [
    ['question' => 'Quels appareils sont supportés ?', 'answer' => 'Toutes les Smart TV, Android/Apple, FireStick, MAG, PC/Mac et même ChromeCast.'],
    ['question' => 'Combien de temps pour activer mon compte ?', 'answer' => 'Entre 5 et 7 minutes après validation de votre paiement WhatsApp.'],
    ['question' => 'Puis-je tester avant d’acheter ?', 'answer' => 'Oui, demande un test 24h directement via le bouton WhatsApp.'],
    ['question' => 'Combien de connexions simultanées ?', 'answer' => 'Chaque offre inclut 1 connexion, option multi-écrans disponible sur demande.'],
    ['question' => 'Quels modes de paiement ?', 'answer' => 'Interac, virement bancaire, crypto USDT ou PayPal selon disponibilité.'],
];

$testimonials = [
    ['name' => 'Omar – Montréal', 'message' => 'Service rapide, zéro freeze pendant les matchs de NHL. Merci !', 'capture' => 'https://res.cloudinary.com/demo/image/upload/v1700001020/iptv/wa-1.webp'],
    ['name' => 'Nadia – Ottawa', 'message' => 'Support WhatsApp toujours présent, j’ai renouvelé pour 12 mois direct.', 'capture' => 'https://res.cloudinary.com/demo/image/upload/v1700001021/iptv/wa-2.webp'],
    ['name' => 'Youssef – Québec', 'message' => 'Les VOD sont mis à jour tous les jours. Netflix, Apple TV+, tout y est.', 'capture' => 'https://res.cloudinary.com/demo/image/upload/v1700001022/iptv/wa-3.webp'],
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
            <span class="logo-icon">IPTV</span>
            <div>
                <strong><?= e($brandName) ?></strong>
                <small>Ultra IPTV · Canada</small>
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
                <div class="hero-cta">
                    <a class="btn primary" href="#offres"><?= e($settings['hero_cta'] ?? 'Voir les offres') ?></a>
                    <a class="btn outline" href="<?= e(getWhatsappLink($config['whatsapp_number'], 'Je veux tester 24h')) ?>" target="_blank" rel="noopener">Tester 24h</a>
                </div>
                <ul class="device-icons">
                    <?php foreach (['Smart TV', 'Laptops/PC', 'Android', 'iOS', 'Windows'] as $device): ?>
                        <li><?= e($device) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="hero-visual">
                <div class="screen-frame">
                    <div class="slider" data-slider="hero">
                        <?php foreach ($sliders as $slider): ?>
                            <article class="slide">
                                <div class="media">
                                    <?php if ($slider['media_type'] === 'video'): ?>
                                        <video autoplay muted loop playsinline>
                                            <source src="<?= e($slider['media_url']) ?>" type="video/mp4">
                                        </video>
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
            <div class="logo-track">
                <?php foreach ($providers as $provider): ?>
                    <div class="logo-item">
                        <img src="<?= e($provider['logo_url']) ?>" alt="<?= e($provider['name']) ?>">
                    </div>
                <?php endforeach; ?>
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
                <div class="slider" data-slider="movies">
                    <?php foreach ($moviePosters as $poster): ?>
                        <article class="slide poster">
                            <img src="<?= e($poster['image']) ?>" alt="<?= e($poster['title']) ?>">
                            <span><?= e($poster['title']) ?></span>
                        </article>
                    <?php endforeach; ?>
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
                <div class="slider" data-slider="sports">
                    <?php foreach ($sportEvents as $event): ?>
                        <article class="slide poster">
                            <img src="<?= e($event['image']) ?>" alt="<?= e($event['title']) ?>">
                            <span><?= e($event['title']) ?></span>
                        </article>
                    <?php endforeach; ?>
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
                <div class="slider" data-slider="testimonials">
                    <?php foreach ($testimonials as $testimonial): ?>
                        <article class="slide testimonial">
                            <img src="<?= e($testimonial['capture']) ?>" alt="Capture WhatsApp">
                            <div>
                                <strong><?= e($testimonial['name']) ?></strong>
                                <p><?= e($testimonial['message']) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="slider-nav" data-slider-nav="testimonials">
                    <button type="button" data-slider-target="testimonials" data-direction="prev">‹</button>
                    <button type="button" data-slider-target="testimonials" data-direction="next">›</button>
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
                        <div class="alert success">Merci ! Message bien reçu.</div>
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
                <div class="analytics">
                    <h3>Top pays visiteurs</h3>
                    <ul>
                        <?php foreach ($visitStats['countries'] as $country): ?>
                            <li>
                                <span><?= e($country['country'] ?: 'Inconnu') ?></span>
                                <span><?= e($country['total']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
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

    <a class="whatsapp-float" href="<?= e(getWhatsappLink($config['whatsapp_number'], 'Salut ABDO, besoin info IPTV')) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
        <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
            <path fill="currentColor" d="M12 2a10 10 0 0 0-8.94 14.5L2 22l5.65-1.48A10 10 0 1 0 12 2zm0 1.8a8.2 8.2 0 0 1 6.69 12.85 8.2 8.2 0 0 1-9.34 2.59l-.27-.1-3.38.88.9-3.34-.17-.28A8.2 8.2 0 0 1 12 3.8zm3.66 5.04c-.2-.005-.49-.01-.77.48-.27.49-.9 1.4-.98 1.5-.08.1-.18.15-.32.08-.14-.07-.6-.22-1.14-.56-.84-.48-1.37-1.08-1.53-1.26-.16-.18-.02-.28.12-.41.12-.12.3-.32.42-.48.14-.16.18-.28.26-.46.08-.18.04-.35-.02-.48-.07-.13-.6-1.46-.82-2-.22-.54-.46-.48-.63-.48h-.54c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.6.12.16 1.78 2.72 4.3 3.9.6.27 1.07.43 1.44.55.6.19 1.14.16 1.57.1.48-.07 1.48-.61 1.69-1.21.21-.6.21-1.12.15-1.21-.06-.09-.22-.14-.42-.15z"/>
        </svg>
    </a>

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

    <script>
        window.APP_THEME = <?= json_encode($settings['active_theme'] ?? 'onyx') ?>;
    </script>
    <script src="<?= $basePath ?>/assets/js/main.js?v=<?= time() ?>" defer></script>
</body>
</html>
