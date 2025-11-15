<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$config = require __DIR__ . '/config.php';

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['db']['host'], $config['db']['name'], $config['db']['charset']);

try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Database connection failed: ' . $exception->getMessage());
}

initializeDatabase($pdo, $config);

return $pdo;
