<?php 
header('Content-Type: application/json');
require "conexion3.php";

// Recibir datos
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$titulo = trim($_POST['titulo'] ?? '');
$caja = filter_input(INPUT_POST, 'caja', FILTER_VALIDATE_INT);
$carpeta = filter_input(INPUT_POST, 'carpeta', FILTER_VALIDATE_INT);

// Validar campos obligatorios
if (!$id || !$titulo || !$caja || !$carpeta) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

// Actualizar solo el título
$sql = "UPDATE IndiceTemp SET DescripcionUnidadDocumental = ? WHERE id2 = ? AND Caja = ? AND Carpeta = ?";
$stmt = $conec->prepare($sql);
$stmt->bind_param("siii", $titulo, $id, $caja, $carpeta);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$stmt->close();
$conec->close();
