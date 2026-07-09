<?php
/**
 * StokvelX – Database Connection
 * -------------------------------
 * Edit these 4 values to match your local MySQL setup.
 * Default values below work out-of-the-box with XAMPP / WAMP / MAMP
 * (root user, no password, default port).
 */

$DB_HOST = 'localhost';
$DB_NAME = 'stokvelx';
$DB_USER = 'root';
$DB_PASS = '';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Make sure MySQL is running and the "stokvelx" database has been imported. (' . $e->getMessage() . ')',
    ]);
    exit;
}
