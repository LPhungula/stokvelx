<?php
/**
 * POST /backend/api/wallet/add-money.php
 * Body: amount, method ("eft" | "card" | "ozow")
 * Simulates an external deposit arriving and credits the wallet.
 */
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$userId = require_login();
$input  = get_input();

$amount = (float)($input['amount'] ?? 0);
$method = in_array($input['method'] ?? '', ['eft', 'card', 'ozow'], true) ? $input['method'] : 'eft';

if ($amount < 10) {
    send_json(['success' => false, 'message' => 'Minimum top-up amount is R10.'], 422);
}
if ($amount > 50000) {
    send_json(['success' => false, 'message' => 'Maximum single top-up is R50 000.'], 422);
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?');
    $stmt->execute([$amount, $userId]);

    $stmt = $pdo->prepare('INSERT INTO wallet_transactions (user_id, type, category, amount, description)
                            VALUES (?, "credit", "deposit", ?, ?)');
    $stmt->execute([$userId, $amount, 'Wallet top-up via ' . strtoupper($method)]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    send_json(['success' => false, 'message' => 'Top-up failed: ' . $e->getMessage()], 500);
}

$stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
$stmt->execute([$userId]);
$newBalance = (float)$stmt->fetchColumn();

send_json([
    'success'        => true,
    'message'        => 'R' . number_format($amount, 2) . ' added to your wallet.',
    'amount_added'   => $amount,
    'wallet_balance' => $newBalance,
]);
