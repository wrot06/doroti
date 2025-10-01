<?php
require "rene/conexion3.php";

/**
 * Transforma un texto a estilo "Horacio":
 * - Trim y colapsa espacios
 * - Todo a minúsculas
 * - Primera letra en mayúscula
 * - Mayúscula después de puntos
 */
function tipoHoracio(string $texto): string {
    $texto = preg_replace('/\s+/u', ' ', trim($texto));
    if ($texto === '') return '';
    $texto = mb_strtolower($texto, 'UTF-8');
    $chars = preg_split('//u', $texto, -1, PREG_SPLIT_NO_EMPTY);
    $capitalizeNext = true;

    foreach ($chars as $i => $ch) {
        if ($capitalizeNext && preg_match('/\p{L}/u', $ch)) {
            $chars[$i] = mb_strtoupper($ch, 'UTF-8');
            $capitalizeNext = false;
        }
        if ($ch === '.') {
            $capitalizeNext = true;
        }
    }
    return implode('', $chars);
}

/**
 * Normaliza título:
 * - trim
 * - colapsa espacios intermedios
 * - capitaliza primera letra si empieza con letra
 * - corta a $maxLen caracteres
 */
function normalizarTitulo(string $texto, int $maxLen = 56): string {
    $texto = preg_replace('/\s+/u', ' ', trim($texto));
    if ($texto === '') return '';
    if (preg_match('/^\p{L}/u', $texto)) {
        $texto = mb_strtoupper(mb_substr($texto, 0, 1, 'UTF-8')) . mb_substr($texto, 1, null, 'UTF-8');
    }
    if (mb_strlen($texto, 'UTF-8') > $maxLen) {
        $texto = mb_substr($texto, 0, $maxLen, 'UTF-8');
    }
    return $texto;
}

function validarFecha(string $fecha): bool {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

// --- Captura y saneamiento ---
$caja     = intval($_POST['caja'] ?? 0);
$carpeta  = intval($_POST['carpeta'] ?? 0);
$car2     = $carpeta;
$folios   = max(1, intval($_POST['folios'] ?? 1));
$Serie    = trim($_POST['serie'] ?? '');
$subs     = trim($_POST['subserie'] ?? '');

// Título → normalizado y luego a estilo Horacio
$tituloCarpeta = tipoHoracio(
    normalizarTitulo((string)($_POST['tituloCarpeta'] ?? ''))
);

$fechaInicial = trim((string)($_POST['fechaInicial'] ?? ''));
$fechaFinal   = trim((string)($_POST['fechaFinal'] ?? ''));
$estado       = 'C';

// --- Validaciones básicas ---
if ($caja <= 0 || $carpeta <= 0) {
    echo "<div class='alert alert-danger'>Error: Caja o Carpeta inválidas.</div>";
    exit;
}

if ($tituloCarpeta === '') {
    echo "<div class='alert alert-danger'>Error: El título de la carpeta no puede quedar vacío.</div>";
    exit;
}

if ($Serie === '') {
    echo "<div class='alert alert-danger'>Error: Debe seleccionar una Serie.</div>";
    exit;
}

if (!validarFecha($fechaInicial) || !validarFecha($fechaFinal)) {
    echo "<div class='alert alert-danger'>Error: Las fechas no son válidas. Formato esperado: YYYY-MM-DD.</div>";
    exit;
}

if ($fechaFinal < $fechaInicial) {
    echo "<div class='alert alert-danger'>Error: La fecha final no puede ser menor que la inicial.</div>";
    exit;
}

// --- Lógica principal con transacción ---
try {
    $conec->begin_transaction();

    // UPDATE Carpetas
    $stmt = $conec->prepare(
        "UPDATE Carpetas 
         SET Car2 = ?, Serie = ?, Subs = ?, Titulo = ?, FInicial = ?, FFinal = ?, Folios = ?, Estado = ? 
         WHERE Caja = ? AND Car2 = ?"
    );
    if (!$stmt) throw new Exception("Prepare failed (UPDATE): " . $conec->error);

    $stmt->bind_param(
        "isssssisii",
        $car2,
        $Serie,
        $subs,
        $tituloCarpeta,
        $fechaInicial,
        $fechaFinal,
        $folios,
        $estado,
        $caja,
        $car2
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar Carpeta: " . $stmt->error);
    }
    $stmt->close();

    // Transferir datos: INSERT ... SELECT
    $transferQuery = 
        "INSERT INTO IndiceDocumental (Caja, Carpeta, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, Soporte, FechaIngreso) 
         SELECT Caja, Carpeta, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, Soporte, FechaIngreso 
         FROM IndiceTemp 
         WHERE Caja = ? AND Carpeta = ?";
    $stmtTransfer = $conec->prepare($transferQuery);
    if (!$stmtTransfer) throw new Exception("Prepare failed (TRANSFER): " . $conec->error);
    $stmtTransfer->bind_param("ii", $caja, $carpeta);
    if (!$stmtTransfer->execute()) {
        throw new Exception("Error al transferir datos: " . $stmtTransfer->error);
    }
    $stmtTransfer->close();

    // Borrar de IndiceTemp
    $deleteQuery = "DELETE FROM IndiceTemp WHERE Caja = ? AND Carpeta = ?";
    $stmtDelete = $conec->prepare($deleteQuery);
    if (!$stmtDelete) throw new Exception("Prepare failed (DELETE): " . $conec->error);
    $stmtDelete->bind_param("ii", $caja, $carpeta);
    if (!$stmtDelete->execute()) {
        throw new Exception("Error al eliminar datos de IndiceTemp: " . $stmtDelete->error);
    }
    $stmtDelete->close();

    // Commit
    $conec->commit();
    echo "<div class='alert alert-success'>Carpeta finalizada y datos transferidos correctamente.</div>";

} catch (Exception $e) {
    $conec->rollback();
    error_log("finalizarcarpeta.php error: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Ocurrió un error al procesar la solicitud. Contacte al administrador.</div>";
} finally {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
    if (isset($stmtTransfer) && $stmtTransfer instanceof mysqli_stmt) $stmtTransfer->close();
    if (isset($stmtDelete) && $stmtDelete instanceof mysqli_stmt) $stmtDelete->close();
    $conec->close();
}
?>

<!-- HTML de confirmación -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalización de Carpeta</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5 d-flex justify-content-center">
    <div class="card shadow-sm" style="max-width: 500px; width: 100%;">
        <div class="card-body text-center">
            <h2 class="card-title mb-4">Gestión de Carpetas</h2>
            <a href="index.php" class="btn btn-primary btn-lg">Regresar al Inicio</a>
        </div>
    </div>
</div>
</body>
</html>
