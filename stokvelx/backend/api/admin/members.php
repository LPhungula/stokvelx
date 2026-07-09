<?php
/**
 * GET /backend/api/admin/members.php  (admin only)
 * Query params: page=1, limit=20, search=<name or email>
 */
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

$where  = ['role = "student"'];
$params = [];
if ($search !== '') {
    $where[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)';
    $like    = '%' . $search . '%';
    $params  = [$like, $like, $like];
}
$whereClause = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$queryParams = array_merge($params, [$limit, $offset]);
$stmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name, u.email, u.phone,
                               u.university, u.wallet_balance, u.kyc_verified, u.created_at,
                               (SELECT COUNT(*) FROM circle_members cm WHERE cm.user_id = u.id AND cm.status = 'active') AS active_circles,
                               (SELECT COUNT(*) FROM referrals r WHERE r.referrer_id = u.id AND r.status = 'paid') AS paid_referrals,
                               (SELECT COALESCE(SUM(co.amount),0) FROM contributions co WHERE co.user_id = u.id AND co.status = 'paid') AS total_contributed
                        FROM users u
                        $whereClause
                        ORDER BY u.created_at DESC
                        LIMIT ? OFFSET ?");
$stmt->execute($queryParams);
$members = $stmt->fetchAll();

$result = array_map(function ($m) {
    return [
        'id'               => (int)$m['id'],
        'name'             => $m['first_name'] . ' ' . $m['last_name'],
        'email'            => $m['email'],
        'phone'            => $m['phone'],
        'university'       => $m['university'],
        'wallet_balance'   => (float)$m['wallet_balance'],
        'kyc_verified'     => (bool)$m['kyc_verified'],
        'active_circles'   => (int)$m['active_circles'],
        'paid_referrals'   => (int)$m['paid_referrals'],
        'total_contributed'=> (float)$m['total_contributed'],
        'joined'           => $m['created_at'],
    ];
}, $members);

send_json([
    'success'     => true,
    'total'       => $total,
    'page'        => $page,
    'limit'       => $limit,
    'total_pages' => (int)ceil($total / $limit),
    'members'     => $result,
]);
