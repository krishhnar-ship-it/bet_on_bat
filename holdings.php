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
  <title>Your Holdings - Bet On Bat</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <nav class="navbar">
    <div class="logo">Bet On Bat</div>
    <ul class="nav-links">
      <li><a href="index.php" class="nav-item">Home</a></li>
      <li><a href="live.php" class="nav-item">Live Matches</a></li>
      <li><a href="holdings.php" class="nav-item active">Holdings</a></li>
      <li><a href="wallet.php" class="nav-item">Wallet</a></li>
      <li><a href="performers.php" class="nav-item">Top/Weak</a></li>
      <li><a href="about.php" class="nav-item">About</a></li>
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
    <h1>Your Holdings</h1>

    <?php if (!$approved): ?>
      <p style="color:#f85149; text-align:center; font-weight:bold;">
        Pending approval. Holdings not visible yet.
      </p>
    <?php else: ?>
      <div id="holdings-container">
        <p>Loading your holdings...</p>
      </div>
    <?php endif; ?>
  </main>

  <aside class="ai-analyst">
    <div class="ai-header">AI Analyst</div>
    <div id="ai-advice">Waiting for live data...</div>
  </aside>

  <script src="app.js"></script>
</body>
</html>