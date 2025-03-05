<?php
$host = "localhost";
$user = "root";
$pass = ""; // Change if needed
$db = "attendance_system";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>
