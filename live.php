<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username,wallet,status FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$username = htmlspecialchars($user['username']);
$wallet   = floatval($user['wallet'] ?? 0);
$approved = ($user['status'] === 'approved');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Live Trading - Bet On Bat</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *{box-sizing:border-box}
    body{background:#0d1117;color:#e6edf3;font-family:'Segoe UI',sans-serif;margin:0}
    .live-wrap{max-width:1280px;margin:2rem auto;padding:0 20px}
    h1{font-size:1.8rem;font-weight:700;margin-bottom:4px}
    h1 span{color:#3fb950}
    .sub{color:#8b949e;font-size:.9rem;margin-bottom:1.5rem}

    /* ── Section labels ── */
    .section-label{
      display:flex;align-items:center;gap:10px;
      margin:28px 0 12px;font-size:.78rem;font-weight:700;
      text-transform:uppercase;letter-spacing:1.5px
    }
    .section-label .dot{width:10px;height:10px;border-radius:50%}
    .dot-live{background:#3fb950;box-shadow:0 0 8px #3fb950}
    .dot-recent{background:#58a6ff}
    .dot-upcoming{background:#f0883e}

    /* ── Match card ── */
    .match-card{
      background:#161b22;border-radius:16px;padding:22px;
      margin-bottom:20px;border:1px solid #30363d
    }
    .match-head{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px}
    .teams{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .tbadge{background:#21262d;border:1px solid #30363d;border-radius:8px;padding:5px 13px;font-weight:700;font-size:1rem;letter-spacing:.5px}
    .vs{color:#8b949e;font-size:.85rem}
    .mbadge{padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px}
    .b-live{background:#3fb9502a;color:#3fb950;border:1px solid #3fb950}
    .b-recent{background:#58a6ff2a;color:#58a6ff;border:1px solid #58a6ff}
    .b-upcoming{background:#f0883e2a;color:#f0883e;border:1px solid #f0883e}

    .scores{display:flex;gap:16px;font-size:.85rem;color:#8b949e;margin-bottom:14px;flex-wrap:wrap}
    .scores strong{color:#e6edf3}
    .venue{font-size:.78rem;color:#8b949e;margin-bottom:14px}

    /* ── Filter tabs ── */
    .tabs{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:14px}
    .tab{padding:5px 14px;border-radius:20px;border:1px solid #30363d;background:transparent;
      color:#8b949e;cursor:pointer;font-size:.8rem;font-weight:600;transition:all .15s}
    .tab.on{background:#3fb950;color:#0d1117;border-color:#3fb950}

    /* ── Player grid ── */
    .pgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
    .pcard{background:#0d1117;border-radius:11px;padding:16px;border:1px solid #30363d;
      transition:border-color .2s,transform .15s}
    .pcard:hover{border-color:#3fb950;transform:translateY(-2px)}

    .role-pill{display:inline-block;font-size:.67rem;font-weight:700;text-transform:uppercase;
      letter-spacing:.8px;padding:2px 8px;border-radius:4px;margin-bottom:7px}
    .r-bat{background:#3fb9501a;color:#3fb950}
    .r-bowl{background:#f851491a;color:#f85149}
    .r-ar{background:#58a6ff1a;color:#58a6ff}

    .pname{font-size:1rem;font-weight:700;margin-bottom:2px}
    .pteam{font-size:.75rem;color:#8b949e;margin-bottom:10px}

    .price-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
    .cprice{font-size:1.35rem;font-weight:700;color:#3fb950}
    .delta{font-size:.78rem;font-weight:600;padding:2px 7px;border-radius:4px}
    .d-up{background:#3fb9501a;color:#3fb950}
    .d-dn{background:#f851491a;color:#f85149}
    .d-eq{color:#8b949e}

    .btns{display:flex;gap:7px}
    .btn-t{flex:1;padding:8px 0;border:none;border-radius:7px;font-weight:700;
      font-size:.82rem;cursor:pointer;transition:opacity .2s,transform .1s}
    .btn-t:active{transform:scale(.97)}
    .btn-buy{background:#3fb950;color:#0d1117}
    .btn-sell{background:#f85149;color:#fff}
    .btn-t:disabled{opacity:.35;cursor:not-allowed}

    /* ── No match / loading ── */
    .empty{text-align:center;padding:36px;color:#8b949e;background:#161b22;
      border-radius:12px;border:1px dashed #30363d}
    .spin-wrap{text-align:center;padding:50px;color:#8b949e}
    .spin{display:inline-block;width:32px;height:32px;border:3px solid #30363d;
      border-top-color:#3fb950;border-radius:50%;animation:sp .8s linear infinite;margin-bottom:10px}
    @keyframes sp{to{transform:rotate(360deg)}}

    /* ── Toast ── */
    #toast{position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:10px;
      font-weight:600;font-size:.9rem;z-index:9999;opacity:0;transition:opacity .3s;pointer-events:none}
    #toast.show{opacity:1}
    #toast.ok{background:#3fb950;color:#0d1117}
    #toast.err{background:#f85149;color:#fff}

    .locked{background:#f851491a;border:1px solid #f85149;color:#f85149;
      border-radius:8px;padding:11px 16px;margin-bottom:18px;font-size:.88rem}
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
    Welcome, <strong><?= $username ?></strong> |
    ₹<span id="wal"><?= number_format($wallet,2) ?></span>
    <button onclick="addMoney()" class="btn-add-money">+ Add Money</button>
    <a href="logout.php" class="btn-logout">Logout</a>
  </div>
</nav>

<div id="toast"></div>

<main class="live-wrap">
  <h1>Live Trading <span>Dashboard</span></h1>
  <p class="sub">Prices update automatically based on match events — you cannot change prices manually.</p>

  <?php if (!$approved): ?>
    <div class="locked"><i class="fa fa-lock"></i> Account pending approval — trading is disabled.</div>
  <?php endif; ?>

  <div id="root"><div class="spin-wrap"><div class="spin"></div><br>Loading matches...</div></div>
</main>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const APPROVED = <?= $approved ? 'true' : 'false' ?>;

// ── Toast ─────────────────────────────────────────────────────────────────────
function toast(msg, ok=true) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'show ' + (ok ? 'ok' : 'err');
  setTimeout(() => t.className = '', 3000);
}

// ── Render helpers ────────────────────────────────────────────────────────────
function roleClass(r) {
  return r==='Batsman'?'r-bat':r==='Bowler'?'r-bowl':'r-ar';
}

function playerCard(p) {
  const delta  = p.current_price - p.base_price;
  const dTxt   = (delta>=0?'+':'')+delta;
  const dClass = delta>0?'d-up':delta<0?'d-dn':'d-eq';
  const dis    = !APPROVED ? 'disabled' : '';
  const name   = p.name.replace(/'/g,"\\'");
  return `
    <div class="pcard" id="pc-${p.player_id}">
      <span class="role-pill ${roleClass(p.role)}">${p.role}</span>
      <div class="pname">${p.name}</div>
      <div class="pteam">${p.team}</div>
      <div class="price-row">
        <span class="cprice" id="pr-${p.player_id}">₹${p.current_price}</span>
        <span class="delta ${dClass}" id="dl-${p.player_id}">${dTxt}</span>
      </div>
      <div class="btns">
        <button class="btn-t btn-buy"  ${dis} onclick="trade(${p.player_id},'${name}','buy')">
          <i class="fa fa-arrow-up"></i> Buy
        </button>
        <button class="btn-t btn-sell" ${dis} onclick="trade(${p.player_id},'${name}','sell')">
          <i class="fa fa-arrow-down"></i> Sell
        </button>
      </div>
    </div>`;
}

function playerGrid(players, filterId) {
  if (!players.length) return '<p style="color:#8b949e;grid-column:1/-1;padding:20px 0">No players found.</p>';
  return players.map(playerCard).join('');
}

function filterPlayers(gridId, filter, btn) {
  // Tab highlight
  btn.closest('.match-card').querySelectorAll('.tab').forEach(b=>b.classList.remove('on'));
  btn.classList.add('on');

  document.querySelectorAll(`#${gridId} .pcard`).forEach(card => {
    const team = card.querySelector('.pteam').textContent.trim();
    const role = card.querySelector('.role-pill').textContent.trim();
    const show = filter==='all' || team===filter || role===filter;
    card.style.display = show?'':'none';
  });
}

function matchCard(m, section) {
  const bClass = section==='live'?'b-live':section==='recent'?'b-recent':'b-upcoming';
  const bLabel = section==='live'?'🔴 Live':section==='recent'?'✓ Recent':'⏰ Upcoming';
  const gridId = 'g-'+section+'-'+m.id;

  const t1players = m.players.filter(p=>p.team===m.team1||resolveAlias(p.team,m.team1));
  const t2players = m.players.filter(p=>p.team===m.team2||resolveAlias(p.team,m.team2));

  const canTrade = section === 'live';   // Only live matches allow trading

  const playersHtml = m.players.map(p => {
    const delta  = p.current_price - p.base_price;
    const dTxt   = (delta>=0?'+':'')+delta;
    const dClass = delta>0?'d-up':delta<0?'d-dn':'d-eq';
    const dis    = (!APPROVED || !canTrade) ? 'disabled' : '';
    const name   = p.name.replace(/'/g,"\\'");
    return `
      <div class="pcard" id="pc-${p.player_id}">
        <span class="role-pill ${roleClass(p.role)}">${p.role}</span>
        <div class="pname">${p.name}</div>
        <div class="pteam">${p.team}</div>
        <div class="price-row">
          <span class="cprice" id="pr-${p.player_id}">₹${p.current_price}</span>
          <span class="delta ${dClass}" id="dl-${p.player_id}">${dTxt}</span>
        </div>
        <div class="btns">
          <button class="btn-t btn-buy"  ${dis} onclick="trade(${p.player_id},'${name}','buy')" title="${!canTrade?'Trading only available during live matches':''}">
            <i class="fa fa-arrow-up"></i> Buy
          </button>
          <button class="btn-t btn-sell" ${dis} onclick="trade(${p.player_id},'${name}','sell')" title="${!canTrade?'Trading only available during live matches':''}">
            <i class="fa fa-arrow-down"></i> Sell
          </button>
        </div>
      </div>`;
  }).join('');

  return `
    <div class="match-card">
      <div class="match-head">
        <div class="teams">
          <span class="tbadge">${m.team1}</span>
          <span class="vs">vs</span>
          <span class="tbadge">${m.team2}</span>
          <small style="color:#8b949e;margin-left:6px">${m.desc||''}</small>
        </div>
        <span class="mbadge ${bClass}">${bLabel}</span>
      </div>
      ${m.score1||m.score2?`
      <div class="scores">
        ${m.score1?`<div><span class="tbadge" style="font-size:.72rem;padding:2px 8px">${m.team1}</span> <strong>${m.score1}</strong></div>`:''}
        ${m.score2?`<div><span class="tbadge" style="font-size:.72rem;padding:2px 8px">${m.team2}</span> <strong>${m.score2}</strong></div>`:''}
      </div>`:''}
      ${m.venue?`<div class="venue"><i class="fa fa-location-dot"></i> ${m.venue}</div>`:''}
      ${!canTrade?`<div style="font-size:.8rem;color:#f0883e;margin-bottom:12px;"><i class="fa fa-info-circle"></i> Trading available only for live matches</div>`:''}
      <div class="tabs">
        <button class="tab on" onclick="filterPlayers('${gridId}','all',this)">All (${m.players.length})</button>
        <button class="tab" onclick="filterPlayers('${gridId}','${m.team1}',this)">${m.team1} (${t1players.length})</button>
        <button class="tab" onclick="filterPlayers('${gridId}','${m.team2}',this)">${m.team2} (${t2players.length})</button>
        <button class="tab" onclick="filterPlayers('${gridId}','Batsman',this)">Batsmen</button>
        <button class="tab" onclick="filterPlayers('${gridId}','All-Rounder',this)">All-Rounders</button>
        <button class="tab" onclick="filterPlayers('${gridId}','Bowler',this)">Bowlers</button>
      </div>
      <div class="pgrid" id="${gridId}">${playersHtml}</div>
    </div>`;
}

function resolveAlias(dbTeam, apiTeam) {
  const m={PBKS:['PUN','PK'],CSK:['CHE'],MI:['MUM'],KKR:['KOL'],DC:['DEL'],
           SRH:['HYD'],RR:['RAJ'],GT:['GUJ'],LSG:['LKN'],RCB:['BAN']};
  return (m[dbTeam]||[]).includes(apiTeam.toUpperCase());
}

// ── Load matches ──────────────────────────────────────────────────────────────
async function load() {
  const root = document.getElementById('root');
  try {
    const res  = await fetch('/betonbat/api/matches.php');
    const data = await res.json();
    let html   = '';

    // LIVE
    html += `<div class="section-label"><span class="dot dot-live"></span> Live Match</div>`;
    if (data.live && data.live.length) {
      data.live.forEach(m => html += matchCard(m,'live'));
    } else {
      html += `<div class="empty"><i class="fa fa-satellite-dish fa-lg" style="margin-bottom:8px;display:block"></i>No live IPL match right now</div>`;
    }

    // RECENT
    html += `<div class="section-label"><span class="dot dot-recent"></span> Previous Match</div>`;
    if (data.recent && data.recent.length) {
      data.recent.forEach(m => html += matchCard(m,'recent'));
    } else {
      html += `<div class="empty">No recent match data</div>`;
    }

    // UPCOMING
    html += `<div class="section-label"><span class="dot dot-upcoming"></span> Upcoming Match</div>`;
    if (data.upcoming && data.upcoming.length) {
      data.upcoming.forEach(m => html += matchCard(m,'upcoming'));
    } else {
      html += `<div class="empty">No upcoming match data</div>`;
    }

    root.innerHTML = html;

    // Auto-refresh every 60 seconds if there's a live match
    if (data.live && data.live.length) {
      setTimeout(load, 60000);
    }

  } catch(e) {
    root.innerHTML = `<div class="empty"><i class="fa fa-wifi fa-lg" style="color:#f85149;margin-bottom:8px;display:block"></i>Could not load matches. Check your API key or internet connection.</div>`;
  }
}

// ── Trade ─────────────────────────────────────────────────────────────────────
async function trade(playerId, playerName, type) {
  const qty = parseInt(prompt(`${type.toUpperCase()} how many shares of ${playerName}?`, '1'));
  if (!qty || isNaN(qty) || qty <= 0) return;

  try {
    const res  = await fetch('/betonbat/api/trade.php', {
      method: 'POST',
      credentials: 'include',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({player_id:playerId, player:playerName, type, qty})
      // NOTE: price is NOT sent — server fetches it from DB
    });
    const data = await res.json();
    if (data.success) {
      toast('✅ ' + data.message);
      document.getElementById('wal').textContent = parseFloat(data.wallet).toLocaleString('en-IN',{minimumFractionDigits:2});
    } else {
      toast('❌ ' + (data.error||'Trade failed'), false);
    }
  } catch(e) {
    toast('❌ Network error', false);
  }
}

// ── Razorpay ──────────────────────────────────────────────────────────────────
function addMoney() {
  const amount = prompt("Enter deposit amount (₹):","1000");
  if (!amount||isNaN(amount)||Number(amount)<=0) return alert("Invalid amount");
  new Razorpay({
    key:"rzp_test_SXWXNyfEjgYBCf", amount:Number(amount)*100, currency:"INR",
    name:"Bet On Bat", description:"Wallet Deposit",
    handler: function(r){
      fetch("/betonbat/api/wallet2.php",{
        method:"POST",credentials:"include",
        headers:{"Content-Type":"application/json"},
        body:JSON.stringify({action:"razorpay_verify",razorpay_payment_id:r.razorpay_payment_id,amount:Number(amount)})
      }).then(x=>x.json()).then(d=>{
        if(d.success){toast('₹'+amount+' added!');setTimeout(()=>location.reload(),1500);}
      });
    }
  }).open();
}

window.onload = load;
</script>
</body>
</html>