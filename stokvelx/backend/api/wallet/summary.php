<?php
/**
 * GET /backend/api/wallet/summary.php
 * Returns the logged-in user's wallet balance plus small summary stats
 * used on the Wallet page (total in, total out, this month's activity).
 */
require_once __DIR__ . '/../../includes/auth.php';

$userId = require_login();

$stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
$stmt->execute([$userId]);
$balance = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE user_id = ? AND type = "credit"');
$stmt->execute([$userId]);
$totalIn = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE user_id = ? AND type = "debit"');
$stmt->execute([$userId]);
$totalOut = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT bank_name, account_number, account_type, verified FROM bank_details WHERE user_id = ? LIMIT 1');
$stmt->execute([$userId]);
$bank = $stmt->fetch();

send_json([
    'success' => true,
    'wallet_balance' => $balance,
    'total_in' => $totalIn,
    'total_out' => $totalOut,
    'bank_details' => $bank ?: null,
]);
