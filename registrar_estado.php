<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// CRÍTICO: Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'conexion.php';

// Obtener datos del body
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Log temporal para debugging (eliminar en producción)
error_log("Raw data: " . $rawData);
error_log("Decoded data: " . print_r($data, true));

// Validar que se recibieron datos
if (!$data) {
    echo json_encode([
        'success' => false, 
        'error' => 'No se recibieron datos',
        'raw' => $rawData
    ]);
    exit;
}

$usuario_id = isset($data['usuario_id']) ? intval($data['usuario_id']) : 0;
$redaccion = $data['redaccion'] ?? null;
$emocion = $data['emocion'] ?? null;
$comentario = $data['comentario'] ?? null;
$frase = $data['frase'] ?? '';
$recomendacion = $data['recomendacion'] ?? '';

// Validar usuario_id
if ($usuario_id <= 0) {
    echo json_encode([
        'success' => false, 
        'error' => 'Usuario inválido',
        'usuario_id_recibido' => $usuario_id
    ]);
    exit;
}

// Validar que haya al menos redacción o emoción
if (empty($redaccion) && empty($emocion)) {
    echo json_encode([
        'success' => false,
        'error' => 'Se requiere redacción o emoción'
    ]);
    exit;
}

try {
    // Preparar query
    $stmt = $conn->prepare(
        "INSERT INTO emociones (usuario_id, redaccion, emocion, comentario, frase, recomendacion, fecha) 
         VALUES (?, ?, ?, ?, ?, ?, NOW())"
    );
    
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error' => 'Error preparando query',
            'detalle' => $conn->error
        ]);
        exit;
    }
    
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
            'error' => 'Error al ejecutar query',
            'detalle' => $stmt->error
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Excepción capturada',
        'detalle' => $e->getMessage()
    ]);
}

$conn->close();
?>
