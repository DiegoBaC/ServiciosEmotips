<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

include 'conexion.php';

// Capturar parámetros - ahora aceptamos 'texto' que puede ser redacción o emoción
$texto = $conn->real_escape_string($_GET['texto'] ?? '');
$emocion = $conn->real_escape_string($_GET['emocion'] ?? ''); // Por compatibilidad
$comentario = $conn->real_escape_string($_GET['comentario'] ?? '');

// Usar texto si está disponible, sino usar emoción
$inputPrincipal = !empty($texto) ? $texto : $emocion;

// Validar entrada
if (empty($inputPrincipal)) {
    echo json_encode(['error' => 'Texto o emoción requerida']);
    exit;
}

// Detectar si es una emoción predefinida o redacción libre
$emocionesValidas = ['feliz', 'triste', 'ansioso', 'enojado', 'motivado', 'cansado', 'agradecido', 'confundido', 'estresado', 'neutral', 'relajado'];
$esEmocion = in_array(strtolower($inputPrincipal), $emocionesValidas);

// Si es una emoción sin comentario, buscar en BD
if ($esEmocion && empty($comentario)) {
    $sql = "SELECT texto FROM frases WHERE emocion = '$inputPrincipal' ORDER BY RAND() LIMIT 1";
    $res = $conn->query($sql);

    if ($row = $res->fetch_assoc()) {
        echo json_encode(['texto' => $row['texto'], 'fuente' => 'database']);
        exit;
    }
}

// Si no hay en BD o hay comentario o es redacción libre, usar Groq
$apiKey = $_ENV['GROQ_API_KEY'];

if (empty($apiKey)) {
    echo json_encode(['error' => 'API Key de Groq no configurada']);
    exit;
}

// Construir el prompt según el tipo de entrada
if ($esEmocion) {
    $prompt = "Escribe una frase motivacional breve en español (máximo 2 oraciones) para alguien que se siente '$inputPrincipal'";
    if (!empty($comentario)) {
        $prompt .= " y comentó: '$comentario'";
    }
} else {
    // Es redacción libre
    $prompt = "La persona escribió lo siguiente sobre cómo se siente: '$inputPrincipal'.";
    if (!empty($comentario)) {
        $prompt .= " También agregó: '$comentario'.";
    }
    $prompt .= " Escribe una frase motivacional breve en español (máximo 2 oraciones) que sea empática y apropiada para lo que expresó.";
}

$prompt .= " Solo responde con la frase, sin comillas.";

// Llamada a la API de Groq
$url = 'https://api.groq.com/openai/v1/chat/completions';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'llama-3.3-70b-versatile',
    'messages' => [
        [
            'role' => 'system',
            'content' => 'Eres un asistente empático que escribe frases motivacionales breves en español. Responde solo con la frase, sin explicaciones.'
        ],
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ],
    'max_tokens' => 150,
    'temperature' => 0.8
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['error' => 'Error de conexión', 'detalle' => $curlError]);
    exit;
}

if ($httpCode !== 200) {
    $errorData = json_decode($response, true);
    echo json_encode([
        'error' => 'Error en la API de Groq',
        'codigo' => $httpCode,
        'detalle' => $errorData['error']['message'] ?? 'Error desconocido'
    ]);
    exit;
}

$data = json_decode($response, true);

if (isset($data['choices'][0]['message']['content'])) {
    $textoIA = trim($data['choices'][0]['message']['content']);
    $textoIA = trim($textoIA, '"\'');
    
    // Solo guardar en catálogo si es emoción predefinida sin comentario
    if ($esEmocion && empty($comentario)) {
        $stmt = $conn->prepare("INSERT INTO frases (emocion, texto) VALUES (?, ?)");
        $stmt->bind_param("ss", $inputPrincipal, $textoIA);
        $stmt->execute();
        $stmt->close();
    }
    
    echo json_encode(['texto' => $textoIA, 'fuente' => 'groq']);
} else {
    // Fallback
    $frasesFallback = [
        'feliz' => 'Disfruta este momento, te lo mereces. La alegría es contagiosa.',
        'triste' => 'Los días difíciles también pasan. Eres más fuerte de lo que crees.',
        'ansioso' => 'Respira profundo. Un paso a la vez, todo va a estar bien.',
        'enojado' => 'Está bien sentir enojo. Toma un momento para ti y deja que pase.',
        'motivado' => '¡Sigue adelante! Tu energía es increíble y todo es posible.',
        'cansado' => 'Descansa sin culpa. Tu cuerpo y mente lo necesitan.',
        'agradecido' => 'La gratitud transforma lo que tenemos en suficiente. Qué hermoso sentir esto.',
        'confundido' => 'No tener todas las respuestas es normal. Date tiempo para encontrar claridad.',
        'estresado' => 'Un paso a la vez. Todo pasará, respira y date un momento.',
        'neutral' => 'Cada día es una oportunidad. Está bien sentirse así.',
        'relajado' => 'Disfruta esta calma. Te lo mereces.'
    ];
    
    $fraseDefault = $frasesFallback[$inputPrincipal] ?? 'Estamos contigo. Cada emoción es válida.';
    echo json_encode(['texto' => $fraseDefault, 'fuente' => 'fallback']);
}
?>