<?php
header('Access-Control-Allow-Origin:*');
$host = "localhost";
$user = "root";
$pass = "12345";
$db = "emotips";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
