<?php
// api/trade.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Not logged in']); exit;
}

$userId = $_SESSION['user_id'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

$player_id   = intval($body['player_id']   ?? 0);
$player_name = trim($body['player']        ?? '');
$type        = strtolower($body['type']    ?? '');   // 'buy' or 'sell'
$qty         = intval($body['qty']         ?? 0);

if (!$player_id || !$player_name || !in_array($type,['buy','sell']) || $qty <= 0) {
    echo json_encode(['success'=>false,'error'=>'Invalid parameters']); exit;
}

// ── Get current price from DB (users cannot pass their own price) ─────────────
$stmt = $pdo->prepare("SELECT current_price FROM players WHERE player_id=? AND is_active=1");
$stmt->execute([$player_id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$player) { echo json_encode(['success'=>false,'error'=>'Player not found']); exit; }

$price      = floatval($player['current_price']);
$totalCost  = $price * $qty;

// ── Get user wallet ───────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT wallet, status FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['status'] !== 'approved') {
    echo json_encode(['success'=>false,'error'=>'Account not approved']); exit;
}
$wallet = floatval($user['wallet']);

// ── Get current holding ───────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT id, qty, avg_price FROM holdings WHERE user_id=? AND player=?");
$stmt->execute([$userId, $player_name]);
$holding = $stmt->fetch(PDO::FETCH_ASSOC);
$heldQty  = $holding ? intval($holding['qty'])       : 0;
$avgPrice = $holding ? floatval($holding['avg_price']) : 0;

// ── BUY ───────────────────────────────────────────────────────────────────────
if ($type === 'buy') {
    if ($wallet < $totalCost) {
        echo json_encode(['success'=>false,'error'=>"Insufficient wallet. Need ₹".number_format($totalCost,2)." but you have ₹".number_format($wallet,2)]); exit;
    }
    $newWallet  = $wallet - $totalCost;
    $newQty     = $heldQty + $qty;
    // Weighted average buy price
    $newAvgPrice = (($heldQty * $avgPrice) + ($qty * $price)) / $newQty;

    $pdo->beginTransaction();
    try {
        // Deduct wallet
        $pdo->prepare("UPDATE users SET wallet=? WHERE id=?")->execute([$newWallet, $userId]);

        // Upsert holding
        if ($holding) {
            $pdo->prepare("UPDATE holdings SET qty=?, avg_price=? WHERE id=?")->execute([$newQty, $newAvgPrice, $holding['id']]);
        } else {
            $pdo->prepare("INSERT INTO holdings (user_id,player,qty,avg_price) VALUES (?,?,?,?)")->execute([$userId,$player_name,$newQty,$newAvgPrice]);
        }

        // Log trade
        $pdo->prepare("INSERT INTO trades (user_id,player,type,price,qty) VALUES (?,?,?,?,?)")->execute([$userId,$player_name,'buy',$price,$qty]);

        $pdo->commit();
        echo json_encode(['success'=>true,'wallet'=>$newWallet,'message'=>"Bought {$qty}x {$player_name} @ ₹{$price}"]);
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'error'=>'Transaction failed: '.$e->getMessage()]);
    }
}

// ── SELL ──────────────────────────────────────────────────────────────────────
elseif ($type === 'sell') {
    if ($heldQty < $qty) {
        echo json_encode(['success'=>false,'error'=>"You only hold {$heldQty} shares of {$player_name}"]); exit;
    }
    $newWallet = $wallet + $totalCost;
    $newQty    = $heldQty - $qty;

    $pdo->beginTransaction();
    try {
        // Credit wallet
        $pdo->prepare("UPDATE users SET wallet=? WHERE id=?")->execute([$newWallet, $userId]);

        // Update or remove holding
        if ($newQty > 0) {
            // avg_price stays the same on sell (cost basis doesn't change)
            $pdo->prepare("UPDATE holdings SET qty=? WHERE id=?")->execute([$newQty, $holding['id']]);
        } else {
            $pdo->prepare("DELETE FROM holdings WHERE id=?")->execute([$holding['id']]);
        }

        // Log trade
        $pdo->prepare("INSERT INTO trades (user_id,player,type,price,qty) VALUES (?,?,?,?,?)")->execute([$userId,$player_name,'sell',$price,$qty]);

        $pdo->commit();
        $pnl = ($price - $avgPrice) * $qty;
        $pnlStr = ($pnl >= 0 ? '+' : '') . number_format($pnl, 2);
        echo json_encode(['success'=>true,'wallet'=>$newWallet,'message'=>"Sold {$qty}x {$player_name} @ ₹{$price} | P&L: ₹{$pnlStr}"]);
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'error'=>'Transaction failed: '.$e->getMessage()]);
    }
}
?>