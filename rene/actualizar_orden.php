<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "conexion3.php"; // Asegúrate de incluir tu archivo de conexión

// Obtener los datos de la URL
if (isset($_GET['data'])) {
    $data = $_GET['data'];
    // Decodificar los datos JSON
    $jsonData = json_decode(urldecode($data), true);

    // Verificar la conexión
    if ($conec->connect_error) {
        die("Connection failed: " . $conec->connect_error);
    }

    $cambios = $jsonData['cambios'];
    $caja = $jsonData['caja'];
    $carpeta = $jsonData['carpeta'];

    // Preparar la consulta de actualización
    $sql = "UPDATE IndiceTemp SET NoFolioInicio = ?, NoFolioFin = ?, DescripcionUnidadDocumental = ?, paginas = ? WHERE id2 = ? AND Caja = ? AND Carpeta = ?";
    $stmt = $conec->prepare($sql);

    // Verificar que la preparación de la consulta fue exitosa
    if (!$stmt) {
        die("Prepare failed: " . $conec->error);
    }

    // Vincular parámetros y ejecutar la consulta para cada cambio
    foreach ($cambios as $capitulo) {
        $inicio = $capitulo['inicio'];
        $fin = $capitulo['fin'];
        $titulo = $capitulo['titulo'];
        $paginas = $capitulo['paginas'];
        $id2 = $capitulo['id']; // Asegúrate de que estás usando el id correcto

        // Vincular los parámetros (usar "i" para enteros y "s" para strings)
        $stmt->bind_param("iisiiii", $inicio, $fin, $titulo, $paginas, $id2, $caja, $carpeta);

        // Ejecutar la consulta
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar: ' . $stmt->error]);
            exit;
        }
    }

    // Cerrar la declaración y la conexión
    $stmt->close();
    $conec->close();

    // Retornar un mensaje de éxito
    echo json_encode(['status' => 'success', 'message' => 'Actualización exitosa']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos']);
}
?>
