<?php
require "conexion3.php"; // Archivo de conexión a la base de datos

session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

// Verifica si el ID del capítulo, caja y carpeta fueron enviados
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['caja']) && isset($_POST['carpeta'])) {
    $idCapitulo = intval($_POST['id']); // Sanitiza el ID recibido
    $caja = intval($_POST['caja']); // Sanitiza la caja
    $carpeta = intval($_POST['carpeta']); // Sanitiza la carpeta

    // Verifica la conexión
    if ($conec->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'Error de conexión']);
        exit();
    }

    // Iniciar una transacción
    $conec->begin_transaction();

    try {
        // Paso 1: Eliminar el capítulo
        $sql_delete = "DELETE FROM IndiceTemp WHERE id2 = ? AND Caja = ? AND Carpeta = ?";
        $stmt_delete = $conec->prepare($sql_delete);

        if ($stmt_delete === false) {
            throw new Exception("Error en la preparación de la consulta: " . $conec->error);
        }

        $stmt_delete->bind_param("iii", $idCapitulo, $caja, $carpeta);

        if (!$stmt_delete->execute()) {
            throw new Exception("Error al eliminar el capítulo: " . $stmt_delete->error);
        }

        $stmt_delete->close();

        // Paso 2: Obtener los capítulos restantes, ordenados por id2
        $sql_select = "SELECT id2, paginas FROM IndiceTemp WHERE Caja = ? AND Carpeta = ? ORDER BY id2 ASC";
        $stmt_select = $conec->prepare($sql_select);

        if ($stmt_select === false) {
            throw new Exception("Error en la preparación de la consulta: " . $conec->error);
        }

        $stmt_select->bind_param("ii", $caja, $carpeta);

        if (!$stmt_select->execute()) {
            throw new Exception("Error al obtener los capítulos: " . $stmt_select->error);
        }

        $result = $stmt_select->get_result();
        $capitulos = $result->fetch_all(MYSQLI_ASSOC);
        $stmt_select->close();

        // Paso 3: Recalcular páginas y actualizar cada capítulo
        $siguientePagina = 1;

        $sql_update = "UPDATE IndiceTemp SET NoFolioInicio = ?, NoFolioFin = ?, id2 = ? WHERE Caja = ? AND Carpeta = ? AND id2 = ?";
        $stmt_update = $conec->prepare($sql_update);

        if ($stmt_update === false) {
            throw new Exception("Error en la preparación de la consulta: " . $conec->error);
        }

        foreach ($capitulos as $index => $capitulo) {
            $paginaInicio = $siguientePagina;
            $paginaFinal = $paginaInicio + $capitulo['paginas'] - 1;
            $nuevoId2 = $index + 1;

            $stmt_update->bind_param("iiiiii", $paginaInicio, $paginaFinal, $nuevoId2, $caja, $carpeta, $capitulo['id2']);

            if (!$stmt_update->execute()) {
                throw new Exception("Error al actualizar el capítulo: " . $stmt_update->error);
            }

            $siguientePagina = $paginaFinal + 1;
        }

        $stmt_update->close();

        // Confirmar la transacción
        $conec->commit();

        echo json_encode(['status' => 'success', 'message' => 'Capítulo eliminado y páginas recalculadas correctamente']);
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conec->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    // Cerrar la conexión
    $conec->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida']);
    exit();
}
?>
