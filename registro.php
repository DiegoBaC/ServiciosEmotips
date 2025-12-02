<?php

header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include 'conexion.php';
$data = json_decode(file_get_contents('php://input'), true);
$nombre = $data['nombre'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $nombre, $email, $password);
if ($stmt->execute()) echo json_encode(['success' => true]);
else echo json_encode(['success' => false, 'error' => $conn->error]);
?>
