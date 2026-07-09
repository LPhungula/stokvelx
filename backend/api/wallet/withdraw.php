<?php
/**
 * POST /backend/api/wallet/withdraw.php
 * Body: amount
 * Deducts from wallet and logs a withdrawal transaction.
 * Requires a verified bank account to be on file.
 */
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$userId = require_login();
$input  = get_input();

$amount = (float)($input['amount'] ?? 0);
if ($amount < 50) {
    send_json(['success' => false, 'message' => 'Minimum withdrawal amount is R50.'], 422);
}

// Check for a bank account on file
$stmt = $pdo->prepare('SELECT bank_name, account_number FROM bank_details WHERE user_id = ? LIMIT 1');
$stmt->execute([$userId]);
$bank = $stmt->fetch();
if (!$bank) {
    send_json(['success' => false, 'message' => 'Please add a bank account before withdrawing.'], 422);
}

// Check balance
$stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
$stmt->execute([$userId]);
$balance = (float)$stmt->fetchColumn();
if ($balance < $amount) {
    send_json(['success' => false, 'message' => 'Insufficient wallet balance.'], 422);
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?');
    $stmt->execute([$amount, $userId]);

    // Mask account number for the description
    $masked = '****' . substr($bank['account_number'], -4);
    $stmt = $pdo->prepare('INSERT INTO wallet_transactions (user_id, type, category, amount, description)
                            VALUES (?, "debit", "withdrawal", ?, ?)');
    $stmt->execute([$userId, $amount, 'Withdrawal to ' . $bank['bank_name'] . ' ' . $masked]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    send_json(['success' => false, 'message' => 'Withdrawal failed: ' . $e->getMessage()], 500);
}

$stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
$stmt->execute([$userId]);
$newBalance = (float)$stmt->fetchColumn();

send_json([
    'success'        => true,
    'message'        => 'Withdrawal of R' . number_format($amount, 2) . ' is being processed. Funds arrive in 1–2 business days.',
    'amount'         => $amount,
    'wallet_balance' => $newBalance,
]);
