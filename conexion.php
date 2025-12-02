<?php
header('Access-Control-Allow-Origin: *');

$host = getenv('MYSQL_HOST') ?: ($_ENV['MYSQL_HOST'] ?? 'mysql.railway.internal');
$user = getenv('MYSQL_USER') ?: ($_ENV['MYSQL_USER'] ?? 'root');
$pass = getenv('MYSQL_PASSWORD') ?: ($_ENV['MYSQL_PASSWORD'] ?? 'bqEqlRXHPrqZCTxNZqppwxLjCvYqzbPM');
$db   = getenv('MYSQL_DATABASE') ?: ($_ENV['MYSQL_DATABASE'] ?? 'railway');
$port = getenv('MYSQL_PORT') ?: ($_ENV['MYSQL_PORT'] ?? 3306);

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    error_log("Error de conexión MySQL: " . $conn->connect_error);
    die(json_encode([
        'success' => false,
        'error' => 'Error de conexión a base de datos'
    ]));
}

$conn->set_charset("utf8mb4");
?>
