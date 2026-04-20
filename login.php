<?php
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
        if ($identifier === 'admin@betonbat.com' && $password === 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin_approve.php?key=secret123");
            exit;
        }

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
    }

    .input-group {
      position: relative;
      margin-bottom: 24px;
    }

    input {
      width: 100%;
      padding: 16px 18px;
      padding-right: 52px;
      border-radius: 10px;
      background: #1a1a1a;
      color: #e0e0e0;
      border: none;
    }

    input:focus {
      outline: none;
      box-shadow: 0 0 0 2px rgba(77, 159, 255, 0.25);
    }

    .eye-icon {
      position: absolute;
      right: 18px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
    }

    button {
      width: 100%;
      padding: 16px;
      background: #28a745;
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      cursor: pointer;
    }

    .signup-link {
      text-align: center;
      margin-top: 16px;
      color: #888;
    }

    .signup-link a {
      color: #4d9fff;
      text-decoration: none;
    }

    .error {
      color: red;
      text-align: center;
      margin: 10px 0;
    }

    /* ✅ FORGOT PASSWORD AT BOTTOM */
    .bottom-forgot {
      position: fixed;
      bottom: 15px;
      left: 50%;
      transform: translateX(-50%);
    }

    .bottom-forgot a {
      color: #4d9fff;
      text-decoration: none;
      font-size: 0.9rem;
    }

    .bottom-forgot a:hover {
      text-decoration: underline;
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
      <input type="text" name="identifier" placeholder="Username or Email" required>
    </div>

    <div class="input-group">
      <input type="password" id="password" name="password" placeholder="Password" required>
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

<!-- ✅ FORGOT PASSWORD LINK -->
<div class="bottom-forgot">
  <a href="forgot_password.php">Forgot Password?</a>
</div>

<script>
const togglePassword = document.getElementById('togglePassword');
const passwordInput  = document.getElementById('password');

togglePassword.addEventListener('click', () => {
  passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
});
</script>

</body>
</html>
