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
  <title>Bet On Bat - Cricket Player Trading</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .hero {
      padding: 100px 0 80px;
      background: #0d1117;
      min-height: 85vh;
      display: flex;
      align-items: center;
    }
    .hero-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4rem;
      align-items: center;
    }
    .hero-left h1 {
      font-size: 3.8rem;
      line-height: 1.05;
      margin-bottom: 1rem;
      color: white;
    }
    .hero-left .highlight {
      color: #3fb950;
    }
    .hero-left p {
      font-size: 1.25rem;
      color: #8b949e;
      line-height: 1.7;
      margin-bottom: 2.5rem;
    }
    .hero-buttons {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .btn-primary {
      background: #3fb950;
      color: #0d1117;
      padding: 16px 36px;
      border: none;
      border-radius: 10px;
      font-weight: 700;
      font-size: 1.1rem;
    }
    .btn-secondary {
      background: transparent;
      color: #58a6ff;
      border: 2px solid #58a6ff;
      padding: 16px 36px;
      border-radius: 10px;
      font-weight: 600;
    }
    .status {
      margin-top: 2rem;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 10px;
    }
    .status.approved {
      background: rgba(63,185,80,0.15);
      color: #3fb950;
      border: 1px solid #3fb950;
    }

    /* Right Side Preview Card */
    .preview-card {
      background: #161b22;
      border-radius: 20px;
      padding: 28px;
      box-shadow: 0 30px 60px rgba(0,0,0,0.7);
      border: 1px solid #30363d;
      position: relative;
      transform: rotate(-3deg);
      transition: transform 0.4s ease;
    }
    .preview-card:hover {
      transform: rotate(0deg) scale(1.03);
    }
    .preview-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .live-badge {
      background: #3fb950;
      color: #0d1117;
      padding: 6px 14px;
      border-radius: 30px;
      font-size: 0.85rem;
      font-weight: 700;
    }
    .player-name {
      font-size: 1.55rem;
      font-weight: 600;
      margin: 12px 0 8px;
    }
    .price {
      color: #3fb950;
      font-size: 1.4rem;
      font-weight: 600;
    }
    .holding-info {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid #30363d;
      color: #8b949e;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar">
    <div class="logo">Bet On Bat</div>
    <ul class="nav-links">
      <li><a href="index.php" class="nav-item active">Home</a></li>
      <li><a href="live.php" class="nav-item">Live Matches</a></li>
      <li><a href="holdings.php" class="nav-item">Holdings</a></li>
      <li><a href="wallet.php" class="nav-item">Wallet</a></li>
      <li><a href="performers.php" class="nav-item">Top/Weak</a></li>
      <li><a href="about.php" class="nav-item">About</a></li>
    </ul>
    <div class="user-info">
      <span>Welcome, <strong><?php echo $username; ?></strong></span>
      <span>₹<?php echo number_format($wallet, 2); ?></span>
      <button onclick="openRazorpayFromNav()" class="btn-add-money">+ Add Money</button>
      <a href="logout.php" class="btn-logout">Logout</a>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-container">
      <!-- Left Side -->
      <div class="hero-left">
        <h1>Trade Players.<br><span class="highlight">Win Big.</span></h1>
        <p>
          Buy and sell virtual stocks of cricket players during live matches.<br>
          Real-time prices based on performance. Secure deposits via Razorpay.
        </p>

        <div class="hero-buttons">
          <a href="live.php" class="btn-primary">Start Trading Now</a>
          <a href="wallet.php" class="btn-secondary">Go to Wallet</a>
        </div>

        <?php if($approved): ?>
          <div class="status approved">
            <i class="fas fa-check-circle"></i> You are approved — Start trading now!
          </div>
        <?php else: ?>
          <div class="status" style="color:#f85149;">
            <i class="fas fa-clock"></i> Account pending approval
          </div>
        <?php endif; ?>
      </div>

      <!-- Right Side - Top Player Preview Card -->
      <div class="preview-card">
        <div class="preview-header">
          <div>IND vs AUS • T20</div>
          <div class="live-badge">LIVE</div>
        </div>
        
        <div class="player-name">Virat Kohli</div>
        <div style="margin:12px 0;">
          Current Price: <span class="price">₹1,420 (+8.2%)</span>
        </div>
        
        <div class="holding-info">
          <strong>Top Performer Today</strong><br>
          <small>Most traded • Rising fast</small>
        </div>

        <div style="margin-top:28px; display:flex; gap:12px;">
          <button onclick="window.location.href='live.php'" class="btn-primary" style="flex:1;">BUY</button>
          <button onclick="window.location.href='live.php'" class="btn-secondary" style="flex:1;">SELL</button>
        </div>
      </div>
    </div>
  </section>

  <script src="app.js"></script>
  <script>
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
            if (data.success) {
              alert(`₹${amount} added successfully!`);
              location.reload();
            } else {
              alert(data.error || "Payment failed");
            }
          });
        }
      };
      new Razorpay(options).open();
    }
  </script>
</body>
</html>