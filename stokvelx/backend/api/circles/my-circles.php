<?php
/**
 * GET /backend/api/circles/my-circles.php
 * Returns every circle the logged-in user belongs to, with their position,
 * this week's contribution progress, next contribution due date and next
 * payout date/status.
 */
require_once __DIR__ . '/../../includes/auth.php';

$userId = require_login();

$stmt = $pdo->prepare('SELECT cm.circle_id, cm.position, cm.status AS member_status,
                               c.name, c.type, c.weekly_contribution, c.max_members,
                               c.admin_fee, c.cycle_weeks, c.status AS circle_status
                        FROM circle_members cm
                        JOIN circles c ON c.id = cm.circle_id
                        WHERE cm.user_id = ?
                        ORDER BY cm.status = "active" DESC, c.id ASC');
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

$result = [];
foreach ($rows as $row) {
    $circleId = (int)$row['circle_id'];

    // Find the current (latest) contribution week for this circle
    $stmt2 = $pdo->prepare('SELECT week_number, due_date FROM contributions WHERE circle_id = ? ORDER BY week_number DESC LIMIT 1');
    $stmt2->execute([$circleId]);
    $currentWeek = $stmt2->fetch();

    $paidCount = 0;
    $weekNumber = null;
    $dueDate = null;
    if ($currentWeek) {
        $weekNumber = (int)$currentWeek['week_number'];
        $dueDate = $currentWeek['due_date'];
        $stmt3 = $pdo->prepare('SELECT COUNT(*) FROM contributions WHERE circle_id = ? AND week_number = ? AND status = "paid"');
        $stmt3->execute([$circleId, $weekNumber]);
        $paidCount = (int)$stmt3->fetchColumn();
    }

    // Has the logged-in user paid their contribution for the current week?
    $myContributionPaid = null;
    if ($weekNumber !== null) {
        $stmt4 = $pdo->prepare('SELECT status FROM contributions WHERE circle_id = ? AND user_id = ? AND week_number = ?');
        $stmt4->execute([$circleId, $userId, $weekNumber]);
        $myContributionPaid = $stmt4->fetchColumn() ?: null;
    }

    // Next payout info for this user in this circle
    $stmt5 = $pdo->prepare('SELECT amount, payout_date, status FROM payouts WHERE circle_id = ? AND user_id = ? ORDER BY payout_date ASC LIMIT 1');
    $stmt5->execute([$circleId, $userId]);
    $payout = $stmt5->fetch();

    $result[] = [
        'circle_id'           => $circleId,
        'name'                => $row['name'],
        'type'                => $row['type'],
        'weekly_contribution' => (float)$row['weekly_contribution'],
        'max_members'         => (int)$row['max_members'],
        'position'            => (int)$row['position'],
        'member_status'       => $row['member_status'],
        'circle_status'       => $row['circle_status'],
        'current_week'        => $weekNumber,
        'due_date'            => $dueDate,
        'paid_count'          => $paidCount,
        'progress_percent'    => $row['max_members'] > 0 ? round(($paidCount / (int)$row['max_members']) * 100) : 0,
        'my_contribution_status' => $myContributionPaid,
        'next_payout' => $payout ? [
            'amount'      => (float)$payout['amount'],
            'payout_date' => $payout['payout_date'],
            'status'      => $payout['status'],
        ] : null,
    ];
}

send_json(['success' => true, 'circles' => $result]);
