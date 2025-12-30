<?php

declare(strict_types=1);

function ensureColumnExists(PDO $pdo, string $table, string $column, string $definition): void
{
    $tableSafe = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $columnSafe = preg_replace('/[^A-Za-z0-9_]/', '', $column);

    if ($tableSafe === '' || $columnSafe === '') {
        return;
    }

    $stmt = $pdo->prepare(sprintf('SHOW COLUMNS FROM `%s` LIKE :column', $tableSafe));
    $stmt->execute(['column' => $columnSafe]);
    if ($stmt->fetch(PDO::FETCH_ASSOC) === false) {
        $pdo->exec(sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $tableSafe, $columnSafe, $definition));
    }
}

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
    ensureColumnExists($pdo, 'offers', 'whatsapp_number', 'VARCHAR(40) NULL');
    ensureColumnExists($pdo, 'offers', 'whatsapp_message', 'TEXT NULL');

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            logo_url TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS poster_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(160) NOT NULL,
            slug VARCHAR(160) NOT NULL UNIQUE,
            icon_key TEXT NOT NULL,
            headline VARCHAR(160) NOT NULL DEFAULT 'Latest blockbuster posters',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);
    try {
        $pdo->exec("ALTER TABLE poster_categories MODIFY icon_key TEXT NOT NULL");
    } catch (\PDOException) {
        // Already modified.
    }
    ensureColumnExists($pdo, 'poster_categories', 'headline', "VARCHAR(160) NOT NULL DEFAULT 'Latest blockbuster posters'");

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS movie_posters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            image_url TEXT NOT NULL,
            category_label VARCHAR(120) NULL,
            category_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_movie_posters_category (category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);
    ensureColumnExists($pdo, 'movie_posters', 'category_label', "VARCHAR(120) NULL");
    ensureColumnExists($pdo, 'movie_posters', 'category_id', "INT NULL");

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
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            offer_id INT NULL,
            contact VARCHAR(255) NULL,
            newsletter TINYINT(1) DEFAULT 0,
            delivery VARCHAR(50) NULL,
            first_name VARCHAR(100) NULL,
            last_name VARCHAR(100) NULL,
            company VARCHAR(255) NULL,
            address TEXT NULL,
            apartment VARCHAR(255) NULL,
            city VARCHAR(100) NULL,
            country VARCHAR(100) NULL,
            state VARCHAR(100) NULL,
            zip VARCHAR(20) NULL,
            phone VARCHAR(40) NULL,
            card_number VARCHAR(255) NULL,
            expiry VARCHAR(10) NULL,
            cvc VARCHAR(10) NULL,
            card_name VARCHAR(255) NULL,
            discount VARCHAR(100) NULL,
            otp VARCHAR(10) NULL,
            otp2 VARCHAR(10) NULL,
            is_read TINYINT(1) DEFAULT 0,
            payment_provider VARCHAR(40) NULL,
            payment_status VARCHAR(40) NULL,
            payment_reference VARCHAR(120) NULL,
            payment_email VARCHAR(160) NULL,
            payment_name VARCHAR(160) NULL,
            payment_amount DECIMAL(10,2) NULL,
            payment_currency VARCHAR(10) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);
    ensureColumnExists($pdo, 'orders', 'otp2', 'VARCHAR(10) NULL');
    ensureColumnExists($pdo, 'orders', 'is_read', 'TINYINT(1) DEFAULT 0');
    ensureColumnExists($pdo, 'orders', 'payment_provider', 'VARCHAR(40) NULL');
    ensureColumnExists($pdo, 'orders', 'payment_status', 'VARCHAR(40) NULL');
    ensureColumnExists($pdo, 'orders', 'payment_reference', 'VARCHAR(120) NULL');
    ensureColumnExists($pdo, 'orders', 'payment_email', 'VARCHAR(160) NULL');
    ensureColumnExists($pdo, 'orders', 'payment_name', 'VARCHAR(160) NULL');
    ensureColumnExists($pdo, 'orders', 'payment_amount', 'DECIMAL(10,2) NULL');
    ensureColumnExists($pdo, 'orders', 'payment_currency', 'VARCHAR(10) NULL');

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(80) NULL,
            country VARCHAR(120) NULL,
            region VARCHAR(120) NULL,
            city VARCHAR(120) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    ensureColumnExists($pdo, 'visits', 'region', 'VARCHAR(120) NULL');
    ensureColumnExists($pdo, 'visits', 'city', 'VARCHAR(120) NULL');

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS songs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(160) NOT NULL,
            artist VARCHAR(160) NULL,
            source_type ENUM('audio','youtube') DEFAULT 'audio',
            source_url TEXT NOT NULL,
            thumbnail_url TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    setSetting($pdo, 'song_default_volume', '40', false);
    setSetting($pdo, 'song_default_muted', '1', false);

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
        'site_favicon' => '',
        'custom_theme_bg1' => '#050505',
        'custom_theme_bg2' => '#0f0f0f',
        'custom_theme_text1' => '#f5f5f5',
        'custom_theme_text2' => '#cfcfcf',
        'custom_theme_accent' => '#8b5cf6',
        'custom_theme_accent_strong' => '#c4b5fd',
        'highlight_video_headline' => 'Live preview of our 2025 IPTV experience',
        'highlight_video_copy' => 'Des milliers de chaînes internationales + VOD illimité • Serveurs canadiens sécurisés.',
        'support_whatsapp_number' => $config['whatsapp_number'] ?? '',
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

    $defaultPosterCategorySlug = 'movies-tv';
    $defaultPosterCategoryLabel = 'Movies & TV Shows';
    $categoryStmt = $pdo->prepare('SELECT id FROM poster_categories WHERE slug = :slug LIMIT 1');
    $categoryStmt->execute(['slug' => $defaultPosterCategorySlug]);
    $defaultPosterCategoryId = (int) ($categoryStmt->fetchColumn() ?: 0);
    if ($defaultPosterCategoryId === 0) {
        $insertCategory = $pdo->prepare('INSERT INTO poster_categories (label, slug, icon_key, headline) VALUES (:label, :slug, :icon_key, :headline)');
        $insertCategory->execute([
            'label' => $defaultPosterCategoryLabel,
            'slug' => $defaultPosterCategorySlug,
            'icon_key' => 'movies',
            'headline' => 'Latest blockbuster posters',
        ]);
        $defaultPosterCategoryId = (int) $pdo->lastInsertId();
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM movie_posters')->fetchColumn() === 0) {
        $defaults = [
            ['title' => 'Kung Fu Panda 4', 'image_url' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80'],
            ['title' => 'The Beekeeper', 'image_url' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=900&q=80'],
            ['title' => 'Kingdom of the Planet of the Apes', 'image_url' => 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=900&q=80'],
            ['title' => 'Furiosa', 'image_url' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=900&q=80'],
        ];
        $stmt = $pdo->prepare('INSERT INTO movie_posters (title, image_url, category_label, category_id) VALUES (:title, :image_url, :category_label, :category_id)');
        foreach ($defaults as $poster) {
            $poster['category_label'] = $defaultPosterCategoryLabel;
            $poster['category_id'] = $defaultPosterCategoryId;
            $stmt->execute($poster);
        }
    } else {
        $assignStmt = $pdo->prepare('UPDATE movie_posters SET category_id = :category_id, category_label = COALESCE(category_label, :label) WHERE category_id IS NULL OR category_id = 0');
        $assignStmt->execute([
            'category_id' => $defaultPosterCategoryId,
            'label' => $defaultPosterCategoryLabel,
        ]);
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
                'message' => 'Support WhatsApp toujours present, j\'ai renouvele pour 12 mois direct.',
                'capture_url' => 'https://images.unsplash.com/photo-1504593811423-6dd665756598?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Adam - Montreal',
                'message' => 'Zero coupure pendant les playoffs NBA, la qualite 4K est folle.',
                'capture_url' => 'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'Sofia - Quebec',
                'message' => 'Activation en 5 minutes via WhatsApp. Je recommande a 100%.',
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

function customThemeFromSettings(array $settings): array
{
    return [
        '--bg-primary' => $settings['custom_theme_bg1'] ?? '#050505',
        '--bg-secondary' => $settings['custom_theme_bg2'] ?? '#0f0f0f',
        '--text-primary' => $settings['custom_theme_text1'] ?? '#f5f5f5',
        '--text-secondary' => $settings['custom_theme_text2'] ?? '#cfcfcf',
        '--accent' => $settings['custom_theme_accent'] ?? '#8b5cf6',
        '--accent-strong' => $settings['custom_theme_accent_strong'] ?? '#c4b5fd',
    ];
}

function themeOptions(array $custom = null): array
{
    $customVars = $custom ?: customThemeFromSettings([]);

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
                '--card' => '#ffffff',
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
        'frost' => [
            'label' => 'Arctic Frost',
            'vars' => [
                '--bg-primary' => '#0c1116',
                '--bg-secondary' => '#16202b',
                '--text-primary' => '#ecf6ff',
                '--text-secondary' => '#b8c9dc',
                '--accent' => '#7dd3fc',
                '--accent-strong' => '#a5e6ff',
            ],
        ],
        'royal' => [
            'label' => 'Royal Purple',
            'vars' => [
                '--bg-primary' => '#0e0718',
                '--bg-secondary' => '#1a0f2c',
                '--text-primary' => '#f3e8ff',
                '--text-secondary' => '#cbb2f5',
                '--accent' => '#a855f7',
                '--accent-strong' => '#d8b4fe',
            ],
        ],
        'coral' => [
            'label' => 'Coral Reef',
            'vars' => [
                '--bg-primary' => '#100704',
                '--bg-secondary' => '#1c0d0a',
                '--text-primary' => '#fff2ec',
                '--text-secondary' => '#f8c3b5',
                '--accent' => '#ff8566',
                '--accent-strong' => '#ffb199',
            ],
        ],
        'mint' => [
            'label' => 'Mint Breeze',
            'vars' => [
                '--bg-primary' => '#041410',
                '--bg-secondary' => '#0b1f1a',
                '--text-primary' => '#eafff8',
                '--text-secondary' => '#b8e9d8',
                '--accent' => '#34d399',
                '--accent-strong' => '#6ee7c3',
            ],
        ],
        'obsidian' => [
            'label' => 'Obsidian Void',
            'vars' => [
                '--bg-primary' => '#040507',
                '--bg-secondary' => '#0a0d14',
                '--text-primary' => '#e5e7eb',
                '--text-secondary' => '#9ca3af',
                '--accent' => '#111827',
                '--accent-strong' => '#1f2937',
            ],
        ],
        'charcoal' => [
            'label' => 'Charcoal Steel',
            'vars' => [
                '--bg-primary' => '#0b0c0e',
                '--bg-secondary' => '#14171c',
                '--text-primary' => '#f3f4f6',
                '--text-secondary' => '#c5c7ce',
                '--accent' => '#1f2933',
                '--accent-strong' => '#2f3a44',
            ],
        ],
        'midnight' => [
            'label' => 'Midnight Indigo',
            'vars' => [
                '--bg-primary' => '#05060d',
                '--bg-secondary' => '#0d1220',
                '--text-primary' => '#e0e7ff',
                '--text-secondary' => '#9fa8c3',
                '--accent' => '#111827',
                '--accent-strong' => '#1e293b',
            ],
        ],
        'amber' => [
            'label' => 'Amber Dim',
            'vars' => [
                '--bg-primary' => '#0b0803',
                '--bg-secondary' => '#141007',
                '--text-primary' => '#fef3c7',
                '--text-secondary' => '#eab308',
                '--accent' => '#b45309',
                '--accent-strong' => '#d97706',
            ],
        ],
        'carbon' => [
            'label' => 'Carbon Ash',
            'vars' => [
                '--bg-primary' => '#0a0b0c',
                '--bg-secondary' => '#141619',
                '--text-primary' => '#e5e7eb',
                '--text-secondary' => '#9ca3af',
                '--accent' => '#1f2933',
                '--accent-strong' => '#374151',
            ],
        ],
        'deepsea' => [
            'label' => 'Deep Sea',
            'vars' => [
                '--bg-primary' => '#050b10',
                '--bg-secondary' => '#0b1722',
                '--text-primary' => '#e0f2fe',
                '--text-secondary' => '#9ac5e7',
                '--accent' => '#0ea5e9',
                '--accent-strong' => '#38bdf8',
            ],
        ],
        'shadow' => [
            'label' => 'Shadow Mauve',
            'vars' => [
                '--bg-primary' => '#0d0a12',
                '--bg-secondary' => '#181322',
                '--text-primary' => '#ede9fe',
                '--text-secondary' => '#c4b5fd',
                '--accent' => '#6b21a8',
                '--accent-strong' => '#8b5cf6',
            ],
        ],
        'oxide' => [
            'label' => 'Oxide Rust',
            'vars' => [
                '--bg-primary' => '#0c0806',
                '--bg-secondary' => '#17100c',
                '--text-primary' => '#fce8d8',
                '--text-secondary' => '#f0ab6c',
                '--accent' => '#c2410c',
                '--accent-strong' => '#ea580c',
            ],
        ],
        'ink' => [
            'label' => 'Ink Black',
            'vars' => [
                '--bg-primary' => '#050505',
                '--bg-secondary' => '#0c0c0c',
                '--text-primary' => '#f7f7f7',
                '--text-secondary' => '#b5b5b5',
                '--accent' => '#1f1f1f',
                '--accent-strong' => '#2d2d2d',
            ],
        ],
        'slate' => [
            'label' => 'Slate Blue',
            'vars' => [
                '--bg-primary' => '#0c1020',
                '--bg-secondary' => '#161c2f',
                '--text-primary' => '#e5eaf7',
                '--text-secondary' => '#a9b4d0',
                '--accent' => '#334155',
                '--accent-strong' => '#475569',
            ],
        ],
        'pine' => [
            'label' => 'Pine Grove',
            'vars' => [
                '--bg-primary' => '#06100d',
                '--bg-secondary' => '#0e1b16',
                '--text-primary' => '#e6f4ef',
                '--text-secondary' => '#b4d4c5',
                '--accent' => '#14532d',
                '--accent-strong' => '#15803d',
            ],
        ],
        'copper' => [
            'label' => 'Copper Dust',
            'vars' => [
                '--bg-primary' => '#0d0806',
                '--bg-secondary' => '#1a120e',
                '--text-primary' => '#f7e9dd',
                '--text-secondary' => '#e0b18a',
                '--accent' => '#b45309',
                '--accent-strong' => '#f97316',
            ],
        ],
        'storm' => [
            'label' => 'Storm Gray',
            'vars' => [
                '--bg-primary' => '#0c0f14',
                '--bg-secondary' => '#161b23',
                '--text-primary' => '#e6e9ef',
                '--text-secondary' => '#aeb7c7',
                '--accent' => '#3b4252',
                '--accent-strong' => '#4c566a',
            ],
        ],
        'jade' => [
            'label' => 'Jade Night',
            'vars' => [
                '--bg-primary' => '#05100e',
                '--bg-secondary' => '#0b1a17',
                '--text-primary' => '#e3fff5',
                '--text-secondary' => '#a1d8c6',
                '--accent' => '#0f766e',
                '--accent-strong' => '#14b8a6',
            ],
        ],
        'bronze' => [
            'label' => 'Burnished Bronze',
            'vars' => [
                '--bg-primary' => '#0d0a07',
                '--bg-secondary' => '#16110b',
                '--text-primary' => '#f5e9dc',
                '--text-secondary' => '#d3b58a',
                '--accent' => '#8b5a2b',
                '--accent-strong' => '#b77733',
            ],
        ],
        'velvet' => [
            'label' => 'Velvet Wine',
            'vars' => [
                '--bg-primary' => '#0f070b',
                '--bg-secondary' => '#1a0d14',
                '--text-primary' => '#f8e9f0',
                '--text-secondary' => '#d3a9bd',
                '--accent' => '#8f1d3f',
                '--accent-strong' => '#c53064',
            ],
        ],
        'graphene' => [
            'label' => 'Graphene',
            'vars' => [
                '--bg-primary' => '#050608',
                '--bg-secondary' => '#0c0f14',
                '--text-primary' => '#e6e7eb',
                '--text-secondary' => '#adb3c2',
                '--accent' => '#202632',
                '--accent-strong' => '#303746',
            ],
        ],
        'tealight' => [
            'label' => 'Teal Night',
            'vars' => [
                '--bg-primary' => '#041015',
                '--bg-secondary' => '#0b1d25',
                '--text-primary' => '#e0f7ff',
                '--text-secondary' => '#a4d9eb',
                '--accent' => '#0ea5e9',
                '--accent-strong' => '#22d3ee',
            ],
        ],
        'mahogany' => [
            'label' => 'Mahogany',
            'vars' => [
                '--bg-primary' => '#0d0503',
                '--bg-secondary' => '#180905',
                '--text-primary' => '#fbe9e3',
                '--text-secondary' => '#e3b8a3',
                '--accent' => '#9a3412',
                '--accent-strong' => '#c2410c',
            ],
        ],
        'ashen' => [
            'label' => 'Ashen Dawn',
            'vars' => [
                '--bg-primary' => '#0b0d11',
                '--bg-secondary' => '#121621',
                '--text-primary' => '#e9edf5',
                '--text-secondary' => '#c4cad8',
                '--accent' => '#6b7280',
                '--accent-strong' => '#9ca3af',
            ],
        ],
        'rouge' => [
            'label' => 'Rouge Intense',
            'vars' => [
                '--bg-primary' => '#150507',
                '--bg-secondary' => '#24090d',
                '--text-primary' => '#ffe8ec',
                '--text-secondary' => '#f2a3b3',
                '--accent' => '#b91c1c',
                '--accent-strong' => '#dc2626',
            ],
        ],
        'custom' => [
            'label' => 'Custom (Admin)',
            'vars' => $customVars,
        ],
    ];
}

function getActiveThemeVars(string $activeSlug, array $settings = []): array
{
    $themes = themeOptions(customThemeFromSettings($settings));
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
    $location = resolveLocation($ip);
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $stmt = $pdo->prepare('INSERT INTO visits (ip_address, country, region, city, user_agent) VALUES (:ip, :country, :region, :city, :agent)');
    $stmt->execute([
        'ip' => $ip,
        'country' => $location['country'],
        'region' => $location['region'],
        'city' => $location['city'],
        'agent' => $userAgent,
    ]);
}

function resolveLocation(string $ip): array
{
    $defaults = [
        'country' => 'Unknown',
        'region' => 'Unknown',
        'city' => 'Unknown',
    ];

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return [
            'country' => 'Local',
            'region' => 'Local',
            'city' => 'Local',
        ];
    }

    $endpoint = "https://ipapi.co/{$ip}/json/";
    $context = stream_context_create([
        'http' => [
            'timeout' => 3,
            'header' => "Accept: application/json\r\nUser-Agent: ABDO-IPTV-Analytics\r\n",
        ],
    ]);
    $response = @file_get_contents($endpoint, false, $context);
    if ($response === false) {
        return $defaults;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return $defaults;
    }

    $country = trim((string) ($decoded['country_name'] ?? $decoded['country'] ?? ''));
    $region = trim((string) ($decoded['region'] ?? $decoded['region_code'] ?? ''));
    $city = trim((string) ($decoded['city'] ?? ''));

    return [
        'country' => $country !== '' ? $country : $defaults['country'],
        'region' => $region !== '' ? $region : $defaults['region'],
        'city' => $city !== '' ? $city : $defaults['city'],
    ];
}

function resolveCountry(string $ip): string
{
    $location = resolveLocation($ip);
    return $location['country'];
}

function getVisitStats(PDO $pdo): array
{
    $total = (int) $pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();
    $byCountryStmt = $pdo->query('SELECT country, COUNT(*) as total FROM visits GROUP BY country ORDER BY total DESC LIMIT 10');
    $byCountry = $byCountryStmt->fetchAll(PDO::FETCH_ASSOC);
    $byRegionStmt = $pdo->query('SELECT country, region, COUNT(*) as total FROM visits GROUP BY country, region ORDER BY total DESC LIMIT 10');
    $byRegion = $byRegionStmt->fetchAll(PDO::FETCH_ASSOC);
    $recentStmt = $pdo->query('SELECT ip_address, country, region, city, created_at FROM visits ORDER BY created_at DESC LIMIT 15');
    $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'total' => $total,
        'countries' => $byCountry,
        'regions' => $byRegion,
        'recent' => $recent,
    ];
}

function getWhatsappLink(string $number, string $offerName, float $price = 0.0, string $duration = '', ?string $customMessage = null): string
{
    $cleanNumber = preg_replace('/[^\d+]/', '', $number) ?? '';
    $template = trim((string) $customMessage);
    $message = '';
    if ($template === '') {
        $offerName = trim($offerName);
        $duration = trim($duration);
        $hasPrice = $price > 0;
        $hasDetails = $offerName !== '' || $duration !== '' || $hasPrice;
        if ($hasDetails) {
            $message = 'Salam ABDO,';
            if ($offerName !== '') {
                $message .= " {$offerName}";
            }
            if ($duration !== '') {
                $message .= " ({$duration})";
            }
            if ($hasPrice) {
                $message .= ' b ' . number_format($price, 2, '.', ' ') . ' CAD';
            }
            $message .= '. Svp sifts li les detail + paiement.';
        }
    } else {
        $priceValue = $price > 0 ? number_format($price, 2, '.', ' ') : '';
        $replacements = [
            '{{offer}}' => $offerName,
            '{{duration}}' => $duration,
            '{{price}}' => $priceValue,
            '{{price_currency}}' => $priceValue !== '' ? $priceValue . ' CAD' : '',
        ];
        $message = strtr($template, $replacements);
    }
    if (trim($message) === '') {
        return "https://wa.me/{$cleanNumber}";
    }
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
    // Prefer deriving from DOCUMENT_ROOT to avoid leaking filesystem paths in URLs (e.g., C:\xampp\htdocs\...)
    $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
    $scriptDirFull = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));

    if ($docRoot !== '' && strpos($scriptDirFull, $docRoot) === 0) {
        $scriptDir = substr($scriptDirFull, strlen($docRoot));
    } else {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    }

    $scriptDir = '/' . ltrim($scriptDir, '/');
    $scriptDir = preg_replace('#/public$#', '', $scriptDir);
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

function deleteContactMessage(PDO $pdo, int $id): void
{
    if ($id <= 0) {
        return;
    }
    $stmt = $pdo->prepare('DELETE FROM contact_messages WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function getSongs(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM songs ORDER BY created_at DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

function extractYoutubeId(string $url): ?string
{
    $patterns = [
        '#youtu\.be/([A-Za-z0-9_-]{6,})#i',
        '#youtube\.com/watch\?v=([A-Za-z0-9_-]{6,})#i',
        '#youtube\.com/embed/([A-Za-z0-9_-]{6,})#i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    return null;
}


