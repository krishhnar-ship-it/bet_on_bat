<?php
// logout.php - Destroy session and redirect to login

session_start();           // Start session (required to destroy it)
session_unset();           // Clear all session variables
session_destroy();         // Completely destroy the session

// Redirect to login page
header("Location: login.php");
exit;
?>