<?php
require 'config.php';

// URL key protection
if (!isset($_GET['key']) || $_GET['key'] !== 'secret123') {
    die("<h2 style='color:#f85149; text-align:center; margin:5rem 0;'>Access Denied</h2>");
}

$msg = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $newStatus = 'approved';
        $msgColor = '#3fb950';
        $msgText = "approved successfully! User can now trade.";
    } elseif ($action === 'reject') {
        $newStatus = 'rejected';
        $msgColor = '#f85149';
        $msgText = "rejected.";
    } else {
        $msg = "<div style='color:#f85149; text-align:center;'>Invalid action.</div>";
        goto show_page;
    }

    // Update status in database
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $success = $stmt->execute([$newStatus, $id]);

    if ($success) {
        // Get user info for message
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $username = $user['username'] ?? 'User';
        $email = $user['email'] ?? 'unknown';

        $msg = "<div style='background:#1f3a1f; color:$msgColor; padding:1rem; border-radius:8px; margin:1rem 0; text-align:center;'>
                  User ID $id ($username) $msgText<br>
                  Simulated email sent to $email.
                </div>";
    } else {
        $msg = "<div style='color:#f85149; text-align:center;'>Failed to update status.</div>";
    }

    // Refresh page to hide processed user
    header("Location: ?key=secret123");
    exit;
}

show_page:
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin – User Approvals</title>
  <link rel="stylesheet" href="style.css">
</head>
<body style="padding:2rem; background:#0d1117; color:#c9d1d9;">

  <div style="max-width:1100px; margin:0 auto; background:#161b22; padding:2rem; border-radius:12px; border:1px solid #30363d;">
    <h1 style="color:#58a6ff; text-align:center;">Admin – User Approvals</h1>

    <?php if ($msg): ?>
      <?php echo $msg; ?>
    <?php endif; ?>

    <table style="width:100%; border-collapse:collapse; margin:1.5rem 0;">
      <tr style="background:#0d1117;">
        <th style="padding:1rem; border:1px solid #30363d; color:#58a6ff;">ID</th>
        <th style="padding:1rem; border:1px solid #30363d; color:#58a6ff;">Username</th>
        <th style="padding:1rem; border:1px solid #30363d; color:#58a6ff;">Email</th>
        <th style="padding:1rem; border:1px solid #30363d; color:#58a6ff;">Wallet</th>
        <th style="padding:1rem; border:1px solid #30363d; color:#58a6ff;">Phone</th>
        <th style="padding:1rem; border:1px solid #30363d; color:#58a6ff;">PAN</th>
        <th style="padding:1rem; border:1px solid #30363d; color:#58a6ff;">Actions</th>
      </tr>

      <?php
      // Only show pending users
      $stmt = $pdo->prepare("SELECT * FROM users WHERE status = 'pending' ORDER BY id DESC");
      $stmt->execute();
      $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (empty($pending)):
      ?>
        <tr><td colspan="7" style="text-align:center; padding:3rem;">No pending users to review.</td></tr>
      <?php else: ?>
        <?php foreach ($pending as $u): ?>
          <tr>
            <td style="padding:1rem; border:1px solid #30363d;"><?php echo $u['id']; ?></td>
            <td style="padding:1rem; border:1px solid #30363d;"><?php echo htmlspecialchars($u['username']); ?></td>
            <td style="padding:1rem; border:1px solid #30363d;"><?php echo htmlspecialchars($u['email']); ?></td>
            <td style="padding:1rem; border:1px solid #30363d;">₹<?php echo number_format($u['wallet'] ?? 0, 2); ?></td>
            <td style="padding:1rem; border:1px solid #30363d;"><?php echo htmlspecialchars($u['phone']); ?></td>
            <td style="padding:1rem; border:1px solid #30363d;"><?php echo htmlspecialchars($u['pan']); ?></td>
            <td style="padding:1rem; border:1px solid #30363d;">
              <a href="?key=secret123&action=approve&id=<?php echo $u['id']; ?>" 
                 style="background:#3fb950; color:black; padding:0.6rem 1.2rem; border-radius:6px; text-decoration:none; margin-right:0.5rem;">
                 Approve
              </a>
              <a href="?key=secret123&action=reject&id=<?php echo $u['id']; ?>" 
                 style="background:#f85149; color:white; padding:0.6rem 1.2rem; border-radius:6px; text-decoration:none;">
                 Reject
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>

    <p style="text-align:center; margin-top:2rem;">
      <a href="/betonbat/" style="color:#58a6ff;">← Back to Home</a>
    </p>
  </div>

</body>
</html>