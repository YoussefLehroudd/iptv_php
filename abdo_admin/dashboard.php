<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
$config = require __DIR__ . '/../config/config.php';
/** @var PDO $pdo */
$pdo = require __DIR__ . '/../config/database.php';
requireAdmin();

$basePath = appBasePath();
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
$publicBase = $basePath;
if ($docRoot === '' || !is_dir($docRoot . $publicBase . '/assets')) {
    $publicBase = rtrim($basePath . '/public', '/');
}
$assetBase = $publicBase . '/assets';
$adminBase = $basePath . '/abdo_admin';
$previewUrl = rtrim($publicBase, '/') . '/';
$settings = getSettings($pdo);
$faviconUrl = trim($settings['site_favicon'] ?? '') ?: ($assetBase . '/favicon.ico');

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
        'preview' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="14" rx="2"></rect><path d="M9 20h6"></path><path d="M12 18v2"></path><circle cx="12" cy="11" r="3"></circle></svg>',
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
    'branding' => ['label' => 'Logos & favicon', 'icon' => adminNavIcon('content')],
    'preview' => ['label' => 'Vue live', 'icon' => adminNavIcon('preview')],
    'theme' => ['label' => 'Theme & couleurs', 'icon' => adminNavIcon('theme')],
    'slider' => ['label' => 'Slider hero', 'icon' => adminNavIcon('slider')],
    'platforms' => ['label' => 'Plateformes', 'icon' => adminNavIcon('video')],
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
    'checkout' => ['label' => 'Checkout', 'icon' => adminNavIcon('offers')],
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
                'show_header_trial',
                'seo_title',
                'seo_description',
                'highlight_video_headline',
                'highlight_video_copy',
                'brand_title',
                'brand_tagline',
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
                // Handle boolean checkbox values
                if ($field === 'show_header_trial') {
                    $value = isset($_POST[$field]) ? '1' : '0';
                }
                setSetting($pdo, $field, $value, true);
            }
            adminFlashRedirect('Contenu mise à jour.', 'content', $adminBase);
            break;

        case 'update_branding':
            foreach (['brand_logo_desktop', 'brand_logo_mobile', 'site_favicon'] as $field) {
                if (array_key_exists($field, $_POST)) {
                    $value = trim($_POST[$field] ?? '');
                    setSetting($pdo, $field, $value, true);
                }
            }
            foreach ([
                'brand_logo_desktop_file' => 'brand_logo_desktop',
                'brand_logo_mobile_file' => 'brand_logo_mobile',
                'site_favicon_file' => 'site_favicon',
            ] as $fileKey => $settingKey) {
                if (!empty($_FILES[$fileKey]['tmp_name'])) {
                    $upload = uploadToCloudinary($_FILES[$fileKey]['tmp_name'], 'iptv_abdo/brand', $config['cloudinary']);
                    if ($upload) {
                        setSetting($pdo, $settingKey, $upload, true);
                    }
                }
            }
            adminFlashRedirect('Branding mis à jour.', 'branding', $adminBase);
            break;

        case 'update_theme':
            $theme = $_POST['theme'] ?? 'onyx';

            // Handle custom palette save
            if ($theme === 'custom') {
                $sanitizeHex = static function (?string $value, string $fallback) {
                    $clean = trim((string) $value);
                    $clean = ltrim($clean, '#');
                    if (preg_match('/^[0-9a-fA-F]{6}$/', $clean) || preg_match('/^[0-9a-fA-F]{3}$/', $clean)) {
                        return '#' . $clean;
                    }
                    return $fallback;
                };

                $customVars = [
                    'custom_theme_bg1' => $sanitizeHex($_POST['custom_bg1'] ?? null, $settings['custom_theme_bg1'] ?? '#050505'),
                    'custom_theme_bg2' => $sanitizeHex($_POST['custom_bg2'] ?? null, $settings['custom_theme_bg2'] ?? '#0f0f0f'),
                    'custom_theme_text1' => $sanitizeHex($_POST['custom_text1'] ?? null, $settings['custom_theme_text1'] ?? '#f5f5f5'),
                    'custom_theme_text2' => $sanitizeHex($_POST['custom_text2'] ?? null, $settings['custom_theme_text2'] ?? '#cfcfcf'),
                    'custom_theme_accent' => $sanitizeHex($_POST['custom_accent'] ?? null, $settings['custom_theme_accent'] ?? '#8b5cf6'),
                    'custom_theme_accent_strong' => $sanitizeHex($_POST['custom_accent_strong'] ?? null, $settings['custom_theme_accent_strong'] ?? '#c4b5fd'),
                ];

                foreach ($customVars as $key => $val) {
                    setSetting($pdo, $key, $val, true);
                    $settings[$key] = $val;
                }
            }

            if (isset(themeOptions(customThemeFromSettings($settings))[$theme])) {
                setSetting($pdo, 'active_theme', $theme);
                $redirectSection = $_POST['redirect_section'] ?? 'theme';
                if (!array_key_exists($redirectSection, $navItems)) {
                    $redirectSection = 'theme';
                }
                adminFlashRedirect('Thème changé.', $redirectSection, $adminBase);
            }
            break;

        case 'bulk_delete_movie_posters':
            $ids = isset($_POST['ids']) ? array_filter(array_map('intval', (array) $_POST['ids'])) : [];
            foreach ($ids as $id) {
                deleteRecord($pdo, 'movie_posters', $id);
            }
            $_SESSION['admin_flash'] = $ids ? 'Posters supprimés.' : 'Aucune sélection.';
            header('Location: ' . $adminBase . '/dashboard.php?section=' . urlencode($currentSection) . $postersViewQuery);
            exit;

        case 'bulk_delete_sport_events':
            $ids = isset($_POST['ids']) ? array_filter(array_map('intval', (array) $_POST['ids'])) : [];
            foreach ($ids as $id) {
                deleteRecord($pdo, 'sport_events', $id);
            }
            $_SESSION['admin_flash'] = $ids ? 'Événements supprimés.' : 'Aucune sélection.';
            header('Location: ' . $adminBase . '/dashboard.php?section=sports' . $sportsViewQuery);
            exit;

        case 'bulk_delete_testimonials':
            $ids = isset($_POST['ids']) ? array_filter(array_map('intval', (array) $_POST['ids'])) : [];
            foreach ($ids as $id) {
                deleteRecord($pdo, 'testimonials', $id);
            }
            $_SESSION['admin_flash'] = $ids ? 'Témoignages supprimés.' : 'Aucune sélection.';
            header('Location: ' . $adminBase . '/dashboard.php?section=testimonials');
            exit;
        case 'add_platform_card':
            $platforms = json_decode($settings['platform_cards_json'] ?? '[]', true);
            if (!is_array($platforms)) {
                $platforms = [];
            }
            $title = trim($_POST['platform_title'] ?? '');
            $bg = trim($_POST['platform_bg'] ?? '');
            $image = trim($_POST['platform_image'] ?? '');
            if (!empty($_FILES['platform_image_file']['tmp_name'])) {
                $upload = uploadToCloudinary($_FILES['platform_image_file']['tmp_name'], 'iptv_abdo/platforms', $config['cloudinary']);
                if ($upload) {
                    $image = $upload;
                }
            }
            if ($title !== '') {
                $platforms[] = [
                    'id' => bin2hex(random_bytes(4)),
                    'title' => $title,
                    'bg' => $bg !== '' ? $bg : 'linear-gradient(135deg, #0f172a, #1e293b)',
                    'image_url' => $image,
                ];
                setSetting($pdo, 'platform_cards_json', json_encode($platforms, JSON_UNESCAPED_SLASHES));
                $_SESSION['admin_flash'] = 'Plateforme ajoutée.';
            }
            header('Location: ' . $adminBase . '/dashboard.php?section=platforms');
            exit;
        case 'edit_platform_card':
            $platforms = json_decode($settings['platform_cards_json'] ?? '[]', true);
            if (!is_array($platforms)) {
                $platforms = [];
            }
            $id = trim($_POST['platform_id'] ?? '');
            if ($id !== '') {
                foreach ($platforms as &$platform) {
                    if (($platform['id'] ?? '') === $id) {
                        $platform['title'] = trim($_POST['platform_title'] ?? ($platform['title'] ?? ''));
                        $platform['bg'] = trim($_POST['platform_bg'] ?? ($platform['bg'] ?? ''));
                        $image = trim($_POST['platform_image'] ?? ($platform['image_url'] ?? ''));
                        if (!empty($_FILES['platform_image_file']['tmp_name'])) {
                            $upload = uploadToCloudinary($_FILES['platform_image_file']['tmp_name'], 'iptv_abdo/platforms', $config['cloudinary']);
                            if ($upload) {
                                $image = $upload;
                            }
                        }
                        $platform['image_url'] = $image;
                        $_SESSION['admin_flash'] = 'Plateforme mise à jour.';
                        break;
                    }
                }
                unset($platform);
                setSetting($pdo, 'platform_cards_json', json_encode($platforms, JSON_UNESCAPED_SLASHES));
            }
            header('Location: ' . $adminBase . '/dashboard.php?section=platforms');
            exit;
        case 'edit_platform_card':
            $platforms = json_decode($settings['platform_cards_json'] ?? '[]', true);
            if (!is_array($platforms)) {
                $platforms = [];
            }
            $id = trim($_POST['platform_id'] ?? '');
            if ($id !== '') {
                foreach ($platforms as &$platform) {
                    if (($platform['id'] ?? '') === $id) {
                        $platform['title'] = trim($_POST['platform_title'] ?? ($platform['title'] ?? ''));
                        $platform['bg'] = trim($_POST['platform_bg'] ?? ($platform['bg'] ?? ''));
                        $image = trim($_POST['platform_image'] ?? ($platform['image_url'] ?? ''));
                        if (!empty($_FILES['platform_image_file']['tmp_name'])) {
                            $upload = uploadToCloudinary($_FILES['platform_image_file']['tmp_name'], 'iptv_abdo/platforms', $config['cloudinary']);
                            if ($upload) {
                                $image = $upload;
                            }
                        }
                        $platform['image_url'] = $image;
                        $_SESSION['admin_flash'] = 'Plateforme mise à jour.';
                        break;
                    }
                }
                unset($platform);
                setSetting($pdo, 'platform_cards_json', json_encode($platforms, JSON_UNESCAPED_SLASHES));
            }
            header('Location: ' . $adminBase . '/dashboard.php?section=platforms');
            exit;
        case 'delete_platform_card':
            $platforms = json_decode($settings['platform_cards_json'] ?? '[]', true);
            if (!is_array($platforms)) {
                $platforms = [];
            }
            $id = trim($_POST['platform_id'] ?? '');
            if ($id !== '') {
                $platforms = array_values(array_filter($platforms, static fn($item) => ($item['id'] ?? '') !== $id));
                setSetting($pdo, 'platform_cards_json', json_encode($platforms, JSON_UNESCAPED_SLASHES));
                $_SESSION['admin_flash'] = 'Plateforme supprimée.';
            }
            header('Location: ' . $adminBase . '/dashboard.php?section=platforms');
            exit;
        case 'update_support_whatsapp':
            $supportNumber = trim($_POST['support_whatsapp_number'] ?? '');
            setSetting($pdo, 'support_whatsapp_number', $supportNumber);
            adminFlashRedirect('Numéro WhatsApp support mis à jour.', 'offers', $adminBase);
            break;
        case 'update_checkout_settings':
            $modePosted = $_POST['checkout_mode'] ?? '';
            $mode = in_array($modePosted, ['form', 'whatsapp', 'whop', 'paypal'], true)
                ? $modePosted
                : (isset($_POST['checkout_enabled']) ? 'form' : 'whatsapp');
            $enabled = $mode === 'form' ? '1' : '0';
            $whatsapp = trim($_POST['checkout_whatsapp_number'] ?? '');
            $telegramChat = trim($_POST['checkout_telegram_chat_id'] ?? '');
            $telegramToken = trim($_POST['checkout_telegram_bot_token'] ?? '');
            $whopPlanId = trim($_POST['checkout_whop_plan_id'] ?? '');
            $whopProductId = trim($_POST['checkout_whop_product_id'] ?? '');
            $whopCheckoutLink = trim($_POST['checkout_whop_link'] ?? '');
            $paypalLink = trim($_POST['checkout_paypal_link'] ?? '');
            $paypalClientId = trim($_POST['checkout_paypal_client_id'] ?? '');
            $paypalEnv = trim($_POST['checkout_paypal_env'] ?? '');
            $fieldOptions = ['first_name', 'last_name', 'company', 'address', 'apartment', 'city', 'country', 'state', 'zip', 'phone'];
            $postedFields = isset($_POST['checkout_fields']) && is_array($_POST['checkout_fields']) ? array_map('strval', $_POST['checkout_fields']) : [];
            $enabledFields = array_values(array_intersect($fieldOptions, $postedFields));
            setSetting($pdo, 'checkout_mode', $mode);
            setSetting($pdo, 'checkout_enabled', $enabled);
            setSetting($pdo, 'checkout_whatsapp_number', $whatsapp);
            setSetting($pdo, 'checkout_telegram_chat_id', $telegramChat);
            setSetting($pdo, 'checkout_telegram_bot_token', $telegramToken);
            setSetting($pdo, 'checkout_whop_plan_id', $whopPlanId);
            setSetting($pdo, 'checkout_whop_product_id', $whopProductId);
            setSetting($pdo, 'checkout_whop_link', $whopCheckoutLink);
            setSetting($pdo, 'checkout_paypal_link', $paypalLink);
            setSetting($pdo, 'checkout_paypal_client_id', $paypalClientId);
            setSetting($pdo, 'checkout_paypal_env', in_array($paypalEnv, ['sandbox', 'live'], true) ? $paypalEnv : 'sandbox');
            setSetting($pdo, 'checkout_fields_enabled', json_encode($enabledFields, JSON_UNESCAPED_SLASHES));
            adminFlashRedirect('Checkout mis a jour.', 'checkout', $adminBase);
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
        case 'mark_order_read':
            $orderId = (int) ($_POST['order_id'] ?? 0);
            $isRead = isset($_POST['is_read']) ? (int) $_POST['is_read'] : 0;
            if ($orderId > 0) {
                $stmt = $pdo->prepare('UPDATE orders SET is_read = :is_read WHERE id = :id');
                $stmt->execute(['is_read' => $isRead ? 1 : 0, 'id' => $orderId]);
            }
            adminFlashRedirect('Statut de lecture mis à jour.', 'orders', $adminBase);
            break;
        case 'delete_order':
            $orderId = (int) ($_POST['order_id'] ?? 0);
            if ($orderId > 0) {
                $stmt = $pdo->prepare('DELETE FROM orders WHERE id = :id');
                $stmt->execute(['id' => $orderId]);
            }
            adminFlashRedirect('Commande supprimée.', 'orders', $adminBase);
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
$checkoutEnabled = ($settings['checkout_enabled'] ?? '1') === '1';
$checkoutWhatsappNumber = trim($settings['checkout_whatsapp_number'] ?? '');
$checkoutTelegramChatId = trim($settings['checkout_telegram_chat_id'] ?? '');
$checkoutTelegramToken = trim($settings['checkout_telegram_bot_token'] ?? '');
$platformCardsSetting = json_decode($settings['platform_cards_json'] ?? '[]', true);
if (!is_array($platformCardsSetting)) {
    $platformCardsSetting = [];
}
$checkoutModeSetting = trim($settings['checkout_mode'] ?? '');
$checkoutMode = in_array($checkoutModeSetting, ['form', 'whatsapp', 'whop', 'paypal'], true)
    ? $checkoutModeSetting
    : ($checkoutEnabled ? 'form' : 'whatsapp');
$checkoutWhopPlanId = trim($settings['checkout_whop_plan_id'] ?? '');
$checkoutWhopProductId = trim($settings['checkout_whop_product_id'] ?? '');
$checkoutWhopLink = trim($settings['checkout_whop_link'] ?? '');
$checkoutPaypalLink = trim($settings['checkout_paypal_link'] ?? '');
$checkoutPaypalClientId = trim($settings['checkout_paypal_client_id'] ?? '');
$checkoutPaypalEnv = trim($settings['checkout_paypal_env'] ?? '');
if (!in_array($checkoutPaypalEnv, ['sandbox', 'live'], true)) {
    $checkoutPaypalEnv = 'sandbox';
}
$checkoutPaypalLink = trim($settings['checkout_paypal_link'] ?? '');
$checkoutFieldsSetting = $settings['checkout_fields_enabled'] ?? '';
$checkoutFieldsEnabled = json_decode($checkoutFieldsSetting, true);
if (!is_array($checkoutFieldsEnabled)) {
    $checkoutFieldsEnabled = ['first_name', 'last_name', 'company', 'address', 'apartment', 'city', 'country', 'state', 'zip', 'phone'];
}
$supportWhatsappNumber = $supportWhatsappNumberSetting !== '' ? $supportWhatsappNumberSetting : ($config['whatsapp_number'] ?? '');
$themeVars = getActiveThemeVars($settings['active_theme'] ?? 'onyx', $settings);
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
$orders = fetchAllAssoc($pdo, 'SELECT o.*, off.name AS offer_name, off.price AS offer_price, o.is_read FROM orders o LEFT JOIN offers off ON off.id = o.offer_id ORDER BY o.created_at DESC LIMIT 200');
$visitStats = getVisitStats($pdo);
$themes = themeOptions(customThemeFromSettings($settings));
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
    <link rel="icon" href="<?= e($faviconUrl) ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= $assetBase ?>/css/admin.css?v=<?= time() ?>">
    <style>
        /* Admin panel needs copy/paste + text selection */
        body.admin,
        body.admin * {
            user-select: text;
            -webkit-user-select: text;
        }

        .live-preview {
            display: grid;
            gap: 1.5rem;
        }

        .preview-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            justify-content: flex-start;
            margin-left: 0.5rem;
        }

        .preview-toolbar .btn {
            padding: 0.55rem 0.9rem;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            align-items: start;
        }

        .preview-frame-wrap {
            background: #0f1117;
            border: 1px solid var(--surface-200, rgba(255, 255, 255, 0.08));
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            padding: 0.5rem;
        }

        .preview-frame {
            width: 100%;
            height: calc(100vh - 220px);
            min-height: 640px;
            background: #0d0f14;
            border: none;
            border-radius: 12px;
        }

        .preview-frame-wrap.is-tablet .preview-frame {
            width: 900px;
            margin: 0 auto;
        }

        .preview-frame-wrap.is-mobile .preview-frame {
            width: 428px;
            margin: 0 auto;
        }

        .preview-size {
            display: inline-flex;
            gap: 0.35rem;
            align-items: center;
        }

        .preview-size button {
            padding: 0.5rem 0.75rem;
            border-radius: 10px;
            border: 1px solid var(--surface-200, rgba(255, 255, 255, 0.1));
            background: var(--surface-200, rgba(255, 255, 255, 0.04));
            color: inherit;
            cursor: pointer;
        }

        .preview-size button.active {
            border-color: var(--accent-500, #7c3aed);
            background: var(--accent-500, #7c3aed);
            color: #fff;
        }

        .preview-quick-edits {
            margin: 0.5rem 0 0.75rem;
            display: grid;
            gap: 0.35rem;
        }

        .preview-quick-edits .eyebrow {
            margin: 0;
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .quick-edit-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .quick-edit-grid a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.55rem 0.85rem;
            border-radius: 10px;
            background: var(--surface-300, rgba(255, 255, 255, 0.04));
            border: 1px solid var(--surface-200, rgba(255, 255, 255, 0.08));
            color: inherit;
            text-decoration: none;
            transition: transform 0.15s ease, border-color 0.15s ease, background 0.15s ease;
            font-size: 0.92rem;
            white-space: nowrap;
        }

        .quick-edit-grid a:hover {
            transform: translateY(-1px);
            border-color: var(--accent-400, #7c3aed);
            background: var(--surface-400, rgba(255, 255, 255, 0.08));
        }

        /* Theme picker slider */
        .theme-slider {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 0.8rem;
            padding: 0.35rem 0;
        }

        .theme-card {
            position: relative;
            min-width: 0;
            cursor: pointer;
        }

        .theme-card input {
            position: absolute;
            inset: 0;
            opacity: 0;
            pointer-events: none;
        }

        .theme-card__body {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 14px;
            border: 1px solid var(--surface-200, rgba(255, 255, 255, 0.08));
            background: var(--surface-200, rgba(255, 255, 255, 0.03));
            transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
            min-height: 86px;
        }

        .theme-card__name {
            display: block;
            font-weight: 600;
        }

        .theme-card__slug {
            display: block;
            margin-top: 0.15rem;
            font-size: 0.85rem;
            opacity: 0.65;
            letter-spacing: 0.02em;
        }

        .theme-card__swatches {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .theme-card__swatch {
            width: 78px;
            height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--swatch-from, #0d0f14) 0%, var(--swatch-to, #1b1f29) 90%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.25);
        }

        .theme-card__chip {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: var(--swatch-chip, #7c3aed);
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.3);
        }

        .theme-card__toggle {
            width: 42px;
            height: 22px;
            border-radius: 999px;
            border: 1px solid var(--surface-200, rgba(255, 255, 255, 0.2));
            background: var(--surface-300, rgba(255, 255, 255, 0.08));
            position: relative;
        }

        .theme-card__toggle::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #9ca3af;
            transition: transform 0.15s ease, background 0.15s ease;
        }

        .theme-card input:checked + .theme-card__body {
            border-color: var(--accent-500, #7c3aed);
            box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.35);
            transform: translateY(-2px);
        }

        .theme-card input:checked + .theme-card__body .theme-card__toggle {
            border-color: var(--accent-500, #7c3aed);
            background: var(--accent-500, #7c3aed);
        }

        .theme-card input:checked + .theme-card__body .theme-card__toggle::after {
            transform: translateX(20px);
            background: #fff;
        }

        .theme-card:hover .theme-card__body {
            border-color: var(--surface-300, rgba(255, 255, 255, 0.16));
        }

        @media (max-width: 720px) {
            .theme-slider {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            .theme-card__body {
                gap: 0.7rem;
                padding: 0.85rem 0.95rem;
            }
            .theme-card__swatch {
                width: 68px;
            }
        }

        .custom-theme-box {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid var(--surface-200, rgba(255, 255, 255, 0.08));
            background: var(--surface-200, rgba(255, 255, 255, 0.03));
            display: none;
        }

        .custom-grid {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .custom-theme-box label {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            font-size: 0.95rem;
        }

        .custom-theme-box input[type="color"] {
            width: 100%;
            height: 46px;
            border-radius: 10px;
            border: 1px solid var(--surface-200, rgba(255, 255, 255, 0.12));
            background: var(--surface-300, rgba(255, 255, 255, 0.08));
            padding: 0;
            cursor: pointer;
        }

        /* Hide sidebar in preview by default for a full-width view; toggle button can reopen it */
        .preview-mode {
            overflow-x: hidden;
        }

        .preview-mode .admin-layout {
            grid-template-columns: 260px 1fr;
            max-width: 100%;
            width: 100%;
            padding: 0 0.75rem 1.5rem;
            margin: 0 auto;
        }

        .preview-mode .admin-content {
            max-width: 1200px;
            width: 100%;
            padding: 0 0.75rem;
            margin: 0 auto;
        }

        .preview-mode .admin-sidebar {
            display: block;
        }

        .preview-mode .sidebar-overlay {
            display: none;
        }

        .preview-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .preview-toggle .btn {
            padding: 0.45rem 0.75rem;
        }

        .preview-mode .admin-section.live-preview {
            padding: 1.5rem 0 1rem;
            margin: 0 auto;
            background: transparent;
            box-shadow: none;
            width: 100%;
            max-width: 1240px;
            top: 0;
        }

        .preview-mode .preview-frame-wrap {
            border: none;
            border-radius: 16px;
            box-shadow: none;
            padding: 0;
            width: 100%;
            max-width: 1240px;
            margin: 0 auto;
        }

        .preview-mode .preview-frame {
            width: 100%;
            height: calc(100vh - 120px);
            min-height: 760px;
        }

        /* Allow manual hide/show of sidebar even on desktop */
        body.sidebar-hidden .admin-layout {
            grid-template-columns: 1fr;
            max-width: 1240px;
        }

        body.sidebar-hidden .admin-sidebar {
            display: none;
        }

        body.sidebar-hidden .sidebar-overlay {
            display: none !important;
        }

        @media (max-width: 1100px) {
            .preview-frame {
                height: 70vh;
            }
        }

    </style>
</head>
<?php $isPreviewSection = $currentSection === 'preview'; ?>
<body class="admin<?= $isPreviewSection ? ' preview-mode' : '' ?>">
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
        <nav class="sidebar-nav" data-nav-sort>
            <?php foreach ($navItems as $slug => $item): ?>
                <?php
                $navQuery = '';
                if (str_starts_with($slug, 'poster_')) {
                    $navQuery = $postersViewQuery;
                } elseif ($slug === 'sports') {
                    $navQuery = $sportsViewQuery;
                }
                ?>
                <a class="<?= $currentSection === $slug ? 'active' : '' ?>" href="<?= $adminBase ?>/dashboard.php?section=<?= $slug ?><?= $navQuery ?>" draggable="true" data-section="<?= e($slug) ?>">
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
                    <label>Titre hero
                        <input type="text" name="hero_title" value="<?= e($settings['hero_title'] ?? '') ?>" required>
                    </label>
                    <label>Sous-titre
                        <input type="text" name="hero_subtitle" value="<?= e($settings['hero_subtitle'] ?? '') ?>">
                    </label>
                    <label>Texte CTA
                        <input type="text" name="hero_cta" value="<?= e($settings['hero_cta'] ?? '') ?>">
                    </label>
                    <label class="checkbox checkbox-inline switch">
                        <input type="checkbox" name="show_header_trial" value="1" <?= ($settings['show_header_trial'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <span>Afficher le bouton "Free Trial" dans le header</span>
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
        <?php elseif ($currentSection === 'branding'): ?>
            <section class="admin-section">
                <h2>Logos & favicon</h2>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=branding" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="update_branding">
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
                    <label>Favicon / Icone du site (32x32 ou 64x64)
                        <input type="file" name="site_favicon_file" accept="image/*">
                        <span class="form-note">PNG/SVG/Ico · Sera utilisé comme icône d'onglet (site + panel).</span>
                    </label>
                    <label>Ou URL favicon
                        <input type="url" name="site_favicon" placeholder="https://" value="<?= e($settings['site_favicon'] ?? '') ?>">
                        <?php if (!empty($settings['site_favicon'])): ?>
                            <span class="form-note">Actuel : <a class="link-light" href="<?= e($settings['site_favicon']) ?>" target="_blank" rel="noopener">Voir le favicon</a></span>
                        <?php endif; ?>
                    </label>
                    <button class="btn" type="submit">Enregistrer</button>
                </form>
            </section>
        <?php elseif ($currentSection === 'platforms'): ?>
            <section class="admin-section">
                <h2>Plateformes streaming</h2>
                <form id="platform-form" method="POST" action="<?= $adminBase ?>/dashboard.php?section=platforms" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="add_platform_card">
                    <input type="hidden" name="platform_id" value="">
                    <div class="form-grid two">
                        <label>Nom de la plateforme
                            <input type="text" name="platform_title" placeholder="Netflix, HBO Max..." required>
                        </label>
                        <label>Dégradé de fond
                            <input type="text" name="platform_bg" placeholder="linear-gradient(135deg, #0f1b2c, #1b1f2f)">
                            <span class="form-note">Hex ou gradient CSS (optionnel).</span>
                        </label>
                        <label>URL image
                            <input type="url" name="platform_image" placeholder="https://">
                        </label>
                        <label>Upload image
                            <input type="file" name="platform_image_file" accept="image/*">
                        </label>
                    </div>
                    <div class="row gap-8">
                        <button class="btn" type="submit" data-platform-submit>Ajouter</button>
                        <button class="btn ghost" type="button" data-platform-reset>Annulerr</button>
                    </div>
                </form>
                <div class="admin-media-grid" style="margin-top: 1.5rem;">
                    <?php if (empty($platformCardsSetting)): ?>
                        <p class="form-note">Aucune plateforme pour l'instant.</p>
                    <?php else: ?>
                        <?php foreach ($platformCardsSetting as $platform): ?>
                            <article>
                                <div class="media-card-body">
                                    <strong><?= e($platform['title'] ?? '') ?></strong>
                                    <?php if (!empty($platform['image_url'])): ?>
                                        <img src="<?= e($platform['image_url']) ?>" alt="<?= e($platform['title'] ?? '') ?>" style="height:160px;object-fit:cover;border-radius:12px;">
                                    <?php endif; ?>
                                    <?php if (!empty($platform['bg'])): ?>
                                        <small><?= e($platform['bg']) ?></small>
                                    <?php endif; ?>
                                    <button class="btn ghost" type="button"
                                            data-platform-edit
                                            data-id="<?= e($platform['id'] ?? '') ?>"
                                            data-title="<?= e($platform['title'] ?? '') ?>"
                                            data-bg="<?= e($platform['bg'] ?? '') ?>"
                                            data-image="<?= e($platform['image_url'] ?? '') ?>">
                                        Modifier
                                    </button>
                                    <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=platforms" onsubmit="return confirm('Supprimer cette plateforme ?');">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                                        <input type="hidden" name="action" value="delete_platform_card">
                                        <input type="hidden" name="platform_id" value="<?= e($platform['id'] ?? '') ?>">
                                        <button class="btn ghost danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
            <script>
                (function () {
                    const form = document.getElementById('platform-form');
                    if (!form) return;
                    const actionInput = form.querySelector('input[name=action]');
                    const idInput = form.querySelector('input[name=platform_id]');
                    const titleInput = form.querySelector('input[name=platform_title]');
                    const bgInput = form.querySelector('input[name=platform_bg]');
                    const imgInput = form.querySelector('input[name=platform_image]');
                    const submitBtn = form.querySelector('[data-platform-submit]');
                    const resetBtn = form.querySelector('[data-platform-reset]');

                    const setAddMode = () => {
                        if (actionInput) actionInput.value = 'add_platform_card';
                        if (idInput) idInput.value = '';
                        if (titleInput) titleInput.value = '';
                        if (bgInput) bgInput.value = '';
                        if (imgInput) imgInput.value = '';
                        if (submitBtn) submitBtn.textContent = 'Ajouter';
                        if (resetBtn) resetBtn.style.display = 'none';
                    };

                    document.querySelectorAll('[data-platform-edit]').forEach((btn) => {
                        btn.addEventListener('click', () => {
                            if (submitBtn) submitBtn.textContent = 'Mettre à jour';
                            if (actionInput) actionInput.value = 'edit_platform_card';
                            if (idInput) idInput.value = btn.dataset.id || '';
                            if (titleInput) titleInput.value = btn.dataset.title || '';
                            if (bgInput) bgInput.value = btn.dataset.bg || '';
                            if (imgInput) imgInput.value = btn.dataset.image || '';
                            titleInput?.focus();
                            window.scrollTo({ top: form.offsetTop - 10, behavior: 'smooth' });
                            if (resetBtn) resetBtn.style.display = 'inline-flex';
                        });
                    });

                    resetBtn?.addEventListener('click', setAddMode);
                    // Also reset on page load
                    setAddMode();
                })();
            </script>
        <?php elseif ($currentSection === 'preview'): ?>
            <section class="admin-section live-preview">
                <div class="preview-toolbar">
                    <div>
                        <p class="eyebrow">Vue live</p>
                        <h2>Aperçu complet du site</h2>
                        <p>Affiche le site public (index) dans le panel et saute vers l'edition en un clic.</p>
                    </div>
                    <div class="preview-toolbar preview-toggle">
                        <button class="btn ghost" type="button" data-preview-toggle-sidebar>Sidebar</button>
                        <button class="btn ghost" type="button" data-preview-reload>Rafraichir l'aperçu</button>
                        <a class="btn ghost" href="<?= $adminBase ?>/dashboard.php?section=theme">Editer thème</a>
                        <a class="btn" href="<?= e($previewUrl) ?>" target="_blank" rel="noopener">Ouvrir dans un nouvel onglet</a>
                        <div class="preview-size" data-preview-size>
                            <button type="button" class="active" data-preview-target="desktop">Desktop</button>
                            <button type="button" data-preview-target="tablet">Tablet</button>
                            <button type="button" data-preview-target="mobile">Mobile</button>
                        </div>
                    </div>
                </div>

                <div class="preview-quick-edits">
                    <p class="eyebrow">Edition rapide du texte</p>
                    <div class="quick-edit-grid">
                        <a href="<?= $adminBase ?>/dashboard.php?section=content">Hero & SEO</a>
                        <a href="<?= $adminBase ?>/dashboard.php?section=branding">Logos & favicon</a>
                        <a href="<?= $adminBase ?>/dashboard.php?section=slider">Slider hero</a>
                        <a href="<?= $adminBase ?>/dashboard.php?section=offers">Offres</a>
                        <a href="<?= $adminBase ?>/dashboard.php?section=providers">Providers</a>
                        <a href="<?= $adminBase ?>/dashboard.php?section=poster_categories">Posters</a>
                        <?php if ($defaultPosterCategory): ?>
                            <a href="<?= $adminBase ?>/dashboard.php?section=poster_<?= e($defaultPosterCategory['slug']) ?><?= $postersViewQuery ?>">Poster: <?= e($defaultPosterCategory['label']) ?></a>
                        <?php endif; ?>
                        <a href="<?= $adminBase ?>/dashboard.php?section=sports<?= $sportsViewQuery ?>">Sports</a>
                        <a href="<?= $adminBase ?>/dashboard.php?section=testimonials">Témoignages</a>
                        <a href="<?= $adminBase ?>/dashboard.php?section=checkout">Checkout</a>
                        <a href="<?= $adminBase ?>/dashboard.php?section=messages">Messages</a>
                        <a href="<?= $adminBase ?>/dashboard.php?section=orders">Commandes</a>
                    </div>
                </div>

                <div class="preview-grid">
                    <div class="preview-frame-wrap" data-preview-wrap>
                        <iframe class="preview-frame" src="<?= e($previewUrl) ?>" title="Aperçu du site public" loading="lazy" data-preview-frame></iframe>
                    </div>
                </div>
            </section>
        <?php elseif ($currentSection === 'theme'): ?>
            <?php $themeCount = is_array($themes) ? count($themes) : 0; ?>
            <section class="admin-section">
                <h2>Thème & couleurs<?= $themeCount ? ' (' . $themeCount . ')' : '' ?></h2>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=theme">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="update_theme">
                    <div class="theme-slider" aria-label="Choisir le thème" data-theme-slider>
                    <?php foreach ($themes as $slug => $theme): ?>
                        <?php
                            $bg1 = $theme['vars']['--bg-primary'] ?? '#0d0f14';
                            $bg2 = $theme['vars']['--bg-secondary'] ?? $bg1;
                            $chip = $theme['vars']['--accent-strong'] ?? '#7c3aed';
                            $active = ($settings['active_theme'] ?? 'onyx') === $slug;
                        ?>
                        <label class="theme-card" data-theme-card="<?= e($slug) ?>" style="--swatch-from: <?= e($bg1) ?>; --swatch-to: <?= e($bg2) ?>; --swatch-chip: <?= e($chip) ?>;">
                            <input type="radio" name="theme" value="<?= e($slug) ?>" <?= $active ? 'checked' : '' ?>>
                            <div class="theme-card__body">
                                <div>
                                    <span class="theme-card__name"><?= e($theme['label']) ?></span>
                                    <span class="theme-card__slug"><?= strtoupper(e($slug)) ?><?= $active ? ' · Actif' : '' ?></span>
                                </div>
                                <div class="theme-card__swatches">
                                    <span class="theme-card__swatch" aria-hidden="true"></span>
                                    <span class="theme-card__chip" aria-hidden="true"></span>
                                    <span class="theme-card__toggle" aria-hidden="true"></span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                    </div>

                    <div class="custom-theme-box" data-custom-box>
                        <p class="eyebrow">Palette personnalisée</p>
                        <div class="custom-grid">
                            <label>Fond primaire
                                <input type="color" name="custom_bg1" value="<?= e($settings['custom_theme_bg1'] ?? '#050505') ?>" data-custom-color="--bg-primary">
                            </label>
                            <label>Fond secondaire
                                <input type="color" name="custom_bg2" value="<?= e($settings['custom_theme_bg2'] ?? '#0f0f0f') ?>" data-custom-color="--bg-secondary">
                            </label>
                            <label>Texte principal
                                <input type="color" name="custom_text1" value="<?= e($settings['custom_theme_text1'] ?? '#f5f5f5') ?>" data-custom-color="--text-primary">
                            </label>
                            <label>Texte secondaire
                                <input type="color" name="custom_text2" value="<?= e($settings['custom_theme_text2'] ?? '#cfcfcf') ?>" data-custom-color="--text-secondary">
                            </label>
                            <label>Accent
                                <input type="color" name="custom_accent" value="<?= e($settings['custom_theme_accent'] ?? '#8b5cf6') ?>" data-custom-color="--accent">
                            </label>
                            <label>Accent fort
                                <input type="color" name="custom_accent_strong" value="<?= e($settings['custom_theme_accent_strong'] ?? '#c4b5fd') ?>" data-custom-color="--accent-strong">
                            </label>
                        </div>
                        <p class="form-note">Sélectionne « Custom » puis choisis tes couleurs.</p>
                    </div>

                    <button class="btn" type="submit">Changer le thème</button>
                </form>
            </section>

            <script>
                (function () {
                    const customBox = document.querySelector('[data-custom-box]');
                    const customCard = document.querySelector('[data-theme-card="custom"]');
                    const radios = document.querySelectorAll('input[name="theme"]');
                    const colorInputs = document.querySelectorAll('[data-custom-color]');

                    const applyCustomPreview = () => {
                        if (!customCard) return;
                        const style = customCard.style;
                        colorInputs.forEach((input) => {
                            const prop = input.dataset.customColor;
                            if (!prop) return;
                            if (prop === '--accent-strong') {
                                style.setProperty('--swatch-chip', input.value);
                            } else if (prop === '--bg-secondary') {
                                style.setProperty('--swatch-to', input.value);
                            } else if (prop === '--bg-primary') {
                                style.setProperty('--swatch-from', input.value);
                            }
                        });
                    };

                    const toggleCustomBox = () => {
                        const isCustom = document.querySelector('input[name="theme"][value="custom"]')?.checked;
                        if (customBox) customBox.style.display = isCustom ? 'block' : 'none';
                    };

                    radios.forEach((radio) => {
                        radio.addEventListener('change', toggleCustomBox);
                    });
                    colorInputs.forEach((input) => {
                        input.addEventListener('input', applyCustomPreview);
                    });

                    toggleCustomBox();
                    applyCustomPreview();
                })();
            </script>
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
                    <form class="bulk-form" method="POST" action="<?= $adminBase ?>/dashboard.php?section=<?= e($currentSection) ?><?= $postersViewQuery ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="action" value="bulk_delete_movie_posters">
                        <div class="bulk-actions">
                            <label class="bulk-checkbox">
                                <input type="checkbox" data-select-all>
                                <span>Sélectionner tout</span>
                            </label>
                            <button class="btn danger" type="submit" data-bulk-delete disabled>Supprimer la sélection</button>
                        </div>
                        <div class="admin-media-grid">
                            <?php foreach ($activeMoviePosterList as $poster): ?>
                                <?php $posterSlug = $poster['category_slug'] ?? ($currentPosterCategory['slug'] ?? ''); ?>
                                <article>
                                    <label class="bulk-checkbox">
                                        <input type="checkbox" name="ids[]" value="<?= (int) $poster['id'] ?>">
                                        <span class="sr-only">Sélectionner</span>
                                    </label>
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
                    </form>
                <?php else: ?>
                    <form class="bulk-form" method="POST" action="<?= $adminBase ?>/dashboard.php?section=<?= e($currentSection) ?><?= $postersViewQuery ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="action" value="bulk_delete_movie_posters">
                        <div class="bulk-actions">
                            <label class="bulk-checkbox">
                                <input type="checkbox" data-select-all>
                                <span>Sélectionner tout</span>
                            </label>
                            <button class="btn danger" type="submit" data-bulk-delete disabled>Supprimer la sélection</button>
                        </div>
                        <div class="list admin-media-list">
                            <?php foreach ($activeMoviePosterList as $poster): ?>
                                <?php $posterSlug = $poster['category_slug'] ?? ($currentPosterCategory['slug'] ?? ''); ?>
                                <article>
                                    <label class="bulk-checkbox">
                                        <input type="checkbox" name="ids[]" value="<?= (int) $poster['id'] ?>">
                                        <span class="sr-only">Sélectionner</span>
                                    </label>
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
                    </form>
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
                    <form class="bulk-form" method="POST" action="<?= $adminBase ?>/dashboard.php?section=sports<?= $sportsViewQuery ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="action" value="bulk_delete_sport_events">
                        <div class="bulk-actions">
                            <label class="bulk-checkbox">
                                <input type="checkbox" data-select-all>
                                <span>Sélectionner tout</span>
                            </label>
                            <button class="btn danger" type="submit" data-bulk-delete disabled>Supprimer la sélection</button>
                        </div>
                        <div class="admin-media-grid">
                            <?php foreach ($sportEvents as $event): ?>
                                <article>
                                    <label class="bulk-checkbox">
                                        <input type="checkbox" name="ids[]" value="<?= (int) $event['id'] ?>">
                                        <span class="sr-only">Sélectionner</span>
                                    </label>
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
                    </form>
                <?php else: ?>
                    <form class="bulk-form" method="POST" action="<?= $adminBase ?>/dashboard.php?section=sports<?= $sportsViewQuery ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="action" value="bulk_delete_sport_events">
                        <div class="bulk-actions">
                            <label class="bulk-checkbox">
                                <input type="checkbox" data-select-all>
                                <span>Sélectionner tout</span>
                            </label>
                            <button class="btn danger" type="submit" data-bulk-delete disabled>Supprimer la sélection</button>
                        </div>
                        <div class="list admin-media-list">
                            <?php foreach ($sportEvents as $event): ?>
                                <article>
                                    <label class="bulk-checkbox">
                                        <input type="checkbox" name="ids[]" value="<?= (int) $event['id'] ?>">
                                        <span class="sr-only">Sélectionner</span>
                                    </label>
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
                    </form>
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
                <?php if (empty($testimonialGallery)): ?>
                    <p class="form-note">Aucun témoignage visuel pour le moment.</p>
                <?php else: ?>
                    <form class="bulk-form" method="POST" action="<?= $adminBase ?>/dashboard.php?section=testimonials">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="action" value="bulk_delete_testimonials">
                        <div class="bulk-actions">
                            <label class="bulk-checkbox">
                                <input type="checkbox" data-select-all>
                                <span>Sélectionner tout</span>
                            </label>
                            <button class="btn danger" type="submit" data-bulk-delete disabled>Supprimer la sélection</button>
                        </div>
                        <div class="list admin-media-list">
                            <?php foreach ($testimonialGallery as $testimonial): ?>
                                <article>
                                    <label class="bulk-checkbox">
                                        <input type="checkbox" name="ids[]" value="<?= (int) $testimonial['id'] ?>">
                                        <span class="sr-only">Sélectionner</span>
                                    </label>
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
                    </form>
                <?php endif; ?>
            </section>
        <?php elseif ($currentSection === 'checkout'): ?>
            <section class="admin-section">
                <h2>Checkout</h2>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=checkout" class="support-form">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="update_checkout_settings">
                    <?php
                    $checkoutFieldOptions = [
                        'first_name' => 'First name',
                        'last_name' => 'Last name',
                        'company' => 'Company',
                        'address' => 'Address',
                        'apartment' => 'Apartment / Suite',
                        'city' => 'City',
                        'country' => 'Country / Region',
                        'state' => 'State / Province',
                        'zip' => 'ZIP / Postal code',
                        'phone' => 'Phone',
                    ];
                    ?>
                    <label>Mode du checkout
                        <select name="checkout_mode">
                            <option value="form" <?= $checkoutMode === 'form' ? 'selected' : '' ?>>Checkout interne (formulaire)</option>
                            <option value="whatsapp" <?= $checkoutMode === 'whatsapp' ? 'selected' : '' ?>>Redirection WhatsApp</option>
                            <option value="whop" <?= $checkoutMode === 'whop' ? 'selected' : '' ?>>Paiement Whop (plan)</option>
                            <option value="paypal" <?= $checkoutMode === 'paypal' ? 'selected' : '' ?>>Paiement PayPal</option>
                        </select>
                        <span class="form-note">Choisis si le bouton passe par le formulaire, WhatsApp, PayPal ou directement par Whop.</span>
                    </label>
                    <label data-checkout-field="whatsapp" <?= $checkoutMode === 'whatsapp' ? '' : 'hidden' ?>>Numero WhatsApp pour redirection
                        <input type="text" name="checkout_whatsapp_number" value="<?= e($checkoutWhatsappNumber !== '' ? $checkoutWhatsappNumber : $supportWhatsappNumberSetting) ?>" placeholder="+15145550000">
                        <span class="form-note">Si le checkout est desactive, le bouton enverra les clients vers ce numero (sinon celui de support).</span>
                    </label>
                    <label data-checkout-field="telegram" <?= $checkoutMode === 'form' ? '' : 'hidden' ?>>ID Telegram (chat)
                        <input type="text" name="checkout_telegram_chat_id" value="<?= e($checkoutTelegramChatId) ?>" placeholder="123456789">
                        <span class="form-note">Chat ID pour recevoir les commandes via bot (optionnel).</span>
                    </label>
                    <label data-checkout-field="telegram" <?= $checkoutMode === 'form' ? '' : 'hidden' ?>>Bot token Telegram
                        <input type="text" name="checkout_telegram_bot_token" value="<?= e($checkoutTelegramToken) ?>" placeholder="123456:ABCDEF">
                        <span class="form-note">Clé du bot qui enverra le message (optionnel).</span>
                    </label>
                    <label data-checkout-field="whop" <?= $checkoutMode === 'whop' ? '' : 'hidden' ?>>Lien Whop (optionnel)
                        <input type="text" name="checkout_whop_link" value="<?= e($checkoutWhopLink) ?>" placeholder="https://whop.com/checkout/plan_xxxxx">
                        <span class="form-note">Colle le lien Whop si tu en as un. Sinon, on utilise l'ID du plan pour le generer.</span>
                    </label>
                    <label data-checkout-field="whop" <?= $checkoutMode === 'whop' ? '' : 'hidden' ?>>ID plan Whop
                        <input type="text" name="checkout_whop_plan_id" value="<?= e($checkoutWhopPlanId) ?>" placeholder="plan_xxxxxxxxxxxxx">
                    </label>
                    <label data-checkout-field="whop" <?= $checkoutMode === 'whop' ? '' : 'hidden' ?>>ID produit Whop
                        <input type="text" name="checkout_whop_product_id" value="<?= e($checkoutWhopProductId) ?>" placeholder="prod_xxxxxxxxxxxxx">
                        <span class="form-note">Optionnel : ajoute en parametre pour le suivi produit.</span>
                    </label>
                    <label data-checkout-field="paypal" <?= $checkoutMode === 'paypal' ? '' : 'hidden' ?>>Lien PayPal (optionnel)
                        <input type="url" name="checkout_paypal_link" value="<?= e($checkoutPaypalLink) ?>" placeholder="https://www.paypal.com/checkoutnow?token=XXXX">
                        <span class="form-note">Facultatif : lien direct PayPal/PayPal.me. Sinon on affiche un bouton PayPal integr‚ dans le checkout.</span>
                    </label>
                    <label data-checkout-field="paypal" <?= $checkoutMode === 'paypal' ? '' : 'hidden' ?>>PayPal Client ID
                        <input type="text" name="checkout_paypal_client_id" value="<?= e($checkoutPaypalClientId) ?>" placeholder="Abc123...">
                        <span class="form-note">Copie le Client ID Sandbox/Live depuis dashboard PayPal (App & Credentials). Oblige pour afficher le bouton PayPal/Carte.</span>
                    </label>
                    <label data-checkout-field="paypal" <?= $checkoutMode === 'paypal' ? '' : 'hidden' ?>>Environnement PayPal
                        <select name="checkout_paypal_env">
                            <option value="sandbox" <?= $checkoutPaypalEnv === 'sandbox' ? 'selected' : '' ?>>Sandbox (test)</option>
                            <option value="live" <?= $checkoutPaypalEnv === 'live' ? 'selected' : '' ?>>Live (prod)</option>
                        </select>
                        <span class="form-note">Utilise Sandbox pour tester avec les comptes tests PayPal (ou carte test). Passe en Live pour encaisser en prod.</span>
                    </label>
                    <div class="checkout-fields-grid" data-checkout-field="form-fields" <?= $checkoutMode === 'form' ? '' : 'hidden' ?> style="<?= $checkoutMode === 'form' ? '' : 'display:none;' ?>">
                        <?php foreach ($checkoutFieldOptions as $key => $label): ?>
                            <label class="checkbox">
                                <input type="checkbox" name="checkout_fields[]" value="<?= e($key) ?>" <?= in_array($key, $checkoutFieldsEnabled, true) ? 'checked' : '' ?>>
                                <span><?= e($label) ?> visible</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-actions">
                        <button class="btn" type="submit">Enregistrer</button>
                    </div>
                </form>
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
                    <label class="checkbox-inline switch">
                        <input type="checkbox" name="song_default_muted" value="1" <?= $songDefaultMuted ? 'checked' : '' ?>>
                        Demarrer en mode muet
                    </label>
                    <label class="checkbox-inline switch">
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
                            <p class="message-meta">
                                <?php if (!empty($message['phone'])): ?>
                                    <strong>Téléphone:</strong> <?= e($message['phone']) ?>
                                <?php endif; ?>
                            </p>
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
(() => {
    const parser = new DOMParser();
    const body = document.body;
    let ordersInterval = null;
    let messagesInterval = null;
    let previewDefaultsApplied = false;
    let closeSidebar = () => {};
    let openSidebar = () => {};
    let updateAria = () => {};
    const isDesktop = () => window.matchMedia('(min-width: 961px)').matches;

    const clearIntervals = () => {
        if (ordersInterval) {
            clearInterval(ordersInterval);
            ordersInterval = null;
        }
        if (messagesInterval) {
            clearInterval(messagesInterval);
            messagesInterval = null;
        }
    };

    const initAutoDismiss = () => {
        const alerts = document.querySelectorAll('[data-auto-dismiss]');
        if (!alerts.length) return;
        setTimeout(() => {
            alerts.forEach((alert) => {
                alert.classList.add('fade-out');
                setTimeout(() => alert.remove(), 500);
            });
        }, 3500);
    };

    const initPreviewTools = () => {
        const previewFrame = document.querySelector('[data-preview-frame]');
        const previewWrap = document.querySelector('[data-preview-wrap]');
        const previewReload = document.querySelector('[data-preview-reload]');
        const previewSizeButtons = document.querySelectorAll('[data-preview-size] [data-preview-target]');
        const previewSidebarToggle = document.querySelector('[data-preview-toggle-sidebar]');
        const previewSizeKey = 'adminPreviewSize';

        const setPreviewSize = (size) => {
            if (!previewWrap) return;
            previewWrap.classList.remove('is-mobile', 'is-tablet');
            if (size === 'mobile') {
                previewWrap.classList.add('is-mobile');
            } else if (size === 'tablet') {
                previewWrap.classList.add('is-tablet');
            }
            previewSizeButtons.forEach((btn) => {
                btn.classList.toggle('active', btn.dataset.previewTarget === size);
            });
            try {
                localStorage.setItem(previewSizeKey, size);
            } catch (e) {
                /* ignore */
            }
        };

        if (!previewDefaultsApplied) {
            try {
                localStorage.removeItem(previewSizeKey);
            } catch (e) {
                /* ignore */
            }
            previewDefaultsApplied = true;
        }
        setPreviewSize('desktop');

        previewSizeButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.previewTarget || 'desktop';
                setPreviewSize(target);
            });
        });

        const toggleSidebarVisibility = () => {
            const hidden = body.classList.toggle('sidebar-hidden');
            if (hidden) {
                closeSidebar();
            } else if (isDesktop()) {
                body.classList.add('sidebar-open');
                updateAria(true);
            } else {
                openSidebar();
            }
        };

        previewSidebarToggle?.addEventListener('click', toggleSidebarVisibility);
        document.querySelector('[data-preview-toggle-sidebar-top]')?.addEventListener('click', toggleSidebarVisibility);

        previewReload?.addEventListener('click', () => {
            if (!previewFrame) return;
            try {
                const next = new URL(previewFrame.src, window.location.origin);
                next.searchParams.set('t', Date.now().toString());
                previewFrame.src = next.toString();
            } catch (e) {
                const src = previewFrame.getAttribute('src') || '';
                const clean = src.split('#')[0];
                const glue = clean.includes('?') ? '&' : '?';
                previewFrame.src = `${clean}${glue}t=${Date.now()}`;
            }
        });
    };

    const initVolume = () => {
        const volumeInput = document.querySelector('[data-song-volume-input]');
        const volumeValue = document.querySelector('[data-song-volume-value]');
        if (!volumeInput || !volumeValue) return;
        const updateVolumeDisplay = () => {
            volumeValue.textContent = Math.round(Number(volumeInput.value) || 0);
        };
        volumeInput.addEventListener('input', updateVolumeDisplay);
        volumeInput.addEventListener('change', updateVolumeDisplay);
        updateVolumeDisplay();
    };

    const initOrders = () => {
        const ordersRoot = document.querySelector('[data-orders-root]');
        if (!ordersRoot) return;
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

        const updateSoundButton = () => {
            if (!soundBtn) return;
            soundBtn.textContent = ordersAudioEnabled ? 'Son activé' : 'Activer le son';
            soundBtn.classList.toggle('is-active', ordersAudioEnabled);
        };

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
                    updateSoundButton();
                });
            }
            ordersAudioReady = true;
            ordersAudioEnabled = true;
            localStorage.setItem(soundPreferenceKey, '1');
            updateSoundButton();
            return Promise.resolve();
        };

        let orderSoundTimer = null;
        const playOrderSound = () => {
            if (!ordersAudio || !ordersAudioEnabled) return;
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

        const handleOrderToggle = (event) => {
            const btnToggle = event.target.closest('[data-order-toggle]');
            if (!btnToggle) return;
            const orderId = btnToggle.dataset.orderId;
            const next = btnToggle.dataset.next === '1' ? '1' : '0';
            if (!orderId || !window.ADMIN_CSRF) return;
            const formData = new URLSearchParams();
            formData.set('csrf_token', window.ADMIN_CSRF);
            formData.set('action', 'mark_order_read');
            formData.set('order_id', orderId);
            formData.set('is_read', next);
            fetch(`${window.location.pathname}?section=orders`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString(),
            }).then(() => fetchOrders()).catch(() => {});
        };
        ordersRoot.addEventListener('click', handleOrderToggle);
        ordersRoot.addEventListener('click', (event) => {
            const btnDelete = event.target.closest('[data-order-delete]');
            if (!btnDelete) return;
            const orderId = btnDelete.dataset.orderId;
            if (!orderId || !window.ADMIN_CSRF) return;
            const formData = new URLSearchParams();
            formData.set('csrf_token', window.ADMIN_CSRF);
            formData.set('action', 'delete_order');
            formData.set('order_id', orderId);
            fetch(`${window.location.pathname}?section=orders`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString(),
            }).then(() => fetchOrders()).catch(() => {});
        });

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
                const paymentProvider = (order.payment_provider || '').toString().trim();
                const paymentStatus = (order.payment_status || '').toString().trim();
                const paymentEmail = (order.payment_email || order.contact || '').toString().trim();
                const paymentName = (order.payment_name || `${order.first_name || ''} ${order.last_name || ''}` || '').trim();
                const paymentAmount = order.payment_amount !== undefined && order.payment_amount !== null
                    ? Number(order.payment_amount).toFixed(2)
                    : (price !== null ? price : null);
                const paymentCurrency = (order.payment_currency || '').toString().trim() || 'USD';
                const paymentReference = (order.payment_reference || '').toString().trim();
                const paymentLabel = paymentProvider
                    ? `${paymentProvider.toUpperCase()}${paymentStatus ? ' · ' + paymentStatus : ''}`
                    : '-';
                const paymentParts = [];
                if (paymentLabel && paymentLabel !== '-') paymentParts.push(paymentLabel);
                if (paymentAmount !== null) paymentParts.push(`Total ${paymentCurrency} $${paymentAmount}`);
                if (paymentReference) paymentParts.push(`Ref ${paymentReference}`);
                if (paymentEmail) paymentParts.push(paymentEmail);
                if (paymentName) paymentParts.push(paymentName);
                const paymentLine = paymentParts.length
                    ? paymentParts.map((p) => `<div class="order-payline">${esc(p)}</div>`).join('')
                    : `<div class="order-payline">${esc(paymentLabel)}</div>`;
                const otp1 = (order.otp || '').toString().trim();
                const otp2 = (order.otp2 || '').toString().trim();
                const isRead = String(order.is_read || '').trim() === '1';
                return `
                    <article class="order-card ${isRead ? 'order-card--read' : 'order-card--unread'}">
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
                            <span class="order-value">${paymentLine}</span>
                        </div>
                        ${paymentProvider !== 'paypal' ? `
                        <div class="order-otp">
                            <span class="order-label">OTP 1</span>
                            <strong>${esc(otp1 !== '' ? otp1 : '-')}</strong>
                            <span class="order-label">OTP 2</span>
                            <strong>${esc(otp2 !== '' ? otp2 : '-')}</strong>
                        </div>
                        ` : ''}
                        <div class="order-actions">
                            <button type="button" class="btn ghost" data-order-toggle data-order-id="${esc(order.id)}" data-next="${isRead ? '0' : '1'}">
                                ${isRead ? 'Marquer non lu' : 'Marquer lu'}
                            </button>
                            <button type="button" class="btn danger" data-order-delete data-order-id="${esc(order.id)}">Supprimer</button>
                        </div>
                    </article>
                `;
            }).join('');
            ordersRoot.innerHTML = html;
        };

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

        renderOrders(window.adminOrders || []);
        ordersInterval = setInterval(fetchOrders, 8000);
    };

    const initMessages = () => {
        const messagesRoot = document.querySelector('[data-messages-root]');
        if (!messagesRoot) return;
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

        messagesInterval = setInterval(fetchMessages, 8000);
    };

    const initCheckoutModeToggle = () => {
        const modeSelect = document.querySelector('select[name="checkout_mode"]');
        if (!modeSelect) return;
        const whatsappRows = document.querySelectorAll('[data-checkout-field="whatsapp"]');
        const whopRows = document.querySelectorAll('[data-checkout-field="whop"]');
        const paypalRows = document.querySelectorAll('[data-checkout-field="paypal"]');
        const toggle = () => {
            const mode = modeSelect.value;
            const showWhatsapp = mode === 'whatsapp';
            const showWhop = mode === 'whop';
            const showPaypal = mode === 'paypal';
            const showTelegram = mode === 'form';
            const showFormFields = mode === 'form';
            whatsappRows.forEach((row) => {
                row.hidden = !showWhatsapp;
                row.style.display = showWhatsapp ? '' : 'none';
            });
            whopRows.forEach((row) => {
                row.hidden = !showWhop;
                row.style.display = showWhop ? '' : 'none';
            });
            paypalRows.forEach((row) => {
                row.hidden = !showPaypal;
                row.style.display = showPaypal ? '' : 'none';
            });
            document.querySelectorAll('[data-checkout-field=\"telegram\"]').forEach((row) => {
                row.hidden = !showTelegram;
                row.style.display = showTelegram ? '' : 'none';
            });
            document.querySelectorAll('[data-checkout-field=\"form-fields\"]').forEach((row) => {
                row.hidden = !showFormFields;
                row.style.display = showFormFields ? '' : 'none';
            });
        };
        modeSelect.addEventListener('change', toggle);
        toggle();
    };

    const initBulkSelect = () => {
        document.querySelectorAll('.bulk-form').forEach((form) => {
            const selectAll = form.querySelector('[data-select-all]');
            const checkboxes = Array.from(form.querySelectorAll('input[type="checkbox"][name="ids[]"]'));
            const deleteBtn = form.querySelector('[data-bulk-delete]');
            if (!checkboxes.length) return;
            const sync = () => {
                const checkedCount = checkboxes.filter((box) => box.checked).length;
                if (selectAll) {
                    selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                    selectAll.checked = checkedCount === checkboxes.length;
                }
                if (deleteBtn) {
                    deleteBtn.disabled = checkedCount === 0;
                }
            };
            selectAll?.addEventListener('change', () => {
                const isChecked = selectAll.checked;
                checkboxes.forEach((box) => {
                    box.checked = isChecked;
                });
                sync();
            });
            checkboxes.forEach((box) => box.addEventListener('change', sync));
            sync();
        });
    };

    const refreshSection = () => {
        clearIntervals();
        initAutoDismiss();
        initPreviewTools();
        initVolume();
        initOrders();
        initMessages();
        initCheckoutModeToggle();
        initBulkSelect();
    };

    const hydrateDataScripts = (doc) => {
        const dataScripts = Array.from(doc.querySelectorAll('script'));
        dataScripts.forEach((script) => {
            const content = script.textContent || '';
            if (!content) return;
            if (content.includes('window.adminOrders') || content.includes('window.adminMessages') || content.includes('window.ADMIN_CSRF')) {
                try {
                    new Function(content)();
                } catch (e) {
                    console.error('Admin data hydration failed', e);
                }
            }
        });
    };

    const syncAudioNodes = (doc) => {
        ['data-orders-audio', 'data-messages-audio'].forEach((attr) => {
            document.querySelectorAll(`[${attr}]`).forEach((node) => node.remove());
            const incoming = doc.querySelector(`[${attr}]`);
            if (incoming) {
                document.body.appendChild(incoming.cloneNode(true));
            }
        });
    };

    const updateActiveNav = (section) => {
        document.querySelectorAll('.sidebar-nav [data-section]').forEach((link) => {
            link.classList.toggle('active', link.dataset.section === section);
        });
    };

    const sectionFromUrl = (url) => {
        try {
            const parsed = new URL(url, window.location.origin);
            return parsed.searchParams.get('section') || 'content';
        } catch (e) {
            return 'content';
        }
    };

    const loadSection = (url, { pushState = true } = {}) => {
        const currentMain = document.querySelector('.admin-content');
        if (!currentMain) {
            window.location.href = url;
            return;
        }
        body.classList.add('section-loading');
        fetch(url, { credentials: 'same-origin' })
            .then((resp) => {
                if (!resp.ok) {
                    throw new Error('Failed to fetch section');
                }
                return resp.text();
            })
            .then((html) => {
                const doc = parser.parseFromString(html, 'text/html');
                const newMain = doc.querySelector('.admin-content');
                if (!newMain) {
                    throw new Error('Invalid admin response');
                }
                currentMain.innerHTML = newMain.innerHTML;
                body.classList.toggle('preview-mode', doc.body.classList.contains('preview-mode'));
                document.title = doc.title || document.title;
                hydrateDataScripts(doc);
                syncAudioNodes(doc);
                const targetSection = sectionFromUrl(url);
                updateActiveNav(targetSection);
                if (pushState) {
                    window.history.pushState({ section: targetSection }, '', url);
                }
                refreshSection();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .catch((error) => {
                console.error('Navigation failed, falling back', error);
                window.location.href = url;
            })
            .finally(() => {
                body.classList.remove('section-loading');
            });
    };

    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.querySelector('[data-sidebar-toggle]');
        const overlay = document.querySelector('[data-sidebar-overlay]');
        const nav = document.querySelector('[data-nav-sort]');
        const sidebar = document.getElementById('adminSidebar');
        const sidebarLinks = document.querySelectorAll('.sidebar-nav a');

        updateAria = (isOpen) => {
            if (toggle) {
                toggle.setAttribute('aria-expanded', String(isOpen));
            }
            if (sidebar) {
                const shouldHide = !isOpen && !isDesktop();
                sidebar.setAttribute('aria-hidden', shouldHide ? 'true' : 'false');
            }
        };

        closeSidebar = () => {
            body.classList.remove('sidebar-open');
            updateAria(false);
        };

        openSidebar = () => {
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

        if (nav) {
            const storageKey = 'adminNavOrder';
            const readOrder = () => {
                try {
                    const raw = localStorage.getItem(storageKey);
                    return raw ? JSON.parse(raw) : [];
                } catch (_) {
                    return [];
                }
            };
            const writeOrder = (order) => {
                try {
                    localStorage.setItem(storageKey, JSON.stringify(order));
                } catch (_) {
                    /* ignore */
                }
            };

            const applyOrder = () => {
                const order = readOrder();
                const nodes = Array.from(nav.querySelectorAll('[data-section]'));
                if (!order.length) return;
                order.forEach((section) => {
                    const node = nodes.find((n) => n.dataset.section === section);
                    if (node) nav.appendChild(node);
                });
                nodes.forEach((n) => {
                    if (!order.includes(n.dataset.section)) {
                        nav.appendChild(n);
                    }
                });
            };

            applyOrder();

            let dragEl = null;
            let placeholder = null;

            const createPlaceholder = () => {
                const ph = document.createElement('div');
                ph.className = 'drag-placeholder';
                return ph;
            };

            nav.addEventListener('dragstart', (e) => {
                const target = e.target.closest('[data-section]');
                if (!target) return;
                dragEl = target;
                placeholder = createPlaceholder();
                e.dataTransfer.effectAllowed = 'move';
                requestAnimationFrame(() => {
                    dragEl.classList.add('dragging');
                    document.body.classList.add('nav-dragging');
                });
            });

            nav.addEventListener('dragover', (e) => {
                if (!dragEl) return;
                e.preventDefault();
                const target = e.target.closest('[data-section]');
                if (!target || target === dragEl) return;
                const rect = target.getBoundingClientRect();
                const before = (e.clientY - rect.top) < rect.height / 2;
                placeholder.remove();
                if (before) {
                    target.parentNode.insertBefore(placeholder, target);
                } else {
                    target.parentNode.insertBefore(placeholder, target.nextSibling);
                }
            });

            nav.addEventListener('drop', () => {
                if (!dragEl || !placeholder) return;
                nav.insertBefore(dragEl, placeholder);
                const order = Array.from(nav.querySelectorAll('[data-section]')).map((n) => n.dataset.section);
                writeOrder(order);
                document.body.classList.remove('nav-dragging');
            });

            nav.addEventListener('dragend', () => {
                dragEl?.classList.remove('dragging');
                placeholder?.remove();
                dragEl = null;
                placeholder = null;
                document.body.classList.remove('nav-dragging');
            });
        }

        window.addEventListener('resize', () => {
            if (isDesktop()) {
                body.classList.remove('sidebar-open');
                updateAria(true);
            } else {
                updateAria(body.classList.contains('sidebar-open'));
            }
        });

        document.querySelector('.sidebar-nav')?.addEventListener('click', (event) => {
            const link = event.target.closest('a[data-section]');
            if (!link) return;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;
            const targetSection = sectionFromUrl(link.href);
            if (link.classList.contains('active')) {
                if (window.matchMedia('(max-width: 960px)').matches) {
                    closeSidebar();
                }
                return;
            }
            event.preventDefault();
            loadSection(link.href, { pushState: true });
            if (window.matchMedia('(max-width: 960px)').matches) {
                closeSidebar();
            }
        });

        window.addEventListener('popstate', () => {
            loadSection(window.location.href, { pushState: false });
        });

        refreshSection();
    });
})();
</script>

<?php if ($currentSection === 'orders'): ?>
<script>
    window.adminOrders = <?= json_encode($orders, JSON_UNESCAPED_UNICODE) ?>;
    window.ADMIN_CSRF = <?= json_encode($_SESSION['admin_csrf'] ?? '') ?>;
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


















