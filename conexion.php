<?php
header('Access-Control-Allow-Origin:*');

$host = "mysql.railway.internal"; 
$user = "root";
$pass = "bqEqlRXHPrqZCTxNZqppwxLjCvYqzbPM";
$db   = "railway";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
