<?php
header('Content-Type: application/json');

$apiKey = '6fce51ce-8395-4ec6-af52-41b1af2ec3cf';

// Get player statistics for T20 format
$url = "https://api.cricapi.com/v1/players?apikey={$apiKey}&offset=0&search=";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Top T20 performers based on recent form and rankings
$topPerformers = [
    [
        'name' => 'Babar Azam',
        'team' => 'Pakistan',
        'price' => 1350,
        'change' => 4.2,
        'runs' => 2500,
        'avg' => 45.5,
        'sr' => 128.5,
        'performance' => 'top'
    ],
    [
        'name' => 'Virat Kohli',
        'team' => 'India', 
        'price' => 1320,
        'change' => 3.8,
        'runs' => 4000,
        'avg' => 50.8,
        'sr' => 138.2,
        'performance' => 'top'
    ],
    [
        'name' => 'Jos Buttler',
        'team' => 'England',
        'price' => 1280,
        'change' => 5.1,
        'runs' => 2200,
        'avg' => 40.2,
        'sr' => 144.8,
        'performance' => 'top'
    ],
    [
        'name' => 'Rashid Khan',
        'team' => 'Afghanistan',
        'price' => 1200,
        'change' => 2.9,
        'wickets' => 150,
        'avg' => 18.5,
        'eco' => 6.2,
        'performance' => 'top'
    ],
    [
        'name' => 'Jasprit Bumrah',
        'team' => 'India',
        'price' => 1180,
        'change' => 3.5,
        'wickets' => 85,
        'avg' => 19.8,
        'eco' => 6.8,
        'performance' => 'top'
    ]
];

$weakPerformers = [
    [
        'name' => 'Player A',
        'team' => 'Team X',
        'price' => 620,
        'change' => -2.1,
        'runs' => 450,
        'avg' => 22.5,
        'sr' => 115.2,
        'performance' => 'weak'
    ],
    [
        'name' => 'Player B', 
        'team' => 'Team Y',
        'price' => 580,
        'change' => -3.4,
        'wickets' => 12,
        'avg' => 35.8,
        'eco' => 8.9,
        'performance' => 'weak'
    ]
];

echo json_encode([
    'success' => true,
    'topPerformers' => $topPerformers,
    'weakPerformers' => $weakPerformers
]);
?>
