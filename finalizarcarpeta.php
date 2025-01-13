<?php
require "rene/conexion3.php";

$caja = htmlspecialchars($_POST['caja'] ?? '', ENT_QUOTES, 'UTF-8');
$carpeta = htmlspecialchars($_POST['carpeta'] ?? '', ENT_QUOTES, 'UTF-8');
$car2 = $carpeta; 
$folios = max(1, intval($_POST['folios'] ?? 1));
$Serie = htmlspecialchars($_POST['serie'] ?? '', ENT_QUOTES, 'UTF-8');
$subs = htmlspecialchars($_POST['subserie'] ?? '', ENT_QUOTES, 'UTF-8');
$tituloCarpeta = htmlspecialchars($_POST['tituloCarpeta'] ?? '', ENT_QUOTES, 'UTF-8');
$estado = 'C'; 

$fechaInicial = htmlspecialchars($_POST['fechaInicial'] ?? '', ENT_QUOTES, 'UTF-8');
$fechaFinal = htmlspecialchars($_POST['fechaFinal'] ?? '', ENT_QUOTES, 'UTF-8');

// Depuración
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Validar que todos los campos requeridos están completos
$requiredFields = ['caja', 'carpeta', 'folios', 'serie', 'tituloCarpeta', 'fechaInicial', 'fechaFinal'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        echo "<div class='alert alert-danger'>Error: Todos los campos deben ser completados.</div>";
        exit;
    }
}

// Validar el formato de las fechas
if (!DateTime::createFromFormat('Y-m-d', $fechaInicial) || !DateTime::createFromFormat('Y-m-d', $fechaFinal)) {
    echo "<div class='alert alert-danger'>Error: Las fechas no son válidas. Formato esperado: YYYY-MM-DD.</div>";
    exit;
}

try {
    // Prepara la consulta para actualizar la tabla carpetas
    $stmt = $conec->prepare("UPDATE carpetas SET Car2 = ?, Serie = ?, Subs = ?, Titulo = ?, FInicial = ?, FFinal = ?, Folios = ?, Estado = ? WHERE Caja = ? AND Car2 = ?");
    
    // Vincula los parámetros
    $stmt->bind_param("ssssssssii", $car2, $Serie, $subs, $tituloCarpeta, $fechaInicial, $fechaFinal, $folios, $estado, $caja, $car2);

    // Ejecuta la consulta
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Carpeta actualizada con éxito.</div>";
    } else {
        throw new Exception("Error al actualizar la carpeta: " . $stmt->error);
    }

    $stmt->close();
    
    // Transfiriendo datos de indicetemp a indicedocumental
    $transferQuery = "INSERT INTO indicedocumental (Caja, Carpeta, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, Soporte, FechaIngreso) 
                      SELECT Caja, Carpeta, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, Soporte, FechaIngreso 
                      FROM indicetemp 
                      WHERE Caja = ? AND Carpeta = ?";

    $stmtTransfer = $conec->prepare($transferQuery);
    $stmtTransfer->bind_param("ii", $caja, $carpeta);
    
    if ($stmtTransfer->execute()) {
        echo "<div class='alert alert-success'>Datos transferidos a la tabla indicedocumental con éxito.</div>";
        
        // Borrar datos de indicetemp
        $deleteQuery = "DELETE FROM indicetemp WHERE Caja = ? AND Carpeta = ?";
        $stmtDelete = $conec->prepare($deleteQuery);
        $stmtDelete->bind_param("ii", $caja, $carpeta);
        
        if ($stmtDelete->execute()) {
            echo "<div class='alert alert-success'>Datos eliminados de la tabla indicetemp con éxito.</div>";
        } else {
            throw new Exception("Error al eliminar los datos de indicetemp: " . $stmtDelete->error);
        }

        $stmtDelete->close();
    } else {
        throw new Exception("Error al transferir los datos: " . $stmtTransfer->error);
    }

    $stmtTransfer->close();
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
} finally {
    $conec->close();
}
?>

<a href="indice.php" class="btn btn-primary">Regresar</a>
