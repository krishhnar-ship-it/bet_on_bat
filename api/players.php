<?php
// api/players.php
header('Content-Type: application/json');

require '../config.php';

$match_id = $_GET['match_id'] ?? '';
$action   = $_GET['action'] ?? 'get';
$player_name = $_GET['player'] ?? '';
$event    = $_GET['event'] ?? '';

if (empty($match_id)) {
    echo json_encode(['success' => false, 'error' => 'Match ID required']);
    exit;
}

// ====================== UPDATE PRICE ======================
if ($action === 'update_price' && $player_name && $event) {

    $stmt = $pdo->prepare("SELECT * FROM players WHERE name = ? AND match_id = ?");
    $stmt->execute([$player_name, $match_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$player) {
        echo json_encode(['success' => false, 'error' => 'Player not found']);
        exit;
    }

    $change = 0;
    $note = '';

    // Basic Events
    if ($event === 'six') $change = 6;
    elseif ($event === 'four') $change = 4;
    elseif ($event === 'double') $change = 2;
    elseif ($event === 'single') $change = 1;
    elseif ($event === 'dot') $change = 1;
    elseif ($event === 'wicket') $change = 25;
    elseif ($event === 'catch') $change = 10;
    elseif ($event === 'direct_hit') $change = 10;
    elseif ($event === 'save_boundary') $change = 5;
    elseif ($event === 'out_under_20') $change = -20;
    elseif ($event === 'miss_ball') $change = -2;
    elseif ($event === 'concede_six') $change = -6;
    elseif ($event === 'concede_four') $change = -4;
    elseif ($event === 'concede_five') $change = -5;
    elseif ($event === 'concede_three') $change = -3;
    elseif ($event === 'concede_two') $change = -2;
    elseif ($event === 'concede_one') $change = -1;
    elseif ($event === 'miss_field_four') $change = -4;
    elseif ($event === 'miss_field_six') $change = -6;
    elseif ($event === 'overthrow_one') $change = -2;
    elseif ($event === 'overthrow_two') $change = -4;

    // Milestone Bonuses
    if ($event === 'fifty') $change += 10;      // Batsman 50 runs
    if ($event === 'century') $change += 20;    // Batsman 100 runs

    if ($event === '3wickets') $change += 6;
    if ($event === '4wickets') $change += 8;
    if ($event === '5wickets') $change += 10;
    if ($event === '6wickets') $change += 12;
    if ($event === '7wickets') $change += 14;
    if ($event === '8wickets') $change += 16;
    if ($event === '9wickets') $change += 18;

    // Strike Rate & Economy
    if ($event === 'high_strike_rate') $change += 8;   // >150
    if ($event === 'very_high_strike_rate') $change += 15; // >180
    if ($event === 'good_economy') $change += 10;      // <6
    if ($event === 'poor_economy') $change -= 12;      // >10

    $new_price = max(100, $player['current_price'] + $change);

    $stmt = $pdo->prepare("UPDATE players SET current_price = ?, last_updated = NOW() WHERE id = ?");
    $stmt->execute([$new_price, $player['id']]);

    echo json_encode([
        'success' => true,
        'player' => $player_name,
        'event' => $event,
        'change' => $change,
        'new_price' => $new_price,
        'note' => $note
    ]);
    exit;
}

// ====================== GET PLAYERS ======================
$stmt = $pdo->prepare("SELECT * FROM players WHERE match_id = ? ORDER BY current_price DESC");
$stmt->execute([$match_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'players' => $players
]);
?>