<?php
/**
 * POST /backend/api/circles/join.php
 * Body: circle_id
 * Adds the logged-in user to a circle at the next available position and
 * creates a pending contribution for the circle's current week so they can
 * pay it straight away.
 */
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$userId = require_login();
$input = get_input();

if (empty($input['circle_id'])) {
    send_json(['success' => false, 'message' => 'circle_id is required.'], 422);
}
$circleId = (int)$input['circle_id'];

$stmt = $pdo->prepare('SELECT * FROM circles WHERE id = ? AND status = "active"');
$stmt->execute([$circleId]);
$circle = $stmt->fetch();
if (!$circle) {
    send_json(['success' => false, 'message' => 'Circle not found or no longer active.'], 404);
}

// Already a member?
$stmt = $pdo->prepare('SELECT id FROM circle_members WHERE circle_id = ? AND user_id = ? AND status = "active"');
$stmt->execute([$circleId, $userId]);
if ($stmt->fetch()) {
    send_json(['success' => false, 'message' => 'You are already a member of this circle.'], 409);
}

// Is there space?
$stmt = $pdo->prepare('SELECT COUNT(*) FROM circle_members WHERE circle_id = ? AND status = "active"');
$stmt->execute([$circleId]);
$memberCount = (int)$stmt->fetchColumn();
if ($memberCount >= (int)$circle['max_members']) {
    send_json(['success' => false, 'message' => 'This circle is full.'], 409);
}

$pdo->beginTransaction();
try {
    // Next position
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM circle_members WHERE circle_id = ?');
    $stmt->execute([$circleId]);
    $position = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('INSERT INTO circle_members (circle_id, user_id, position, status) VALUES (?, ?, ?, "active")');
    $stmt->execute([$circleId, $userId, $position]);

    // Figure out the circle's current contribution week (or start at week 1)
    $stmt = $pdo->prepare('SELECT week_number, due_date FROM contributions WHERE circle_id = ? ORDER BY week_number DESC LIMIT 1');
    $stmt->execute([$circleId]);
    $current = $stmt->fetch();
    $weekNumber = $current ? (int)$current['week_number'] : 1;
    $dueDate = $current ? $current['due_date'] : date('Y-m-d', strtotime('+7 days'));

    // Create their first pending contribution (skip if one already exists for this week, e.g. re-join edge case)
    $stmt = $pdo->prepare('SELECT id FROM contributions WHERE circle_id = ? AND user_id = ? AND week_number = ?');
    $stmt->execute([$circleId, $userId, $weekNumber]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare('INSERT INTO contributions (circle_id, user_id, amount, week_number, due_date, status) VALUES (?, ?, ?, ?, ?, "pending")');
        $stmt->execute([$circleId, $userId, $circle['weekly_contribution'], $weekNumber, $dueDate]);
    }

    // Queue a payout for this member at the end of the rotation
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(payout_date), CURDATE()) FROM payouts WHERE circle_id = ?');
    $stmt->execute([$circleId]);
    $lastPayoutDate = $stmt->fetchColumn();
    $nextPayoutDate = date('Y-m-d', strtotime($lastPayoutDate . ' +7 days'));
    $netPayout = ((float)$circle['weekly_contribution'] - (float)$circle['admin_fee']) * (int)$circle['max_members'];

    $stmt = $pdo->prepare('INSERT INTO payouts (circle_id, user_id, amount, payout_date, status) VALUES (?, ?, ?, ?, "pending")');
    $stmt->execute([$circleId, $userId, $netPayout, $nextPayoutDate]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    send_json(['success' => false, 'message' => 'Could not join circle: ' . $e->getMessage()], 500);
}

send_json([
    'success' => true,
    'message' => 'Joined ' . $circle['name'] . '! Your first contribution of R' . number_format((float)$circle['weekly_contribution'], 2) . ' is now due.',
    'circle_id' => $circleId,
    'position' => $position,
    'week_number' => $weekNumber,
    'amount_due' => (float)$circle['weekly_contribution'],
]);
