<?php
/**
 * POST /backend/api/circles/create.php  (admin only)
 * Body: name, type, weekly_contribution, max_members, admin_fee, extra_fee, cycle_weeks
 */
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

require_admin();

$input = get_input();
$missing = missing_fields($input, ['name', 'type', 'weekly_contribution', 'max_members']);
if ($missing) {
    send_json(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing)], 422);
}

$name        = trim($input['name']);
$type        = $input['type'];
$weekly      = (float)$input['weekly_contribution'];
$maxMembers  = (int)$input['max_members'];
$adminFee    = (float)($input['admin_fee'] ?? 10.00);
$extraFee    = (float)($input['extra_fee'] ?? 0.00);
$cycleWeeks  = (int)($input['cycle_weeks'] ?? $maxMembers);

if (!in_array($type, ['standard', 'premium', 'investment'], true)) {
    send_json(['success' => false, 'message' => 'Invalid circle type.'], 422);
}
if ($weekly <= 0 || $maxMembers <= 0) {
    send_json(['success' => false, 'message' => 'Weekly contribution and max members must be greater than zero.'], 422);
}

$stmt = $pdo->prepare('INSERT INTO circles (name, type, weekly_contribution, max_members, admin_fee, extra_fee, cycle_weeks, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, "active")');
$stmt->execute([$name, $type, $weekly, $maxMembers, $adminFee, $extraFee, $cycleWeeks]);

send_json([
    'success' => true,
    'message' => 'Circle "' . $name . '" created successfully.',
    'circle_id' => (int)$pdo->lastInsertId(),
]);
