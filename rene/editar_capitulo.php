<?php 
header('Content-Type: application/json');
require "conexion3.php";

// Recibir datos
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$titulo = trim($_POST['titulo'] ?? '');
$id_carpeta = filter_input(INPUT_POST, 'id_carpeta', FILTER_VALIDATE_INT);

// Validar campos obligatorios
if (!$id || !$titulo || !$id_carpeta) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

// Actualizar solo el título
$sql = "UPDATE IndiceTemp SET DescripcionUnidadDocumental = ? WHERE id2 = ? AND carpeta_id = ?";
$stmt = $conec->prepare($sql);
$stmt->bind_param("sii", $titulo, $id, $id_carpeta);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$stmt->close();
$conec->close();
