<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT username, wallet, status, 
           COALESCE(bank_account, '') as bank_account,
           COALESCE(ifsc, '') as ifsc,
           COALESCE(beneficiary_name, '') as beneficiary_name 
    FROM users WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$username     = htmlspecialchars($user['username']);
$wallet       = floatval($user['wallet'] ?? 0);
$approved     = ($user['status'] === 'approved');
$bankAccount  = htmlspecialchars($user['bank_account']);
$ifsc         = htmlspecialchars($user['ifsc']);
$beneficiary  = htmlspecialchars($user['beneficiary_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Wallet - Bet On Bat</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <style>
    .wallet-card { background:#161b22; padding:2.8rem; border-radius:16px; border:1px solid #30363d; max-width:620px; margin:2rem auto; box-shadow:0 15px 40px rgba(0,0,0,0.7); }
    .balance-box { background:#0d1117; padding:2rem; border-radius:12px; text-align:center; margin-bottom:2.5rem; }
    .balance-amount { font-size:3.8rem; font-weight:700; color:#3fb950; margin:0.5rem 0; }
    .section-title { color:#58a6ff; margin:2rem 0 1.2rem; font-size:1.35rem; }
    .form-input { width:100%; padding:14px 16px; margin:10px 0; background:#0d1117; border:1px solid #30363d; border-radius:10px; color:#c9d1d9; font-size:1rem; }
    .form-input:focus { border-color:#58a6ff; box-shadow:0 0 0 3px rgba(88,166,255,0.15); outline:none; }
    .btn-modern { padding:14px 32px; border:none; border-radius:10px; font-size:1.08rem; font-weight:600; cursor:pointer; transition:all 0.3s; margin:8px 6px; }
    .btn-deposit { background:linear-gradient(135deg,#3fb950,#2ea043); color:#0d1117; }
    .btn-withdraw { background:linear-gradient(135deg,#f85149,#c92c2c); color:white; }
    .btn-modern:hover { transform:translateY(-4px); box-shadow:0 10px 25px rgba(0,0,0,0.5); }
    .btn-modern:disabled { opacity:0.5; cursor:not-allowed; }
    .message { padding:1rem; border-radius:10px; margin:1.2rem 0; text-align:center; font-weight:500; }
    .success { background:#1f3a1f; color:#3fb950; }
    .error { background:#3a1f1f; color:#f85149; }
  </style>
</head>
<body>

  <nav class="navbar">
    <div class="logo">Bet On Bat</div>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="live.php">Live Matches</a></li>
      <li><a href="holdings.php">Holdings</a></li>
      <li><a href="wallet.php" class="active">Wallet</a></li>
      <li><a href="performers.php">Top/Weak</a></li>
      <li><a href="about.php">About</a></li>
    </ul>
    <div class="user-info">
      Welcome, <strong><?php echo $username; ?></strong> | 
      ₹<?php echo number_format($wallet, 2); ?> 
      <button onclick="openRazorpayFromNav()" class="btn-add-money">+ Add Money</button>
      <a href="logout.php" style="color:#f85149; margin-left:15px;">Logout</a>
    </div>
  </nav>

  <main>
    <div class="wallet-card">
      <h1>Wallet</h1>

      <?php if (!$approved): ?>
        <div class="message error">Your account is pending admin approval.<br>Deposit and withdrawal will be enabled after approval.</div>
      <?php else: ?>

        <div class="balance-box">
          <div style="color:#8b949e; font-size:1.1rem;">Available Balance</div>
          <div class="balance-amount">₹<?php echo number_format($wallet, 2); ?></div>
        </div>

        <div class="section-title">💳 Bank / UPI Details</div>
        <form method="POST">
          <input type="hidden" name="action" value="save_bank">
          <input type="text" name="bank_account" class="form-input" placeholder="UPI ID or Account Number" value="<?php echo $bankAccount; ?>" required>
          <input type="text" name="ifsc" class="form-input" placeholder="IFSC Code (Optional)" value="<?php echo $ifsc; ?>">
          <input type="text" name="beneficiary" class="form-input" placeholder="Beneficiary Name" value="<?php echo $beneficiary; ?>" required>
          <button type="submit" class="btn-modern btn-deposit" style="width:100%; margin-top:12px;">
            <?php echo $bankAccount ? 'Update Bank / UPI Details' : 'Save Bank / UPI Details'; ?>
          </button>
        </form>

        <div style="margin-top:3rem; text-align:center;">
          <button onclick="openRazorpay()" class="btn-modern btn-deposit" style="font-size:1.15rem; padding:16px 40px;">
            <i class="fas fa-plus"></i> Deposit via Razorpay
          </button>
          <button onclick="requestWithdraw()" class="btn-modern btn-withdraw" style="font-size:1.15rem; padding:16px 40px;" <?php if($wallet <= 0) echo 'disabled'; ?>>
            <i class="fas fa-minus"></i> Withdraw Money
          </button>
        </div>

      <?php endif; ?>
    </div>
  </main>

  <script>
    const razorpayKey = "rzp_test_SXWXNyfEjgYBCf";

    // This function is called from navbar "+ Add Money" button
    function openRazorpayFromNav() {
      openRazorpay();
    }

    function openRazorpay() {
      let amountStr = prompt("Enter deposit amount (₹):", "1000");
      let amount = parseFloat(amountStr);
      if (!amount || amount <= 0) {
        alert("Please enter a valid amount greater than ₹0");
        return;
      }

      const options = {
        key: razorpayKey,
        amount: amount * 100,
        currency: "INR",
        name: "Bet On Bat",
        description: "Wallet Deposit",
        handler: function (response) {
          fetch("/betonbat/api/wallet2.php", {
            method: "POST",
            credentials: 'include',
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              action: "razorpay_verify",
              razorpay_payment_id: response.razorpay_payment_id,
              amount: amount
            })
          })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              alert(`✅ ₹${amount} added successfully!`);
              location.reload();
            } else {
              alert(data.error || "Payment verification failed");
            }
          });
        },
        theme: { color: "#3fb950" }
      };

      new Razorpay(options).open();
    }

    function requestWithdraw() {
      if (<?php echo $wallet; ?> <= 0) {
        alert("❌ You have insufficient balance to withdraw.");
        return;
      }

      let amountStr = prompt("Enter withdrawal amount (₹):", "500");
      let amount = parseFloat(amountStr);
      if (!amount || amount <= 0) return alert("Invalid amount");
      if (amount > <?php echo $wallet; ?>) return alert(`❌ Insufficient balance! You only have ₹<?php echo number_format($wallet, 2); ?>`);

      if (confirm(`Confirm withdrawal of ₹${amount}?`)) {
        fetch("/betonbat/api/wallet2.php", {
          method: "POST",
          credentials: 'include',
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "withdraw", amount: amount })
        })
        .then(r => r.json())
        .then(data => {
          alert(data.success ? `✅ Withdrawal request submitted!` : (data.error || "Failed"));
          if (data.success) location.reload();
        });
      }
    }
  </script>
</body>
</html>