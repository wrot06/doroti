<?php
/**
 * Script de Limpieza y Resumen de Descripciones con Gemini API.
 * Ejecución vía CLI (Consola).
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ser ejecutado desde la consola de comandos.\n");
}

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../rene/conexion3.php';

if ($conec->connect_error) {
    die("Error de conexión a la base de datos: " . $conec->connect_error . "\n");
}

// Configuración por defecto
$options = getopt("", [
    "key:",       // API Key de Gemini
    "table:",     // Tabla (indice_documental_dep_6 o indice_documental_dep_9)
    "batch::",    // Tamaño de lote (por defecto 40)
    "limit::",    // Límite de registros a procesar
    "delay::",    // Retraso entre lotes en segundos (por defecto 1)
    "dry-run",    // Modo simulación (no escribe en DB)
    "model::"     // Modelo de Gemini a usar (por defecto gemini-1.5-flash)
]);

$apiKey = $options['key'] ?? getenv('GEMINI_API_KEY') ?? null;
$table = $options['table'] ?? null;
$batchSize = isset($options['batch']) ? (int)$options['batch'] : 40;
$limit = isset($options['limit']) ? (int)$options['limit'] : 0;
$delay = isset($options['delay']) ? (int)$options['delay'] : 1;
$dryRun = isset($options['dry-run']);
$modelName = $options['model'] ?? 'gemini-1.5-flash';

// Colores ANSI para la consola
$green = "\033[0;32m";
$red = "\033[0;31m";
$yellow = "\033[1;33m";
$cyan = "\033[0;36m";
$reset = "\033[0m";

// Validaciones iniciales
if (!$apiKey) {
    echo "{$red}Error: Se requiere la API Key de Gemini. Usa el parámetro --key=TU_API_KEY o define la variable de entorno GEMINI_API_KEY.{$reset}\n";
    exit(1);
}

$allowedTables = ['indice_documental_dep_6', 'indice_documental_dep_9'];
if (!$table || !in_array($table, $allowedTables)) {
    echo "{$red}Error: Debes especificar una tabla válida usando --table. Valores permitidos: " . implode(', ', $allowedTables) . "{$reset}\n";
    exit(1);
}

// Obtener estadísticas
$sqlTotal = "SELECT COUNT(*) FROM `$table`";
$resTotal = $conec->query($sqlTotal);
$total = $resTotal ? (int)$resTotal->fetch_row()[0] : 0;

$sqlPendientes = "SELECT COUNT(*) FROM `$table` WHERE `procesado_ia` = 0";
$resPendientes = $conec->query($sqlPendientes);
$pendientes = $resPendientes ? (int)$resPendientes->fetch_row()[0] : 0;

$procesados = $total - $pendientes;

echo "\n{$cyan}=== CONFIGURACIÓN DE LIMPIEZA CON GEMINI API ==={$reset}\n";
echo "Tabla seleccionada:  {$yellow}$table{$reset}\n";
echo "Modelo de IA:        {$yellow}$modelName{$reset}\n";
echo "Registros Totales:   {$yellow}$total{$reset}\n";
echo "Ya Procesados:       {$green}$procesados{$reset}\n";
echo "Pendientes de IA:    {$red}$pendientes{$reset}\n";
echo "Tamaño de Lote:      {$yellow}$batchSize{$reset} registros por petición\n";
echo "Retraso (delay):     {$yellow}$delay{$reset} segundos entre peticiones\n";
if ($limit > 0) {
    echo "Límite de este run:  {$yellow}$limit{$reset} registros\n";
}
if ($dryRun) {
    echo "{$yellow}MODO SIMULACIÓN (DRY RUN): No se guardarán los cambios en la base de datos.{$reset}\n";
}
echo "================================================\n\n";

if ($pendientes === 0) {
    echo "{$green}¡Todos los registros de la tabla ya están marcados como procesados!{$reset}\n\n";
    exit(0);
}

// Si no es un dry-run ni limit menor a 10, pedir confirmación interactiva
if (!$dryRun && $limit === 0) {
    echo "¿Deseas continuar con el proceso? (escribe 'si' para confirmar): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim(strtolower($line)) !== 'si') {
        echo "Operación cancelada por el usuario.\n";
        exit(0);
    }
}

// Definir cuántos procesaremos en total en esta ejecución
$aProcesar = $limit > 0 ? min($limit, $pendientes) : $pendientes;
$procesadosEnEjecucion = 0;

// Preparar statement de actualización
$updateStmt = null;
if (!$dryRun) {
    $updateStmt = $conec->prepare("UPDATE `$table` SET `DescripcionUnidadDocumental` = ?, `procesado_ia` = 1 WHERE `id` = ?");
    if (!$updateStmt) {
        die("{$red}Error al preparar consulta de actualización: " . $conec->error . "{$reset}\n");
    }
}

while ($procesadosEnEjecucion < $aProcesar) {
    $currentBatchSize = min($batchSize, $aProcesar - $procesadosEnEjecucion);
    
    // Obtener lote
    $sqlBatch = "SELECT `id`, `DescripcionUnidadDocumental` FROM `$table` WHERE `procesado_ia` = 0 LIMIT $currentBatchSize";
    $resBatch = $conec->query($sqlBatch);
    if (!$resBatch) {
        echo "{$red}Error al obtener registros del lote: " . $conec->error . "{$reset}\n";
        break;
    }
    
    $records = [];
    while ($row = $resBatch->fetch_assoc()) {
        // Limpiamos espacios y validamos si está vacío
        $desc = trim($row['DescripcionUnidadDocumental']);
        $records[] = [
            'id' => (int)$row['id'],
            'texto' => $desc
        ];
    }
    
    if (empty($records)) {
        break;
    }
    
    echo "Procesando lote de " . count($records) . " registros (Progreso: " . ($procesadosEnEjecucion + $procesados) . "/$total)... ";
    
    // Ejecutar llamada a la API con reintentos
    $responseItems = callGeminiAPI($records, $apiKey, $modelName);
    
    if ($responseItems === null) {
        echo "{$red}Fallo al obtener respuesta de la API. Deteniendo ejecución para evitar bucles.{$reset}\n";
        break;
    }
    
    // Actualizar base de datos
    $conec->begin_transaction();
    try {
        $actualizadosEnLote = 0;
        foreach ($responseItems as $item) {
            $id = (int)$item['id'];
            $textoCorregido = trim($item['texto_corregido']);
            
            if ($dryRun) {
                // En modo simulación, mostramos un ejemplo
                if ($actualizadosEnLote === 0) {
                    // Encontrar texto original
                    $original = '';
                    foreach ($records as $r) {
                        if ($r['id'] === $id) {
                            $original = $r['texto'];
                            break;
                        }
                    }
                    echo "\n{$yellow}[Simulación] ID $id:\n  Original:  $original\n  Corregido: $textoCorregido{$reset}\n";
                }
                $actualizadosEnLote++;
            } else {
                $updateStmt->bind_param("si", $textoCorregido, $id);
                $updateStmt->execute();
                if ($updateStmt->affected_rows >= 0) {
                    $actualizadosEnLote++;
                }
            }
        }
        
        // Si hay IDs en el lote original que no vinieron en la respuesta de la IA,
        // los marcamos como procesados de todas formas para no trabar el script, o los dejamos en 0.
        // Lo mejor es marcarlos como procesados pero dejando su texto original para que no se reintenten indefinidamente si causan problemas.
        if (!$dryRun) {
            $responseIds = array_column($responseItems, 'id');
            foreach ($records as $r) {
                if (!in_array($r['id'], $responseIds)) {
                    // Marcar como procesado con su propio texto para evitar bucles de error
                    $updateStmt->bind_param("si", $r['texto'], $r['id']);
                    $updateStmt->execute();
                }
            }
            $conec->commit();
        }
        
        echo "{$green}¡Éxito! ($actualizadosEnLote actualizados){$reset}\n";
        $procesadosEnEjecucion += count($records);
        
    } catch (Exception $e) {
        if (!$dryRun) {
            $conec->rollback();
        }
        echo "{$red}Error al actualizar base de datos: " . $e->getMessage() . "{$reset}\n";
        break;
    }
    
    // Retraso para evitar límites de tasa (Rate Limits)
    if ($delay > 0 && $procesadosEnEjecucion < $aProcesar) {
        sleep($delay);
    }
}

if ($updateStmt) {
    $updateStmt->close();
}

echo "\n{$green}Proceso finalizado. Se procesaron $procesadosEnEjecucion registros en esta ejecución.{$reset}\n\n";

/**
 * Función para llamar a la API de Gemini
 */
function callGeminiAPI(array $records, string $apiKey, string $modelName): ?array {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key=" . $apiKey;
    
    // Instrucciones detalladas de comportamiento
    $prompt = "Eres un sistema experto en archivística y corrección ortográfica de bases de datos. 
Tu tarea es limpiar los campos de texto 'texto' proporcionados en la lista JSON.

REGLAS CRÍTICAS Y OBLIGATORIAS PARA CADA REGISTRO:
1. Si el texto original contiene el carácter de dos puntos (':'), conserva el texto que está antes del primer ':' exactamente igual, sin modificar ninguna palabra ni borrar ninguna letra. Solo realiza correcciones y resúmenes en la parte del texto que se encuentra después del primer ':'.
2. No debes colocar puntos ('.') ni dos puntos (':') en la parte corregida o resumida del texto (la parte que va después del primer ':'). Si en el texto original a corregir había puntos o dos puntos, elimínalos o cámbialos por espacios o comas. El único ':' permitido en el texto final es el que separa la primera parte de la segunda.
3. Corrige los errores ortográficos y la redacción en español de la parte posterior al primer ':'.
4. Si la descripción posterior al primer ':' es extremadamente larga (más de 200 caracteres), genera un resumen conciso y fluido en español de esa parte que conserve los datos esenciales (nombres, fechas, códigos), asegurándote de no usar puntos ('.') ni dos puntos (':').
5. Si la descripción posterior al primer ':' es corta o mediana, solo corrige la ortografía y redacción sin resumir.
6. Retorna la respuesta estrictamente en el formato JSON estructurado definido, asociando el texto procesado al ID original del registro.

Lista de registros a procesar:
" . json_encode($records, JSON_UNESCAPED_UNICODE);

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
                'type' => 'ARRAY',
                'items' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'id' => ['type' => 'INTEGER'],
                        'texto_corregido' => ['type' => 'STRING']
                    ],
                    'required' => ['id', 'texto_corregido']
                ]
            ]
        ]
    ];
    
    $maxRetries = 3;
    $retryDelay = 5; // segundos
    
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $responseText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            
            if ($responseText) {
                $cleanedData = json_decode(trim($responseText), true);
                if (is_array($cleanedData)) {
                    return $cleanedData;
                }
            }
            
            echo "\n[Intento $attempt] Error al parsear JSON devuelto por la IA. Reintentando...\n";
        } elseif ($httpCode === 429) {
            echo "\n[Intento $attempt] Límite de tasa excedido (HTTP 429). Esperando {$retryDelay}s antes de reintentar...\n";
            sleep($retryDelay);
            $retryDelay *= 2; // Retroceso exponencial
        } else {
            echo "\n[Intento $attempt] Error de API (HTTP $httpCode). Curl: $curlError. Body: $response. Reintentando...\n";
            sleep(2);
        }
    }
    
    return null;
}
