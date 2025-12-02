<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'conexion.php';

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!$data) {
    http_response_code(200);
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos']);
    exit;
}

$usuario_id = isset($data['usuario_id']) ? intval($data['usuario_id']) : 0;

// IMPORTANTE: Convertir null a string vacío para campos NOT NULL
$redaccion = $data['redaccion'] ?? '';  // ← Cambio aquí
$emocion = $data['emocion'] ?? '';      // ← Cambio aquí
$comentario = $data['comentario'] ?? ''; // ← Cambio aquí
$frase = $data['frase'] ?? '';
$recomendacion = $data['recomendacion'] ?? '';

if ($usuario_id <= 0) {
    http_response_code(200);
    echo json_encode(['success' => false, 'error' => 'Usuario inválido']);
    exit;
}

try {
    $stmt = $conn->prepare(
        "INSERT INTO emociones (usuario_id, redaccion, emocion, comentario, frase, recomendacion) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    
    if (!$stmt) {
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'error' => 'Error preparando query',
            'detalle' => $conn->error
        ]);
        exit;
    }
    
    $stmt->bind_param("isssss", $usuario_id, $redaccion, $emocion, $comentario, $frase, $recomendacion);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'id' => $conn->insert_id,
            'message' => 'Estado guardado correctamente'
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            'success' => false, 
            'error' => 'Error al insertar',
            'detalle' => $stmt->error
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => 'Excepción',
        'mensaje' => $e->getMessage()
    ]);
}

$conn->close();
?>
