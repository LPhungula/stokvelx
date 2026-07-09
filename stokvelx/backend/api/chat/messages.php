<?php
/**
 * GET /backend/api/chat/messages.php?circle_id=1&after=0
 * Returns chat messages for a circle in ascending order.
 * Pass ?after=<last_message_id> to only fetch new messages (for polling).
 * User must be a member of the circle.
 */
require_once __DIR__ . '/../../includes/auth.php';

$userId   = require_login();
$circleId = (int)($_GET['circle_id'] ?? 0);
$after    = (int)($_GET['after'] ?? 0);

if (!$circleId) {
    send_json(['success' => false, 'message' => 'circle_id is required.'], 422);
}

// Check the user is actually a member of this circle
$stmt = $pdo->prepare('SELECT id FROM circle_members WHERE circle_id = ? AND user_id = ?');
$stmt->execute([$circleId, $userId]);
if (!$stmt->fetch()) {
    send_json(['success' => false, 'message' => 'You are not a member of this circle.'], 403);
}

$stmt = $pdo->prepare('SELECT cm.id, cm.message, cm.created_at,
                               u.id AS user_id, u.first_name, u.last_name
                        FROM chat_messages cm
                        JOIN users u ON u.id = cm.user_id
                        WHERE cm.circle_id = ? AND cm.id > ?
                        ORDER BY cm.created_at ASC
                        LIMIT 100');
$stmt->execute([$circleId, $after]);
$messages = $stmt->fetchAll();

$result = array_map(function ($m) use ($userId) {
    return [
        'id'         => (int)$m['id'],
        'message'    => $m['message'],
        'created_at' => $m['created_at'],
        'user_id'    => (int)$m['user_id'],
        'name'       => $m['first_name'] . ' ' . $m['last_name'],
        'is_mine'    => (int)$m['user_id'] === (int)$userId,
    ];
}, $messages);

send_json(['success' => true, 'messages' => $result]);
