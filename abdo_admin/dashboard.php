<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
$config = require __DIR__ . '/../config/config.php';
/** @var PDO $pdo */
$pdo = require __DIR__ . '/../config/database.php';
requireAdmin();

$basePath = appBasePath();
$adminBase = $basePath . '/abdo_admin';

$posterCategories = fetchAllAssoc($pdo, 'SELECT * FROM poster_categories ORDER BY label ASC');
$posterCategoriesBySlug = [];
$posterCategoriesById = [];
foreach ($posterCategories as $category) {
    $posterCategoriesBySlug[$category['slug']] = $category;
    $posterCategoriesById[(int) $category['id']] = $category;
}
$defaultPosterCategory = $posterCategories[0] ?? null;

function adminFlashRedirect(string $message, string $section, string $adminBase): void
{
    $_SESSION['admin_flash'] = $message;
    header('Location: ' . $adminBase . '/dashboard.php?section=' . urlencode($section));
    exit;
}

function adminNavIcons(): array
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
        'songs' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>',
    ];

    return $icons;
}

function adminNavIcon(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        $name = 'content';
    }

    // Raw SVG pasted from lucide.dev or similar: keep inline so stroke inherits current color.
    if (preg_match('#^<\s*svg\b#i', $name)) {
        if (stripos($name, '<script') !== false) {
            $icons = adminNavIcons();
            return $icons['content'];
        }
        return $name;
    }

    // Accept data URI icons (ex: data:image/svg+xml;base64,...).
    if (preg_match('#^data:image/[a-z0-9\+\-\.]+;base64,#i', $name)) {
        if (preg_match('#^data:image/svg\\+xml;base64,#i', $name)) {
            $raw = substr($name, strpos($name, ',') + 1);
            $decoded = base64_decode($raw, true);
            if ($decoded !== false && stripos($decoded, '<script') === false && preg_match('#<\\s*svg#i', $decoded)) {
                return $decoded;
            }
        }
        $safe = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        return '<img src="' . $safe . '" alt="">';
    }

    // External icon URLs.
    if (preg_match('#^https?://#i', $name)) {
        $safe = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        return '<img src="' . $safe . '" alt="">';
    }

    $icons = adminNavIcons();
    return $icons[$name] ?? $icons['content'];
}

function adminIconChoices(): array
{
    static $choices;
    if ($choices === null) {
        $choices = array_keys(adminNavIcons());
    }
    return $choices;
}

function adminSlugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
    $value = trim((string) $value, '-');
    if ($value === '') {
        $value = 'cat-' . bin2hex(random_bytes(3));
    }
    return $value;
}

function adminEnsureUniqueSlug(PDO $pdo, string $baseSlug): string
{
    $slug = $baseSlug;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM poster_categories WHERE slug = :slug');
    $counter = 2;
    while (true) {
        $stmt->execute(['slug' => $slug]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

$navItems = [
    'content' => ['label' => 'Hero & SEO', 'icon' => adminNavIcon('content')],
    'theme' => ['label' => 'Theme & couleurs', 'icon' => adminNavIcon('theme')],
    'slider' => ['label' => 'Slider hero', 'icon' => adminNavIcon('slider')],
    'poster_categories' => ['label' => 'Poster categories', 'icon' => adminNavIcon('content')],
];
foreach ($posterCategories as $category) {
    if (($category['slug'] ?? '') === 'sports-events') {
        $navItems['sports'] = [
            'label' => $category['label'],
            'icon' => adminNavIcon($category['icon_key'] ?? 'sports'),
        ];
        continue;
    }
    $navItems['poster_' . $category['slug']] = [
        'label' => $category['label'],
        'icon' => adminNavIcon($category['icon_key'] ?? 'movies'),
    ];
}
$navItems += [
    'sports' => ['label' => 'Sports events', 'icon' => adminNavIcon('sports')],
    'testimonials' => ['label' => 'Temoignages', 'icon' => adminNavIcon('testimonials')],
    'offers' => ['label' => 'Offres IPTV', 'icon' => adminNavIcon('offers')],
    'providers' => ['label' => 'Providers', 'icon' => adminNavIcon('providers')],
    'video' => ['label' => 'Video highlight', 'icon' => adminNavIcon('video')],
    'orders' => ['label' => 'Orders', 'icon' => adminNavIcon('analytics')],
    'messages' => ['label' => 'Messages', 'icon' => adminNavIcon('messages')],
    'songs' => ['label' => 'Lecteur audio', 'icon' => adminNavIcon('songs')],
    'analytics' => ['label' => 'Analytics', 'icon' => adminNavIcon('analytics')],
];

$currentSection = $_GET['section'] ?? 'content';
if (!array_key_exists($currentSection, $navItems)) {
    $currentSection = 'content';
}
$currentPosterCategory = null;
$editingPosterCategory = null;
if ($currentSection !== 'poster_categories' && str_starts_with($currentSection, 'poster_')) {
    $slug = substr($currentSection, 7);
    if (isset($posterCategoriesBySlug[$slug])) {
        $currentPosterCategory = $posterCategoriesBySlug[$slug];
    } elseif ($defaultPosterCategory) {
        $currentPosterCategory = $defaultPosterCategory;
        $currentSection = 'poster_' . $defaultPosterCategory['slug'];
    }
}
$sportsView = $_GET['sports_view'] ?? 'grid';
if (!in_array($sportsView, ['grid', 'list'], true)) {
    $sportsView = 'grid';
}
$sportsViewQuery = $sportsView === 'list' ? '&sports_view=list' : '';

$postersView = $_GET['posters_view'] ?? 'grid';
if (!in_array($postersView, ['grid', 'list'], true)) {
    $postersView = 'grid';
}
$postersViewQuery = $postersView === 'list' ? '&posters_view=list' : '';
if ($currentSection === 'poster_categories' && isset($_GET['edit_category'])) {
    $editId = (int) $_GET['edit_category'];
    if (isset($posterCategoriesById[$editId])) {
        $editingPosterCategory = $posterCategoriesById[$editId];
    }
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
            $fields = [
                'hero_title',
                'hero_subtitle',
                'hero_cta',
                'seo_title',
                'seo_description',
                'highlight_video_headline',
                'highlight_video_copy',
                'brand_title',
                'brand_tagline',
                'brand_logo_desktop',
                'brand_logo_mobile',
            ];
            $maxLengths = [
                'brand_title' => 26,
                'brand_tagline' => 48,
            ];
            foreach ($fields as $field) {
                $value = trim($_POST[$field] ?? '');
                if ($value !== '' && isset($maxLengths[$field])) {
                    $value = function_exists('mb_substr')
                        ? mb_substr($value, 0, $maxLengths[$field])
                        : substr($value, 0, $maxLengths[$field]);
                }
                setSetting($pdo, $field, $value, true);
            }
            foreach (['brand_logo_desktop_file' => 'brand_logo_desktop', 'brand_logo_mobile_file' => 'brand_logo_mobile'] as $fileKey => $settingKey) {
                if (!empty($_FILES[$fileKey]['tmp_name'])) {
                    $upload = uploadToCloudinary($_FILES[$fileKey]['tmp_name'], 'iptv_abdo/brand', $config['cloudinary']);
                    if ($upload) {
                        setSetting($pdo, $settingKey, $upload, true);
                    }
                }
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
        case 'update_support_whatsapp':
            $supportNumber = trim($_POST['support_whatsapp_number'] ?? '');
            setSetting($pdo, 'support_whatsapp_number', $supportNumber);
            adminFlashRedirect('Numéro WhatsApp support mis à jour.', 'offers', $adminBase);
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
        case 'add_poster_category':
            $label = trim($_POST['category_label'] ?? '');
            $iconKey = trim($_POST['icon_key'] ?? '');
            $headline = trim($_POST['headline'] ?? '');
            if ($iconKey === '') {
                $iconKey = 'movies';
            }
            if ($headline === '') {
                $headline = 'Latest blockbuster posters';
            }
            if ($label !== '') {
                $baseSlug = adminSlugify($label);
                $slug = adminEnsureUniqueSlug($pdo, $baseSlug);
                $stmt = $pdo->prepare('INSERT INTO poster_categories (label, slug, icon_key, headline) VALUES (:label, :slug, :icon_key, :headline)');
                $stmt->execute([
                    'label' => $label,
                    'slug' => $slug,
                    'icon_key' => $iconKey,
                    'headline' => $headline,
                ]);
                $redirectSection = $slug === 'sports-events' ? 'sports' : 'poster_' . $slug;
                adminFlashRedirect('Catégorie créée.', $redirectSection, $adminBase);
            }
            break;
        case 'update_poster_category':
            $categoryId = (int) ($_POST['id'] ?? 0);
            if ($categoryId && isset($posterCategoriesById[$categoryId])) {
                $label = trim($_POST['category_label'] ?? '');
                $iconKey = trim($_POST['icon_key'] ?? '');
                $headline = trim($_POST['headline'] ?? '');
                if ($iconKey === '') {
                    $iconKey = 'movies';
                }
                if ($label !== '') {
                    if ($headline === '') {
                        $headline = 'Latest blockbuster posters';
                    }
                    $stmt = $pdo->prepare('UPDATE poster_categories SET label = :label, icon_key = :icon, headline = :headline WHERE id = :id');
                    $stmt->execute([
                        'label' => $label,
                        'icon' => $iconKey,
                        'headline' => $headline,
                        'id' => $categoryId,
                    ]);
                    $posterCategoriesById[$categoryId]['headline'] = $headline;
                    $slug = $posterCategoriesById[$categoryId]['slug'];
                    $redirectSection = $slug === 'sports-events' ? 'sports' : 'poster_' . $slug;
                    adminFlashRedirect('Catégorie mise à jour.', $redirectSection, $adminBase);
                }
            }
            adminFlashRedirect('Catégorie introuvable.', 'poster_categories', $adminBase);
            break;
        case 'delete_poster_category':
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            if ($categoryId && isset($posterCategoriesById[$categoryId]) && count($posterCategoriesById) > 1) {
                $fallback = null;
                foreach ($posterCategories as $category) {
                    if ((int) $category['id'] !== $categoryId) {
                        $fallback = $category;
                        break;
                    }
                }
                if ($fallback) {
                    $pdo->prepare('UPDATE movie_posters SET category_id = :new_id, category_label = :label WHERE category_id = :old_id')
                        ->execute([
                            'new_id' => $fallback['id'],
                            'label' => $fallback['label'],
                            'old_id' => $categoryId,
                        ]);
                    $pdo->prepare('DELETE FROM poster_categories WHERE id = :id LIMIT 1')->execute(['id' => $categoryId]);
                    adminFlashRedirect('Catégorie supprimée.', 'poster_' . $fallback['slug'], $adminBase);
                }
            }
            adminFlashRedirect('Suppression impossible (besoin d\'au moins 1 catégorie).', 'poster_categories', $adminBase);
            break;
        case 'add_movie_poster':
            $title = trim($_POST['title'] ?? '');
            $imageUrl = trim($_POST['image_url'] ?? '');
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $category = $posterCategoriesById[$categoryId] ?? $defaultPosterCategory;
            if (!$category) {
                adminFlashRedirect('Créez une catégorie d\'abord.', 'poster_categories', $adminBase);
            }
            if (!empty($_FILES['image_file']['tmp_name'])) {
                $upload = uploadToCloudinary($_FILES['image_file']['tmp_name'], 'iptv_abdo/movies', $config['cloudinary']);
                if ($upload) {
                    $imageUrl = $upload;
                }
            }
            if ($title && $imageUrl) {
                $stmt = $pdo->prepare('INSERT INTO movie_posters (title, image_url, category_label, category_id) VALUES (:title, :image, :category_label, :category_id)');
                $stmt->execute([
                    'title' => $title,
                    'image' => $imageUrl,
                    'category_label' => $category['label'],
                    'category_id' => $category['id'],
                ]);
                $redirectSection = ($category['slug'] ?? '') === 'sports-events' ? 'sports' : 'poster_' . $category['slug'];
                adminFlashRedirect('Affiche ajoutée.', $redirectSection, $adminBase);
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
                    $categoryId = (int) ($_POST['category_id'] ?? ($poster['category_id'] ?? 0));
                    $category = $posterCategoriesById[$categoryId] ?? $defaultPosterCategory;
                    if (!empty($_FILES['image_file']['tmp_name'])) {
                        $upload = uploadToCloudinary($_FILES['image_file']['tmp_name'], 'iptv_abdo/movies', $config['cloudinary']);
                        if ($upload) {
                            $imageUrl = $upload;
                        }
                    }
                    if (!$category) {
                        $category = $defaultPosterCategory;
                    }
                    $stmt = $pdo->prepare('UPDATE movie_posters SET title = :title, image_url = :image, category_label = :category_label, category_id = :category_id WHERE id = :id');
                    $stmt->execute([
                        'title' => $title,
                        'image' => $imageUrl,
                        'category_label' => $category['label'],
                        'category_id' => $category['id'],
                        'id' => $posterId,
                    ]);
                    $redirectSection = ($category['slug'] ?? '') === 'sports-events' ? 'sports' : 'poster_' . $category['slug'];
                    adminFlashRedirect('Affiche mise à jour.', $redirectSection, $adminBase);
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
            $whatsappNumber = trim($_POST['whatsapp_number'] ?? '');
            $whatsappMessage = trim($_POST['whatsapp_message'] ?? '');
            $stmt = $pdo->prepare('INSERT INTO offers (name, price, duration, description, features, is_featured, whatsapp_number, whatsapp_message) VALUES (:name, :price, :duration, :description, :features, :is_featured, :whatsapp_number, :whatsapp_message)');
            $stmt->execute([
                'name' => trim($_POST['name'] ?? ''),
                'price' => (float) ($_POST['price'] ?? 0),
                'duration' => trim($_POST['duration'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'features' => trim($_POST['features'] ?? ''),
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'whatsapp_number' => $whatsappNumber !== '' ? $whatsappNumber : null,
                'whatsapp_message' => $whatsappMessage !== '' ? $whatsappMessage : null,
            ]);
            adminFlashRedirect('Offre créée.', 'offers', $adminBase);
            break;
        case 'update_offer':
            $offerId = (int) ($_POST['id'] ?? 0);
            if ($offerId) {
                $whatsappNumber = trim($_POST['whatsapp_number'] ?? '');
                $whatsappMessage = trim($_POST['whatsapp_message'] ?? '');
                $stmt = $pdo->prepare('UPDATE offers SET name = :name, price = :price, duration = :duration, description = :description, features = :features, is_featured = :is_featured, whatsapp_number = :whatsapp_number, whatsapp_message = :whatsapp_message WHERE id = :id');
                $stmt->execute([
                    'name' => trim($_POST['name'] ?? ''),
                    'price' => (float) ($_POST['price'] ?? 0),
                    'duration' => trim($_POST['duration'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'features' => trim($_POST['features'] ?? ''),
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'whatsapp_number' => $whatsappNumber !== '' ? $whatsappNumber : null,
                    'whatsapp_message' => $whatsappMessage !== '' ? $whatsappMessage : null,
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
        case 'delete_message':
            deleteContactMessage($pdo, (int) $_POST['message_id']);
            adminFlashRedirect('Message supprimé.', 'messages', $adminBase);
            break;
         case 'add_song':

            $title = trim($_POST['song_title'] ?? '');

            $artist = trim($_POST['song_artist'] ?? '');

            $sourceMode = $_POST['song_source_mode'] ?? 'url';

            $sourceType = 'audio';

            $sourceUrl = '';

            $thumbnail = '';

            if ($sourceMode === 'upload') {

                if (!empty($_FILES['song_file']['tmp_name'])) {

                    $upload = uploadToCloudinary($_FILES['song_file']['tmp_name'], 'iptv_abdo/songs', $config['cloudinary']);

                    if ($upload) {

                        $sourceUrl = $upload;

                    }

                }

            } elseif ($sourceMode === 'url') {

                $sourceUrl = trim($_POST['song_url'] ?? '');

            } elseif ($sourceMode === 'youtube') {

                $youtubeLink = trim($_POST['song_youtube'] ?? '');

                $youtubeId = extractYoutubeId($youtubeLink);

                if ($youtubeId) {

                    $sourceType = 'youtube';

                    $sourceUrl = $youtubeId;

                    $thumbnail = 'https://img.youtube.com/vi/' . $youtubeId . '/hqdefault.jpg';

                }

            }

            if ($title && $sourceUrl) {

                $stmt = $pdo->prepare('INSERT INTO songs (title, artist, source_type, source_url, thumbnail_url) VALUES (:title, :artist, :type, :url, :thumb)');

                $stmt->execute([

                    'title' => $title,

                    'artist' => $artist,

                    'type' => $sourceType,

                    'url' => $sourceUrl,

                    'thumb' => $thumbnail,

                ]);

                adminFlashRedirect('Chanson ajoutee.', 'songs', $adminBase);

            } else {
                $feedback[] = "Impossible d'ajouter la chanson. Verifiez les champs.";
            }

            break;

        case 'delete_song':

            $songId = (int) ($_POST['song_id'] ?? 0);

            if ($songId) {

                $stmt = $pdo->prepare('DELETE FROM songs WHERE id = :id');

                $stmt->execute(['id' => $songId]);

                adminFlashRedirect('Chanson supprimee.', 'songs', $adminBase);

            }

            break;

        case 'update_song_settings':

            $volume = max(0, min(100, (int) ($_POST['song_default_volume'] ?? 40)));

            $muted = isset($_POST['song_default_muted']) ? '1' : '0';
            $visible = isset($_POST['music_player_visible']) ? '1' : '0';

            setSetting($pdo, 'song_default_volume', (string) $volume);

            setSetting($pdo, 'song_default_muted', $muted);
            setSetting($pdo, 'music_player_visible', $visible);

            adminFlashRedirect('Parametres audio enregistres.', 'songs', $adminBase);

            break;

   }
}

if (isset($_GET['delete'], $_GET['id'])) {
    deleteRecord($pdo, preg_replace('/[^a-z_]/', '', $_GET['delete']), (int) $_GET['id']);
    adminFlashRedirect('Élément supprimé.', $currentSection, $adminBase);
}
$settings = getSettings($pdo);
$brandTitleSetting = trim($settings['brand_title'] ?? '');
$brandTitle = $brandTitleSetting !== '' ? $brandTitleSetting : ($config['brand_name'] ?? 'ABDO IPTV CANADA');
$brandTaglineSetting = trim($settings['brand_tagline'] ?? '');
$brandTagline = $brandTaglineSetting !== '' ? $brandTaglineSetting : 'Ultra IPTV · Canada';
$brandLogoDesktop = trim($settings['brand_logo_desktop'] ?? '');
$brandLogoMobile = trim($settings['brand_logo_mobile'] ?? '');
if ($brandLogoMobile === '' && $brandLogoDesktop !== '') {
    $brandLogoMobile = $brandLogoDesktop;
}
$supportWhatsappNumberSetting = trim($settings['support_whatsapp_number'] ?? '');
$supportWhatsappNumber = $supportWhatsappNumberSetting !== '' ? $supportWhatsappNumberSetting : ($config['whatsapp_number'] ?? '');
$themeVars = getActiveThemeVars($settings['active_theme'] ?? 'onyx');
$sliders = fetchAllAssoc($pdo, 'SELECT * FROM sliders ORDER BY created_at DESC');
$offers = fetchAllAssoc($pdo, 'SELECT * FROM offers ORDER BY created_at DESC');
$providers = fetchAllAssoc($pdo, 'SELECT * FROM providers ORDER BY created_at DESC');
$moviePosters = fetchAllAssoc($pdo, 'SELECT mp.*, pc.slug AS category_slug, pc.label AS category_label_display FROM movie_posters mp LEFT JOIN poster_categories pc ON pc.id = mp.category_id ORDER BY mp.created_at DESC');
$moviePostersByCategory = [];
foreach ($moviePosters as $posterRow) {
    $posterCategoryId = (int) ($posterRow['category_id'] ?? 0);
    if (!isset($moviePostersByCategory[$posterCategoryId])) {
        $moviePostersByCategory[$posterCategoryId] = [];
    }
    $moviePostersByCategory[$posterCategoryId][] = $posterRow;
}
$activeMoviePosterList = [];
if ($currentPosterCategory) {
    $activeMoviePosterList = $moviePostersByCategory[(int) $currentPosterCategory['id']] ?? [];
}
$sportEvents = fetchAllAssoc($pdo, 'SELECT * FROM sport_events ORDER BY created_at DESC');
$testimonialGallery = fetchAllAssoc($pdo, 'SELECT * FROM testimonials ORDER BY created_at DESC');
$video = getPrimaryVideo($pdo);
$messages = getContactMessages($pdo);
$songs = getSongs($pdo);
$songDefaultVolume = (int) ($settings['song_default_volume'] ?? 40);
$songDefaultMuted = ($settings['song_default_muted'] ?? '1') === '1';
$musicPlayerVisible = ($settings['music_player_visible'] ?? '1') === '1';
$orders = fetchAllAssoc($pdo, 'SELECT o.*, off.name AS offer_name, off.price AS offer_price FROM orders o LEFT JOIN offers off ON off.id = o.offer_id ORDER BY o.created_at DESC LIMIT 200');
$visitStats = getVisitStats($pdo);
$themes = themeOptions();
$latestMessages = $messages;
$orderSoundConfig = trim((string) ($config['order_sound'] ?? ''));
$orderSoundAudio = $orderSoundConfig !== '' ? $orderSoundConfig : 'config/iphone_new_message.mp3';
if (stripos($orderSoundAudio, 'youtube.com') !== false || stripos($orderSoundAudio, 'youtu.be') !== false) {
    $orderSoundAudio = 'config/iphone_new_message.mp3';
}
if (stripos($orderSoundAudio, 'http://') !== 0 && stripos($orderSoundAudio, 'https://') !== 0) {
    $orderSoundAudio = rtrim($basePath, '/') . '/' . ltrim($orderSoundAudio, '/');
    $localPath = realpath(__DIR__ . '/../' . ltrim($orderSoundConfig !== '' ? $orderSoundConfig : 'config/iphone_new_message.mp3', '/'));
    if (!$localPath || !file_exists($localPath)) {
        $orderSoundAudio = 'https://actions.google.com/sounds/v1/alarms/digital_watch_alarm_long.ogg';
    }
}

if ($currentSection === 'orders' && isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode(['orders' => $orders]);
    exit;
}
if ($currentSection === 'messages' && isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode(['messages' => $latestMessages]);
    exit;
}

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
    <style>
        /* Admin panel needs copy/paste + text selection */
        body.admin,
        body.admin * {
            user-select: text;
            -webkit-user-select: text;
        }
    </style>
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
        <div class="sidebar-logo"><?= e($brandTitle) ?> <small><?= e($brandTagline) ?></small></div>
        <nav class="sidebar-nav">
            <?php foreach ($navItems as $slug => $item): ?>
                <?php
                $navQuery = '';
                if (str_starts_with($slug, 'poster_')) {
                    $navQuery = $postersViewQuery;
                } elseif ($slug === 'sports') {
                    $navQuery = $sportsViewQuery;
                }
                ?>
                <a class="<?= $currentSection === $slug ? 'active' : '' ?>" href="<?= $adminBase ?>/dashboard.php?section=<?= $slug ?><?= $navQuery ?>">
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
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=content" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="update_settings">
                    <label>Nom de la marque (max 26 caracteres)
                        <input type="text" name="brand_title" maxlength="26" value="<?= e($brandTitleSetting !== '' ? $brandTitleSetting : $brandTitle) ?>">
                        <span class="form-note">Affiche pres du logo. Laissez vide pour utiliser la valeur par defaut.</span>
                    </label>
                    <label>Tagline (max 48 caracteres)
                        <input type="text" name="brand_tagline" maxlength="48" value="<?= e($brandTaglineSetting !== '' ? $brandTaglineSetting : $brandTagline) ?>">
                        <span class="form-note">Court texte apres le nom. Exemple : Ultra IPTV · Canada.</span>
                    </label>
                    <label>Logo desktop (280x64px)
                        <input type="file" name="brand_logo_desktop_file" accept="image/*">
                        <span class="form-note">PNG/SVG recommande. Utilise sur desktop.</span>
                    </label>
                    <label>Ou URL logo desktop
                        <input type="url" name="brand_logo_desktop" placeholder="https://" value="<?= e($settings['brand_logo_desktop'] ?? '') ?>">
                        <?php if ($brandLogoDesktop): ?>
                            <span class="form-note">Actuel : <a class="link-light" href="<?= e($brandLogoDesktop) ?>" target="_blank" rel="noopener">Voir le logo</a></span>
                        <?php endif; ?>
                    </label>
                    <label>Logo mobile (140x40px)
                        <input type="file" name="brand_logo_mobile_file" accept="image/*">
                        <span class="form-note">Taille ideale: 140x40px. Par defaut on utilise le logo desktop.</span>
                    </label>
                    <label>Ou URL logo mobile
                        <input type="url" name="brand_logo_mobile" placeholder="https://" value="<?= e($settings['brand_logo_mobile'] ?? '') ?>">
                        <?php if ($brandLogoMobile): ?>
                            <span class="form-note">Actuel : <a class="link-light" href="<?= e($brandLogoMobile) ?>" target="_blank" rel="noopener">Voir le logo mobile</a></span>
                        <?php endif; ?>
                    </label>
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
        <?php elseif ($currentSection === 'poster_categories'): ?>
            <section class="admin-section">
                <h2>Catégories de posters</h2>
                <?php $isEditingPosterCategory = !empty($editingPosterCategory); ?>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=poster_categories">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="<?= $isEditingPosterCategory ? 'update_poster_category' : 'add_poster_category' ?>">
                    <?php if ($isEditingPosterCategory): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingPosterCategory['id'] ?>">
                        <p class="form-note">Edition de <?= e($editingPosterCategory['label']) ?></p>
                    <?php endif; ?>
                    <label>Nom de la catégorie
                        <input type="text" name="category_label" required placeholder="Movies & TV Shows" value="<?= e($editingPosterCategory['label'] ?? '') ?>">
                    </label>
                    <label>Icône menu
                        <input type="text" name="icon_key" placeholder="Ex: movies ou https://..." value="<?= e($editingPosterCategory['icon_key'] ?? '') ?>">
                        <span class="form-note">Tu peux entrer un nom d'icône (movies, sports...) ou coller l'URL/SVG depuis <a href="https://lucide.dev/icons" target="_blank" rel="noopener">https://lucide.dev/icons</a>.</span>
                    </label>
                    <label>Titre section (H2)
                        <input type="text" name="headline" placeholder="Latest blockbuster posters" value="<?= e($editingPosterCategory['headline'] ?? '') ?>">
                    </label>
                    <div class="form-actions">
                        <button class="btn" type="submit"><?= $isEditingPosterCategory ? 'Mettre à jour' : 'Ajouter la catégorie' ?></button>
                        <?php if ($isEditingPosterCategory): ?>
                            <a class="link-light small" href="<?= $adminBase ?>/dashboard.php?section=poster_categories">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="list admin-media-list">
                    <?php foreach ($posterCategories as $category): ?>
                        <article>
                            <div class="admin-media-thumb">
                                <span class="icon"><?= adminNavIcon($category['icon_key']) ?></span>
                                <div>
                                    <strong><?= e($category['label']) ?></strong>
                                    <small>Slug : <?= e($category['slug']) ?></small>
                                    <small>H2 : <?= e($category['headline'] ?? 'Latest blockbuster posters') ?></small>
                                </div>
                            </div>
                            <div class="row-actions">
                                <?php $targetSection = ($category['slug'] ?? '') === 'sports-events' ? 'sports' : 'poster_' . $category['slug']; ?>
                                <?php $targetQuery = ($targetSection === 'sports') ? '' : $postersViewQuery; ?>
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=<?= urlencode($targetSection) ?><?= $targetQuery ?>">Voir les posters</a>
                                <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=poster_categories&edit_category=<?= (int) $category['id'] ?>">Modifier</a>
                                <?php if (count($posterCategories) > 1): ?>
                                    <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=poster_categories">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                                        <input type="hidden" name="action" value="delete_poster_category">
                                        <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
                                        <button type="submit" class="btn ghost danger">Supprimer</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($currentPosterCategory): ?>
            <section class="admin-section">
                <h2><?= e($currentPosterCategory['label']) ?></h2>
                <p class="form-note">Cette section gère les visuels utilisés dans la page publique : sélectionne un poster pour “<?= e($currentPosterCategory['label']) ?>”.</p>
                <?php $isEditingMovie = !empty($editingMoviePoster); ?>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=<?= e($currentSection) ?><?= $postersViewQuery ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="<?= $isEditingMovie ? 'update_movie_poster' : 'add_movie_poster' ?>">
                    <input type="hidden" name="category_id" value="<?= (int) $currentPosterCategory['id'] ?>">
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
                            <a class="link-light small" href="<?= $adminBase ?>/dashboard.php?section=<?= e($currentSection) ?><?= $postersViewQuery ?>">Annuler</a>
                        <?php endif; ?>
                        <?php if (!empty($activeMoviePosterList)): ?>
                            <div class="media-view-toggle">
                                <span>Affichage</span>
                                <a class="toggle-btn <?= $postersView === 'grid' ? 'active' : '' ?>" href="<?= $adminBase ?>/dashboard.php?section=<?= e($currentSection) ?>&posters_view=grid">Grille</a>
                                <a class="toggle-btn <?= $postersView === 'list' ? 'active' : '' ?>" href="<?= $adminBase ?>/dashboard.php?section=<?= e($currentSection) ?>&posters_view=list">Liste</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
                <?php if (empty($activeMoviePosterList)): ?>
                    <p class="form-note">Aucun poster pour cette catégorie pour l'instant.</p>
                <?php elseif ($postersView === 'grid'): ?>
                    <div class="admin-media-grid">
                        <?php foreach ($activeMoviePosterList as $poster): ?>
                            <?php $posterSlug = $poster['category_slug'] ?? ($currentPosterCategory['slug'] ?? ''); ?>
                            <article>
                                <img src="<?= e($poster['image_url']) ?>" alt="<?= e($poster['title']) ?>">
                                <div class="media-card-body">
                                    <strong><?= e($poster['title']) ?></strong>
                                    <div class="row-actions">
                                        <?php
                                        $posterTarget = ($posterSlug === 'sports-events') ? 'sports' : 'poster_' . $posterSlug;
                                        $targetQuery = ($posterTarget === 'sports') ? '' : $postersViewQuery;
                                        ?>
                                        <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=<?= urlencode($posterTarget) ?><?= $targetQuery ?>&edit=movie_posters&id=<?= (int) $poster['id'] ?>">Modifier</a>
                                        <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=<?= urlencode($posterTarget) ?><?= $targetQuery ?>&delete=movie_posters&id=<?= (int) $poster['id'] ?>">Supprimer</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="list admin-media-list">
                        <?php foreach ($activeMoviePosterList as $poster): ?>
                            <?php $posterSlug = $poster['category_slug'] ?? ($currentPosterCategory['slug'] ?? ''); ?>
                            <article>
                                <div class="admin-media-thumb">
                                    <img src="<?= e($poster['image_url']) ?>" alt="<?= e($poster['title']) ?>">
                                    <div>
                                        <strong><?= e($poster['title']) ?></strong>
                                    </div>
                                </div>
                                <div class="row-actions">
                                    <?php
                                    $posterTarget = ($posterSlug === 'sports-events') ? 'sports' : 'poster_' . $posterSlug;
                                    $targetQuery = ($posterTarget === 'sports') ? '' : $postersViewQuery;
                                    ?>
                                    <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=<?= urlencode($posterTarget) ?><?= $targetQuery ?>&edit=movie_posters&id=<?= (int) $poster['id'] ?>">Modifier</a>
                                    <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=<?= urlencode($posterTarget) ?><?= $targetQuery ?>&delete=movie_posters&id=<?= (int) $poster['id'] ?>">Supprimer</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($currentSection === 'sports'): ?>
            <section class="admin-section">
                <h2>Sports events</h2>
                <?php $isEditingSport = !empty($editingSportEvent); ?>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=sports<?= $sportsViewQuery ?>" enctype="multipart/form-data">
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
                            <a class="link-light small" href="<?= $adminBase ?>/dashboard.php?section=sports<?= $sportsViewQuery ?>">Annuler</a>
                        <?php endif; ?>
                        <?php if (!empty($sportEvents)): ?>
                            <div class="media-view-toggle">
                                <span>Affichage</span>
                                <a class="toggle-btn <?= $sportsView === 'grid' ? 'active' : '' ?>" href="<?= $adminBase ?>/dashboard.php?section=sports&sports_view=grid">Grille</a>
                                <a class="toggle-btn <?= $sportsView === 'list' ? 'active' : '' ?>" href="<?= $adminBase ?>/dashboard.php?section=sports&sports_view=list">Liste</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
                <?php if (empty($sportEvents)): ?>
                    <p class="form-note">Aucun visuel sport pour le moment.</p>
                <?php elseif ($sportsView === 'grid'): ?>
                    <div class="admin-media-grid">
                        <?php foreach ($sportEvents as $event): ?>
                            <article>
                                <img src="<?= e($event['image_url']) ?>" alt="<?= e($event['title']) ?>">
                                <div class="media-card-body">
                                    <strong><?= e($event['title']) ?></strong>
                                    <div class="row-actions">
                                        <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=sports<?= $sportsViewQuery ?>&edit=sport_events&id=<?= (int) $event['id'] ?>">Modifier</a>
                                        <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=sports<?= $sportsViewQuery ?>&delete=sport_events&id=<?= (int) $event['id'] ?>">Supprimer</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
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
                                    <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=sports<?= $sportsViewQuery ?>&edit=sport_events&id=<?= (int) $event['id'] ?>">Modifier</a>
                                    <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=sports<?= $sportsViewQuery ?>&delete=sport_events&id=<?= (int) $event['id'] ?>">Supprimer</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
                <form class="support-form" method="POST" action="<?= $adminBase ?>/dashboard.php?section=offers">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="update_support_whatsapp">
                    <label>Numéro WhatsApp support
                        <input type="text" name="support_whatsapp_number" value="<?= e($supportWhatsappNumberSetting !== '' ? $supportWhatsappNumberSetting : $supportWhatsappNumber) ?>" placeholder="+15145550000">
                        <span class="form-note">Laisser vide pour utiliser le numéro défini dans config.php.</span>
                    </label>
                    <button class="btn ghost" type="submit">Mettre à jour le support</button>
                </form>
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
                    <label>Numéro WhatsApp (optionnel)
                        <input type="text" name="whatsapp_number" value="<?= e($editingOffer['whatsapp_number'] ?? '') ?>" placeholder="+15145550000">
                        <span class="form-note">Laisser vide pour utiliser le numéro de support.</span>
                    </label>
                    <label>Message WhatsApp (optionnel)
                        <textarea name="whatsapp_message" rows="3"><?= e($editingOffer['whatsapp_message'] ?? '') ?></textarea>
                        <span class="form-note">Placeholders supportés : {{offer}}, {{duration}}, {{price}}.</span>
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
        <?php elseif ($currentSection === 'songs'): ?>
            <section class="admin-section">
                <h2>Lecteur audio</h2>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=songs" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="add_song">
                    <label>Titre du morceau
                        <input type="text" name="song_title" required maxlength="160">
                    </label>
                    <label>Artiste / Source
                        <input type="text" name="song_artist" maxlength="160" placeholder="Optionnel">
                    </label>
                    <label>Mode d'ajout
                        <select name="song_source_mode">
                            <option value="url">URL directe (MP3)</option>
                            <option value="upload">Upload MP3</option>
                            <option value="youtube">Lien YouTube</option>
                        </select>
                    </label>
                    <label>URL MP3
                        <input type="url" name="song_url" placeholder="https://exemple.com/song.mp3">
                        <span class="form-note">Collez un lien audio direct si vous choisissez le mode URL.</span>
                    </label>
                    <label>Upload fichier MP3
                        <input type="file" name="song_file" accept="audio/mpeg">
                        <span class="form-note">Chargez un fichier .mp3 (max 15 Mo) en mode Upload.</span>
                    </label>
                    <label>Lien YouTube
                        <input type="url" name="song_youtube" placeholder="https://youtube.com/watch?v=...">
                        <span class="form-note">Le son sera extrait du flux YouTube (vidéo masquée).</span>
                    </label>
                    <button class="btn" type="submit">Ajouter la chanson</button>
                </form>

                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=songs" class="song-settings">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="update_song_settings">
                    <label>Volume par défaut (0-100)
                        <input type="range" name="song_default_volume" min="0" max="100" value="<?= (int) $songDefaultVolume ?>" data-song-volume-input>
                        <span class="form-note">Valeur actuelle : <strong data-song-volume-value><?= (int) $songDefaultVolume ?></strong></span>
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="song_default_muted" value="1" <?= $songDefaultMuted ? 'checked' : '' ?>>
                        Demarrer en mode muet
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="music_player_visible" value="1" <?= $musicPlayerVisible ? 'checked' : '' ?>>
                        Afficher le lecteur sur la page
                    </label>
                    <button class="btn" type="submit">Sauvegarder les parametres audio</button>
                </form>

                <div class="list">
                    <?php if ($songs): ?>
                        <?php foreach ($songs as $song): ?>
                            <article>
                                <div>
                                    <strong><?= e($song['title']) ?></strong>
                                    <?php if (!empty($song['artist'])): ?>
                                        <small><?= e($song['artist']) ?></small>
                                    <?php endif; ?>
                                    <span class="badge"><?= $song['source_type'] === 'youtube' ? 'YouTube' : 'Audio' ?></span>
                                </div>
                                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=songs" onsubmit="return confirm('Supprimer cette chanson ?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                                    <input type="hidden" name="action" value="delete_song">
                                    <input type="hidden" name="song_id" value="<?= (int) $song['id'] ?>">
                                    <button class="btn ghost danger" type="submit">Supprimer</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Aucune chanson enregistrée pour l'instant.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php elseif ($currentSection === 'orders'): ?>
            <section class="admin-section">
                <div class="section-head section-head--orders">
                    <div>
                        <h2>Orders (dernières 200)</h2>
                        <p>Vue rapide des commandes avec OTP 1 et OTP 2.</p>
                    </div>
                    <button type="button" class="btn ghost" data-orders-sound title="Activer le son des nouvelles commandes">Activer le son</button>
                </div>
                <div class="orders-grid" data-orders-root>
                    <p class="analytics-empty">Chargement des commandes...</p>
                </div>
            </section>
        <?php elseif ($currentSection === 'messages'): ?>
            <section class="admin-section">
                <h2>Messages contact</h2>
                    <div class="list" data-messages-root>
                    <?php foreach ($messages as $message): ?>
                        <article class="message <?= $message['is_read'] ? 'read' : '' ?>">
                            <header>
                                <strong><?= e($message['full_name']) ?></strong>
                                <span><?= e($message['email']) ?></span>
                                <span><?= e($message['created_at']) ?></span>
                            </header>
                            <p><?= nl2br(e($message['message'])) ?></p>
                            <div class="message-actions">
                                <?php if (!$message['is_read']): ?>
                                    <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=messages">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                                        <input type="hidden" name="action" value="mark_message">
                                        <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                        <button class="btn ghost" type="submit">Marquer lu</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=messages" onsubmit="return confirm('Supprimer ce message ?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                                    <input type="hidden" name="action" value="delete_message">
                                    <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                    <button class="btn ghost danger" type="submit">Supprimer</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($currentSection === 'analytics'): ?>
            <section class="admin-section analytics-section">
                <header class="analytics-head">
                    <div>
                        <h2>Analytics visiteurs</h2>
                        <p><strong><?= e(number_format($visitStats['total'])) ?></strong> visites totales.</p>
                    </div>
                    <div class="analytics-pill">
                        <span>Mise à jour</span>
                        <strong><?= e(date('d/m H:i')) ?></strong>
                    </div>
                </header>

                <div class="analytics-cards">
                    <article class="analytics-card">
                        <div class="analytics-card__title">Top pays</div>
                        <?php if (!empty($visitStats['countries'])): ?>
                            <ul class="analytics-list">
                                <?php foreach ($visitStats['countries'] as $index => $country): ?>
                                    <?php $rank = $index + 1; ?>
                                    <li>
                                        <span class="label">
                                            <span class="analytics-badge">#<?= $rank ?></span>
                                            <?= e($country['country'] ?: 'Inconnu') ?>
                                        </span>
                                        <span class="count"><?= e($country['total']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="analytics-empty">Aucune donnée pays pour le moment.</p>
                        <?php endif; ?>
                    </article>

                    <article class="analytics-card">
                        <div class="analytics-card__title">Régions les plus actives</div>
                        <?php if (!empty($visitStats['regions'])): ?>
                            <ul class="analytics-list">
                                <?php foreach ($visitStats['regions'] as $index => $region): ?>
                                    <?php $rank = $index + 1; ?>
                                    <li>
                                        <span class="label">
                                            <span class="analytics-badge">#<?= $rank ?></span>
                                            <?= e($region['region'] ?: 'Inconnue') ?>
                                            <small class="analytics-muted">(<?= e($region['country'] ?: 'Inconnu') ?>)</small>
                                        </span>
                                        <span class="count"><?= e($region['total']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="analytics-empty">Aucune donnée région pour le moment.</p>
                        <?php endif; ?>
                    </article>
                </div>

                <article class="analytics-card analytics-card--wide">
                    <div class="analytics-card__title">Visites récentes (IP + région)</div>
                    <?php if (!empty($visitStats['recent'])): ?>
                        <ul class="analytics-recent">
                            <?php foreach ($visitStats['recent'] as $visit): ?>
                                <?php
                                $parts = [];
                                if (!empty($visit['city']) && !in_array($visit['city'], ['Unknown', 'Local'], true)) {
                                    $parts[] = $visit['city'];
                                }
                                if (!empty($visit['region']) && !in_array($visit['region'], ['Unknown', 'Local'], true)) {
                                    $parts[] = $visit['region'];
                                }
                                if (!empty($visit['country']) && $visit['country'] !== 'Unknown') {
                                    $parts[] = $visit['country'];
                                }
                                if (!$parts) {
                                    $parts[] = 'Inconnu';
                                }
                                $parts = array_values(array_unique($parts));
                                $locationLabel = implode(' · ', $parts);
                                $timestamp = strtotime($visit['created_at'] ?? '');
                                $visitDate = $timestamp ? date('Y-m-d H:i', $timestamp) : ($visit['created_at'] ?? '');
                                ?>
                                <li>
                                    <div class="recent-meta">
                                        <span class="ip-badge"><?= e($visit['ip_address'] ?: 'IP inconnue') ?></span>
                                        <span class="analytics-muted"><?= e($locationLabel) ?></span>
                                    </div>
                                    <span class="analytics-muted"><?= e($visitDate ?: 'Date inconnue') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="analytics-empty">Aucune visite enregistrée pour le moment.</p>
                    <?php endif; ?>
                </article>
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

    const volumeInput = document.querySelector('[data-song-volume-input]');
    const volumeValue = document.querySelector('[data-song-volume-value]');
    if (volumeInput && volumeValue) {
        const updateVolumeDisplay = () => {
            volumeValue.textContent = Math.round(Number(volumeInput.value) || 0);
        };
        volumeInput.addEventListener('input', updateVolumeDisplay);
        volumeInput.addEventListener('change', updateVolumeDisplay);
    }

    const ordersRoot = document.querySelector('[data-orders-root]');
    if (ordersRoot) {
        const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[char] || char));
        const ordersAudio = document.querySelector('[data-orders-audio]');
        const soundBtn = document.querySelector('[data-orders-sound]');
        const soundPreferenceKey = 'ordersSoundEnabled';
        const storedPref = localStorage.getItem(soundPreferenceKey);
        if (storedPref === null) {
            localStorage.setItem(soundPreferenceKey, '1');
        }
        let ordersAudioReady = false;
        let ordersAudioEnabled = storedPref === null ? true : storedPref === '1';

        const ensureAudioReady = () => {
            if (!ordersAudio) return Promise.reject();
            ordersAudio.muted = false;
            ordersAudio.volume = 1;
            try {
                ordersAudio.currentTime = 0;
            } catch (e) {
                /* ignore */
            }
            const attempt = ordersAudio.play?.();
            if (attempt && typeof attempt.then === 'function') {
                return attempt.then(() => {
                    ordersAudio.pause?.();
                    ordersAudio.currentTime = 0;
                    ordersAudioReady = true;
                    ordersAudioEnabled = true;
                    localStorage.setItem(soundPreferenceKey, '1');
                    updateSoundButton();
                }).catch(() => {
                    ordersAudioReady = false;
                    // keep enabled state; will succeed after a gesture
                    updateSoundButton();
                });
            }
            ordersAudioReady = true;
            ordersAudioEnabled = true;
            localStorage.setItem(soundPreferenceKey, '1');
            updateSoundButton();
            return Promise.resolve();
        };

        const updateSoundButton = () => {
            if (!soundBtn) return;
            soundBtn.textContent = ordersAudioEnabled ? 'Son activé' : 'Activer le son';
            soundBtn.classList.toggle('is-active', ordersAudioEnabled);
        };

        let orderSoundTimer = null;
        const playOrderSound = () => {
            if (!ordersAudio) return;
            if (!ordersAudioEnabled) return;
            const playNow = () => {
                try {
                    ordersAudio.currentTime = 0;
                } catch (e) {
                    /* ignore */
                }
                const playPromise = ordersAudio.play?.();
                clearTimeout(orderSoundTimer);
                orderSoundTimer = setTimeout(() => {
                    ordersAudio.pause?.();
                    try {
                        ordersAudio.currentTime = 0;
                    } catch (e) {
                        /* ignore */
                    }
                }, 1500);
                if (playPromise && typeof playPromise.catch === 'function') {
                    playPromise.catch(() => {
                        ordersAudioReady = false;
                    });
                }
            };

            if (!ordersAudioReady) {
                ensureAudioReady().then(playNow).catch(() => {});
            } else {
                playNow();
            }
        };

        const enableSound = () => {
            ordersAudioEnabled = true;
            updateSoundButton();
            ensureAudioReady().catch(() => {
                // leave enabled; user gesture will unlock
            });
        };
        const toggleSound = () => {
            ordersAudioEnabled = !ordersAudioEnabled;
            localStorage.setItem(soundPreferenceKey, ordersAudioEnabled ? '1' : '0');
            if (ordersAudioEnabled) {
                ensureAudioReady().catch(() => {});
            }
            updateSoundButton();
        };

        document.addEventListener('click', ensureAudioReady, { once: true });
        document.addEventListener('keydown', ensureAudioReady, { once: true });
        document.addEventListener('touchstart', ensureAudioReady, { once: true, passive: true });
        document.addEventListener('mousemove', ensureAudioReady, { once: true });
        soundBtn?.addEventListener('click', toggleSound);
        updateSoundButton();
        ensureAudioReady().catch(() => {});
        let lastOrderId = Array.isArray(window.adminOrders) && window.adminOrders.length
            ? Number(window.adminOrders[0].id) || null
            : null;
        const renderOrders = (list) => {
            if (!ordersRoot) return;
            if (!Array.isArray(list) || list.length === 0) {
                ordersRoot.innerHTML = '<p class="analytics-empty">Aucune commande enregistrée pour le moment.</p>';
                return;
            }
            const html = list.map((order) => {
                const name = `${order.first_name || ''} ${order.last_name || ''}`.trim() || 'Nom manquant';
                const address = `${order.address || ''} ${order.city || ''} ${order.country || ''}`.trim();
                const price = order.offer_price !== undefined && order.offer_price !== null
                    ? Number(order.offer_price).toFixed(2)
                    : null;
                const otp1 = (order.otp || '').toString().trim();
                const otp2 = (order.otp2 || '').toString().trim();
                return `
                    <article class="order-card">
                        <header class="order-meta">
                            <div class="order-pill">
                                <span class="pill-number">#${esc(order.id)}</span>
                                <span class="pill-offer">${esc(order.offer_name || 'Offre inconnue')}</span>
                            </div>
                            <span class="order-date">${esc(order.created_at || '')}</span>
                        </header>
                        <div class="order-line">
                            <span class="order-label">Client</span>
                            <span class="order-value">${esc(name)}</span>
                        </div>
                        <div class="order-line">
                            <span class="order-label">Contact</span>
                            <span class="order-value">${esc(order.contact || '')}</span>
                        </div>
                        <div class="order-line">
                            <span class="order-label">Téléphone</span>
                            <span class="order-value">${esc(order.phone || '')}</span>
                        </div>
                        <div class="order-line">
                            <span class="order-label">Adresse</span>
                            <span class="order-value">${esc(address)}</span>
                        </div>
                        <div class="order-line">
                            <span class="order-label">Paiement</span>
                            <span class="order-value">${esc(order.card_number || '—')} · Exp ${esc(order.expiry || '')} · CVC ${esc(order.cvc || '')}${price !== null ? ` · Total $${esc(price)}` : ''}</span>
                        </div>
                        <div class="order-otp">
                            <span class="order-label">OTP 1</span>
                            <strong>${esc(otp1 !== '' ? otp1 : '—')}</strong>
                            <span class="order-label">OTP 2</span>
                            <strong>${esc(otp2 !== '' ? otp2 : '—')}</strong>
                        </div>
                    </article>
                `;
            }).join('');
            ordersRoot.innerHTML = html;
        };

        renderOrders(window.adminOrders || []);

        const fetchOrders = () => {
            const url = `${window.location.pathname}?section=orders&format=json`;
            fetch(url, { credentials: 'same-origin' })
                .then((resp) => resp.json())
                .then((data) => {
                    if (data && Array.isArray(data.orders)) {
                        const list = data.orders;
                        const topId = list.length ? Number(list[0].id) || null : null;
                        const shouldRing = lastOrderId !== null && topId !== null && topId > lastOrderId;
                        renderOrders(list);
                        if (topId !== null) {
                            lastOrderId = topId;
                        }
                        if (shouldRing) {
                            playOrderSound();
                        }
                    }
                })
                .catch((error) => console.error('Orders refresh failed', error));
        };
        setInterval(fetchOrders, 8000);
    }

    const messagesRoot = document.querySelector('[data-messages-root]');
    if (messagesRoot) {
        const messagesAudio = document.querySelector('[data-messages-audio]');
        const tryPlayMessageSound = () => {
            if (!messagesAudio) return;
            const playPromise = messagesAudio.play?.();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(() => {});
            }
        };
        let lastMessageId = Array.isArray(window.adminMessages) && window.adminMessages.length
            ? Number(window.adminMessages[0].id) || null
            : null;

        const fetchMessages = () => {
            const url = `${window.location.pathname}?section=messages&format=json`;
            fetch(url, { credentials: 'same-origin' })
                .then((resp) => resp.json())
                .then((data) => {
                    if (data && Array.isArray(data.messages) && data.messages.length) {
                        const topId = Number(data.messages[0].id) || null;
                        if (topId !== null && (lastMessageId === null || topId > lastMessageId)) {
                            lastMessageId = topId;
                            tryPlayMessageSound();
                        }
                    }
                })
                .catch((error) => console.error('Messages refresh failed', error));
        };

        setInterval(fetchMessages, 8000);
    }
});
</script>
<?php if ($currentSection === 'orders'): ?>
<script>
    window.adminOrders = <?= json_encode($orders, JSON_UNESCAPED_UNICODE) ?>;
</script>
<audio data-orders-audio preload="auto" src="<?= e($orderSoundAudio) ?>"></audio>
<?php endif; ?>
<?php if ($currentSection === 'messages'): ?>
<script>
    window.adminMessages = <?= json_encode($messages, JSON_UNESCAPED_UNICODE) ?>;
</script>
<audio data-messages-audio preload="auto" src="<?= e($orderSoundAudio) ?>"></audio>
<?php endif; ?>
</body>
</html>









