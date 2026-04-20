<?php
// api/matches.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require '../config.php';

$api_key = '8c7730bdb0mshb97799a2a4f4f65p1e6e52jsn1c6e65a5db4c';

function fetchCricbuzz($endpoint, $api_key) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://unofficial-cricbuzz.p.rapidapi.com/" . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-RapidAPI-Key: $api_key",
        "X-RapidAPI-Host: unofficial-cricbuzz.p.rapidapi.com"
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

function resolveTeam($n) {
    $map = [
        'RCB'=>'RCB','CSK'=>'CSK','MI'=>'MI','KKR'=>'KKR','DC'=>'DC',
        'SRH'=>'SRH','RR'=>'RR','GT'=>'GT','LSG'=>'LSG','PBKS'=>'PBKS',
        'BAN'=>'RCB','CHE'=>'CSK','MUM'=>'MI','KOL'=>'KKR','DEL'=>'DC',
        'HYD'=>'SRH','RAJ'=>'RR','GUJ'=>'GT','LKN'=>'LSG','PUN'=>'PBKS','PK'=>'PBKS'
    ];
    return $map[strtoupper($n)] ?? strtoupper($n);
}

function extractIPL($data, $type) {
    $out = [];
    if (!$data || !isset($data['typeMatches'])) return $out;
    foreach ($data['typeMatches'] as $t) {
        foreach (($t['seriesMatches'] ?? []) as $s) {
            $wrap = $s['seriesAdWrapper'] ?? null;
            if (!$wrap) continue;
            $sName = $wrap['seriesName'] ?? '';
            if (stripos($sName,'Indian Premier League')===false && stripos($sName,'IPL')===false) continue;
            foreach (($wrap['matches'] ?? []) as $m) {
                $mi = $m['matchInfo'] ?? [];
                $ms = $m['matchScore'] ?? [];
                $t1 = $mi['team1']['teamSName'] ?? ($mi['team1']['teamName'] ?? 'TBD');
                $t2 = $mi['team2']['teamSName'] ?? ($mi['team2']['teamName'] ?? 'TBD');
                $score1 = $score2 = '';
                if (isset($ms['team1Score']['inngs1'])) {
                    $i=$ms['team1Score']['inngs1'];
                    $score1=($i['runs']??0).'/'.($i['wickets']??0).' ('.($i['overs']??0).' ov)';
                }
                if (isset($ms['team2Score']['inngs1'])) {
                    $i=$ms['team2Score']['inngs1'];
                    $score2=($i['runs']??0).'/'.($i['wickets']??0).' ('.($i['overs']??0).' ov)';
                }
                $out[] = [
                    'id'     => $mi['matchId'] ?? null,
                    'team1'  => $t1, 'team2' => $t2,
                    'status' => $mi['state'] ?? $type,
                    'desc'   => $mi['matchDesc'] ?? '',
                    'venue'  => $mi['venueInfo']['ground'] ?? '',
                    'score1' => $score1, 'score2' => $score2,
                    'type'   => $type,
                ];
            }
        }
    }
    return $out;
}

function getPlayers($pdo, $t1, $t2) {
    $d1 = resolveTeam($t1); $d2 = resolveTeam($t2);
    $stmt = $pdo->prepare(
        "SELECT player_id, name, team, role, current_price, base_price
         FROM players WHERE team IN (?,?) AND is_active=1
         ORDER BY team, FIELD(role,'Batsman','All-Rounder','Bowler'), name"
    );
    $stmt->execute([$d1,$d2]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$live     = extractIPL(fetchCricbuzz('matches/v1/live',     $api_key), 'live');
$recent   = array_slice(extractIPL(fetchCricbuzz('matches/v1/recent',   $api_key), 'recent'),   0, 1);
$upcoming = array_slice(extractIPL(fetchCricbuzz('matches/v1/upcoming', $api_key), 'upcoming'), 0, 1);

foreach ($live     as &$m) $m['players'] = getPlayers($pdo,$m['team1'],$m['team2']);
foreach ($recent   as &$m) $m['players'] = getPlayers($pdo,$m['team1'],$m['team2']);
foreach ($upcoming as &$m) $m['players'] = getPlayers($pdo,$m['team1'],$m['team2']);

echo json_encode(['success'=>true,'live'=>$live,'recent'=>$recent,'upcoming'=>$upcoming]);
?>