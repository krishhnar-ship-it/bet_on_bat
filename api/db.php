<?php
$conn = new mysqli("localhost","root","","betonbat");

if($conn->connect_error){
 die("Database connection failed");
}
?>
