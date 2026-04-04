<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = 'localhost';
$dbname   = 'betonbat';
$dbuser   = 'root';
$dbpass   = '';  // default XAMPP password is empty
$env = parse_ini_file('.env');
define('CRICKET_API_KEY', $env['CRICKET_API_KEY']);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ── Helper: Check if username or email already exists ───────────────────────
function checkUserExists($pdo, $username, $email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$username, $email]);
    return $stmt->fetch() !== false;
}

// ── Helper: Add new user with hashed password ──────────────────────────────
function addUser($pdo, $data) {
    if (checkUserExists($pdo, $data[':username'], $data[':email'])) {
        return false; // duplicate
    }

    // Hash the password
    $data[':password'] = password_hash($data[':password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, wallet, phone, pan, password, status)
        VALUES (:username, :email, 0.00, :phone, :pan, :password, 'pending')
    ");
    $stmt->execute($data);
    return $pdo->lastInsertId();
}

// ── Helper: Get all users (for admin) ───────────────────────────────────────
function getAllUsers($pdo) {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Helper: Get pending users only (for admin_approve.php) ──────────────────
function getPendingUsers($pdo) {
    $stmt = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Helper: Authenticate user with password_verify ──────────────────────────
function authenticateUser($pdo, $identifier, $password) {
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE username = ? OR email = ?
        LIMIT 1
    ");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return null;
}

// ── Helper: Special admin check (hardcoded for now) ─────────────────────────
function isAdmin($identifier, $password) {
    return $identifier === 'admin@betonbat.com' && $password === 'admin123';
}