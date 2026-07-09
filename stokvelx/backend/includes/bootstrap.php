<?php
/**
 * StokvelX – Bootstrap
 * Included at the top of every API endpoint.
 * Handles sessions, response headers and small helper functions.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

// Basic CORS support (only needed if you load the frontend from a different
// origin/port than the backend, e.g. VS Code Live Server). Same-origin
// requests through XAMPP/WAMP/MAMP do not need this at all.
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
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

/** Read JSON body (or fall back to $_POST for form submissions). */
function get_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data)) {
        return $data;
    }
    return $_POST;
}

/** Generate a unique-ish referral code from a first name, e.g. "SIPHO42". */
function generate_referral_code(string $firstName): string {
    $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $firstName));
    $base = $base !== '' ? substr($base, 0, 8) : 'USER';
    return $base . random_int(10, 99);
}

/** Simple required-fields validator. Returns list of missing field names. */
function missing_fields(array $input, array $required): array {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}
