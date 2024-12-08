<?php
require "conexion3.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cambios = $_POST['cambios'];
    $caja = $_POST['caja'];
    $carpeta = $_POST['carpeta'];
    $originales = []; 

    // Obtener los capítulos originales
    $sqlOriginales = "SELECT * FROM IndiceTemp WHERE Caja = ? AND Carpeta = ?";
    $stmtOriginales = $conec->prepare($sqlOriginales);
    $stmtOriginales->bind_param("ii", $caja, $carpeta);
    $stmtOriginales->execute();
    $resultOriginales = $stmtOriginales->get_result();

    while ($row = $resultOriginales->fetch_assoc()) {
        $originales[$row['id2']] = $row;
    }
    $stmtOriginales->close();

    // Actualizar los capítulos en la base de datos
    $errores = [];
    foreach ($cambios as $cambio) {
        $id2 = $cambio['id'];
        $descripcionNueva = $cambio['descripcion'];
        $inicio = $cambio['inicio'];
        $fin = $cambio['fin'];

        // Comprobar si el capítulo original existe
        if (isset($originales[$id2])) {
            // Actualizar la base de datos
            $sql = "UPDATE IndiceTemp SET DescripcionUnidadDocumental = ?, NoFolioInicio = ?, NoFolioFin = ?, id2 = ? WHERE id2 = ?";
            $stmt = $conec->prepare($sql);
            $nuevoId2 = $cambio['nuevoId']; // Asignar el nuevo id2

            $stmt->bind_param("siiii", $descripcionNueva, $inicio, $fin, $nuevoId2, $id2);

            if (!$stmt->execute()) {
                $errores[] = "Error actualizando id2= $id2: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errores[] = "Capítulo original no encontrado para id2= $id2.";
        }
    }

    // Enviar respuesta
    if (empty($errores)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => implode(", ", $errores)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
$conec->close();
