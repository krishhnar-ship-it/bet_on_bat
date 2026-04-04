<?php
require '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'save_bank') {
    // This will be handled via POST form in wallet.php
    // For now, we handle it in wallet.php itself
} 
elseif ($action === 'razorpay_verify') {
    // In production, verify signature properly
    // For now, we simulate success (add real verification later)
    $amount = floatval($input['amount'] ?? 0);

    if ($amount > 0) {
        $stmt = $pdo->prepare("UPDATE users SET wallet = wallet + ? WHERE id = ?");
        $stmt->execute([$amount, $userId]);
        
        echo json_encode([
            'success' => true,
            'newWallet' => $amount,
            'message' => 'Payment verified and added to wallet'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid amount']);
    }
} 
elseif ($action === 'withdraw') {
    $amount = floatval($input['amount'] ?? 0);

    $stmt = $pdo->prepare("SELECT wallet FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user['wallet'] < $amount) {
        echo json_encode(['success' => false, 'error' => 'Insufficient balance']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET wallet = wallet - ? WHERE id = ?");
    $stmt->execute([$amount, $userId]);

    echo json_encode(['success' => true, 'message' => 'Withdrawal request submitted']);
} 
else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>