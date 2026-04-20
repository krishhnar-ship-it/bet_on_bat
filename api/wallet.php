<?php
// api/wallet2.php
require '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? '';

if ($action === 'save_bank') {
    $bank = trim($_POST['bank_account'] ?? '');
    $ifsc = trim($_POST['ifsc'] ?? '');
    $name = trim($_POST['beneficiary'] ?? '');

    $stmt = $pdo->prepare("UPDATE users SET bank_account = ?, ifsc = ?, beneficiary_name = ? WHERE id = ?");
    $success = $stmt->execute([$bank, $ifsc, $name, $userId]);

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Bank details saved successfully' : 'Failed to save bank details'
    ]);
} 
elseif ($action === 'razorpay_verify') {
    $amount = floatval($input['amount'] ?? 0);

    if ($amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid amount']);
        exit;
    }

    // Add money to wallet
    $stmt = $pdo->prepare("UPDATE users SET wallet = wallet + ? WHERE id = ?");
    $stmt->execute([$amount, $userId]);

    // Get updated wallet balance
    $stmt = $pdo->prepare("SELECT wallet FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $newWallet = floatval($stmt->fetchColumn());

    echo json_encode([
        'success' => true,
        'newWallet' => $newWallet,
        'message' => 'Payment successful. Money added to wallet.'
    ]);
} 
elseif ($action === 'withdraw') {
    $amount = floatval($input['amount'] ?? 0);

    if ($amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid amount']);
        exit;
    }

    // Get current balance
    $stmt = $pdo->prepare("SELECT wallet FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentWallet = floatval($stmt->fetchColumn());

    if ($currentWallet < $amount) {
        echo json_encode(['success' => false, 'error' => 'Insufficient balance']);
        exit;
    }

    // Deduct from wallet
    $newWallet = $currentWallet - $amount;
    $stmt = $pdo->prepare("UPDATE users SET wallet = ? WHERE id = ?");
    $success = $stmt->execute([$newWallet, $userId]);

    echo json_encode([
        'success' => $success,
        'newWallet' => $newWallet,
        'message' => $success ? 'Withdrawal request submitted successfully' : 'Failed to process withdrawal'
    ]);
} 
else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>