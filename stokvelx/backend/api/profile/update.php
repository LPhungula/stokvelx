<?php
/**
 * GET  /backend/api/profile/update.php  → returns current profile + bank details
 * POST /backend/api/profile/update.php  → updates allowed fields
 *
 * Allowed POST body fields:
 *   first_name, last_name, phone, university, student_number
 *   bank_name, account_number, account_type  (creates/updates bank_details row)
 *   current_password, new_password           (password change, both required together)
 */
require_once __DIR__ . '/../../includes/auth.php';

$userId = require_login();

// ── GET: return profile ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = get_public_user($pdo, $userId);
    $stmt = $pdo->prepare('SELECT bank_name, account_number, account_type, verified FROM bank_details WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $bank = $stmt->fetch();

    send_json([
        'success' => true,
        'user'    => $user,
        'bank'    => $bank ?: null,
    ]);
}

// ── POST: update profile ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$input = get_input();

// --- User table updates ---
$userUpdates = [];
$userParams  = [];
$allowed = ['first_name', 'last_name', 'phone', 'university', 'student_number'];
foreach ($allowed as $field) {
    if (isset($input[$field]) && trim($input[$field]) !== '') {
        $userUpdates[] = "$field = ?";
        $userParams[]  = trim($input[$field]);
    }
}

// Password change
if (!empty($input['new_password'])) {
    if (empty($input['current_password'])) {
        send_json(['success' => false, 'message' => 'Please provide your current password to set a new one.'], 422);
    }
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();
    if (!password_verify($input['current_password'], $hash)) {
        send_json(['success' => false, 'message' => 'Current password is incorrect.'], 401);
    }
    if (strlen($input['new_password']) < 6) {
        send_json(['success' => false, 'message' => 'New password must be at least 6 characters.'], 422);
    }
    $userUpdates[] = 'password_hash = ?';
    $userParams[]  = password_hash($input['new_password'], PASSWORD_BCRYPT);
}

if ($userUpdates) {
    $userParams[] = $userId;
    $pdo->prepare('UPDATE users SET ' . implode(', ', $userUpdates) . ' WHERE id = ?')
        ->execute($userParams);
}

// --- Bank details ---
if (!empty($input['bank_name']) || !empty($input['account_number'])) {
    $stmt = $pdo->prepare('SELECT id FROM bank_details WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare('UPDATE bank_details SET bank_name = ?, account_number = ?, account_type = ? WHERE user_id = ?');
        $stmt->execute([
            trim($input['bank_name'] ?? ''),
            trim($input['account_number'] ?? ''),
            trim($input['account_type'] ?? 'Cheque / Current'),
            $userId,
        ]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO bank_details (user_id, bank_name, account_number, account_type, verified) VALUES (?, ?, ?, ?, 0)');
        $stmt->execute([
            $userId,
            trim($input['bank_name'] ?? ''),
            trim($input['account_number'] ?? ''),
            trim($input['account_type'] ?? 'Cheque / Current'),
        ]);
    }
}

send_json([
    'success' => true,
    'message' => 'Profile updated successfully.',
    'user'    => get_public_user($pdo, $userId),
]);
