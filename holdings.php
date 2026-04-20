<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username,wallet,status FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$username = htmlspecialchars($user['username']);
$wallet   = floatval($user['wallet'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Holdings - Bet On Bat</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *{box-sizing:border-box}
    body{background:#0d1117;color:#e6edf3;font-family:'Segoe UI',sans-serif;margin:0}
    .wrap{max-width:1100px;margin:2rem auto;padding:0 20px}
    h1{font-size:1.7rem;font-weight:700;margin-bottom:4px}
    h1 span{color:#3fb950}
    .sub{color:#8b949e;font-size:.88rem;margin-bottom:1.5rem}

    /* ── Summary cards ── */
    .summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:28px}
    .scard{background:#161b22;border-radius:12px;padding:16px 18px;border:1px solid #30363d}
    .scard .lbl{font-size:.72rem;color:#8b949e;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px}
    .scard .val{font-size:1.5rem;font-weight:700;color:#e6edf3}
    .scard .val.green{color:#3fb950}
    .scard .val.red{color:#f85149}

    /* ── Holdings table ── */
    .section-head{font-size:.78rem;font-weight:700;color:#8b949e;text-transform:uppercase;
      letter-spacing:1.2px;margin:0 0 12px;padding-bottom:8px;border-bottom:1px solid #21262d}
    .tbl-wrap{overflow-x:auto;margin-bottom:28px}
    table{width:100%;border-collapse:collapse;font-size:.88rem}
    th{text-align:left;padding:10px 12px;color:#8b949e;font-weight:600;
      font-size:.72rem;text-transform:uppercase;letter-spacing:.8px;border-bottom:1px solid #21262d}
    td{padding:11px 12px;border-bottom:1px solid #21262d;vertical-align:middle}
    tr:hover td{background:#161b22}
    tr:last-child td{border-bottom:none}

    .role-pill{display:inline-block;font-size:.67rem;font-weight:700;padding:2px 8px;border-radius:4px;text-transform:uppercase;letter-spacing:.6px}
    .r-bat{background:#3fb9501a;color:#3fb950}
    .r-bowl{background:#f851491a;color:#f85149}
    .r-ar{background:#58a6ff1a;color:#58a6ff}

    .pnl-pos{color:#3fb950;font-weight:700}
    .pnl-neg{color:#f85149;font-weight:700}

    /* ── Sell button inline ── */
    .btn-sell-sm{padding:5px 14px;background:#f85149;color:#fff;border:none;
      border-radius:6px;font-weight:700;font-size:.78rem;cursor:pointer;transition:opacity .2s}
    .btn-sell-sm:hover{opacity:.8}

    /* ── Trade history ── */
    .trade-list{display:flex;flex-direction:column;gap:8px}
    .trade-row{display:flex;align-items:center;justify-content:space-between;
      background:#161b22;border-radius:8px;padding:11px 16px;border:1px solid #21262d;flex-wrap:wrap;gap:8px}
    .trade-badge{font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase}
    .t-buy{background:#3fb9501a;color:#3fb950;border:1px solid #3fb950}
    .t-sell{background:#f851491a;color:#f85149;border:1px solid #f85149}
    .trade-info{font-size:.88rem}
    .trade-meta{font-size:.75rem;color:#8b949e}

    .empty{text-align:center;padding:36px;color:#8b949e;background:#161b22;
      border-radius:12px;border:1px dashed #30363d}
    .spin-wrap{text-align:center;padding:40px;color:#8b949e}
    .spin{display:inline-block;width:28px;height:28px;border:3px solid #30363d;
      border-top-color:#3fb950;border-radius:50%;animation:sp .8s linear infinite;margin-bottom:8px}
    @keyframes sp{to{transform:rotate(360deg)}}

    #toast{position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:10px;
      font-weight:600;font-size:.9rem;z-index:9999;opacity:0;transition:opacity .3s;pointer-events:none}
    #toast.show{opacity:1}
    #toast.ok{background:#3fb950;color:#0d1117}
    #toast.err{background:#f85149;color:#fff}
  </style>
</head>
<body>

<nav class="navbar">
  <div class="logo">Bet On Bat</div>
  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="live.php">Live Matches</a></li>
    <li><a href="holdings.php" class="active">Holdings</a></li>
    <li><a href="wallet.php">Wallet</a></li>
    <li><a href="performers.php">Top/Weak</a></li>
    <li><a href="about.php">About</a></li>
  </ul>
  <div class="user-info">
    Welcome, <strong><?= $username ?></strong> |
    ₹<span id="wal"><?= number_format($wallet,2) ?></span>
    <a href="logout.php" class="btn-logout">Logout</a>
  </div>
</nav>

<div id="toast"></div>

<main class="wrap">
  <h1>My <span>Holdings</span></h1>
  <p class="sub">Your cricket player stock portfolio — live P&amp;L based on current market prices</p>

  <!-- Summary -->
  <div class="summary" id="summary">
    <div class="scard"><div class="lbl">Wallet Balance</div><div class="val">₹<?= number_format($wallet,2) ?></div></div>
    <div class="scard"><div class="lbl">Total Invested</div><div class="val" id="s-invested">—</div></div>
    <div class="scard"><div class="lbl">Current Value</div><div class="val" id="s-value">—</div></div>
    <div class="scard"><div class="lbl">Unrealised P&L</div><div class="val" id="s-pnl">—</div></div>
    <div class="scard"><div class="lbl">Stocks Held</div><div class="val" id="s-count">—</div></div>
  </div>

  <!-- Holdings table -->
  <div class="section-head">Portfolio</div>
  <div id="holdings-wrap">
    <div class="spin-wrap"><div class="spin"></div><br>Loading...</div>
  </div>

  <!-- Trade History -->
  <div class="section-head" style="margin-top:28px">Trade History</div>
  <div id="trades-wrap">
    <div class="spin-wrap"><div class="spin"></div><br>Loading...</div>
  </div>
</main>

<script>
function toast(msg, ok=true) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'show '+(ok?'ok':'err');
  setTimeout(()=>t.className='', 3000);
}

function roleClass(r){return r==='Batsman'?'r-bat':r==='Bowler'?'r-bowl':'r-ar'}

function fmt(n){return parseFloat(n).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}

async function load() {
  try {
    const res  = await fetch('/betonbat/api/holdings.php');
    const data = await res.json();

    if (!data.success) {
      document.getElementById('holdings-wrap').innerHTML = `<div class="empty">Failed to load holdings.</div>`;
      return;
    }

    const h = data.holdings;

    // ── Summary ──────────────────────────────────────────────────────────────
    const totalInvested = h.reduce((s,r)=>s+parseFloat(r.invested_value),0);
    const totalValue    = h.reduce((s,r)=>s+parseFloat(r.current_value),0);
    const totalPnl      = totalValue - totalInvested;
    const pnlClass      = totalPnl >= 0 ? 'green' : 'red';
    const pnlSign       = totalPnl >= 0 ? '+' : '';

    document.getElementById('s-invested').textContent = '₹'+fmt(totalInvested);
    document.getElementById('s-value').textContent    = '₹'+fmt(totalValue);
    document.getElementById('s-pnl').innerHTML        = `<span class="${pnlClass}">${pnlSign}₹${fmt(Math.abs(totalPnl))}</span>`;
    document.getElementById('s-count').textContent    = h.length;

    // ── Holdings table ────────────────────────────────────────────────────────
    const hw = document.getElementById('holdings-wrap');
    if (!h.length) {
      hw.innerHTML = `<div class="empty"><i class="fa fa-inbox fa-2x" style="margin-bottom:10px;display:block"></i>No holdings yet.<br><a href="live.php" style="color:#3fb950">Go to Live Matches to buy player stocks →</a></div>`;
    } else {
      let rows = h.map(r => {
        const pnl      = parseFloat(r.unrealised_pnl);
        const pnlTxt   = (pnl>=0?'+':'') + '₹' + fmt(Math.abs(pnl));
        const pnlClass = pnl>=0?'pnl-pos':'pnl-neg';
        const role     = r.role || '—';
        return `
          <tr>
            <td>
              <strong>${r.player}</strong><br>
              <small style="color:#8b949e">${r.team||'—'}</small>
            </td>
            <td><span class="role-pill ${roleClass(role)}">${role}</span></td>
            <td style="text-align:right">${r.qty}</td>
            <td style="text-align:right">₹${fmt(r.avg_price)}</td>
            <td style="text-align:right;color:#3fb950;font-weight:600">₹${r.current_price||'—'}</td>
            <td style="text-align:right">₹${fmt(r.invested_value)}</td>
            <td style="text-align:right">₹${fmt(r.current_value)}</td>
            <td style="text-align:right"><span class="${pnlClass}">${pnlTxt}</span></td>
            <td style="text-align:right">
              <button class="btn-sell-sm" onclick="sellHolding(${r.player_id},'${r.player.replace(/'/g,"\\'")}',${r.current_price},${r.qty})">
                Sell
              </button>
            </td>
          </tr>`;
      }).join('');

      hw.innerHTML = `
        <div class="tbl-wrap">
        <table>
          <thead><tr>
            <th>Player</th><th>Role</th><th style="text-align:right">Qty</th>
            <th style="text-align:right">Avg Buy</th><th style="text-align:right">Current</th>
            <th style="text-align:right">Invested</th><th style="text-align:right">Value</th>
            <th style="text-align:right">P&L</th><th style="text-align:right">Action</th>
          </tr></thead>
          <tbody>${rows}</tbody>
        </table>
        </div>`;
    }

    // ── Trade history ─────────────────────────────────────────────────────────
    const tw = document.getElementById('trades-wrap');
    if (!data.trades || !data.trades.length) {
      tw.innerHTML = `<div class="empty">No trades yet.</div>`;
    } else {
      const tradeHtml = data.trades.map(t => {
        const total = (parseFloat(t.price) * parseInt(t.qty)).toFixed(2);
        const date  = new Date(t.created_at).toLocaleString('en-IN',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'});
        return `
          <div class="trade-row">
            <span class="trade-badge ${t.type==='buy'?'t-buy':'t-sell'}">${t.type}</span>
            <div class="trade-info"><strong>${t.player}</strong></div>
            <div class="trade-meta">${t.qty} shares @ ₹${parseFloat(t.price).toFixed(2)}</div>
            <div class="trade-meta" style="color:${t.type==='buy'?'#f85149':'#3fb950'}">${t.type==='buy'?'-':'+'} ₹${total}</div>
            <div class="trade-meta">${date}</div>
          </div>`;
      }).join('');
      tw.innerHTML = `<div class="trade-list">${tradeHtml}</div>`;
    }

  } catch(e) {
    document.getElementById('holdings-wrap').innerHTML = `<div class="empty">Error loading holdings.</div>`;
  }
}

async function sellHolding(playerId, playerName, currentPrice, maxQty) {
  const qty = parseInt(prompt(`Sell how many shares of ${playerName}? (You have ${maxQty})`, '1'));
  if (!qty || isNaN(qty) || qty<=0 || qty>maxQty) {
    if (qty > maxQty) toast(`❌ You only have ${maxQty} shares`, false);
    return;
  }
  try {
    const res  = await fetch('/betonbat/api/trade.php', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({player_id:playerId, player:playerName, type:'sell', qty})
    });
    const data = await res.json();
    if (data.success) {
      toast('✅ ' + data.message);
      document.getElementById('wal').textContent = parseFloat(data.wallet).toLocaleString('en-IN',{minimumFractionDigits:2});
      setTimeout(load, 800); // reload holdings
    } else {
      toast('❌ '+(data.error||'Sell failed'), false);
    }
  } catch(e) { toast('❌ Network error', false); }
}

window.onload = load;
</script>
</body>
</html>