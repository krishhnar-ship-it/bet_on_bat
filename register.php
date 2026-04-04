<?php
require 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
    $pan      = strtoupper(trim($_POST['pan'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($phone) || empty($pan) || empty($password)) {
        $error = "All fields are required.";
    } elseif (strlen($phone) !== 10) {
        $error = "Phone must be 10 digits.";
    } elseif (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
        $error = "Invalid PAN format.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email.";
    } else {
        // Check for duplicate username or email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "This username or email is already registered. <a href='login.php' style='color:#4d9fff;'>Login instead.</a>";
        } else {
            $data = [
                ':username' => $username,
                ':email'    => $email,
                ':phone'    => $phone,
                ':pan'      => $pan,
                ':password' => $password,  // hashed in config.php
            ];

            try {
                $newId = addUser($pdo, $data);
                $success = "Registration successful!<br>Account ID: #$newId<br>Waiting for admin approval.";
            } catch (PDOException $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up – Bet On Bat</title>
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
      padding-right: 52px;          /* space for eye icon */
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

    /* ─── Eye icon ─── */
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

    .success {
      color: #28a745;
      text-align: center;
      margin: 16px 0;
      font-size: 0.95rem;
    }
  </style>
</head>
<body>

  <div class="login-container">
    <h2>Sign Up</h2>

    <?php if($error): ?>
      <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if($success): ?>
      <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="input-group">
        <input type="text" name="username" placeholder="Username" required>
      </div>

      <div class="input-group">
        <input type="email" name="email" placeholder="Email" required>
      </div>

      <div class="input-group">
        <input type="tel" name="phone" placeholder="Phone (10 digits)" pattern="[0-9]{10}" required>
      </div>

      <div class="input-group">
        <input type="text" name="pan" placeholder="PAN (ABCDE1234F)" maxlength="10" required>
      </div>

      <div class="input-group">
        <input type="password" name="password" id="password-reg" placeholder="Password" required>
        <span class="eye-icon" onclick="togglePassword('password-reg')">👁️</span>
      </div>

      <button type="submit">Register</button>
    </form>

    <div class="signup-link">
      Already have account? <a href="login.php">Login</a>
    </div>
  </div>

  <script>
    function togglePassword(id) {
      const input = document.getElementById(id);
      if (input.type === "password") {
        input.type = "text";
      } else {
        input.type = "password";
      }
    }
  </script>

</body>
</html>