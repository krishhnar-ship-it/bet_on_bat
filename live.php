<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, wallet, status FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$username = htmlspecialchars($user['username']);
$wallet   = floatval($user['wallet'] ?? 0);
$approved = ($user['status'] === 'approved');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Live Trading - Bet On Bat</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .live-container { max-width: 1280px; margin: 2rem auto; padding: 0 20px; }
    .match-header {
      background: #161b22;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 30px;
      text-align: center;
    }
    .player-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }
    .player-card {
      background: #0d1117;
      border-radius: 12px;
      padding: 20px;
      border: 1px solid #30363d;
    }
    .player-name { font-size: 1.4rem; font-weight: 600; }
    .current-price { font-size: 1.6rem; font-weight: 700; color: #3fb950; }
    .btn-trade {
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      margin: 6px 4px;
      width: 48%;
    }
    .btn-buy { background: #3fb950; color: #0d1117; }
    .btn-sell { background: #f85149; color: white; }
    .event-log {
      margin-top: 40px;
      padding: 20px;
      background: #1f2937;
      border-radius: 12px;
      max-height: 300px;
      overflow-y: auto;
    }
    .simulate-btn {
      padding: 10px 18px;
      margin: 5px;
      background: #58a6ff;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
  </style>
</head>
<body>

  <nav class="navbar">
    <div class="logo">Bet On Bat</div>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="live.php" class="active">Live Matches</a></li>
      <li><a href="holdings.php">Holdings</a></li>
      <li><a href="wallet.php">Wallet</a></li>
      <li><a href="performers.php">Top/Weak</a></li>
      <li><a href="about.php">About</a></li>
    </ul>
    <div class="user-info">
      Welcome, <strong><?php echo $username; ?></strong> | ₹<?php echo number_format($wallet, 2); ?>
      <button onclick="openRazorpayFromNav()" class="btn-add-money">+ Add Money</button>
      <a href="logout.php" class="btn-logout">Logout</a>
    </div>
  </nav>

  <main class="live-container">
    <h1>Live Trading Dashboard</h1>
    <p>Real-time player prices based on cricket performance (IPL)</p>

    <div class="match-header">
      <h2>IPL Match • IND vs AUS Style Simulation</h2>
      <p>Click buttons to simulate events and see price changes</p>
    </div>

    <!-- Simulation Buttons -->
    <div style="text-align:center; margin-bottom:30px;">
      <button class="simulate-btn" onclick="simulateEvent('Virat Kohli', 'six')">Virat Six (+6)</button>
      <button class="simulate-btn" onclick="simulateEvent('Virat Kohli', 'four')">Virat Four (+4)</button>
      <button class="simulate-btn" onclick="simulateEvent('Virat Kohli', 'fifty')">Virat 50 (+10)</button>
      <button class="simulate-btn" onclick="simulateEvent('Jasprit Bumrah', 'wicket')">Bumrah Wicket (+25)</button>
      <button class="simulate-btn" onclick="simulateEvent('Jasprit Bumrah', '5wickets')">Bumrah 5W (+10)</button>
      <button class="simulate-btn" onclick="simulateEvent('Rohit Sharma', 'miss_field_four')">Miss Field 4 (-4)</button>
    </div>

    <div id="player-grid" class="player-grid">
      <!-- Players will load here -->
    </div>

    <div class="event-log" id="event-log">
      <strong>Live Event Log:</strong><br>
      Waiting for events...
    </div>
  </main>

  <script>
    async function loadPlayers() {
      const container = document.getElementById('player-grid');
      try {
        const res = await fetch('/betonbat/api/players.php?match_id=1');
        const data = await res.json();

        let html = '';
        if (data.players && data.players.length > 0) {
          data.players.forEach(p => {
            html += `
              <div class="player-card">
                <div class="player-name">${p.name}</div>
                <div class="current-price">₹${p.current_price}</div>
                <div style="margin-top:16px;">
                  <button class="btn-trade btn-buy" onclick="tradePlayer('${p.name}', 'buy', ${p.current_price})">BUY</button>
                  <button class="btn-trade btn-sell" onclick="tradePlayer('${p.name}', 'sell', ${p.current_price})">SELL</button>
                </div>
              </div>
            `;
          });
        } else {
          html = '<p>No players found. Run the IPL SQL script first.</p>';
        }
        container.innerHTML = html;
      } catch(e) {
        container.innerHTML = '<p>Error loading players.</p>';
      }
    }

    async function tradePlayer(playerName, action, price) {
      const qty = prompt(`How many shares of ${playerName} do you want to ${action}?`, "10");
      if (!qty || isNaN(qty) || qty <= 0) return;

      const res = await fetch('/betonbat/api/trade.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, player: playerName, qty: parseInt(qty), price })
      });

      const data = await res.json();
      alert(data.message || data.error);
      if (data.success) location.reload();
    }

    async function simulateEvent(playerName, eventType) {
      const res = await fetch(`/betonbat/api/players.php?action=update_price&player=${encodeURIComponent(playerName)}&event=${eventType}`);
      const data = await res.json();

      if (data.success) {
        const log = document.getElementById('event-log');
        const color = data.change > 0 ? '#3fb950' : '#f85149';
        log.innerHTML += `<br><strong>${playerName}:</strong> ${eventType} → <span style="color:${color}">₹${data.change}</span> (New: ₹${data.new_price})`;
        log.scrollTop = log.scrollHeight;
        loadPlayers();
      }
    }

    // Load on start
    window.onload = loadPlayers;

    function openRazorpayFromNav() {
      let amount = prompt("Enter deposit amount (₹):", "1000");
      if (!amount || isNaN(amount) || Number(amount) <= 0) return alert("Invalid amount");

      const options = {
        key: "rzp_test_SXWXNyfEjgYBCf",
        amount: Number(amount) * 100,
        currency: "INR",
        name: "Bet On Bat",
        description: "Wallet Deposit",
        handler: function(response) {
          fetch("/betonbat/api/wallet2.php", {
            method: "POST",
            credentials: 'include',
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              action: "razorpay_verify",
              razorpay_payment_id: response.razorpay_payment_id,
              amount: Number(amount)
            })
          })
          .then(r => r.json())
          .then(data => {
            if (data.success) alert(`₹${amount} added!`);
            location.reload();
          });
        }
      };
      new Razorpay(options).open();
    }
  </script>
</body>
</html>