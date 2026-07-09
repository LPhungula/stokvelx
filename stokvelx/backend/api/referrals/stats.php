<?php
/**
 * GET /backend/api/referrals/stats.php
 * Returns the logged-in user's referral code, total earnings, paid and
 * pending referrals, and a list of referred people.
 */
require_once __DIR__ . '/../../includes/auth.php';

$userId = require_login();

$stmt = $pdo->prepare('SELECT referral_code, wallet_balance FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Summary counts
$stmt = $pdo->prepare('SELECT COUNT(*) FROM referrals WHERE referrer_id = ?');
$stmt->execute([$userId]);
$total = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND status = "paid"');
$stmt->execute([$userId]);
$paid = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND status = "pending"');
$stmt->execute([$userId]);
$pending = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(bonus_amount),0) FROM referrals WHERE referrer_id = ? AND status = "paid"');
$stmt->execute([$userId]);
$totalEarned = (float)$stmt->fetchColumn();

// Also add any milestone bonuses
$stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE user_id = ? AND category = "referral_milestone"');
$stmt->execute([$userId]);
$totalEarned += (float)$stmt->fetchColumn();

// Detailed list of referred users
$stmt = $pdo->prepare('SELECT r.id, r.status, r.bonus_amount, r.created_at,
                               u.first_name, u.last_name, u.university
                        FROM referrals r
                        JOIN users u ON u.id = r.referred_id
                        WHERE r.referrer_id = ?
                        ORDER BY r.created_at DESC');
$stmt->execute([$userId]);
$referrals = $stmt->fetchAll();

$list = array_map(function ($r) {
    return [
        'id'           => (int)$r['id'],
        'name'         => $r['first_name'] . ' ' . $r['last_name'],
        'university'   => $r['university'],
        'bonus_amount' => (float)$r['bonus_amount'],
        'status'       => $r['status'],
        'date'         => $r['created_at'],
    ];
}, $referrals);

send_json([
    'success'       => true,
    'referral_code' => $user['referral_code'],
    'total'         => $total,
    'paid'          => $paid,
    'pending'       => $pending,
    'total_earned'  => $totalEarned,
    'referrals'     => $list,
]);
