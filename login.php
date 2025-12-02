<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include 'conexion.php';
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$stmt = $conn->prepare("SELECT id, nombre, password FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if ($user && $password === $user['password']) {  // comparación directa sin encriptar
  echo json_encode(['success' => true, 'usuario_id' => $user['id'], 'nombre' => $user['nombre']]);
} else {
  echo json_encode(['success' => false]);
}
?>
