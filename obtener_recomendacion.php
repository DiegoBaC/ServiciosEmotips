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
    $sql = "SELECT actividad FROM recomendaciones WHERE emocion = '$inputPrincipal' ORDER BY RAND() LIMIT 1";
    $res = $conn->query($sql);

    if ($row = $res->fetch_assoc()) {
        echo json_encode(['actividad' => $row['actividad'], 'fuente' => 'database']);
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
    $prompt = "Genera una recomendación de actividad práctica en español (máximo 2 oraciones) para alguien que se siente '$inputPrincipal'";
    if (!empty($comentario)) {
        $prompt .= " y comentó: '$comentario'";
    }
} else {
    // Es redacción libre
    $prompt = "La persona escribió lo siguiente sobre cómo se siente: '$inputPrincipal'.";
    if (!empty($comentario)) {
        $prompt .= " También agregó: '$comentario'.";
    }
    $prompt .= " Genera una recomendación de actividad práctica en español (máximo 2 oraciones) que sea específica, accionable y apropiada para lo que expresó.";
}

$prompt .= " Debe ser algo que pueda hacer ahora. Solo responde con la recomendación, sin comillas.";

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
            'content' => 'Eres un asistente empático que recomienda actividades prácticas en español. Responde solo con la recomendación, sin explicaciones.'
        ],
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ],
    'max_tokens' => 150,
    'temperature' => 0.9
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Manejar errores de conexión
if ($curlError) {
    echo json_encode([
        'error' => 'Error de conexión',
        'detalle' => $curlError
    ]);
    exit;
}

// Verificar respuesta HTTP
if ($httpCode !== 200) {
    $errorData = json_decode($response, true);
    echo json_encode([
        'error' => 'Error en la API de Groq',
        'codigo' => $httpCode,
        'detalle' => $errorData['error']['message'] ?? 'Error desconocido'
    ]);
    exit;
}

// Procesar respuesta exitosa
$data = json_decode($response, true);

if (isset($data['choices'][0]['message']['content'])) {
    $textoIA = trim($data['choices'][0]['message']['content']);
    
    // Limpiar comillas si las incluye
    $textoIA = trim($textoIA, '"\'');
    
    // **GUARDAR EN LA TABLA RECOMENDACIONES (para reutilizar en el futuro)**
    // Solo guardar si es emoción predefinida sin comentario (recomendaciones genéricas reutilizables)
    if ($esEmocion && empty($comentario)) {
        $stmt = $conn->prepare("INSERT INTO recomendaciones (emocion, actividad) VALUES (?, ?)");
        $stmt->bind_param("ss", $inputPrincipal, $textoIA);
        $stmt->execute();
        $stmt->close();
    }
    
    echo json_encode([
        'actividad' => $textoIA,
        'fuente' => 'groq'
    ]);
} else {
    // Fallback si la estructura de respuesta es inesperada
    $recomendacionesFallback = [
        'feliz' => 'Comparte tu alegría con alguien que quieres o escribe sobre lo que te hace sentir bien.',
        'triste' => 'Sal a caminar 10 minutos al aire libre o escucha tu canción favorita.',
        'ansioso' => 'Practica respiración profunda por 5 minutos o haz una lista de cosas que puedes controlar.',
        'enojado' => 'Haz ejercicio físico intenso por 15 minutos o escribe lo que sientes sin filtros.',
        'motivado' => 'Aprovecha esta energía para comenzar ese proyecto que tenías pendiente.',
        'cansado' => 'Toma una siesta de 20 minutos o date un baño relajante.',
        'agradecido' => 'Escribe 3 cosas por las que estás agradecido o dile a alguien cuánto lo aprecias.',
        'confundido' => 'Habla con alguien de confianza o escribe tus pensamientos para aclarar tu mente.',
        'estresado' => 'Desconéctate de las pantallas por 30 minutos y haz algo manual como dibujar o cocinar.',
        'neutral' => 'Haz algo que normalmente disfrutas, aunque sea pequeño.',
        'relajado' => 'Aprovecha este momento para hacer algo creativo o simplemente descansar.'
    ];
    
    $actividadDefault = $recomendacionesFallback[$inputPrincipal] ?? 'Tómate un momento para ti. Haz algo pequeño que disfrutes hoy.';
    
    echo json_encode([
        'actividad' => $actividadDefault,
        'fuente' => 'fallback'
    ]);
}
?>