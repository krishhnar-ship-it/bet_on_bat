<!DOCTYPE html>
<html>
<head>
<title>Forgot Password</title>

<style>
body {
  background: #0d0d0d;
  font-family: system-ui, sans-serif;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}

.box {
  background: #111;
  padding: 35px;
  border-radius: 12px;
  width: 360px;
  text-align: center;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

h2 {
  color: #4d9fff;
  margin-bottom: 20px;
}

input {
  width: 100%;
  padding: 12px;
  margin: 15px 0;
  border-radius: 8px;
  border: none;
  background: #1a1a1a;
  color: white;
}

button {
  width: 100%;
  padding: 12px;
  background: #28a745;
  border: none;
  border-radius: 8px;
  color: white;
  cursor: pointer;
  font-weight: 600;
}

button:hover {
  background: #218838;
}

.message {
  margin-top: 15px;
  color: #ccc;
  font-size: 0.95rem;
}

.message a {
  color: #4d9fff;
  text-decoration: none;
}

.message a:hover {
  text-decoration: underline;
}
</style>

</head>

<body>

<div class="box">
  <h2>Forgot Password</h2>

  <form method="POST">
    <input type="email" name="email" placeholder="Enter your email" required>
    <button type="submit">Request Reset</button>
  </form>

  <?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      echo "<div class='message'>
              Password reset is not automatic.<br>
              Please contact the admin at:<br>
              <a href='mailto:bet_on_bat@hotmail.com'>bet_on_bat@hotmail.com</a>
            </div>";
  }
  ?>

</div>

</body>
</html>