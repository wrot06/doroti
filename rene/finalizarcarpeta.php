<?php
require "conexion3.php";

// Captura y sanitiza datos del formulario
$caja = intval($_POST['caja'] ?? 0);
$carpeta = intval($_POST['carpeta'] ?? 0);
$car2 = $carpeta;
$folios = max(1, intval($_POST['folios'] ?? 1));
$Serie = htmlspecialchars($_POST['serie'] ?? '', ENT_QUOTES, 'UTF-8');
$subs = htmlspecialchars($_POST['subserie'] ?? '', ENT_QUOTES, 'UTF-8');
$tituloCarpeta = htmlspecialchars($_POST['tituloCarpeta'] ?? '', ENT_QUOTES, 'UTF-8');
$estado = 'C';
$fechaInicial = htmlspecialchars($_POST['fechaInicial'] ?? '', ENT_QUOTES, 'UTF-8');
$fechaFinal = htmlspecialchars($_POST['fechaFinal'] ?? '', ENT_QUOTES, 'UTF-8');

// Limpieza adicional del título
$tituloCarpeta = trim($tituloCarpeta);
$tituloCarpeta = preg_replace('/\s+/', ' ', $tituloCarpeta);

// Validación de campos requeridos
function validarCampos($campos, $data) {
    foreach ($campos as $campo) {
        if (empty($data[$campo])) {
            return false;
        }
    }
    return true;
}

$camposRequeridos = ['caja', 'carpeta', 'folios', 'serie', 'tituloCarpeta', 'fechaInicial', 'fechaFinal'];

if (!validarCampos($camposRequeridos, $_POST)) {
    echo "<div class='alert alert-danger'>Error: Todos los campos deben ser completados.</div>";
    exit;
}

if (!DateTime::createFromFormat('Y-m-d', $fechaInicial) || !DateTime::createFromFormat('Y-m-d', $fechaFinal)) {
    echo "<div class='alert alert-danger'>Error: Las fechas no son válidas. Formato esperado: YYYY-MM-DD.</div>";
    exit;
}

// ✅ VALIDACIÓN: verificar si existen capítulos
$verificarCapitulos = $conec->prepare("SELECT COUNT(*) AS total FROM IndiceTemp WHERE Caja = ? AND Carpeta = ?");
$verificarCapitulos->bind_param("ii", $caja, $carpeta);
$verificarCapitulos->execute();
$resultadoCapitulos = $verificarCapitulos->get_result();
$totalCapitulos = $resultadoCapitulos->fetch_assoc()['total'] ?? 0;
$verificarCapitulos->close();

if ($totalCapitulos == 0) {
    echo "<div class='alert alert-warning text-center mt-4'>No se puede finalizar la carpeta porque no se ha agregado ningún capítulo.</div>";
    exit;
}

// Inicio de la lógica principal
try {
    // Actualización de la tabla Carpetas
    $stmt = $conec->prepare(
        "UPDATE Carpetas 
        SET Car2 = ?, Serie = ?, Subs = ?, Titulo = ?, FInicial = ?, FFinal = ?, Folios = ?, Estado = ? 
        WHERE Caja = ? AND Car2 = ?"
    );
    $stmt->bind_param("ssssssisii", $car2, $Serie, $subs, $tituloCarpeta, $fechaInicial, $fechaFinal, $folios, $estado, $caja, $car2);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Carpeta actualizada con éxito.</div>";
    } else {
        throw new Exception("Error al actualizar la carpeta: " . $stmt->error);
    }

    // Transferencia de datos de IndiceTemp a IndiceDocumental
    $transferQuery = "
        INSERT INTO IndiceDocumental (
            Caja, Carpeta, serie, DescripcionUnidadDocumental,
            NoFolioInicio, NoFolioFin, paginas, Soporte, FechaIngreso
        )
        SELECT 
            Caja, Carpeta, serie, DescripcionUnidadDocumental,
            NoFolioInicio, NoFolioFin, paginas, ?, NOW()
        FROM IndiceTemp
        WHERE Caja = ? AND Carpeta = ?
    ";
    $stmtTransfer = $conec->prepare($transferQuery);
    $soporte = 'F';
    $stmtTransfer->bind_param("sii", $soporte, $caja, $carpeta);

    if ($stmtTransfer->execute()) {
        echo "<div class='alert alert-success'>Datos transferidos a IndiceDocumental con éxito.</div>";

        // Eliminación de datos de IndiceTemp
        $deleteQuery = "DELETE FROM IndiceTemp WHERE Caja = ? AND Carpeta = ?";
        $stmtDelete = $conec->prepare($deleteQuery);
        $stmtDelete->bind_param("ii", $caja, $carpeta);

        if ($stmtDelete->execute()) {
            echo "<div class='alert alert-success'>Datos eliminados de IndiceTemp con éxito.</div>";
        } else {
            throw new Exception("Error al eliminar datos de IndiceTemp: " . $stmtDelete->error);
        }
        $stmtDelete->close();
    } else {
        throw new Exception("Error al transferir datos: " . $stmtTransfer->error);
    }

    $stmtTransfer->close();
    $stmt->close();
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
} finally {
    $conec->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalización de Carpeta</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/finalizarcarpeta.css">
</head>
<body>
<div class="container mt-5 d-flex justify-content-center">
    <div class="card shadow-sm" style="max-width: 500px; width: 100%;">
        <div class="card-body text-center">
            <h2 class="card-title mb-4">Gestión de Carpetas</h2>
            <a href="../index.php" class="btn btn-primary btn-lg">Regresar al Inicio</a>
        </div>
    </div>
</div>
</body>
</html>
