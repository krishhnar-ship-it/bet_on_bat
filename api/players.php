<?php
// api/players.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require '../config.php';

$action      = $_GET['action'] ?? 'get';
$player_name = $_GET['player'] ?? '';
$event       = $_GET['event'] ?? '';
$team        = $_GET['team'] ?? '';

// ── Price change rules ────────────────────────────────────────────────────────
$priceRules = [
    // Batting
    'six'                 =>  6,
    'four'                =>  4,
    'double'              =>  2,
    'single'              =>  1,
    'dot'                 => -1,
    'out_under_20'        => -30,
    'fifty'               =>  10,
    'century'             =>  20,
    'high_strike_rate'    =>  8,
    'very_high_strike_rate' => 15,
    // Bowling
    'wicket'              =>  25,
    '5wickets'            =>  20,
    'good_economy'        =>  10,
    'poor_economy'        => -12,
    'concede_six'         => -6,
    'concede_four'        => -4,
    'concede_five'        => -5,
    'concede_three'       => -3,
    'concede_two'         => -2,
    'concede_one'         => -1,
    // Fielding
    'catch'               =>  10,
    'direct_hit'          =>  10,
    'save_boundary'       =>  5,
    'miss_ball'           => -2,
    'miss_field_four'     => -4,
    'miss_field_six'      => -6,
    'overthrow_one'       => -2,
    'overthrow_two'       => -4,
    'overthrow_four'      => -8,
];

// ── ACTION: update_price ──────────────────────────────────────────────────────
if ($action === 'update_price' && $player_name && $event) {
    $change = $priceRules[$event] ?? 0;

    // Get current price from DB
    $stmt = $pdo->prepare("SELECT player_id, current_price FROM players WHERE name = ?");
    $stmt->execute([$player_name]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$player) {
        echo json_encode(['success' => false, 'error' => 'Player not found']);
        exit;
    }

    $new_price = max(100, $player['current_price'] + $change);

    // Update in DB
    $upd = $pdo->prepare("UPDATE players SET current_price = ? WHERE player_id = ?");
    $upd->execute([$new_price, $player['player_id']]);

    echo json_encode([
        'success'   => true,
        'player'    => $player_name,
        'event'     => $event,
        'change'    => $change,
        'old_price' => $player['current_price'],
        'new_price' => $new_price
    ]);
    exit;
}

// ── ACTION: get_team ─────────────────────────────────────────────────────────
if ($action === 'get_team' && $team) {
    $stmt = $pdo->prepare(
        "SELECT player_id, name, team, role, current_price, base_price
         FROM players WHERE team = ? AND is_active = 1
         ORDER BY role, name"
    );
    $stmt->execute([strtoupper($team)]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'players' => $players]);
    exit;
}

// ── ACTION: get all players ──────────────────────────────────────────────────
$stmt = $pdo->query(
    "SELECT player_id, name, team, role, current_price, base_price
     FROM players WHERE is_active = 1
     ORDER BY team, role, name"
);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'players' => $players]);
?>