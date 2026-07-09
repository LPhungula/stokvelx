<?php
/**
 * StokvelX – Auth helpers
 * Include this (instead of bootstrap.php directly) in any endpoint that
 * needs to know who is logged in, or that should be restricted.
 */

require_once __DIR__ . '/bootstrap.php';

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/** Stops the request with a 401 if nobody is logged in. Returns the user id. */
function require_login(): int {
    $id = current_user_id();
    if (!$id) {
        send_json(['success' => false, 'message' => 'Please log in to continue.'], 401);
    }
    return $id;
}

/** Stops the request with a 403 if the logged-in user is not an admin. */
function require_admin(): int {
    global $pdo;
    $id = require_login();
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $role = $stmt->fetchColumn();
    if ($role !== 'admin') {
        send_json(['success' => false, 'message' => 'Admin access only.'], 403);
    }
    return $id;
}

/** Fetch a user row by id, with the password hash stripped out. */
function get_public_user(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, phone, university,
                            student_number, role, referral_code, wallet_balance, kyc_verified, created_at
                            FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}
