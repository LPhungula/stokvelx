<?php
/**
 * POST /backend/api/chat/send.php
 * Body: circle_id, message
 * User must be a member of the circle.
 */
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$userId = require_login();
$input  = get_input();

$circleId = (int)($input['circle_id'] ?? 0);
$message  = trim($input['message'] ?? '');

if (!$circleId || $message === '') {
    send_json(['success' => false, 'message' => 'circle_id and message are required.'], 422);
}
if (mb_strlen($message) > 1000) {
    send_json(['success' => false, 'message' => 'Message must be 1 000 characters or fewer.'], 422);
}

// Must be a member
$stmt = $pdo->prepare('SELECT id FROM circle_members WHERE circle_id = ? AND user_id = ?');
$stmt->execute([$circleId, $userId]);
if (!$stmt->fetch()) {
    send_json(['success' => false, 'message' => 'You are not a member of this circle.'], 403);
}

$stmt = $pdo->prepare('INSERT INTO chat_messages (circle_id, user_id, message) VALUES (?, ?, ?)');
$stmt->execute([$circleId, $userId, $message]);
$newId = (int)$pdo->lastInsertId();

$stmt = $pdo->prepare('SELECT cm.id, cm.message, cm.created_at, u.first_name, u.last_name
                        FROM chat_messages cm JOIN users u ON u.id = cm.user_id
                        WHERE cm.id = ?');
$stmt->execute([$newId]);
$row = $stmt->fetch();

send_json([
    'success' => true,
    'message' => [
        'id'         => (int)$row['id'],
        'message'    => $row['message'],
        'created_at' => $row['created_at'],
        'user_id'    => $userId,
        'name'       => $row['first_name'] . ' ' . $row['last_name'],
        'is_mine'    => true,
    ],
]);
