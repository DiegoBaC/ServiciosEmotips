<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include 'conexion.php';

// Obtener datos del body
$data = json_decode(file_get_contents('php://input'), true);

// Validar que se recibieron datos
if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos']);
    exit;
}

$usuario_id = $data['usuario_id'] ?? 0;
$redaccion = $data['redaccion'] ?? null;
$emocion = $data['emocion'] ?? null;
$comentario = $data['comentario'] ?? null;
$frase = $data['frase'] ?? '';
$recomendacion = $data['recomendacion'] ?? '';

// Validar usuario_id
if ($usuario_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Usuario inválido']);
    exit;
}

// Preparar query - usar NULL para campos vacíos
$stmt = $conn->prepare(
    "INSERT INTO emociones (usuario_id, redaccion, emocion, comentario, frase, recomendacion) 
     VALUES (?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param("isssss", $usuario_id, $redaccion, $emocion, $comentario, $frase, $recomendacion);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'id' => $conn->insert_id,
        'message' => 'Estado guardado correctamente'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => $stmt->error,
        'sql_error' => $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>
