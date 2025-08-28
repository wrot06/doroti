<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "conexion3.php"; // Conexión a la BD

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

// Preparar consulta
$sql = "UPDATE IndiceTemp 
        SET NoFolioInicio = ?, NoFolioFin = ?, DescripcionUnidadDocumental = ?, paginas = ?
        WHERE id2 = ? AND Caja = ? AND Carpeta = ?";
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

    $stmt->bind_param("iisiiii", $inicio, $fin, $titulo, $paginas, $id2, $caja, $carpeta);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar: ' . $stmt->error]);
        exit;
    }
}

$stmt->close();
$conec->close();

echo json_encode(['status' => 'success', 'message' => 'Actualización exitosa']);
