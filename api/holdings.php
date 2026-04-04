<?php
require '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'get';

if ($action === 'get') {
    // Get all holdings for this user
    $stmt = $pdo->prepare("SELECT player_name, quantity, avg_price FROM holdings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $holdingRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $holdings = [];
    foreach ($holdingRows as $row) {
        $holdings[$row['player_name']] = [
            'qty' => intval($row['quantity']),
            'avgPrice' => floatval($row['avg_price'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'holdings' => $holdings
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
