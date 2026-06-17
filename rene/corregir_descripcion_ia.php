<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
AuthMiddleware::initSession();

// Verificar autenticación
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// Cargar configuración de la API Key
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}
$configFile = __DIR__ . '/../config/db_config.php';
$dbConfig = file_exists($configFile) ? require $configFile : [];
$apiKey = $dbConfig['gemini_api_key'] ?? null;

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'API Key de Gemini no configurada en el servidor.']);
    exit;
}

// Recibir texto
$texto = trim($_POST['texto'] ?? '');
if ($texto === '') {
    echo json_encode(['status' => 'success', 'texto_corregido' => '']);
    exit;
}

// Preparar llamada a Gemini
$modelName = 'gemini-2.5-flash';
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key=" . $apiKey;

$prompt = "Eres un sistema experto en archivística y corrección ortográfica de bases de datos. 
Tu tarea es corregir y mejorar la descripción de una unidad documental.

REGLAS CRÍTICAS Y OBLIGATORIAS:
1. Si el texto contiene el carácter de dos puntos (':'), conserva el texto que está antes del primer ':' exactamente igual, sin modificar ninguna palabra ni borrar ninguna letra. Solo realiza correcciones y resúmenes en la parte del texto que se encuentra después del primer ':'.
2. No debes colocar puntos ('.') ni dos puntos (':') en la parte corregida o resumida del texto (la parte que va después del primer ':'). Si en el texto original a corregir había puntos o dos puntos, elimínalos o cámbialos por espacios o comas. El único ':' permitido en el texto final es el que separa la primera parte de la segunda.
3. Corrige los errores ortográficos y la redacción en español de la parte posterior al primer ':'.
4. Si la descripción posterior al primer ':' es extremadamente larga o en general el texto final total es largo, genera un resumen conciso y fluido en español de esa parte que conserve los datos esenciales (nombres, fechas, códigos, números), asegurándote de no usar puntos ('.') ni dos puntos (':').
5. Si la descripción posterior al primer ':' es corta o mediana, solo corrige la ortografía y redacción sin resumir.
6. Si no hay errores, devuélvelo igual (pero asegurándote de remover los puntos '.' y dos puntos ':' de la parte posterior al primer ':').
7. LÍMITE ESTRICTO DE LONGITUD: El resultado corregido final completo (incluyendo el prefijo antes de los dos puntos si lo hay) DEBE tener un tamaño máximo de 300 caracteres. Si el texto resultante final supera los 300 caracteres, debes resumirlo y abreviarlo de forma estricta para garantizar que tenga un máximo absoluto de 300 caracteres de longitud total.

Texto a corregir:
\"" . $texto . "\"";

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'responseMimeType' => 'application/json',
        'responseSchema' => [
            'type' => 'OBJECT',
            'properties' => [
                'texto_corregido' => ['type' => 'STRING']
            ],
            'required' => ['texto_corregido']
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $responseText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    
    if ($responseText) {
        $result = json_decode(trim($responseText), true);
        if (isset($result['texto_corregido'])) {
            echo json_encode([
                'status' => 'success',
                'texto_corregido' => trim($result['texto_corregido'])
            ]);
            exit;
        }
    }
    
    echo json_encode(['status' => 'error', 'message' => 'La respuesta de la IA no pudo ser procesada.']);
} elseif ($httpCode === 503) {
    echo json_encode(['status' => 'error', 'message' => 'El servicio de IA está experimentando alta demanda temporal. Por favor, reintenta en unos segundos.']);
} else {
    $errData = json_decode($response, true);
    $errMsg = $errData['error']['message'] ?? 'Error desconocido de la API.';
    echo json_encode(['status' => 'error', 'message' => "Error de API ($httpCode): $errMsg"]);
}
