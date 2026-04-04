<?php
// api/matches.php
header('Content-Type: application/json');

$api_token = "YOUR_SPORTMONKS_API_TOKEN";   // ← Put your real token here

// Get live + upcoming matches
$url = "https://cricket.sportmonks.com/api/v2.0/fixtures?api_token=" . $api_token . 
       "&include=localteam,visitorteam,league,venue&filter[status]=live,upcoming";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>