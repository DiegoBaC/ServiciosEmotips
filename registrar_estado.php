<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Capturar TODOS los errores de PHP
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include 'conexion.php';

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Log para debugging
error_log("=== DATOS RECIBIDOS ===");
error_log(print_r($data, true));

if (!$data) {
    http_response_code(200);
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos']);
    exit;
}

$usuario_id = isset($data['usuario_id']) ? intval($data['usuario_id']) : 0;
$redaccion = $data['redaccion'] ?? null;
$emocion = $data['emocion'] ?? null;
$comentario = $data['comentario'] ?? null;
$frase = $data['frase'] ?? '';
$recomendacion = $data['recomendacion'] ?? '';

if ($usuario_id <= 0) {
    http_response_code(200);
    echo json_encode(['success' => false, 'error' => 'Usuario inválido']);
    exit;
}

try {
    error_log("Intentando preparar statement...");
    
    $stmt = $conn->prepare(
        "INSERT INTO emociones (usuario_id, redaccion, emocion, comentario, frase, recomendacion) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    
    if (!$stmt) {
        error_log("ERROR PREPARE: " . $conn->error);
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'error' => 'Error preparando query',
            'detalle' => $conn->error
        ]);
        exit;
    }
    
    error_log("Statement preparado, binding params...");
    
    // Verificar que bind_param no falle
    if (!$stmt->bind_param("isssss", $usuario_id, $redaccion, $emocion, $comentario, $frase, $recomendacion)) {
        error_log("ERROR BIND: " . $stmt->error);
        throw new Exception("Error en bind_param: " . $stmt->error);
    }
    
    error_log("Ejecutando query...");
    
    if ($stmt->execute()) {
        $insertId = $conn->insert_id;
        error_log("SUCCESS: ID insertado = " . $insertId);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'id' => $insertId,
            'message' => 'Estado guardado correctamente'
        ]);
    } else {
        error_log("ERROR EXECUTE: " . $stmt->error);
        http_response_code(200);
        echo json_encode([
            'success' => false, 
            'error' => 'Error al insertar',
            'detalle' => $stmt->error,
            'errno' => $stmt->errno
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("EXCEPTION CAPTURADA: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => 'Excepción',
        'mensaje' => $e->getMessage(),
        'linea' => $e->getLine(),
        'archivo' => basename($e->getFile())
    ]);
} catch (Error $e) {
    error_log("ERROR FATAL: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => 'Error fatal',
        'mensaje' => $e->getMessage(),
        'linea' => $e->getLine()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
