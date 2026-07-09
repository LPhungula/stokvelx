<?php
/**
 * POST /backend/api/auth/register.php
 * Body: first_name, last_name, email, phone, password, university,
 *       student_number, id_number, referral_code (optional)
 */
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$input = get_input();
$missing = missing_fields($input, ['first_name', 'last_name', 'email', 'password']);
if ($missing) {
    send_json(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing)], 422);
}

$firstName     = trim($input['first_name']);
$lastName      = trim($input['last_name']);
$email         = trim(strtolower($input['email']));
$phone         = trim($input['phone'] ?? '');
$password      = $input['password'];
$university    = trim($input['university'] ?? '');
$studentNumber = trim($input['student_number'] ?? '');
$idNumber      = trim($input['id_number'] ?? '');
$refInput      = trim($input['referral_code'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json(['success' => false, 'message' => 'Please enter a valid email address.'], 422);
}
if (strlen($password) < 6) {
    send_json(['success' => false, 'message' => 'Password must be at least 6 characters.'], 422);
}

// Check email uniqueness
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    send_json(['success' => false, 'message' => 'An account with that email already exists.'], 409);
}

// Resolve referrer (if a valid referral code was supplied)
$referredBy = null;
if ($refInput !== '') {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE referral_code = ?');
    $stmt->execute([strtoupper($refInput)]);
    $referrer = $stmt->fetch();
    if ($referrer) {
        $referredBy = (int)$referrer['id'];
    }
}

// Generate a unique referral code for the new user
do {
    $newCode = generate_referral_code($firstName);
    $stmt = $pdo->prepare('SELECT id FROM users WHERE referral_code = ?');
    $stmt->execute([$newCode]);
} while ($stmt->fetch());

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('INSERT INTO users
        (first_name, last_name, email, phone, password_hash, university, student_number, id_number, role, referral_code, referred_by, wallet_balance)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, "student", ?, ?, 0.00)');
    $stmt->execute([$firstName, $lastName, $email, $phone, $passwordHash, $university, $studentNumber, $idNumber, $newCode, $referredBy]);
    $newUserId = (int)$pdo->lastInsertId();

    // Record the referral relationship (bonus paid once the referred user makes their first contribution)
    if ($referredBy) {
        $stmt = $pdo->prepare('INSERT INTO referrals (referrer_id, referred_id, bonus_amount, status) VALUES (?, ?, 10.00, "pending")');
        $stmt->execute([$referredBy, $newUserId]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    send_json(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()], 500);
}

// Log the new user in immediately
$_SESSION['user_id'] = $newUserId;

send_json([
    'success' => true,
    'message' => 'Account created successfully.',
    'user' => get_public_user($pdo, $newUserId),
]);
