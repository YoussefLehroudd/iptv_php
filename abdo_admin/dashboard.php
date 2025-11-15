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

$navItems = [
    'content' => ['label' => 'Hero & SEO', 'icon' => '📝'],
    'theme' => ['label' => 'Thème & couleurs', 'icon' => '🎨'],
    'slider' => ['label' => 'Slider hero', 'icon' => '🖼️'],
    'offers' => ['label' => 'Offres IPTV', 'icon' => '💳'],
    'providers' => ['label' => 'Providers', 'icon' => '📺'],
    'video' => ['label' => 'Vidéo highlight', 'icon' => '🎬'],
    'messages' => ['label' => 'Messages', 'icon' => '💬'],
    'analytics' => ['label' => 'Analytics', 'icon' => '📈'],
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
            adminFlashRedirect('Contenu mis à jour.', 'content', $adminBase);
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
                        adminFlashRedirect('Slider mis à jour.', 'slider', $adminBase);
                    }
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
                        adminFlashRedirect('Provider mis à jour.', 'providers', $adminBase);
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
$video = getPrimaryVideo($pdo);
$messages = getContactMessages($pdo);
$visitStats = getVisitStats($pdo);
$themes = themeOptions();

$editing = ['sliders' => null, 'offers' => null, 'providers' => null];
if (isset($_GET['edit'], $_GET['id'])) {
    $table = preg_replace('/[^a-z_]/', '', $_GET['edit']);
    $id = (int) $_GET['id'];
    $collections = [
        'sliders' => $sliders,
        'offers' => $offers,
        'providers' => $providers,
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Panel ABDO IPTV</title>
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body class="admin">
<header class="admin-bar">
    <div>
        <h1>Panel ABDO IPTV</h1>
        <p>Contrôle complet du contenu 2025</p>
    </div>
    <div>
        <span><?= e($_SESSION['admin_email']) ?></span>
        <a class="btn ghost" href="<?= $adminBase ?>/logout.php">Déconnexion</a>
    </div>
</header>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-logo">ABDO IPTV <small>Ultra IPTV · Canada</small></div>
        <nav class="sidebar-nav">
            <?php foreach ($navItems as $slug => $item): ?>
                <a class="<?= $currentSection === $slug ? 'active' : '' ?>" href="<?= $adminBase ?>/dashboard.php?section=<?= $slug ?>">
                    <span class="icon"><?= e($item['icon']) ?></span>
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
                <div class="list">
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
});
</script>
</body>
</html>
