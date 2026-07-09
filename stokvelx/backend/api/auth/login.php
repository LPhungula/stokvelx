<?php
/**
 * POST /backend/api/auth/login.php
 * Body: email, password
 */
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$input = get_input();
$missing = missing_fields($input, ['email', 'password']);
if ($missing) {
    send_json(['success' => false, 'message' => 'Please enter both email and password.'], 422);
}

$email    = trim(strtolower($input['email']));
$password = $input['password'];

$stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    send_json(['success' => false, 'message' => 'Incorrect email or password.'], 401);
}

$_SESSION['user_id'] = (int)$user['id'];

send_json([
    'success' => true,
    'message' => 'Login successful.',
    'user' => get_public_user($pdo, (int)$user['id']),
]);
