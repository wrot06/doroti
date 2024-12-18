<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'conexion3.php';

// Leer los datos enviados
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos recibidos
if (!isset($data['cambios']) || !isset($data['caja']) || !isset($data['carpeta'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

$cambios = $data['cambios'];
$caja = $data['caja'];
$carpeta = $data['carpeta'];

$errores = [];

// Actualizar cada capítulo
foreach ($cambios as $capitulo) {
    $id = $capitulo['id'];
    $inicio = (int)$capitulo['inicio'];
    $fin = (int)$capitulo['fin'];

    $sql = "UPDATE IndiceTemp SET NoFolioInicio = ?, NoFolioFin = ? WHERE Caja = ? AND Carpeta = ? AND id2 = ?";
    $stmt = $conec->prepare($sql);
    
    if ($stmt === false) {
        $errores[] = "Error en la preparación de la consulta: " . $conec->error;
        continue; // Salir del bucle si la consulta no se puede preparar
    }

    $stmt->bind_param("iiiii", $inicio, $fin, $caja, $carpeta, $id);

    if (!$stmt->execute()) {
        $errores[] = "Error actualizando ID $id: " . $stmt->error;
    }
    $stmt->close();
}

// Respuesta al cliente
if (empty($errores)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => implode(", ", $errores)]);
}
$conec->close();
?>
