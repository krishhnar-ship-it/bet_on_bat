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
$player = trim($input['player'] ?? '');
$qty    = intval($input['qty'] ?? 0);

if ($qty <= 0 || empty($action) || empty($player)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// Get user wallet and status
$stmt = $pdo->prepare("SELECT wallet, status FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['status'] !== 'approved') {
    echo json_encode(['success' => false, 'error' => 'Account not approved']);
    exit;
}

$price = getPlayerPrice($player);
$commissionRate = 0.02;   // 2% commission (you can change this)

// ====================== BUY ======================
if ($action === 'buy') {
    $subtotal   = $price * $qty;
    $commission = round($subtotal * $commissionRate, 2);   // Platform earns this
    $totalCost  = $subtotal + $commission;

    if ($user['wallet'] < $totalCost) {
        echo json_encode([
            'success' => false,
            'error' => "Insufficient balance. Need ₹" . number_format($totalCost, 2) . 
                       " (incl. 2% commission)"
        ]);
        exit;
    }

    // Update or create holding
    $stmt = $pdo->prepare("SELECT quantity, avg_price FROM holdings WHERE user_id = ? AND player_name = ?");
    $stmt->execute([$userId, $player]);
    $holding = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($holding) {
        $oldQty = $holding['quantity'];
        $oldAvg = $holding['avg_price'];
        $newQty = $oldQty + $qty;
        $newAvg = (($oldQty * $oldAvg) + ($qty * $price)) / $newQty;

        $stmt = $pdo->prepare("UPDATE holdings SET quantity = ?, avg_price = ? WHERE user_id = ? AND player_name = ?");
        $stmt->execute([$newQty, $newAvg, $userId, $player]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO holdings (user_id, player_name, quantity, avg_price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $player, $qty, $price]);
    }

    // Deduct from wallet (subtotal + commission)
    $newWallet = $user['wallet'] - $totalCost;
    $stmt = $pdo->prepare("UPDATE users SET wallet = ? WHERE id = ?");
    $stmt->execute([$newWallet, $userId]);

    echo json_encode([
        'success' => true,
        'message' => "Bought $qty shares of $player @ ₹$price + 2% commission (₹$commission)",
        'newWallet' => floatval($newWallet),
        'commission' => $commission
    ]);

// ====================== SELL ======================
} elseif ($action === 'sell') {
    $stmt = $pdo->prepare("SELECT quantity, avg_price FROM holdings WHERE user_id = ? AND player_name = ?");
    $stmt->execute([$userId, $player]);
    $holding = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$holding || $holding['quantity'] < $qty) {
        echo json_encode(['success' => false, 'error' => 'Not enough shares to sell']);
        exit;
    }

    $subtotal   = $price * $qty;
    $commission = round($subtotal * $commissionRate, 2);
    $netAmount  = $subtotal - $commission;   // User gets after commission

    $newQty = $holding['quantity'] - $qty;

    if ($newQty <= 0) {
        $stmt = $pdo->prepare("DELETE FROM holdings WHERE user_id = ? AND player_name = ?");
        $stmt->execute([$userId, $player]);
    } else {
        $stmt = $pdo->prepare("UPDATE holdings SET quantity = ? WHERE user_id = ? AND player_name = ?");
        $stmt->execute([$newQty, $userId, $player]);
    }

    // Credit net amount to wallet
    $newWallet = $user['wallet'] + $netAmount;
    $stmt = $pdo->prepare("UPDATE users SET wallet = ? WHERE id = ?");
    $stmt->execute([$newWallet, $userId]);

    echo json_encode([
        'success' => true,
        'message' => "Sold $qty shares of $player @ ₹$price - 2% commission (₹$commission)",
        'newWallet' => floatval($newWallet),
        'commission' => $commission,
        'netReceived' => $netAmount
    ]);

} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

// Helper Function
function getPlayerPrice($playerName) {
    $base = 1000;
    if (stripos($playerName, 'Kohli') !== false) return 1420;
    if (stripos($playerName, 'Babar') !== false) return 1350;
    if (stripos($playerName, 'Bumrah') !== false) return 1250;
    if (stripos($playerName, 'Sharma') !== false) return 1300;
    if (stripos($playerName, 'Rashid') !== false) return 1220;
    return rand(800, 1150);
}
?>