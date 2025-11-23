<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'iptv_abdo',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'whatsapp_number' => getenv('WHATSAPP_NUMBER') ?: '+212644819899',
    'brand_name' => getenv('BRAND_NAME') ?: 'ABDO IPTV CANADA',
    'cloudinary' => [
        'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME') ?: 'dziwz75h6',
        'api_key' => getenv('CLOUDINARY_API_KEY') ?: '619322372577237',
        'api_secret' => getenv('CLOUDINARY_API_SECRET') ?: 'MnFhGZ0BYXUC5xsOseUs7TZw_-M',
        'upload_preset' => getenv('CLOUDINARY_UPLOAD_PRESET') ?: null,
    ],
    'order_sound' => getenv('ORDER_SOUND') ?: 'config/iphone_new_message.mp3',
    'admin' => [
        'email' => getenv('ADMIN_EMAIL') ?: 'admin@iptvabdo.com',
        'password' => getenv('ADMIN_PASSWORD') ?: 'Canada#2025',
    ],
];
