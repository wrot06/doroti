<?php
require "rene/conexion3.php"; // Conexión a la base de datos

// Inicialización de variables desde POST
$caja = htmlspecialchars($_POST['caja'] ?? '', ENT_QUOTES, 'UTF-8');
$carpeta = htmlspecialchars($_POST['carpeta'] ?? '', ENT_QUOTES, 'UTF-8');
$car2 = htmlspecialchars($_POST['Car2'] ?? '', ENT_QUOTES, 'UTF-8'); // Asegúrate de recoger Car2
$folios = max(1, intval($_POST['folios'] ?? 1)); // Asegura que folios nunca sea negativo
$serie = htmlspecialchars($_POST['tituloSerie'] ?? '', ENT_QUOTES, 'UTF-8');
$subs = htmlspecialchars($_POST['subserie'] ?? '', ENT_QUOTES, 'UTF-8');
$titulo = htmlspecialchars($_POST['tituloCarpeta'] ?? '', ENT_QUOTES, 'UTF-8');
$fechaInicial = $_POST['fechaInicial'] ?? '';
$fechaFinal = $_POST['fechaFinal'] ?? '';

// Función para registrar logs
function logError($message) {
    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

// Validar campos obligatorios
if (!$caja || !$carpeta || !$car2 || !$serie || !$titulo || !$fechaInicial || !$fechaFinal || !$folios) {
    logError('Datos incompletos: caja=' . $caja . ', carpeta=' . $carpeta . ', car2=' . $car2 . ', serie=' . $serie . ', titulo=' . $titulo . ', fechaInicial=' . $fechaInicial . ', fechaFinal=' . $fechaFinal . ', folios=' . $folios);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

// Iniciar una transacción para asegurar la integridad de los datos
$conec->begin_transaction();

try {
    // Actualizar en la tabla `carpetas`
    $stmt = $conec->prepare("
        UPDATE carpetas 
        SET 
            Caja = ?,
            Car2 = ?, 
            Serie = ?, 
            Subs = ?, 
            Titulo = ?, 
            FInicial = ?, 
            FFinal = ?, 
            Folios = ?, 
            FechaIngreso = NOW(), 
            Estado = 'A'
        WHERE 
            Caja = ? AND 
            Carpeta = ?
    ");
    
    // Corrección aquí: "iisissssii"
    $stmt->bind_param("iisissssii", $caja, $car2, $serie, $subs, $titulo, $fechaInicial, $fechaFinal, $folios, $caja, $carpeta);
    
    if (!$stmt->execute()) {
        logError('Error al ejecutar la consulta de actualización: ' . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta: ' . $stmt->error]);
        exit;
    }

    // Transferir datos de `indicetemp` a `indicedocumental`
    $transferStmt = $conec->prepare("
        INSERT INTO indicedocumental (Caja, Carpeta, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, Soporte, FechaIngreso)
        SELECT Caja, Carpeta, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, Soporte, FechaIngreso
        FROM indicetemp
        WHERE Caja = ? AND Carpeta = ?
    ");
    $transferStmt->bind_param("ii", $caja, $carpeta);
    
    if (!$transferStmt->execute()) {
        logError('Error al transferir datos a indicedocumental: ' . $transferStmt->error);
        throw new Exception('Error al transferir datos a indicedocumental: ' . $transferStmt->error);
    }

    // Eliminar datos de `indicetemp` después de transferirlos
    $deleteStmt = $conec->prepare("DELETE FROM indicetemp WHERE Caja = ? AND Carpeta = ?");
    $deleteStmt->bind_param("ii", $caja, $carpeta);
    
    if (!$deleteStmt->execute()) {
        logError('Error al eliminar datos de indicetemp: ' . $deleteStmt->error);
        throw new Exception('Error al eliminar datos de indicetemp: ' . $deleteStmt->error);
    }

    // Confirmar transacción
    $conec->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conec->rollback();
    logError('Error en transacción: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // Cerrar todas las declaraciones
    if (isset($stmt)) $stmt->close();
    if (isset($transferStmt)) $transferStmt->close();
    if (isset($deleteStmt)) $deleteStmt->close();
}

// Cerrar conexión
$conec->close();
?>
