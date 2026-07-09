<?php
/**
 * POST /backend/api/payments/pay.php
 * Body: circle_id, payment_method ("wallet" | "eft" | "card" | "ozow")
 *
 * Marks the user's next pending contribution for that circle as paid.
 * If paying from "wallet", the amount is deducted from wallet_balance.
 * Other methods are treated as external payments (EFT/Card/Instant EFT)
 * and do not touch the wallet balance, but are still logged for the
 * transaction history.
 *
 * Also handles referral bonus payout: the FIRST contribution a referred
 * user ever makes on the platform triggers the R10 bonus to whoever
 * referred them, plus a one-off R50 bonus to the referrer once they reach
 * 10 paid referrals.
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
$method = in_array($input['payment_method'] ?? '', ['wallet', 'eft', 'card', 'ozow'], true)
    ? $input['payment_method'] : 'wallet';

$stmt = $pdo->prepare('SELECT name FROM circles WHERE id = ?');
$stmt->execute([$circleId]);
$circle = $stmt->fetch();
if (!$circle) {
    send_json(['success' => false, 'message' => 'Circle not found.'], 404);
}

// Find the user's next unpaid contribution for this circle
$stmt = $pdo->prepare('SELECT id, amount, week_number FROM contributions
                        WHERE circle_id = ? AND user_id = ? AND status IN ("pending", "late")
                        ORDER BY week_number DESC LIMIT 1');
$stmt->execute([$circleId, $userId]);
$contribution = $stmt->fetch();

if (!$contribution) {
    send_json(['success' => false, 'message' => 'No pending contribution found for this circle. You may already be paid up!'], 404);
}

$amount = (float)$contribution['amount'];

$pdo->beginTransaction();
try {
    // If paying from wallet, make sure there's enough balance
    if ($method === 'wallet') {
        $stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $balance = (float)$stmt->fetchColumn();
        if ($balance < $amount) {
            $pdo->rollBack();
            send_json(['success' => false, 'message' => 'Insufficient wallet balance. Please top up or choose another payment method.'], 422);
        }
        $stmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?');
        $stmt->execute([$amount, $userId]);
    }

    // Mark the contribution as paid
    $stmt = $pdo->prepare('UPDATE contributions SET status = "paid", payment_method = ?, paid_at = NOW() WHERE id = ?');
    $stmt->execute([$method, $contribution['id']]);

    // Log it in the transaction history regardless of payment method
    $stmt = $pdo->prepare('INSERT INTO wallet_transactions (user_id, type, category, amount, description)
                            VALUES (?, "debit", "contribution", ?, ?)');
    $stmt->execute([$userId, $amount, 'Contribution payment - ' . $circle['name'] . ' (Week ' . $contribution['week_number'] . ')']);

    // ---- Referral bonus logic ----
    $bonusMessage = '';
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM contributions WHERE user_id = ? AND status = "paid"');
    $stmt->execute([$userId]);
    $totalPaidContributions = (int)$stmt->fetchColumn();

    if ($totalPaidContributions === 1) {
        // This was this user's very first ever paid contribution
        $stmt = $pdo->prepare('SELECT id, referrer_id, bonus_amount FROM referrals WHERE referred_id = ? AND status = "pending" LIMIT 1');
        $stmt->execute([$userId]);
        $referral = $stmt->fetch();

        if ($referral) {
            $referrerId = (int)$referral['referrer_id'];
            $bonus = (float)$referral['bonus_amount'];

            $stmt = $pdo->prepare('UPDATE referrals SET status = "paid" WHERE id = ?');
            $stmt->execute([$referral['id']]);

            $stmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?');
            $stmt->execute([$bonus, $referrerId]);

            $stmt = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $payerName = $stmt->fetch();
            $payerFullName = $payerName ? $payerName['first_name'] . ' ' . $payerName['last_name'] : 'A friend';

            $stmt = $pdo->prepare('INSERT INTO wallet_transactions (user_id, type, category, amount, description)
                                    VALUES (?, "credit", "referral_bonus", ?, ?)');
            $stmt->execute([$referrerId, $bonus, 'Referral bonus - ' . $payerFullName . ' made their first payment']);

            // Milestone bonus: R50 once the referrer reaches 10 PAID referrals
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND status = "paid"');
            $stmt->execute([$referrerId]);
            $paidReferralCount = (int)$stmt->fetchColumn();

            if ($paidReferralCount === 10) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM wallet_transactions WHERE user_id = ? AND category = "referral_milestone"');
                $stmt->execute([$referrerId]);
                $alreadyAwarded = (int)$stmt->fetchColumn() > 0;

                if (!$alreadyAwarded) {
                    $stmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance + 50.00 WHERE id = ?');
                    $stmt->execute([$referrerId]);
                    $stmt = $pdo->prepare('INSERT INTO wallet_transactions (user_id, type, category, amount, description)
                                            VALUES (?, "credit", "referral_milestone", 50.00, "Referral milestone bonus - 10 referrals reached")');
                    $stmt->execute([$referrerId]);
                }
            }
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    send_json(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()], 500);
}

$stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
$stmt->execute([$userId]);
$newBalance = (float)$stmt->fetchColumn();

send_json([
    'success' => true,
    'message' => 'Payment of R' . number_format($amount, 2) . ' confirmed for ' . $circle['name'] . '.',
    'amount_paid' => $amount,
    'wallet_balance' => $newBalance,
]);
