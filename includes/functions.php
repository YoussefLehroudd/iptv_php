<?php

declare(strict_types=1);

function initializeDatabase(PDO $pdo, array $config): void
{
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(160) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(160) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS sliders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            subtitle VARCHAR(255) NULL,
            media_url TEXT NULL,
            media_type ENUM('image','video') DEFAULT 'image',
            cta_label VARCHAR(120) NULL,
            cta_description VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS offers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            duration VARCHAR(120) NOT NULL,
            description TEXT NULL,
            features TEXT NULL,
            is_featured TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            logo_url TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS movie_posters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            image_url TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS sport_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            image_url TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS videos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            url TEXT NOT NULL,
            thumbnail_url TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            message TEXT NULL,
            capture_url TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL,
            phone VARCHAR(40) NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_read TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(80) NULL,
            country VARCHAR(120) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    // Ensure default admin
    $adminEmail = $config['admin']['email'] ?? 'admin@iptvabdo.com';
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
    $stmt->execute(['email' => $adminEmail]);
    if ((int) $stmt->fetchColumn() === 0) {
        $passwordHash = password_hash($config['admin']['password'] ?? 'Canada#2025', PASSWORD_BCRYPT);
        $insert = $pdo->prepare('INSERT INTO users (email, password) VALUES (:email, :password)');
        $insert->execute([
            'email' => $adminEmail,
            'password' => $passwordHash,
        ]);
    }

    // Default settings
    $defaults = [
        'hero_title' => 'IPTV 2025 VERSION POUR CANADA',
        'hero_subtitle' => 'Premium 4K/Full HD channels, VOD & sports packages sécurisés pour Canada & diaspora.',
        'hero_cta' => 'Découvrir les offres',
        'seo_title' => 'ABDO IPTV Canada | Premium IPTV Accounts avec Paiement WhatsApp',
        'seo_description' => 'ABDO IPTV Canada propose les meilleurs bouquets IPTV 4K avec paiement sécurisé via WhatsApp. Offres flexibles, support 24/7 et panel admin avancé.',
        'active_theme' => 'onyx',
        'highlight_video_headline' => 'Live preview of our 2025 IPTV experience',
        'highlight_video_copy' => 'Des milliers de chaînes internationales + VOD illimité • Serveurs canadiens sécurisés.',
    ];

    foreach ($defaults as $key => $value) {
        setSetting($pdo, $key, $value, false);
    }

    // Seed slider
    if ((int) $pdo->query('SELECT COUNT(*) FROM sliders')->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO sliders (title, subtitle, media_url, media_type, cta_label, cta_description) VALUES (:title, :subtitle, :media_url, :media_type, :cta_label, :cta_description)')->execute([
            'title' => 'Streaming sans coupure',
            'subtitle' => 'Serveurs 10Gb dédiés + anti-freeze',
            'media_url' => 'https://res.cloudinary.com/dziwz75h6/image/upload/e_background_removal/f_png/v1763174007/t%C3%A9l%C3%A9chargement_1_ruskkt.jpg',
            'media_type' => 'image',
            'cta_label' => 'Activer mon pass',
            'cta_description' => 'Paiement en un clic via WhatsApp',
        ]);
        $pdo->prepare('INSERT INTO sliders (title, subtitle, media_url, media_type, cta_label, cta_description) VALUES (:title, :subtitle, :media_url, :media_type, :cta_label, :cta_description)')->execute([
            'title' => 'Netflix, Prime, Apple TV',
            'subtitle' => 'VOD + séries 2025 mis à jour chaque jour',
            'media_url' => 'https://res.cloudinary.com/demo/video/upload/dog.mp4',
            'media_type' => 'video',
            'cta_label' => 'Tester la qualité',
            'cta_description' => 'Clips de démo instantanés',
        ]);
    }
    $pdo->prepare('UPDATE sliders SET media_url = :new WHERE media_url = :old')->execute([
        'new' => 'https://res.cloudinary.com/dziwz75h6/image/upload/e_background_removal/f_png/v1763174007/t%C3%A9l%C3%A9chargement_1_ruskkt.jpg',
        'old' => 'https://res.cloudinary.com/demo/image/upload/v1700000000/iptv/hero-black.webp',
    ]);
    $pdo->prepare('UPDATE sliders SET media_url = :new WHERE media_url = :old')->execute([
        'new' => 'https://res.cloudinary.com/demo/video/upload/dog.mp4',
        'old' => 'https://res.cloudinary.com/demo/video/upload/v1700000001/iptv/promo.mp4',
    ]);

    // Seed offers
    if ((int) $pdo->query('SELECT COUNT(*) FROM offers')->fetchColumn() === 0) {
        $offers = [
            [
                'name' => 'STARTER 1 MOIS',
                'price' => 16.99,
                'duration' => '30 jours multi-device',
                'description' => 'Idéal pour tester la stabilité premium au Canada.',
                'features' => "+12000 chaînes internationales\nAnti-freeze AI\nSupport WhatsApp 24/7",
                'is_featured' => 0,
            ],
            [
                'name' => 'PRO 6 MOIS',
                'price' => 74.99,
                'duration' => '180 jours UHD',
                'description' => 'La formule la plus vendue pour streamers réguliers.',
                'features' => "4K & Dolby Atmos\nReplay 7 jours\nApps Android / iOS / Smart TV",
                'is_featured' => 1,
            ],
            [
                'name' => 'VIP 12 MOIS',
                'price' => 129.99,
                'duration' => '365 jours full access',
                'description' => 'Support pro + multi devices + panel famille.',
                'features' => "2 connexions simultanées\nVOD + séries illimité\nGarantie uptime 99.9%",
                'is_featured' => 0,
            ],
        ];
        $stmt = $pdo->prepare('INSERT INTO offers (name, price, duration, description, features, is_featured) VALUES (:name, :price, :duration, :description, :features, :is_featured)');
        foreach ($offers as $offer) {
            $stmt->execute($offer);
        }
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM providers')->fetchColumn() === 0) {
        $providers = [
            ['name' => 'DAZN', 'logo_url' => 'https://dummyimage.com/200x80/0b0b0b/f5f5f5&text=DAZN'],
            ['name' => 'BeIN SPORTS', 'logo_url' => 'https://dummyimage.com/200x80/111111/f5f5f5&text=BeIN'],
            ['name' => 'CANAL+', 'logo_url' => 'https://dummyimage.com/200x80/050505/f5f5f5&text=CANAL%2B'],
            ['name' => 'Apple TV+', 'logo_url' => 'https://dummyimage.com/200x80/1a1a1a/fefefe&text=Apple+TV%2B'],
        ];
        $stmt = $pdo->prepare('INSERT INTO providers (name, logo_url) VALUES (:name, :logo_url)');
        foreach ($providers as $provider) {
            $stmt->execute($provider);
        }
    }
    $providerUpdates = [
        'https://res.cloudinary.com/demo/image/upload/v1700000002/iptv/dazn_white.png' => 'https://dummyimage.com/200x80/0b0b0b/f5f5f5&text=DAZN',
        'https://res.cloudinary.com/demo/image/upload/v1700000003/iptv/bein.png' => 'https://dummyimage.com/200x80/111111/f5f5f5&text=BeIN',
        'https://res.cloudinary.com/demo/image/upload/v1700000004/iptv/canal.png' => 'https://dummyimage.com/200x80/050505/f5f5f5&text=CANAL%2B',
        'https://res.cloudinary.com/demo/image/upload/v1700000005/iptv/appletv.png' => 'https://dummyimage.com/200x80/1a1a1a/fefefe&text=Apple+TV%2B',
    ];
    foreach ($providerUpdates as $old => $newUrl) {
        $stmt = $pdo->prepare('UPDATE providers SET logo_url = :new WHERE logo_url = :old');
        $stmt->execute(['new' => $newUrl, 'old' => $old]);
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM movie_posters')->fetchColumn() === 0) {
        $defaults = [
            ['title' => 'Kung Fu Panda 4', 'image_url' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80'],
            ['title' => 'The Beekeeper', 'image_url' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=900&q=80'],
            ['title' => 'Kingdom of the Planet of the Apes', 'image_url' => 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=900&q=80'],
            ['title' => 'Furiosa', 'image_url' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=900&q=80'],
        ];
        $stmt = $pdo->prepare('INSERT INTO movie_posters (title, image_url) VALUES (:title, :image_url)');
        foreach ($defaults as $poster) {
            $stmt->execute($poster);
        }
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM sport_events')->fetchColumn() === 0) {
        $defaults = [
            ['title' => 'Formula 1', 'image_url' => 'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=900&q=80'],
            ['title' => 'NBA Playoffs', 'image_url' => 'https://images.unsplash.com/photo-1519860433024-3b7ee302e518?auto=format&fit=crop&w=900&q=80'],
            ['title' => 'Euro 2024', 'image_url' => 'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=900&q=80'],
            ['title' => 'UFC Fight Night', 'image_url' => 'https://images.unsplash.com/photo-1521410195597-46b98096d2f2?auto=format&fit=crop&w=900&q=80'],
        ];
        $stmt = $pdo->prepare('INSERT INTO sport_events (title, image_url) VALUES (:title, :image_url)');
        foreach ($defaults as $event) {
            $stmt->execute($event);
        }
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn() === 0) {
        $defaults = [
            [
                'name' => 'Nadia - Ottawa',
                'message' => 'Support WhatsApp toujours pr�sent, j\'ai renouvel� pour 12 mois direct.',
                'capture_url' => 'https://images.unsplash.com/photo-1504593811423-6dd665756598?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Adam - Montr�al',
                'message' => 'Zero coupure pendant les playoffs NBA, la qualit� 4K est folle.',
                'capture_url' => 'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Sofia - Qu�bec',
                'message' => 'Activation en 5 minutes via WhatsApp. Je recommande � 100%.',
                'capture_url' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=800&q=80',
            ],
        ];
        $stmt = $pdo->prepare('INSERT INTO testimonials (name, message, capture_url) VALUES (:name, :message, :capture_url)');
        foreach ($defaults as $testimonial) {
            $stmt->execute($testimonial);
        }
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM videos')->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO videos (title, description, url, thumbnail_url) VALUES (:title, :description, :url, :thumbnail_url)')->execute([
            'title' => 'Démo 4K Ultra IPTV',
            'description' => 'Aperçu en 40 secondes de la fluidité multi-support 2025.',
            'url' => 'https://www.youtube.com/embed/pSc6JXq8O4w',
            'thumbnail_url' => 'https://res.cloudinary.com/demo/image/upload/v1700000006/iptv/demo-thumb.webp',
        ]);
    }
}

function setSetting(PDO $pdo, string $key, string $value, bool $overwrite = true): void
{
    if ($overwrite) {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE setting_value = :value2');
        $stmt->execute(['key' => $key, 'value' => $value, 'value2' => $value]);
        return;
    }

    $stmt = $pdo->prepare('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (:key, :value)');
    $stmt->execute(['key' => $key, 'value' => $value]);
}

function getSettings(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
    $settings = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

function themeOptions(): array
{
    return [
        'onyx' => [
            'label' => 'Onyx Black (Default)',
            'vars' => [
                '--bg-primary' => '#040404',
                '--bg-secondary' => '#0f0f0f',
                '--text-primary' => '#f5f5f5',
                '--text-secondary' => '#d0d0d0',
                '--accent' => '#e0e0e0',
                '--accent-strong' => '#ffffff',
            ],
        ],
        'titanium' => [
            'label' => 'Titanium Silver',
            'vars' => [
                '--bg-primary' => '#0a0c0f',
                '--bg-secondary' => '#151821',
                '--text-primary' => '#fcfcfc',
                '--text-secondary' => '#bcc5d2',
                '--accent' => '#9da5b4',
                '--accent-strong' => '#e5e7eb',
            ],
        ],
        'graphite' => [
            'label' => 'Graphite Contrast',
            'vars' => [
                '--bg-primary' => '#080808',
                '--bg-secondary' => '#121212',
                '--text-primary' => '#ffffff',
                '--text-secondary' => '#c4c4c4',
                '--accent' => '#888888',
                '--accent-strong' => '#d6d6d6',
            ],
        ],
        'smoke' => [
            'label' => 'Smoked Glass',
            'vars' => [
                '--bg-primary' => '#050607',
                '--bg-secondary' => '#1a1a1a',
                '--text-primary' => '#fdfdfd',
                '--text-secondary' => '#d7d7d7',
                '--accent' => '#a0a0a0',
                '--accent-strong' => '#f0f0f0',
            ],
        ],
        'pure' => [
            'label' => 'Pure White Mix',
            'vars' => [
                '--bg-primary' => '#f5f7fb',
                '--bg-secondary' => '#ffffff',
                '--text-primary' => '#050505',
                '--text-secondary' => '#3a3a3a',
                '--accent' => '#5e5e5e',
                '--accent-strong' => '#0c0c0c',
            ],
        ],
        'noirwave' => [
            'label' => 'Noir Wave',
            'vars' => [
                '--bg-primary' => '#020305',
                '--bg-secondary' => '#11151c',
                '--text-primary' => '#e8edf2',
                '--text-secondary' => '#9aa0a6',
                '--accent' => '#595f66',
                '--accent-strong' => '#f8f9fb',
            ],
        ],
        'sunset' => [
            'label' => 'Sunset Orange',
            'vars' => [
                '--bg-primary' => '#120704',
                '--bg-secondary' => '#1f0b05',
                '--text-primary' => '#fff2ec',
                '--text-secondary' => '#f8c1a8',
                '--accent' => '#ff7a45',
                '--accent-strong' => '#ffb491',
            ],
        ],
        'pacific' => [
            'label' => 'Pacific Blue',
            'vars' => [
                '--bg-primary' => '#02080f',
                '--bg-secondary' => '#08192a',
                '--text-primary' => '#e8f4ff',
                '--text-secondary' => '#9cc1e8',
                '--accent' => '#4da3ff',
                '--accent-strong' => '#73b9ff',
            ],
        ],
        'emerald' => [
            'label' => 'Emerald Green',
            'vars' => [
                '--bg-primary' => '#03100a',
                '--bg-secondary' => '#072217',
                '--text-primary' => '#e9fff3',
                '--text-secondary' => '#a0d9b8',
                '--accent' => '#34d399',
                '--accent-strong' => '#6ee7b7',
            ],
        ],
        'sunbeam' => [
            'label' => 'Sunbeam Yellow',
            'vars' => [
                '--bg-primary' => '#0e0a02',
                '--bg-secondary' => '#1a1303',
                '--text-primary' => '#fffde7',
                '--text-secondary' => '#f7e6a1',
                '--accent' => '#facc15',
                '--accent-strong' => '#fde047',
            ],
        ],
        'crimson' => [
            'label' => 'Crimson Rouge',
            'vars' => [
                '--bg-primary' => '#140307',
                '--bg-secondary' => '#22050c',
                '--text-primary' => '#ffe8ed',
                '--text-secondary' => '#f4a6b8',
                '--accent' => '#ff4d6d',
                '--accent-strong' => '#ff809b',
            ],
        ],
    ];
}

function getActiveThemeVars(string $activeSlug): array
{
    $themes = themeOptions();
    return $themes[$activeSlug]['vars'] ?? $themes['onyx']['vars'];
}

function uploadToCloudinary(?string $filePath, string $folder, array $cloudinaryConfig): ?string
{
    if (!$filePath || !is_readable($filePath)) {
        return null;
    }

    $cloudName = $cloudinaryConfig['cloud_name'] ?? null;
    $apiKey = $cloudinaryConfig['api_key'] ?? null;
    $apiSecret = $cloudinaryConfig['api_secret'] ?? null;

    if (!$cloudName || !$apiKey || !$apiSecret) {
        return null;
    }

    $timestamp = time();
    $paramsToSign = [
        'folder' => $folder,
        'timestamp' => $timestamp,
    ];
    ksort($paramsToSign);
    $signatureBase = http_build_query($paramsToSign);
    $signature = sha1(urldecode($signatureBase) . $apiSecret);

    $postFields = [
        'file' => curl_file_create($filePath),
        'api_key' => $apiKey,
        'timestamp' => $timestamp,
        'folder' => $folder,
        'signature' => $signature,
    ];

    if (!empty($cloudinaryConfig['upload_preset'])) {
        $postFields['upload_preset'] = $cloudinaryConfig['upload_preset'];
    }

    $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/auto/upload");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        curl_close($ch);
        return null;
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status >= 200 && $status < 300) {
        $decoded = json_decode($response, true);
        return $decoded['secure_url'] ?? null;
    }

    return null;
}

function logVisit(PDO $pdo): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';
    $ip = explode(',', $ip)[0];
    $country = resolveCountry($ip);
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $stmt = $pdo->prepare('INSERT INTO visits (ip_address, country, user_agent) VALUES (:ip, :country, :agent)');
    $stmt->execute([
        'ip' => $ip,
        'country' => $country,
        'agent' => $userAgent,
    ]);
}

function resolveCountry(string $ip): string
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return 'Local';
    }

    $endpoint = "https://ipapi.co/{$ip}/country_name/";
    $context = stream_context_create(['http' => ['timeout' => 2]]);
    $country = @file_get_contents($endpoint, false, $context);
    $country = trim((string) $country);

    return $country !== '' ? $country : 'Unknown';
}

function getVisitStats(PDO $pdo): array
{
    $total = (int) $pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();
    $byCountryStmt = $pdo->query('SELECT country, COUNT(*) as total FROM visits GROUP BY country ORDER BY total DESC LIMIT 10');
    $byCountry = $byCountryStmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'total' => $total,
        'countries' => $byCountry,
    ];
}

function getWhatsappLink(string $number, string $offerName, float $price = 0.0, string $duration = ''): string
{
    $cleanNumber = preg_replace('/[^\d+]/', '', $number);
    $message = "Salam ABDO, bghit n9tar offer {$offerName} ({$duration}) b {$price} CAD. " .
        'Svp sifts li les detail + paiement.';
    $encoded = urlencode($message);
    return "https://wa.me/{$cleanNumber}?text={$encoded}";
}

function fetchAllAssoc(PDO $pdo, string $query): array
{
    return $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

function formatCurrency(float $value): string
{
    return number_format($value, 2, '.', ' ');
}

function appBasePath(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = preg_replace('#/abdo_admin$#', '', $scriptDir);
    if ($scriptDir === '/' || $scriptDir === '\\') {
        return '';
    }
    return rtrim($scriptDir, '/');
}

function requireAdmin(): void
{
    session_start();
    if (empty($_SESSION['admin_id'])) {
        $basePath = appBasePath();
        header('Location: ' . $basePath . '/abdo_admin/index.php');
        exit;
    }
}

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function splitFeatures(?string $features): array
{
    $list = preg_split('/\r?\n/', (string) $features);
    return array_values(array_filter(array_map('trim', $list)));
}

function slugify(string $value): string
{
    $value = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $value)));
    return trim($value, '-');
}

function getPrimaryVideo(PDO $pdo): ?array
{
    $stmt = $pdo->query('SELECT * FROM videos ORDER BY created_at DESC LIMIT 1');
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    return $video ?: null;
}

function getContactMessages(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 50');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markMessageAsRead(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function deleteRecord(PDO $pdo, string $table, int $id): void
{
    $allowed = ['offers', 'sliders', 'providers', 'videos', 'movie_posters', 'sport_events', 'testimonials'];
    if (!in_array($table, $allowed, true)) {
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = :id");
    $stmt->execute(['id' => $id]);
}
