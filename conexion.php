<?php
header('Access-Control-Allow-Origin:*');
$host = "yamabiko.proxy.rlwy.net";
$user = "root";
$pass = "bqEqlRXHPrqZCTxNZqppwxLjCvYqzbPM";
$db = "railway";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
