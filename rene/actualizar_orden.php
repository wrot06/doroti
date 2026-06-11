<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/conexion3.php'; // Conexión a la BD

// Leer el JSON del cuerpo de la solicitud POST
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

// Validar estructura
if (!isset($data['cambios'], $data['caja'], $data['carpeta'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

$cambios = $data['cambios'];
$caja = (int) $data['caja'];
$carpeta = (int) $data['carpeta'];

// Verificar conexión
if ($conec->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $conec->connect_error]);
    exit;
}

// Obtener id de la carpeta correspondiente
$sql_carpeta = "SELECT id FROM carpetas WHERE Caja = ? AND Carpeta = ? AND Estado = 'A' LIMIT 1";
$stmt_carpeta = $conec->prepare($sql_carpeta);
$stmt_carpeta->bind_param("ii", $caja, $carpeta);
$stmt_carpeta->execute();
$res_carpeta = $stmt_carpeta->get_result();
$row_carpeta = $res_carpeta->fetch_assoc();
$carpeta_id = $row_carpeta ? (int)$row_carpeta['id'] : 0;
$stmt_carpeta->close();

if ($carpeta_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No se encontró la carpeta correspondiente']);
    exit;
}

// Preparar consulta
$sql = "UPDATE indice_temp 
        SET NoFolioInicio = ?, NoFolioFin = ?, DescripcionUnidadDocumental = ?, paginas = ?
        WHERE id2 = ? AND carpeta_id = ?";
$stmt = $conec->prepare($sql);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Error preparando la consulta: ' . $conec->error]);
    exit;
}

// Ejecutar actualizaciones
foreach ($cambios as $capitulo) {
    $inicio = (int) $capitulo['inicio'];
    $fin = (int) $capitulo['fin'];
    $titulo = trim($capitulo['titulo']);
    $paginas = (int) $capitulo['paginas'];
    $id2 = (int) $capitulo['id'];

    $stmt->bind_param("iisiii", $inicio, $fin, $titulo, $paginas, $id2, $carpeta_id);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar: ' . $stmt->error]);
        exit;
    }
}

$stmt->close();
$conec->close();

echo json_encode(['status' => 'success', 'message' => 'Actualización exitosa']);
