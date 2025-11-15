<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
$config = require __DIR__ . '/../config/config.php';
/** @var PDO $pdo */
$pdo = require __DIR__ . '/../config/database.php';
requireAdmin();

$basePath = appBasePath();
$adminBase = $basePath . '/abdo_admin';

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
            $feedback[] = 'Contenu mis à jour.';
            break;
        case 'update_theme':
            $theme = $_POST['theme'] ?? 'onyx';
            if (isset(themeOptions()[$theme])) {
                setSetting($pdo, 'active_theme', $theme);
                $feedback[] = 'Thème changé.';
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
                $feedback[] = 'Slider ajouté.';
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
            $feedback[] = 'Offre créée.';
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
                $feedback[] = 'Provider ajouté.';
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
            $feedback[] = 'Vidéo enregistrée.';
            break;
        case 'mark_message':
            markMessageAsRead($pdo, (int) $_POST['message_id']);
            $feedback[] = 'Message marqué comme lu.';
            break;
    }
}

if (isset($_GET['delete'], $_GET['id'])) {
    deleteRecord($pdo, preg_replace('/[^a-z_]/', '', $_GET['delete']), (int) $_GET['id']);
    header('Location: ' . $adminBase . '/dashboard.php?section=' . urlencode($currentSection) . '&deleted=1');
    exit;
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
        <a class="btn ghost" href="<?= $basePath ?>/" target="_blank" rel="noopener">Voir le site</a>
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
            <div class="alert success"><?= e(implode(' · ', $feedback)) ?></div>
        <?php elseif (isset($_GET['deleted'])): ?>
            <div class="alert success">Élément supprimé.</div>
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
                            <input type="radio" name="theme" value="<?= e($slug) ?>" <?= ($settings['active_theme'] ?? 'onyx') === $slug ? 'checked' : '' ?>>
                            <span><?= e($theme['label']) ?></span>
                        </label>
                    <?php endforeach; ?>
                    <button class="btn" type="submit">Changer le thème</button>
                </form>
            </section>
        <?php elseif ($currentSection === 'slider'): ?>
            <section class="admin-section">
                <h2>Slider hero</h2>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=slider" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="add_slider">
                    <label>Titre
                        <input type="text" name="title" required>
                    </label>
                    <label>Sous-titre
                        <input type="text" name="subtitle">
                    </label>
                    <label>Type media
                        <select name="media_type">
                            <option value="image">Image</option>
                            <option value="video">Vidéo</option>
                        </select>
                    </label>
                    <label>Upload media
                        <input type="file" name="media_file">
                    </label>
                    <label>Ou URL media
                        <input type="url" name="media_url" placeholder="https://">
                    </label>
                    <label>CTA label
                        <input type="text" name="cta_label">
                    </label>
                    <label>CTA description
                        <input type="text" name="cta_description">
                    </label>
                    <button class="btn" type="submit">Ajouter</button>
                </form>
                <div class="list">
                    <?php foreach ($sliders as $slider): ?>
                        <article>
                            <div>
                                <strong><?= e($slider['title']) ?></strong>
                                <small><?= e($slider['media_type']) ?></small>
                            </div>
                            <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=slider&delete=sliders&id=<?= (int) $slider['id'] ?>">Supprimer</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($currentSection === 'offers'): ?>
            <section class="admin-section">
                <h2>Offres IPTV</h2>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=offers">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="add_offer">
                    <label>Nom offre
                        <input type="text" name="name" required>
                    </label>
                    <label>Prix CAD
                        <input type="number" step="0.01" name="price" required>
                    </label>
                    <label>Durée
                        <input type="text" name="duration" required>
                    </label>
                    <label>Description
                        <textarea name="description" rows="2"></textarea>
                    </label>
                    <label>Features (1 par ligne)
                        <textarea name="features" rows="3"></textarea>
                    </label>
                    <label>
                        <input type="checkbox" name="is_featured"> Mettre en avant
                    </label>
                    <button class="btn" type="submit">Ajouter l'offre</button>
                </form>
                <div class="list">
                    <?php foreach ($offers as $offer): ?>
                        <article>
                            <div>
                                <strong><?= e($offer['name']) ?></strong>
                                <small><?= e(formatCurrency((float) $offer['price'])) ?> CAD</small>
                            </div>
                            <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=offers&delete=offers&id=<?= (int) $offer['id'] ?>">Supprimer</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($currentSection === 'providers'): ?>
            <section class="admin-section">
                <h2>Providers</h2>
                <form method="POST" action="<?= $adminBase ?>/dashboard.php?section=providers" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="add_provider">
                    <label>Nom
                        <input type="text" name="name" required>
                    </label>
                    <label>Logo upload
                        <input type="file" name="logo_file">
                    </label>
                    <label>ou URL
                        <input type="url" name="logo_url" placeholder="https://">
                    </label>
                    <button class="btn" type="submit">Ajouter</button>
                </form>
                <div class="list">
                    <?php foreach ($providers as $provider): ?>
                        <article>
                            <strong><?= e($provider['name']) ?></strong>
                            <a class="link-light" href="<?= $adminBase ?>/dashboard.php?section=providers&delete=providers&id=<?= (int) $provider['id'] ?>">Supprimer</a>
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
</body>
</html>
