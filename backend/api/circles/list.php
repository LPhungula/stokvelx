<?php
/**
 * GET /backend/api/circles/list.php
 * Optional query param: ?type=standard|premium|investment
 * Returns every circle with member counts, spots left, and whether the
 * logged-in user (if any) has already joined it.
 */
require_once __DIR__ . '/../../includes/auth.php';

$userId = current_user_id();
$type = $_GET['type'] ?? null;

$sql = 'SELECT c.id, c.name, c.type, c.weekly_contribution, c.max_members, c.admin_fee,
               c.extra_fee, c.cycle_weeks, c.status,
               (SELECT COUNT(*) FROM circle_members cm WHERE cm.circle_id = c.id AND cm.status = "active") AS member_count
        FROM circles c
        WHERE c.status = "active"';
$params = [];
if ($type && in_array($type, ['standard', 'premium', 'investment'], true)) {
    $sql .= ' AND c.type = ?';
    $params[] = $type;
}
$sql .= ' ORDER BY c.id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$circles = $stmt->fetchAll();

// Figure out which circles (if any) the current user already belongs to
$joinedIds = [];
if ($userId) {
    $stmt = $pdo->prepare('SELECT circle_id FROM circle_members WHERE user_id = ? AND status = "active"');
    $stmt->execute([$userId]);
    $joinedIds = array_map('intval', array_column($stmt->fetchAll(), 'circle_id'));
}

$result = array_map(function ($c) use ($joinedIds) {
    return [
        'id'                  => (int)$c['id'],
        'name'                => $c['name'],
        'type'                => $c['type'],
        'weekly_contribution' => (float)$c['weekly_contribution'],
        'max_members'         => (int)$c['max_members'],
        'admin_fee'           => (float)$c['admin_fee'],
        'extra_fee'           => (float)$c['extra_fee'],
        'cycle_weeks'         => (int)$c['cycle_weeks'],
        'member_count'        => (int)$c['member_count'],
        'spots_left'          => max(0, (int)$c['max_members'] - (int)$c['member_count']),
        'already_joined'      => in_array((int)$c['id'], $joinedIds, true),
    ];
}, $circles);

send_json(['success' => true, 'circles' => $result]);
