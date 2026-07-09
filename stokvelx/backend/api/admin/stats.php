<?php
/**
 * GET /backend/api/admin/stats.php  (admin only)
 * Returns high-level platform statistics for the admin dashboard.
 */
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$stats = [];

$stats['total_users']    = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn();
$stats['total_circles']  = (int)$pdo->query('SELECT COUNT(*) FROM circles')->fetchColumn();
$stats['active_circles'] = (int)$pdo->query('SELECT COUNT(*) FROM circles WHERE status = "active"')->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(amount),0) FROM contributions WHERE status = "paid"');
$stats['total_contributions'] = (float)$stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(amount),0) FROM payouts WHERE status = "paid"');
$stats['total_payouts'] = (float)$stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(amount),0) FROM referrals WHERE status = "paid"');
$stats['total_referral_bonuses'] = (float)$stmt->fetchColumn();

// Total admin fee revenue
$stmt = $pdo->query('SELECT COALESCE(SUM(c.admin_fee),0) FROM contributions co
                     JOIN circles c ON c.id = co.circle_id WHERE co.status = "paid"');
$stats['total_admin_revenue'] = (float)$stmt->fetchColumn();

// Registrations this month
$stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "student"
                     AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())');
$stats['new_users_this_month'] = (int)$stmt->fetchColumn();

// Monthly contribution totals (last 6 months) for a simple chart
$stmt = $pdo->query('SELECT DATE_FORMAT(paid_at, "%Y-%m") AS month,
                            COALESCE(SUM(amount),0) AS total
                     FROM contributions
                     WHERE status = "paid" AND paid_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                     GROUP BY month
                     ORDER BY month ASC');
$stats['monthly_contributions'] = $stmt->fetchAll();

send_json(['success' => true, 'stats' => $stats]);
