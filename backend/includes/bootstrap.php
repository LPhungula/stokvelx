<?php
/**
 * StokvelX – Bootstrap
 * Handles sessions, CORS, headers and helper functions.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

// CORS — allow Netlify frontend and local development
$allowed_origins = [
    'https://stokvelx.netlify.app',
    'https://stirring-dodol-b8831a.netlify.app',
    'http://localhost',
    'http://localhost:3000',
    'http://127.0.0.1',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';

/** Send a JSON response and stop execution. */
function send_json($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/** Read JSON body or fall back to $_POST. */
function get_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data)) return $data;
    return $_POST;
}

/** Generate a unique referral code from a first name. */
function generate_referral_code(string $firstName): string {
    $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $firstName));
    $base = $base !== '' ? substr($base, 0, 8) : 'USER';
    return $base . random_int(10, 99);
}

/** Returns list of missing required fields. */
function missing_fields(array $input, array $required): array {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}
