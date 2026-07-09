<?php
/**
 * StokvelX – Database Connection
 * Reads credentials from Railway environment variables automatically.
 * Falls back to XAMPP localhost defaults for local development.
 */

$DB_HOST    = getenv('MYSQLHOST')     ?: 'localhost';
$DB_NAME    = getenv('MYSQLDATABASE') ?: 'stokvelx';
$DB_USER    = getenv('MYSQLUSER')     ?: 'root';
$DB_PASS    = getenv('MYSQLPASSWORD') ?: '';
$DB_PORT    = getenv('MYSQLPORT')     ?: '3306';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset={$DB_CHARSET}";

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
        'message' => 'Database connection failed. (' . $e->getMessage() . ')',
    ]);
    exit;
}
