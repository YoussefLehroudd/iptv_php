<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
$config = require __DIR__ . '/../config/config.php';
/** @var PDO $pdo */
$pdo = require __DIR__ . '/../config/database.php';
requireAdmin();

$basePath = appBasePath();
$adminBase = $basePath . '/abdo_admin';

function adminFlashRedirect(string $message, string $section, string $adminBase): void
{
    $_SESSION['admin_flash'] = $message;
    header('Location: ' . $adminBase . '/dashboard.php?section=' . urlencode($section));
    exit;
}

function adminNavIcon(string $name): string
{
    static $icons = [
        'content' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="13" x2="15" y2="13"></line><line x1="9" y1="17" x2="15" y2="17"></line></svg>',
        'theme' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4a8 8 0 0 0-8 8 8 8 0 0 0 8 8h.5a1.5 1.5 0 0 0 0-3H12a3 3 0 0 1 0-6h1a3 3 0 0 0 3-3V9a5 5 0 0 0-5-5z"></path><circle cx="7.5" cy="10.5" r="1"></circle><circle cx="15" cy="8" r="1"></circle><circle cx="17.5" cy="12" r="1"></circle></svg>',
        'slider' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="8.5" cy="11" r="1.5"></circle><path d="M21 17l-5.5-5.5L11 16l-3-3-5 5"></path></svg>',
        'offers' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line><line x1="7" y1="15" x2="9.5" y2="15"></line><line x1="12" y1="15" x2="17" y2="15"></line></svg>',
        'providers' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="7" width="16" height="11" rx="2"></rect><path d="M12 3l-3 4"></path><path d="M12 3l3 4"></path></svg>',
        'movies' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="6" y1="3" x2="6" y2="7"></line><line x1="18" y1="3" x2="18" y2="7"></line><circle cx="9" cy="12" r="1.2"></circle><circle cx="15" cy="12" r="1.2"></circle></svg>',
        'sports' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M5 12h14"></path><path d="M12 3a15 15 0 0 1 0 18"></path></svg>',
        'video' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 11h16v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"></path><path d="M4 7h16v4H4z"></path><path d="M5 7l2-3"></path><path d="M9 7l2-3"></path><path d="M13 7l2-3"></path></svg>',
        'testimonials' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 8h10"></path><path d="M7 12h6"></path><path d="M21 11c0-4.97-4.03-9-9-9S3 6.03 3 11v10l4.5-3H12c4.97 0 9-4.03 9-9z"></path></svg>',
        'messages' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 5H5a3 3 0 0 0-3 3v6a3 3 0 0 0 3 3h2v4l4-4h8a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3z"></path></svg>',
        'analytics' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 16l4-4 4 4 7-7"></path><path d="M20 13V9h-4"></path></svg>',
    ];

    return $icons[$name] ?? $icons['content'];
}

$navItems = [
    'content' => ['label' => 'Hero & SEO', 'icon' => adminNavIcon('content')],
    'theme' => ['label' => 'Theme & couleurs', 'icon' => adminNavIcon('theme')],
    'slider' => ['label' => 'Slider hero', 'icon' => adminNavIcon('slider')],
    'movies' => ['label' => 'Movies posters', 'icon' => adminNavIcon('movies')],
    'sports' => ['label' => 'Sports events', 'icon' => adminNavIcon('sports')],
    'testimonials' => ['label' => 'Temoignages', 'icon' => adminNavIcon('testimonials')],
    'offers' => ['label' => 'Offres IPTV', 'icon' => adminNavIcon('offers')],
    'providers' => ['label' => 'Providers', 'icon' => adminNavIcon('providers')],
    'video' => ['label' => 'Video highlight', 'icon' => adminNavIcon('video')],
    'messages' => ['label' => 'Messages', 'icon' => adminNavIcon('messages')],
    'analytics' => ['label' => 'Analytics', 'icon' => adminNavIcon('analytics')],
];

$currentSection = $_GET['section'] ?? 'content';
if (!array_key_exists($currentSection, $navItems)) {
    $currentSection = 'content';
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$feedback = [];
if (!empty($_SESSION['admin_flash'])) {
    $feedback[] = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['admin_csrf'], $token)) {
        http_response_code(403);
        exit('CSRF token invalide');
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_settings':
            $fields = ['hero_title', 'hero_subtitle', 'hero_cta', 'seo_title', 'seo_description', 'highlight_video_headline', 'highlight_video_copy'];
            foreach ($fields as $field) {
                setSetting($pdo, $field, trim($_POST[$field] ?? ''), true);
            }
            adminFlashRedirect('Contenu mise à jour.', 'content', $adminBase);
            break;
        case 'update_theme':
            $theme = $_POST['theme'] ?? 'onyx';
            if (isset(themeOptions()[$theme])) {
                setSetting($pdo, 'active_theme', $theme);
                adminFlashRedirect('Thème changé.', 'theme', $adminBase);
            }
            break;
        case 'add_slider':
            $title = trim($_POST['title'] ?? '');
            $subtitle = trim($_POST['subtitle'] ?? '');
            $mediaType = $_POST['media_type'] ?? 'image';
            $mediaUrl = trim($_POST['media_url'] ?? '');
            if (!empty($_FILES['media_file']['tmp_name'])) {
                $mediaUrl = uploadToCloudinary($_FILES['media_file']['tmp_name'], 'iptv_abdo/sliders', $config['cloudinary']) ?? $mediaUrl;
            }
            if ($title && $mediaUrl) {
                $stmt = $pdo->prepare('INSERT INTO sliders (title, subtitle, media_url, media_type, cta_label, cta_description) VALUES (:title, :subtitle, :media_url, :media_type, :cta_label, :cta_description)');
                $stmt->execute([
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'media_url' => $mediaUrl,
                    'media_type' => $mediaType === 'video' ? 'video' : 'image',
                    'cta_label' => trim($_POST['cta_label'] ?? ''),
                    'cta_description' => trim($_POST['cta_description'] ?? ''),
                ]);
                adminFlashRedirect('Slider ajouté.', 'slider', $adminBase);
            }
            break;
        case 'update_slider':
            $sliderId = (int) ($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            if ($sliderId && $title) {
                $stmt = $pdo->prepare('SELECT * FROM sliders WHERE id = :id');
                $stmt->execute(['id' => $sliderId]);
                if ($slider = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $mediaType = $_POST['media_type'] ?? $slider['media_type'];
                    $mediaUrl = trim($_POST['media_url'] ?? '');
                    if (!empty($_FILES['media_file']['tmp_name'])) {
                        $upload = uploadToCloudinary($_FILES['media_file']['tmp_name'], 'iptv_abdo/sliders', $config['cloudinary']);
                        if ($upload) {
                            $mediaUrl = $upload;
                        }
                    }
                    if (!$mediaUrl) {
                        $mediaUrl = $slider['media_url'];
                    }
                    if ($mediaUrl) {
                        $stmt = $pdo->prepare('UPDATE sliders SET title = :title, subtitle = :subtitle, media_url = :media_url, media_type = :media_type, cta_label = :cta_label, cta_description = :cta_description WHERE id = :id');
                        $stmt->execute([
                            'title' => $title,
                            'subtitle' => trim($_POST['subtitle'] ?? ''),
                            'media_url' => $mediaUrl,
                            'media_type' => $mediaType === 'video' ? 'video' : 'image',
                            'cta_label' => trim($_POST['cta_label'] ?? ''),
                            'cta_description' => trim($_POST['cta_description'] ?? ''),
                            'id' => $sliderId,
                        ]);
                        adminFlashRedirect('Slider mise à jour.', 'slider', $adminBase);
                    }
                }
            }
            break;
        case 'add_movie_poster':
            $title = trim($_POST['title'] ?? '');
            $imageUrl = trim($_POST['image_url'] ?? '');
            if (!empty($_FILES['image_file']['tmp_name'])) {
                $upload = uploadToCloudinary($_FILES['image_file']['tmp_name'], 'iptv_abdo/movies', $config['cloudinary']);
                if ($upload) {
                    $imageUrl = $upload;
                }
            }
            if ($title && $imageUrl) {
                $stmt = $pdo->prepare('INSERT INTO movie_posters (title, image_url) VALUES (:title, :image)');
                $stmt->execute(['title' => $title, 'image' => $imageUrl]);
                adminFlashRedirect('Affiche ajouté.', 'movies', $adminBase);
            }
            break;
        case 'update_movie_poster':
            $posterId = (int) ($_POST['id'] ?? 0);
            if ($posterId) {
                $stmt = $pdo->prepare('SELECT * FROM movie_posters WHERE id = :id');
                $stmt->execute(['id' => $posterId]);
                if ($poster = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $title = trim($_POST['title'] ?? '') ?: $poster['title'];
                    $imageUrl = trim($_POST['image_url'] ?? '') ?: $poster['image_url'];
                    if (!empty($_FILES['image_file']['tmp_name'])) {
                        $upload = uploadToCloudinary($_FILES['image_file']['tmp_name'], 'iptv_abdo/movies', $config['cloudinary']);
                        if ($upload) {
                            $imageUrl = $upload;
                        }
                    }
                    $stmt = $pdo->prepare('UPDATE movie_posters SET title = :title, image_url = :image WHERE id = :id');
                    $stmt->execute(['title' => $title, 'image' => $imageUrl, 'id' => $posterId]);
                    adminFlashRedirect('Affiche mise à jour.', 'movies', $adminBase);
                }
            }
            break;
        case 'add_sport_event':
            $title = trim($_POST['title'] ?? '');
            $imageUrl = trim($_POST['image_url'] ?? '');
            if (!empty($_FILES['image_file']['tmp_name'])) {
                $upload = uploadToCloudinary($_FILES['image_file']['tmp_name'], 'iptv_abdo/sports', $config['cloudinary']);
                if ($upload) {
                    $imageUrl = $upload;
                }
            }
            if ($title && $imageUrl) {
                $stmt = $pdo->prepare('INSERT INTO sport_events (title, image_url) VALUES (:title, :image)');
                $stmt->execute(['title' => $title, 'image' => $imageUrl]);
                adminFlashRedirect('Visuel sport ajout.', 'sports', $adminBase);
            }
            break;
        case 'update_sport_event':
            $eventId = (int) ($_POST['id'] ?? 0);
            if ($eventId) {
                $stmt = $pdo->prepare('SELECT * FROM sport_events WHERE id = :id');
                $stmt->execute(['id' => $eventId]);
                if ($event = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $title = trim($_POST['title'] ?? '') ?: $event['title'];
                    $imageUrl = trim($_POST['image_url'] ?? '') ?: $event['image_url'];
                    if (!empty($_FILES['image_file']['tmp_name'])) {
                        $upload = uploadToCloudinary($_FILES['image_file']['tmp_name'], 'iptv_abdo/sports', $config['cloudinary']);
                        if ($upload) {
                            $imageUrl = $upload;
                        }
                    }
                    $stmt = $pdo->prepare('UPDATE sport_events SET title = :title, image_url = :image WHERE id = :id');
                    $stmt->execute(['title' => $title, 'image' => $imageUrl, 'id' => $eventId]);
                    adminFlashRedirect('Visuel sport mise à jour.', 'sports', $adminBase);
                }
            }
            break;
        case 'add_testimonial':
            $name = trim($_POST['name'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $captureUrl = trim($_POST['capture_url'] ?? '');
            if (!empty($_FILES['capture_file']['tmp_name'])) {
                $upload = uploadToCloudinary($_FILES['capture_file']['tmp_name'], 'iptv_abdo/testimonials', $config['cloudinary']);
                if ($upload) {
                    $captureUrl = $upload;
                }
            }
            if ($name && $captureUrl) {
                $stmt = $pdo->prepare('INSERT INTO testimonials (name, message, capture_url) VALUES (:name, :message, :capture)');
                $stmt->execute(['name' => $name, 'message' => $message, 'capture' => $captureUrl]);
                adminFlashRedirect('Temoignage ajoute.', 'testimonials', $adminBase);
            }
            break;
        case 'update_testimonial':
            $testimonialId = (int) ($_POST['id'] ?? 0);
            if ($testimonialId) {
                $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE id = :id');
                $stmt->execute(['id' => $testimonialId]);
                if ($testimonial = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $name = trim($_POST['name'] ?? '') ?: $testimonial['name'];
                    $message = trim($_POST['message'] ?? '') ?: $testimonial['message'];
                    $captureUrl = trim($_POST['capture_url'] ?? '') ?: $testimonial['capture_url'];
                    if (!empty($_FILES['capture_file']['tmp_name'])) {
                        $upload = uploadToCloudinary($_FILES['capture_file']['tmp_name'], 'iptv_abdo/testimonials', $config['cloudinary']);
                        if ($upload) {
                            $captureUrl = $upload;
                        }
                    }
                    $stmt = $pdo->prepare('UPDATE testimonials SET name = :name, message = :message, capture_url = :capture WHERE id = :id');
                    $stmt->execute(['name' => $name, 'message' => $message, 'capture' => $captureUrl, 'id' => $testimonialId]);
                    adminFlashRedirect('Temoignage mise à jour.', 'testimonials', $adminBase);
                }
            }
            break;
        case 'add_offer':
            $stmt = $pdo->prepare('INSERT INTO offers (name, price, duration, description, features, is_featured) VALUES (:name, :price, :duration, :description, :features, :is_featured)');
            $stmt->execute([
                'name' => trim($_POST['name'] ?? ''),
                'price' => (float) ($_POST['price'] ?? 0),
                'duration' => trim($_POST['duration'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'features' => trim($_POST['features'] ?? ''),
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            ]);
            adminFlashRedirect('Offre créée.', 'offers', $adminBase);
            break;
        case 'update_offer':
            $offerId = (int) ($_POST['id'] ?? 0);
            if ($offerId) {
                $stmt = $pdo->prepare('UPDATE offers SET name = :name, price = :price, duration = :duration, description = :description, features = :features, is_featured = :is_featured WHERE id = :id');
                $stmt->execute([
                    'name' => trim($_POST['name'] ?? ''),
                    'price' => (float) ($_POST['price'] ?? 0),
                    'duration' => trim($_POST['duration'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'features' => trim($_POST['features'] ?? ''),
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'id' => $offerId,
                ]);
                adminFlashRedirect('Offre mise à jour.', 'offers', $adminBase);
            }
            break;
        case 'add_provider':
            $name = trim($_POST['name'] ?? '');
            $logo = trim($_POST['logo_url'] ?? '');
            if (!empty($_FILES['logo_file']['tmp_name'])) {
                $logo = uploadToCloudinary($_FILES['logo_file']['tmp_name'], 'iptv_abdo/providers', $config['cloudinary']) ?? $logo;
            }
            if ($name && $logo) {
                $stmt = $pdo->prepare('INSERT INTO providers (name, logo_url) VALUES (:name, :logo)');
                $stmt->execute(['name' => $name, 'logo' => $logo]);
                adminFlashRedirect('Provider ajouté.', 'providers', $adminBase);
            }
            break;
        case 'update_provider':
            $providerId = (int) ($_POST['id'] ?? 0);
            if ($providerId) {
                $stmt = $pdo->prepare('SELECT * FROM providers WHERE id = :id');
                $stmt->execute(['id' => $providerId]);
                if ($provider = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $name = trim($_POST['name'] ?? '') ?: $provider['name'];
                    $logo = trim($_POST['logo_url'] ?? '') ?: $provider['logo_url'];
                    if (!empty($_FILES['logo_file']['tmp_name'])) {
                        $uploadedLogo = uploadToCloudinary($_FILES['logo_file']['tmp_name'], 'iptv_abdo/providers', $config['cloudinary']);
                        if ($uploadedLogo) {
                            $logo = $uploadedLogo;
                        }
                    }
                    if ($name && $logo) {
                        $stmt = $pdo->prepare('UPDATE providers SET name = :name, logo_url = :logo WHERE id = :id');
                        $stmt->execute(['name' => $name, 'logo' => $logo, 'id' => $providerId]);
                        adminFlashRedirect('Provider mise à jour.', 'providers', $adminBase);
                    }
                }
            }
            break;
        case 'add_video':
            $stmt = $pdo->prepare('INSERT INTO videos (title, description, url, thumbnail_url) VALUES (:title, :description, :url, :thumbnail_url)');
            $stmt->execute([
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'url' => trim($_POST['url'] ?? ''),
                'thumbnail_url' => trim($_POST['thumbnail_url'] ?? ''),
            ]);
            adminFlashRedirect('Vidéo enregistrée.', 'video', $adminBase);
            break;
        case 'mark_message':
            markMessageAsRead($pdo, (int) $_POST['message_id']);
            adminFlashRedirect('Message marqué comme lu.', 'messages', $adminBase);
            break;
    }
}

if (isset($_GET['delete'], $_GET['id'])) {
    deleteRecord($pdo, preg_replace('/[^a-z_]/', '', $_GET['delete']), (int) $_GET['id']);
    adminFlashRedirect('Élément supprimé.', $currentSection, $adminBase);
}
$settings = getSettings($pdo);
$themeVars = getActiveThemeVars($settings['active_theme'] ?? 'onyx');
$sliders = fetchAllAssoc($pdo, 'SELECT * FROM sliders ORDER BY created_at DESC');
$offers = fetchAllAssoc($pdo, 'SELECT * FROM offers ORDER BY created_at DESC');
$providers = fetchAllAssoc($pdo, 'SELECT * FROM providers ORDER BY created_at DESC');
$moviePosters = fetchAllAssoc($pdo, 'SELECT * FROM movie_posters ORDER BY created_at DESC');
$sportEvents = fetchAllAssoc($pdo, 'SELECT * FROM sport_events ORDER BY created_at DESC');
$testimonialGallery = fetchAllAssoc($pdo, 'SELECT * FROM testimonials ORDER BY created_at DESC');
$video = getPrimaryVideo($pdo);
$messages = getContactMessages($pdo);
$visitStats = getVisitStats($pdo);
$themes = themeOptions();

$editing = [
    'sliders' => null,
    'offers' => null,
    'providers' => null,
    'movie_posters' => null,
    'sport_events' => null,
    'testimonials' => null,
];
if (isset($_GET['edit'], $_GET['id'])) {
    $table = preg_replace('/[^a-z_]/', '', $_GET['edit']);
    $id = (int) $_GET['id'];
    $collections = [
        'sliders' => $sliders,
        'offers' => $offers,
        'providers' => $providers,
        'movie_posters' => $moviePosters,
        'sport_events' => $sportEvents,
        'testimonials' => $testimonialGallery,
    ];
    if (isset($collections[$table])) {
        foreach ($collections[$table] as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                $editing[$table] = $item;
                break;
            }
        }
    }
}
$editingSlider = $editing['sliders'];
$editingOffer = $editing['offers'];
$editingProvider = $editing['providers'];
$editingMoviePoster = $editing['movie_posters'];
$editingSportEvent = $editing['sport_events'];
$editingTestimonial = $editing['testimonials'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel ABDO IPTV</title>
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body class="admin">
<header class="admin-bar">
    <div class="admin-bar-header-row">
        <button class="sidebar-toggle" type="button" aria-controls="adminSidebar" aria-expanded="false" data-sidebar-toggle>
            <span class="sr-only">Menu</span>
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div>
            <h1>Panel ABDO IPTV</h1>
            <p>Contrôle complet du contenu 2025</p>
        </div>
    </div>
    <div>
        <span><?= e($_SESSION['admin_email']) ?></span>
        <a class="btn ghost" href="<?= $adminBase ?>/logout.php">Déconnexion</a>
    </div>
</header>

<button class="sidebar-overlay" type="button" aria-label="Fermer le menu" data-sidebar-overlay></button>

<div class="admin-layout">
    <aside id="adminSidebar" class="admin-sidebar">
        <div class="sidebar-logo">ABDO IPTV <small>Ultra IPTV · Canada</small></div>
        <nav class="sidebar-nav">
            <?php foreach ($navItems as $slug => $item): ?>
                <a class="<?= $currentSection === $slug ? 'active' : '' ?>" href="<?= $adminBase ?>/dashboard.php?section=<?= $slug ?>">
                    <span class="icon"><?= $item['icon'] ?></span>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <small>Connecté : <?= e($_SESSION['admin_email']) ?></small>
            <a class="link-light" href="<?= $basePath ?>/" target="_blank" rel="noopener">↗ Voir le site public</a>
        </div>
    </aside>
    <main class="admin-content">
        <?php if (!empty($feedback)): ?>
            <div class="alert success" data-auto-dismiss><?= e(implode(' · ', $feedback)) ?></div>
        <?php endif; ?>

        <?php if ($currentSection === 'content'): ?>
            <section class="admin-section">
                <h2>Contenu hero & SEO</h2>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=content">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="update_settings">
                    <label>Titre hero
                        <input type="text" name="hero_title" value="<?= e($settings['hero_title'] ?? '') ?>" required>
                    </label>
                    <label>Sous-titre
                        <input type="text" name="hero_subtitle" value="<?= e($settings['hero_subtitle'] ?? '') ?>">
                    </label>
                    <label>Texte CTA
                        <input type="text" name="hero_cta" value="<?= e($settings['hero_cta'] ?? '') ?>">
                    </label>
                    <label>SEO Title
                        <input type="text" name="seo_title" value="<?= e($settings['seo_title'] ?? '') ?>">
                    </label>
                    <label>SEO Description
                        <textarea name="seo_description" rows="4"><?= e($settings['seo_description'] ?? '') ?></textarea>
                    </label>
                    <label>Titre vidéo highlight
                        <input type="text" name="highlight_video_headline" value="<?= e($settings['highlight_video_headline'] ?? '') ?>">
                    </label>
                    <label>Texte vidéo highlight
                        <textarea name="highlight_video_copy" rows="3"><?= e($settings['highlight_video_copy'] ?? '') ?></textarea>
                    </label>
                    <button class="btn" type="submit">Sauvegarder</button>
                </form>
            </section>
        <?php elseif ($currentSection === 'theme'): ?>
            <section class="admin-section">
                <h2>Thème & couleurs</h2>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=theme">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="update_theme">
                    <?php foreach ($themes as $slug => $theme): ?>
                        <label class="theme-option">
                            <span><?= e($theme['label']) ?></span>
                            <input type="radio" name="theme" value="<?= e($slug) ?>" <?= ($settings['active_theme'] ?? 'onyx') === $slug ? 'checked' : '' ?>>
                        </label>
                    <?php endforeach; ?>
                    <button class="btn" type="submit">Changer le thème</button>
                </form>
            </section>
        <?php elseif ($currentSection === 'slider'): ?>
            <section class="admin-section">
                <h2>Slider hero</h2>
                <?php $isEditingSlider = !empty($editingSlider); ?>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=slider" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="<?= $isEditingSlider ? 'update_slider' : 'add_slider' ?>">
                    <?php if ($isEditingSlider): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingSlider['id'] ?>">
                        <p class="form-note">Modification de « <?= e($editingSlider['title']) ?> »</p>
                    <?php endif; ?>
                    <label>Titre
                        <input type="text" name="title" value="<?= e($editingSlider['title'] ?? '') ?>" required>
                    </label>
                    <label>Sous-titre
                        <input type="text" name="subtitle" value="<?= e($editingSlider['subtitle'] ?? '') ?>">
                    </label>
                    <label>Type media
                        <select name="media_type">
                            <option value="image" <?= (($editingSlider['media_type'] ?? '') === 'image') ? 'selected' : '' ?>>Image</option>
                            <option value="video" <?= (($editingSlider['media_type'] ?? '') === 'video') ? 'selected' : '' ?>>Vidéo</option>
                        </select>
                    </label>
                    <label>Upload media
                        <input type="file" name="media_file">
                    </label>
                    <label>Ou URL media
                        <input type="url" name="media_url" placeholder="https://" value="<?= e($editingSlider['media_url'] ?? '') ?>">
                        <?php if ($isEditingSlider && $editingSlider['media_url']): ?>
                            <span class="form-note">Media actuel : <?= e($editingSlider['media_url']) ?></span>
                        <?php endif; ?>
                    </label>
                    <label>CTA label
                        <input type="text" name="cta_label" value="<?= e($editingSlider['cta_label'] ?? '') ?>">
                    </label>
                    <label>CTA description
                        <input type="text" name="cta_description" value="<?= e($editingSlider['cta_description'] ?? '') ?>">
                    </label>
                    <div class="form-actions">
                        <button class="btn" type="submit"><?= $isEditingSlider ? 'Mettre à jour' : 'Ajouter' ?></button>
                        <?php if ($isEditingSlider): ?>
                            <a class="link-light small" href="<?= $adminBase ?>/dashboard.php?section=slider">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="list slider-hero-list">
                    <?php foreach ($sliders as $slider): ?>
                        <article>
                            <div>
                                <strong><?= e($slider['title']) ?></strong>
                                <small><?= e($slider['media_type']) ?></small>
                            </div>
                            <div class="row-actions">
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=slider&edit=sliders&id=<?= (int) $slider['id'] ?>">Modifier</a>
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=slider&delete=sliders&id=<?= (int) $slider['id'] ?>">Supprimer</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($currentSection === 'movies'): ?>
            <section class="admin-section">
                <h2>Movies & TV posters</h2>
                <?php $isEditingMovie = !empty($editingMoviePoster); ?>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=movies" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="<?= $isEditingMovie ? 'update_movie_poster' : 'add_movie_poster' ?>">
                    <?php if ($isEditingMovie): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingMoviePoster['id'] ?>">
                        <p class="form-note">Edition de <?= e($editingMoviePoster['title']) ?></p>
                    <?php endif; ?>
                    <label>Titre visuel
                        <input type="text" name="title" value="<?= e($editingMoviePoster['title'] ?? '') ?>" required>
                    </label>
                    <label>URL image
                        <input type="url" name="image_url" placeholder="https://" value="<?= e($editingMoviePoster['image_url'] ?? '') ?>">
                        <?php if (!empty($editingMoviePoster['image_url'])): ?>
                            <span class="form-note">Image actuelle : <?= e($editingMoviePoster['image_url']) ?></span>
                        <?php endif; ?>
                    </label>
                    <label>Upload image
                        <input type="file" name="image_file" accept="image/*">
                    </label>
                    <div class="form-actions">
                        <button class="btn" type="submit"><?= $isEditingMovie ? 'Mettre à jour' : 'Ajouter' ?></button>
                        <?php if ($isEditingMovie): ?>
                            <a class="link-light small" href="<?= $adminBase ?>/dashboard.php?section=movies">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="list admin-media-list">
                    <?php foreach ($moviePosters as $poster): ?>
                        <article>
                            <div class="admin-media-thumb">
                                <img src="<?= e($poster['image_url']) ?>" alt="<?= e($poster['title']) ?>">
                                <div>
                                    <strong><?= e($poster['title']) ?></strong>
                                </div>
                            </div>
                            <div class="row-actions">
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=movies&edit=movie_posters&id=<?= (int) $poster['id'] ?>">Modifier</a>
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=movies&delete=movie_posters&id=<?= (int) $poster['id'] ?>">Supprimer</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($currentSection === 'sports'): ?>
            <section class="admin-section">
                <h2>Sports events</h2>
                <?php $isEditingSport = !empty($editingSportEvent); ?>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=sports" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="<?= $isEditingSport ? 'update_sport_event' : 'add_sport_event' ?>">
                    <?php if ($isEditingSport): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingSportEvent['id'] ?>">
                        <p class="form-note">Edition de <?= e($editingSportEvent['title']) ?></p>
                    <?php endif; ?>
                    <label>Titre visuel
                        <input type="text" name="title" value="<?= e($editingSportEvent['title'] ?? '') ?>" required>
                    </label>
                    <label>URL image
                        <input type="url" name="image_url" placeholder="https://" value="<?= e($editingSportEvent['image_url'] ?? '') ?>">
                        <?php if (!empty($editingSportEvent['image_url'])): ?>
                            <span class="form-note">Image actuelle : <?= e($editingSportEvent['image_url']) ?></span>
                        <?php endif; ?>
                    </label>
                    <label>Upload image
                        <input type="file" name="image_file" accept="image/*">
                    </label>
                    <div class="form-actions">
                        <button class="btn" type="submit"><?= $isEditingSport ? 'Mettre à jour' : 'Ajouter' ?></button>
                        <?php if ($isEditingSport): ?>
                            <a class="link-light small" href="<?= $adminBase ?>/dashboard.php?section=sports">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="list admin-media-list">
                    <?php foreach ($sportEvents as $event): ?>
                        <article>
                            <div class="admin-media-thumb">
                                <img src="<?= e($event['image_url']) ?>" alt="<?= e($event['title']) ?>">
                                <div>
                                    <strong><?= e($event['title']) ?></strong>
                                </div>
                            </div>
                            <div class="row-actions">
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=sports&edit=sport_events&id=<?= (int) $event['id'] ?>">Modifier</a>
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=sports&delete=sport_events&id=<?= (int) $event['id'] ?>">Supprimer</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($currentSection === 'testimonials'): ?>
            <section class="admin-section">
                <h2>Temoignages visuels</h2>
                <?php $isEditingTestimonial = !empty($editingTestimonial); ?>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=testimonials" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="<?= $isEditingTestimonial ? 'update_testimonial' : 'add_testimonial' ?>">
                    <?php if ($isEditingTestimonial): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingTestimonial['id'] ?>">
                        <p class="form-note">Edition de <?= e($editingTestimonial['name']) ?></p>
                    <?php endif; ?>
                    <label>Nom / localisation
                        <input type="text" name="name" value="<?= e($editingTestimonial['name'] ?? '') ?>" required>
                    </label>
                    <label>Message
                        <textarea name="message" rows="3"><?= e($editingTestimonial['message'] ?? '') ?></textarea>
                    </label>
                    <label>URL capture
                        <input type="url" name="capture_url" placeholder="https://" value="<?= e($editingTestimonial['capture_url'] ?? '') ?>">
                        <?php if (!empty($editingTestimonial['capture_url'])): ?>
                            <span class="form-note">Capture actuelle : <?= e($editingTestimonial['capture_url']) ?></span>
                        <?php endif; ?>
                    </label>
                    <label>Upload capture
                        <input type="file" name="capture_file" accept="image/*">
                    </label>
                    <div class="form-actions">
                        <button class="btn" type="submit"><?= $isEditingTestimonial ? 'Mettre à jour' : 'Ajouter' ?></button>
                        <?php if ($isEditingTestimonial): ?>
                            <a class="link-light small" href="<?= $adminBase ?>/dashboard.php?section=testimonials">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="list admin-media-list">
                    <?php foreach ($testimonialGallery as $testimonial): ?>
                        <article>
                            <div class="admin-media-thumb">
                                <img src="<?= e($testimonial['capture_url']) ?>" alt="<?= e($testimonial['name']) ?>">
                                <div>
                                    <strong><?= e($testimonial['name']) ?></strong>
                                    <?php if (!empty($testimonial['message'])): ?>
                                        <small><?= e($testimonial['message']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="row-actions">
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=testimonials&edit=testimonials&id=<?= (int) $testimonial['id'] ?>">Modifier</a>
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=testimonials&delete=testimonials&id=<?= (int) $testimonial['id'] ?>">Supprimer</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($currentSection === 'offers'): ?>
            <section class="admin-section">
                <h2>Offres IPTV</h2>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=offers">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <?php $isEditingOffer = !empty($editingOffer); ?>
                    <input type="hidden" name="action" value="<?= $isEditingOffer ? 'update_offer' : 'add_offer' ?>">
                    <?php if ($isEditingOffer): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingOffer['id'] ?>">
                        <p class="form-note">Modification de l'offre « <?= e($editingOffer['name']) ?> »</p>
                    <?php endif; ?>
                    <label>Nom offre
                        <input type="text" name="name" value="<?= e($editingOffer['name'] ?? '') ?>" required>
                    </label>
                    <label>Prix CAD
                        <input type="number" step="0.01" name="price" value="<?= e($editingOffer['price'] ?? '') ?>" required>
                    </label>
                    <label>Durée
                        <input type="text" name="duration" value="<?= e($editingOffer['duration'] ?? '') ?>" required>
                    </label>
                    <label>Description
                        <textarea name="description" rows="2"><?= e($editingOffer['description'] ?? '') ?></textarea>
                    </label>
                    <label>Features (1 par ligne)
                        <textarea name="features" rows="3"><?= e($editingOffer['features'] ?? '') ?></textarea>
                    </label>
                    <label class="switch">
                        <span>Mettre en avant</span>
                        <input type="checkbox" name="is_featured" <?= !empty($editingOffer['is_featured']) ? 'checked' : '' ?>>
                    </label>
                    <div class="form-actions">
                        <button class="btn" type="submit"><?= $isEditingOffer ? 'Mettre à jour' : 'Ajouter l\'offre' ?></button>
                        <?php if ($isEditingOffer): ?>
                            <a class="link-light small" href="<?= $adminBase ?>/dashboard.php?section=offers">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="list">
                    <?php foreach ($offers as $offer): ?>
                        <article>
                            <div>
                                <strong><?= e($offer['name']) ?></strong>
                                <small><?= e(formatCurrency((float) $offer['price'])) ?> CAD</small>
                            </div>
                            <div class="row-actions">
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=offers&edit=offers&id=<?= (int) $offer['id'] ?>">Modifier</a>
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=offers&delete=offers&id=<?= (int) $offer['id'] ?>">Supprimer</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($currentSection === 'providers'): ?>
            <section class="admin-section">
                <h2>Providers</h2>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=providers" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <?php $isEditingProvider = !empty($editingProvider); ?>
                    <input type="hidden" name="action" value="<?= $isEditingProvider ? 'update_provider' : 'add_provider' ?>">
                    <?php if ($isEditingProvider): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingProvider['id'] ?>">
                        <p class="form-note">Modification du provider « <?= e($editingProvider['name']) ?> »</p>
                    <?php endif; ?>
                    <label>Nom
                        <input type="text" name="name" value="<?= e($editingProvider['name'] ?? '') ?>" required>
                    </label>
                    <label>Logo upload
                        <input type="file" name="logo_file">
                    </label>
                    <label>ou URL
                        <input type="url" name="logo_url" placeholder="https://" value="<?= e($editingProvider['logo_url'] ?? '') ?>">
                        <?php if ($isEditingProvider && $editingProvider['logo_url']): ?>
                            <span class="form-note">Logo actuel : <?= e($editingProvider['logo_url']) ?></span>
                        <?php endif; ?>
                    </label>
                    <div class="form-actions">
                        <button class="btn" type="submit"><?= $isEditingProvider ? 'Mettre à jour' : 'Ajouter' ?></button>
                        <?php if ($isEditingProvider): ?>
                            <a class="link-light small" href="<?= $adminBase ?>/dashboard.php?section=providers">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="list">
                    <?php foreach ($providers as $provider): ?>
                        <article>
                            <strong><?= e($provider['name']) ?></strong>
                            <div class="row-actions">
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=providers&edit=providers&id=<?= (int) $provider['id'] ?>">Modifier</a>
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=providers&delete=providers&id=<?= (int) $provider['id'] ?>">Supprimer</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($currentSection === 'video'): ?>
            <section class="admin-section">
                <h2>Vidéo highlight</h2>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=video">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="add_video">
                    <label>Titre
                        <input type="text" name="title" required>
                    </label>
                    <label>Description
                        <textarea name="description" rows="2"></textarea>
                    </label>
                    <label>URL (YouTube embed ou MP4)
                        <input type="url" name="url" required>
                    </label>
                    <label>Thumbnail URL (optionnel)
                        <input type="url" name="thumbnail_url">
                    </label>
                    <button class="btn" type="submit">Ajouter</button>
                </form>
                <?php if ($video): ?>
                    <p>Dernière vidéo: <strong><?= e($video['title']) ?></strong></p>
                <?php endif; ?>
            </section>
        <?php elseif ($currentSection === 'messages'): ?>
            <section class="admin-section">
                <h2>Messages contact</h2>
                <div class="list">
                    <?php foreach ($messages as $message): ?>
                        <article class="message <?= $message['is_read'] ? 'read' : '' ?>">
                            <header>
                                <strong><?= e($message['full_name']) ?></strong>
                                <span><?= e($message['email']) ?></span>
                                <span><?= e($message['created_at']) ?></span>
                            </header>
                            <p><?= nl2br(e($message['message'])) ?></p>
                            <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=messages">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                                <input type="hidden" name="action" value="mark_message">
                                <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                <?php if (!$message['is_read']): ?>
                                    <button class="btn ghost" type="submit">Marquer lu</button>
                                <?php endif; ?>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($currentSection === 'analytics'): ?>
            <section class="admin-section">
                <h2>Analytics visiteurs</h2>
                <p><strong><?= e(number_format($visitStats['total'])) ?></strong> visites totales.</p>
                <ul class="list">
                    <?php foreach ($visitStats['countries'] as $country): ?>
                        <li><?= e($country['country'] ?: 'Inconnu') ?> · <?= e($country['total']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('[data-auto-dismiss]');
    if (alerts.length) {
        setTimeout(() => {
            alerts.forEach((alert) => {
                alert.classList.add('fade-out');
                setTimeout(() => alert.remove(), 500);
            });
        }, 3500);
    }

    const body = document.body;
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
    const sidebar = document.getElementById('adminSidebar');
    const isDesktop = () => window.matchMedia('(min-width: 961px)').matches;

    const updateAria = (isOpen) => {
        if (toggle) {
            toggle.setAttribute('aria-expanded', String(isOpen));
        }
        if (sidebar) {
            const shouldHide = !isOpen && !isDesktop();
            sidebar.setAttribute('aria-hidden', shouldHide ? 'true' : 'false');
        }
    };

    const closeSidebar = () => {
        body.classList.remove('sidebar-open');
        updateAria(false);
    };

    const openSidebar = () => {
        body.classList.add('sidebar-open');
        updateAria(true);
    };

    updateAria(isDesktop());

    toggle?.addEventListener('click', () => {
        if (body.classList.contains('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    overlay?.addEventListener('click', closeSidebar);

    sidebarLinks.forEach((link) => {
        link.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 960px)').matches) {
                closeSidebar();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && body.classList.contains('sidebar-open')) {
            closeSidebar();
        }
    });

    window.addEventListener('resize', () => {
        if (isDesktop()) {
            body.classList.remove('sidebar-open');
            updateAria(true);
        } else {
            updateAria(body.classList.contains('sidebar-open'));
        }
    });
});
</script>
</body>
</html>
