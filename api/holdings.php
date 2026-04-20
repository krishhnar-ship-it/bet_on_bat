<?php
// api/holdings.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Not logged in']); exit;
}

$userId = $_SESSION['user_id'];

// Join holdings with current player price for live P&L
$stmt = $pdo->prepare("
    SELECT
        h.id,
        h.player,
        h.qty,
        h.avg_price,
        p.current_price,
        p.team,
        p.role,
        p.player_id,
        (p.current_price - h.avg_price) * h.qty AS unrealised_pnl,
        p.current_price * h.qty                  AS current_value,
        h.avg_price * h.qty                       AS invested_value
    FROM holdings h
    LEFT JOIN players p ON p.name = h.player
    WHERE h.user_id = ?
    ORDER BY h.player
");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Trade history (last 20)
$th = $pdo->prepare("SELECT player, type, price, qty, created_at FROM trades WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$th->execute([$userId]);
$trades = $th->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success'  => true,
    'holdings' => $rows,
    'trades'   => $trades,
]);
?>