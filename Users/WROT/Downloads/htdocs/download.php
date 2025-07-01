<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/rene/conexion3.php';

if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    die("ID inválido.");
}

// 1. Obtener archivo_pdf, archivo_nombre, archivo_ruta, user_id
$sql = "SELECT archivo_pdf, archivo_nombre, archivo_ruta, user_id FROM Documentos WHERE id = ?";
$stmt = $conec->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die("Documento no encontrado.");
}

$doc = $result->fetch_assoc();

// 2. Verificar permisos
if (intval($doc['user_id']) !== intval($_SESSION['user_id'])) {
    http_response_code(403);
    die("No tienes permiso para ver este documento.");
}

// 3. Si hay BLOB, mostrar directamente desde la base de datos
if (!empty($doc['archivo_pdf'])) {
    $filename = basename($doc['archivo_nombre']);
    header("Content-Type: application/pdf");
    header("Content-Disposition: inline; filename=\"$filename\"");
    header("Content-Length: " . strlen($doc['archivo_pdf']));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo $doc['archivo_pdf'];
    exit;
}

// 4. Si no hay BLOB, buscar archivo en ruta del sistema de archivos
if (!empty($doc['archivo_ruta'])) {
    $ruta = __DIR__ . '/' . $doc['archivo_ruta'];
    if (!file_exists($ruta)) {
        http_response_code(404);
        die("El archivo no se encuentra en el servidor.");
    }

    $filename = basename($doc['archivo_nombre']);
    header("Content-Type: application/pdf");
    header("Content-Disposition: inline; filename=\"$filename\"");
    header("Content-Length: " . filesize($ruta));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    readfile($ruta);
    exit;
}

// 5. Si no hay ni BLOB ni archivo físico
http_response_code(404);
die("El documento no tiene contenido disponible.");
