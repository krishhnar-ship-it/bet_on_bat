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
  <title>About Us - Bet On Bat</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .about-container {
      max-width: 1000px;
      margin: 3rem auto;
      padding: 0 20px;
      line-height: 1.8;
      color: #c9d1d9;
    }
    .about-container h1 {
      color: #58a6ff;
      margin-bottom: 2rem;
      text-align: center;
    }
    .about-container h2 {
      color: #3fb950;
      margin-top: 3rem;
      margin-bottom: 1rem;
    }
    .highlight-box {
      background: #1f2937;
      border-left: 5px solid #3fb950;
      padding: 1.5rem;
      margin: 2rem 0;
      border-radius: 8px;
    }
  </style>
</head>
<body>

  <nav class="navbar">
    <div class="logo">Bet On Bat</div>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="live.php">Live Matches</a></li>
      <li><a href="holdings.php">Holdings</a></li>
      <li><a href="wallet.php">Wallet</a></li>
      <li><a href="performers.php">Top/Weak</a></li>
      <li><a href="about.php" class="active">About</a></li>
    </ul>
    <div class="user-info">
      Welcome, <strong><?php echo $username; ?></strong> | ₹<?php echo number_format($wallet, 2); ?>
      <button onclick="openRazorpayFromNav()" class="btn-add-money">+ Add Money</button>
      <a href="logout.php" class="btn-logout">Logout</a>
    </div>
  </nav>

  <main class="about-container">
    <h1>About Bet On Bat</h1>

    <p>
      Bet On Bat is a next-generation virtual stock trading platform that lets users buy and sell shares of real cricket players during live international and IPL matches. 
      Our platform transforms cricket into a skill-based trading experience where player prices fluctuate in real-time based on their on-field performance.
    </p>

    <h2>Why Bet On Bat?</h2>
    <p>
      We believe cricket is more than just a sport — it's data, strategy, and performance analytics. 
      Bet On Bat brings the excitement of the stock market into the world of cricket, allowing fans to apply their knowledge of the game to make informed trading decisions.
    </p>

    <div class="highlight-box">
      <h2>Important: This Platform is Strictly for Adults (18+ Only)</h2>
      <p>
        Bet On Bat is designed exclusively for adults aged 18 years and above. 
        This is a serious skill-based trading platform that requires analytical thinking, understanding of cricket statistics, player form, pitch conditions, and match situations. 
        It must be treated like a professional analyst tool — not casual entertainment. 
        Users are expected to approach trading with discipline, research, and responsibility.
      </p>
    </div>

    <h2>This is NOT Gambling</h2>
    <p>
      Bet On Bat is fundamentally different from gambling. 
      There is no element of pure chance or house edge. 
      Success on our platform depends entirely on your knowledge of cricket, ability to analyze player performance, and strategic decision-making — just like trading stocks in the financial market. 
      Prices move based on real, verifiable on-field events (runs scored, wickets taken, strike rate, economy, fielding efforts, etc.). 
      It is a skill-based virtual trading game, not a game of luck.
    </p>

    <h2>Our Mission</h2>
    <p>
      To create the most engaging and educational cricket trading experience in the world. 
      We aim to bridge the gap between passionate cricket fans and data-driven decision making. 
      Every trade you make helps you understand the game better while giving you the thrill of real-time market movements.
    </p>

    <h2>Key Features</h2>
    <ul style="margin-left:20px; line-height:2;">
      <li>Real-time player price updates based on live match performance</li>
      <li>Secure deposits and withdrawals via Razorpay</li>
      <li>Virtual stock portfolio (Holdings) with P&L tracking</li>
      <li>Top & Weak performers analytics</li>
      <li>Skill-based trading with no house edge</li>
      <li>Bank/UPI details saved securely for easy withdrawals</li>
      <li>Admin-approved user system for responsible trading</li>
    </ul>

    <h2>Responsible Trading</h2>
    <p>
      We strongly encourage responsible usage. Set your own limits, trade only what you can afford to lose in a virtual sense, and treat this as a learning and entertainment tool. 
      Bet On Bat is built to enhance your cricket knowledge and analytical skills — not to encourage reckless behavior.
    </p>

    <p style="text-align:center; margin-top:4rem; color:#8b949e;">
      © 2026 Bet On Bat. All rights reserved.<br>
      This is a skill-based virtual trading platform for cricket enthusiasts aged 18 and above.
    </p>
  </main>

  <script src="app.js"></script>
</body>
</html>