<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");


include 'conexion.php';
$usuario_id = intval($_GET['usuario_id'] ?? 0);
$stmt = $conn->prepare("SELECT emocion, comentario, frase, recomendacion, fecha 
                        FROM emociones WHERE usuario_id = ? ORDER BY fecha DESC");

$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);
echo json_encode($rows);
?>
