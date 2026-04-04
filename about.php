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

if (!$user) {
    unset($_SESSION['user_id']);
    header("Location: login.php");
    exit;
}

$username = $user['username'];
$wallet   = $user['wallet'] ?? 0.00;
$approved = ($user['status'] === 'approved');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About - Bet On Bat</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <nav class="navbar">
    <div class="logo">Bet On Bat</div>
    <ul class="nav-links">
      <li><a href="index.php" class="nav-item">Home</a></li>
      <li><a href="live.php" class="nav-item">Live Matches</a></li>
      <li><a href="holdings.php" class="nav-item">Holdings</a></li>
      <li><a href="wallet.php" class="nav-item">Wallet</a></li>
      <li><a href="performers.php" class="nav-item">Top/Weak</a></li>
      <li><a href="about.php" class="nav-item active">About</a></li>
    </ul>
    <div class="user-info">
      <span>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></span>
      <span>₹<?php echo number_format($wallet, 2); ?></span>
      <button class="btn-add-money" <?php if (!$approved) echo 'disabled title="Pending approval"'; ?>>
        + Add Money
      </button>
      <a href="logout.php" class="btn-logout">Logout</a>
    </div>
  </nav>

  <main>
    <h1>About Bet On Bat</h1>

    <p>Bet On Bat is a virtual stock trading platform for cricket players during live international matches.</p>
    <p>Player prices change based on real-time performance. Buy low, sell high, and earn virtual profits.</p>
    <p>Features include live match data, real-time prices, wallet system, and AI analyst insights.</p>

    <h2>How it works</h2>
    <ul>
      <li>Sign up and get approved by admin</li>
      <li>Add money to your wallet</li>
      <li>Trade players in live matches</li>
      <li>Track your holdings and P&L</li>
      <li>Watch top/weak performers</li>
    </ul>

    <p>Contact us for any queries.</p>
  </main>

  <aside class="ai-analyst">
    <div class="ai-header">AI Analyst</div>
    <div id="ai-advice">Waiting for live data...</div>
  </aside>

  <script src="app.js"></script>
</body>
</html>