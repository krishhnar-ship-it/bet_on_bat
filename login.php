<?php
// Only one session_start() - already in config.php, but safe fallback
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = "Please enter username/email and password.";
    } else {
        // Special admin check - this MUST be before normal user check
        if ($identifier === 'admin@betonbat.com' && $password === 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin_approve.php?key=secret123");
            exit;
        }

        // Normal user login
        $user = authenticateUser($pdo, $identifier, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username/email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - Bet On Bat</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      background: #0d0d0d;
      font-family: system-ui, -apple-system, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .login-container {
      background: #111111;
      border-radius: 16px;
      padding: 48px 40px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 20px 50px rgba(0,0,0,0.6);
      border: 1px solid #222;
    }

    h2 {
      color: #4d9fff;
      font-size: 2.1rem;
      margin-bottom: 36px;
      text-align: center;
      font-weight: 600;
      letter-spacing: -0.5px;
    }

    .input-group {
      position: relative;
      margin-bottom: 24px;
    }

    input {
      width: 100%;
      padding: 16px 18px;
      padding-right: 52px;
      border: none;
      border-radius: 10px;
      background: #1a1a1a;
      color: #e0e0e0;
      font-size: 1.05rem;
      transition: all 0.2s ease;
    }

    input::placeholder {
      color: #777;
    }

    input:focus {
      outline: none;
      box-shadow: 0 0 0 2px rgba(77, 159, 255, 0.25);
      background: #1e1e1e;
    }

    .eye-icon {
      position: absolute;
      right: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #888;
      font-size: 1.3rem;
      cursor: pointer;
      user-select: none;
      transition: color 0.2s ease;
    }

    .eye-icon:hover {
      color: #bbb;
    }

    button {
      width: 100%;
      padding: 16px;
      margin: 16px 0 12px;
      background: #28a745;
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.25s ease;
    }

    button:hover {
      background: #218838;
    }

    .signup-link {
      text-align: center;
      color: #888;
      font-size: 0.95rem;
      margin-top: 16px;
    }

    .signup-link a {
      color: #4d9fff;
      text-decoration: none;
      font-weight: 500;
    }

    .signup-link a:hover {
      text-decoration: underline;
    }

    .error {
      color: #ff4d4d;
      text-align: center;
      margin: 16px 0;
      font-size: 0.95rem;
    }
  </style>
</head>
<body>

<div class="login-container">
  <h2>Login</h2>

  <?php if ($error): ?>
    <div class="error"><?php echo $error; ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="input-group">
      <input type="text" name="identifier" placeholder="Username or Email" required autocomplete="username">
    </div>

    <div class="input-group">
      <input 
        type="password" 
        id="password" 
        name="password"
        placeholder="Password" 
        required 
        autocomplete="current-password"
      >
      <span class="eye-icon" id="togglePassword">
        <i class="fa-solid fa-eye"></i>
      </span>
    </div>

    <button type="submit">Login</button>
  </form>

  <div class="signup-link">
    New user? <a href="register.php">Sign Up</a>
  </div>
</div>

<script>
// Password visibility toggle
const togglePassword = document.getElementById('togglePassword');
const passwordInput  = document.getElementById('password');

togglePassword.addEventListener('click', () => {
  const currentType = passwordInput.type;
  passwordInput.type = currentType === 'password' ? 'text' : 'password';

  const icon = togglePassword.querySelector('i');
  icon.classList.toggle('fa-eye');
  icon.classList.toggle('fa-eye-slash');
});
</script>

</body>
</html>