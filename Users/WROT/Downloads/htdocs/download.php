<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/rene/conexion3.php';  // Debe definir $conec (mysqli)

// ——————————————————————————————
// 1) Autenticación
// ——————————————————————————————
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit();
}

// ——————————————————————————————
// 2) Determinar tabla, id y columnas
// ——————————————————————————————
$table        = '';
$idParam      = null;
$selectCols   = '';  // columnas a seleccionar
$hasFileName  = false;
$hasOwner     = false;

// Si viene ?id= → tabla Documentos
if (isset($_GET['id']) && ($tmp = intval($_GET['id'])) > 0) {
    $table       = 'Documentos';
    $idParam     = $tmp;
    // Documentos: archivo_pdf, archivo_nombre, user_id
    $selectCols  = 'archivo_pdf, archivo_nombre, user_id';
    $hasFileName = true;
    $hasOwner    = true;
}
// Si viene ?id2= → tabla IndiceDocumental
elseif (isset($_GET['id2']) && ($tmp2 = intval($_GET['id2'])) > 0) {
    $table       = 'IndiceDocumental';
    $idParam     = $tmp2;
    // IndiceDocumental: sólo archivo_pdf
    $selectCols  = 'archivo_pdf';
    $hasFileName = false;
    $hasOwner    = false;
}
else {
    http_response_code(400);
    die("Debe proporcionar un parámetro 'id' o 'id2' válido.");
}

// ——————————————————————————————
// 3) Preparar y ejecutar la consulta
// ——————————————————————————————
$sql = sprintf(
    "SELECT %s FROM %s WHERE id = ? AND archivo_pdf IS NOT NULL",
    $selectCols,
    $table
);

$stmt = $conec->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    die("Error al preparar la consulta: " . $conec->error);
}

$stmt->bind_param('i', $idParam);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    http_response_code(404);
    die("Documento no encontrado en la tabla {$table}.");
}

// 4) Obtener resultados
if ($hasFileName && $hasOwner) {
    // Documentos
    $stmt->bind_result($blobPdf, $nombreArchivo, $ownerId);
} else {
    // IndiceDocumental
    $stmt->bind_result($blobPdf);
}
$stmt->fetch();
$stmt->close();

// ——————————————————————————————
// 5) Verificar permiso (solo Documentos)
// ——————————————————————————————
if ($hasOwner) {
    if ($ownerId !== intval($_SESSION['user_id'])) {
        http_response_code(403);
        die("No tienes permiso para ver este documento.");
    }
}

// ——————————————————————————————
// 6) Enviar cabeceras y contenido
// ——————————————————————————————
header("Content-Type: application/pdf");

if ($hasFileName) {
    // Nombre real desde la tabla
    $filename = basename($nombreArchivo);
} else {
    // Nombre genérico para IndiceDocumental
    $filename = "documento_{$idParam}.pdf";
}

header('Content-Disposition: inline; filename="' . $filename . '"');
header("Content-Length: " . strlen($blobPdf));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// 7) Imprimir el PDF
echo $blobPdf;
exit;
?>
