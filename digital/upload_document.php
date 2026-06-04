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

$tipo_documental_id = (int)($_POST['tipo_documental_id'] ?? 0);
if ($tipo_documental_id <= 0) {
    redirectWithError('Tipo documental inválido.');
}

$tamano = (int)$_FILES['pdf']['size'];
$hash = hash_file('sha256', $_FILES['pdf']['tmp_name']);
$nombre_original = basename($_FILES['pdf']['name']);

$target_dir = "/var/www/doroti/uploads/documentos";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0755, true);
}
$target_file = $target_dir . '/' . $hash . '.pdf';
$relative_path = 'uploads/documentos/' . $hash . '.pdf';

if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $target_file)) {
    redirectWithError('No se pudo guardar el archivo físico en el servidor.');
}

try {
    $conec->begin_transaction();

    // Insert main document
    $stmt = $conec->prepare("
        INSERT INTO documentos (tipo, titulo_documento, fecha_creacion, user_id, dependencia_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issii",
        $tipo_documental_id,
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
    $stmt->bind_param("isssi",
        $documento_id,
        $nombre_original,
        $relative_path,
        $hash,
        $tamano
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Error al guardar la versión del documento en la base de datos.');
    }
    $stmt->close();

    // Registrar acción en la tabla de auditoría (historial_acciones)
    $accion = "Subir documento";
    $tabla = "documentos";
    $detalles = "Se creó el documento '" . $_POST['titulo_documento'] . "' versión 1 con hash: " . $hash;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    $audit_stmt = $conec->prepare("
        INSERT INTO historial_acciones (user_id, accion, tabla, registro_id, detalles, ip)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $audit_stmt->bind_param("ississ",
        $user_id,
        $accion,
        $tabla,
        $documento_id,
        $detalles,
        $ip
    );
    $audit_stmt->execute();
    $audit_stmt->close();

    $conec->commit();
    $_SESSION['success'] = '¡Documento subido, guardado y registrado exitosamente!';
    header("Location: documents.php");
    exit;

} catch (Exception $e) {
    $conec->rollback();
    // Limpiar archivo físico si el registro en base de datos falló
    if (file_exists($target_file)) {
        unlink($target_file);
    }
    error_log("Error subiendo documento: " . $e->getMessage());
    redirectWithError('Ocurrió un error interno al guardar el documento. Por favor, inténtelo de nuevo.');
}
