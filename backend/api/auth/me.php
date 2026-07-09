<?php
/** GET /backend/api/auth/me.php — returns the logged-in user, or 401 if not logged in */
require_once __DIR__ . '/../../includes/auth.php';

$userId = current_user_id();
if (!$userId) {
    send_json(['success' => false, 'message' => 'Not logged in.'], 401);
}

$user = get_public_user($pdo, $userId);
if (!$user) {
    send_json(['success' => false, 'message' => 'User not found.'], 404);
}

send_json(['success' => true, 'user' => $user]);
