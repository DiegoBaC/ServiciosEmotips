    <?php
    header("Access-Control-Allow-Origin: *"); 
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");


    include 'conexion.php';
    $data = json_decode(file_get_contents('php://input'), true);
    $usuario_id = $data['usuario_id'] ?? 0;
        $redaccion = $data['redaccion'] ?? '';
    $emocion = $data['emocion'] ?? '';
    $comentario = $data['comentario'] ?? '';
    $frase = $data['frase'] ?? '';
    $recomendacion = $data['recomendacion'] ?? '';

    $stmt = $conn->prepare("INSERT INTO emociones (usuario_id, redaccion, emocion, comentario, frase, recomendacion) VALUES (?, ?, ?,?,?,?)");
    $stmt->bind_param("isssss", $usuario_id, $redaccion, $emocion, $comentario, $frase, $recomendacion);
    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'error' => $conn->error]);
    ?>
