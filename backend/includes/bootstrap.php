<?php
/**
 * StokvelX – Bootstrap
 */

// CORS headers - must be set before ANYTHING else including session_start
$allowed_origins = [
    'https://stokvelx.netlify.app',
    'https://stirring-dodol-b8831a.netlify.app',
    'http://localhost',
    'http://localhost:3000',
    'http://127.0.0.1',
    'http://localhost:80',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Always set CORS for allowed origins
if (in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin, true);
    header('Access-Control-Allow-Credentials: true', true);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS', true);
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept', true);
    header('Access-Control-Max-Age: 3600', true);
}

// Handle preflight OPTIONS request immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/db.php';

function send_json($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function get_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data)) return $data;
    return $_POST;
}

function generate_referral_code(string $firstName): string {
    $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $firstName));
    $base = $base !== '' ? substr($base, 0, 8) : 'USER';
    return $base . random_int(10, 99);
}

function missing_fields(array $input, array $required): array {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}
