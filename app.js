// app.js – Simplified for separate pages (no hash routing)

const API_BASE = "/betonbat/api";

let user = {
  loggedIn: false,
  approved: false,
  username: '',
  wallet: 0.00
};

// Load user status on every page
async function loadUser() {
  try {
    const res = await fetch(API_BASE + "/user.php?action=status", {
      credentials: 'include'
    });
    const data = await res.json();

    console.log("User status:", data);

    if (data.loggedIn) {
      user = {
        loggedIn: true,
        approved: data.approved || false,
        username: data.username || 'User',
        wallet: parseFloat(data.wallet) || 0.00
      };

      // Update UI if elements exist
      document.getElementById("username")?.textContent = user.username;
      document.getElementById("balance")?.textContent = user.wallet.toFixed(2);

      if (!user.approved) {
        document.querySelectorAll('.btn-trade, .btn-add-money, #wallet-deposit').forEach(btn => {
          btn.disabled = true;
          btn.title = "Pending approval";
        });
      }
    }
  } catch (err) {
    console.error("Failed to load user:", err);
  }
}

// Deposit handler (only on wallet.php)
document.addEventListener("DOMContentLoaded", () => {
  const depositBtn = document.getElementById("wallet-deposit");
  if (depositBtn) {
    depositBtn.onclick = async () => {
      if (!user.approved) return alert("Pending approval.");

      const amount = prompt("Enter amount to deposit (₹):", "1000");
      if (!amount || isNaN(amount) || Number(amount) <= 0) return alert("Invalid amount");

      try {
        const res = await fetch(API_BASE + "/wallet.php", {
          method: "POST",
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: "deposit", amount: Number(amount) })
        });
        const data = await res.json();

        if (data.success) {
          user.wallet = data.newWallet;
          document.getElementById("balance")?.textContent = user.wallet.toFixed(2);
          alert(`₹${amount} added! New balance: ₹${user.wallet}`);
        } else {
          alert(data.error || "Deposit failed");
        }
      } catch (err) {
        alert("Error: " + err.message);
      }
    };
  }

  // Load user on every page
  loadUser();
});