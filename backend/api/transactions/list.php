<?php
/**
 * GET /backend/api/transactions/list.php
 * Query params:
 *   page=1          (default 1)
 *   limit=20        (default 20, max 100)
 *   type=credit|debit   (optional filter)
 *   category=...    (optional filter: deposit, withdrawal, contribution, payout,
 *                    referral_bonus, referral_milestone)
 */
require_once __DIR__ . '/../../includes/auth.php';

$userId = require_login();

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$type     = $_GET['type'] ?? null;
$category = $_GET['category'] ?? null;

$where    = ['user_id = ?'];
$params   = [$userId];

if ($type && in_array($type, ['credit', 'debit'], true)) {
    $where[] = 'type = ?';
    $params[] = $type;
}
if ($category) {
    $where[] = 'category = ?';
    $params[] = $category;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM wallet_transactions $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$params[] = $limit;
$params[] = $offset;
$stmt = $pdo->prepare("SELECT id, type, category, amount, description, created_at
                        FROM wallet_transactions
                        $whereClause
                        ORDER BY created_at DESC
                        LIMIT ? OFFSET ?");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$result = array_map(function ($t) {
    return [
        'id'          => (int)$t['id'],
        'type'        => $t['type'],
        'category'    => $t['category'],
        'amount'      => (float)$t['amount'],
        'description' => $t['description'],
        'created_at'  => $t['created_at'],
    ];
}, $transactions);

send_json([
    'success'      => true,
    'total'        => $total,
    'page'         => $page,
    'limit'        => $limit,
    'total_pages'  => (int)ceil($total / $limit),
    'transactions' => $result,
]);
