<?php
declare(strict_types=1);
session_start();
require_once "../rene/conexion3.php";

if (empty($_SESSION['authenticated'])) {
    http_response_code(401);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$dependencia_id = (int)$_SESSION['dependencia_id'];

// Helper function to set error and redirect
function redirectWithError($message) {
    $_SESSION['error'] = $message;
    header("Location: digital.php");
    exit;
}

if (empty($_FILES['pdf']['tmp_name']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    redirectWithError('Se requiere subir un archivo PDF válido.');
}

if ($_FILES['pdf']['size'] > 10 * 1024 * 1024) {
    redirectWithError('El archivo supera el tamaño máximo permitido de 10MB.');
}

// Validate MIME type securely using finfo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $_FILES['pdf']['tmp_name']);
finfo_close($finfo);

if ($mime_type !== 'application/pdf') {
    redirectWithError('El archivo subido no es un PDF válido o está corrupto.');
}

$pdf_bin = file_get_contents($_FILES['pdf']['tmp_name']);
$hash = hash('sha256', $pdf_bin);

try {
    $conec->begin_transaction();

    // Insert main document
    $stmt = $conec->prepare("
        INSERT INTO documentos (tipo, titulo_documento, fecha_creacion, user_id, dependencia_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssii",
        $_POST['serie'],
        $_POST['titulo_documento'],
        $_POST['fecha_creacion'],
        $user_id,
        $dependencia_id
    );
    $stmt->execute();
    $documento_id = $stmt->insert_id;
    $stmt->close();

    // Insert document version
    $stmt = $conec->prepare("
        INSERT INTO documento_versiones (documento_id, version, archivo_nombre, archivo_pdf, hash_sha256, tamano_bytes, activa)
        VALUES (?, 1, ?, ?, ?, ?, 1)
    ");
    $nombre = "doc_{$documento_id}.pdf";
    $tamano = strlen($pdf_bin);
    $pdf_blob = null; 
    
    $stmt->bind_param("isbsi",
        $documento_id,
        $nombre,
        $pdf_blob,
        $hash,
        $tamano
    );
    $stmt->send_long_data(2, $pdf_bin);
    
    // Execute and check for errors
    if (!$stmt->execute()) {
        throw new Exception('Error al guardar la versión del documento en la base de datos.');
    }
    $stmt->close();

    $conec->commit();
    $_SESSION['success'] = '¡Documento subido y guardado exitosamente!';
    header("Location: digital.php");
    exit;

} catch (Exception $e) {
    $conec->rollback();
    // Log the actual error for debugging, but show a user-friendly message
    error_log("Error subiendo documento: " . $e->getMessage());
    redirectWithError('Ocurrió un error interno al guardar el documento. Por favor, inténtelo de nuevo.');
}
