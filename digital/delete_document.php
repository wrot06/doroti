<?php
declare(strict_types=1);
session_start();
require_once "../rene/conexion3.php";

// 1. Authentication Check
if (empty($_SESSION['authenticated'])) {
    http_response_code(401);
    exit;
}

// 2. CSRF & Request Method Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Token de seguridad inválido o petición incorrecta.';
    header("Location: documents.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$documento_id = (int)($_POST['documento_id'] ?? 0);

if ($documento_id <= 0) {
    $_SESSION['error'] = 'ID de documento inválido.';
    header("Location: documents.php");
    exit;
}

try {
    $conec->begin_transaction();

    // 3. Verify Ownership (Only the user who created it can delete it)
    $check_stmt = $conec->prepare("SELECT id FROM documentos WHERE id = ? AND user_id = ? AND estado = 'activo'");
    $check_stmt->bind_param("ii", $documento_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('No tienes permiso para eliminar este documento o el documento no existe.');
    }
    $check_stmt->close();

    // 4. Soft Delete Document
    $update_doc = $conec->prepare("UPDATE documentos SET estado = 'eliminado' WHERE id = ?");
    $update_doc->bind_param("i", $documento_id);
    if (!$update_doc->execute()) {
        throw new Exception('Error al actualizar el estado del documento principal.');
    }
    $update_doc->close();

    // 5. Deactivate all versions of this document
    $update_versions = $conec->prepare("UPDATE documento_versiones SET activa = 0 WHERE documento_id = ?");
    $update_versions->bind_param("i", $documento_id);
    if (!$update_versions->execute()) {
        throw new Exception('Error al desactivar las versiones del documento.');
    }
    $update_versions->close();

    // 6. Commit
    $conec->commit();
    $_SESSION['success'] = 'El documento ha sido eliminado correctamente.';

} catch (Exception $e) {
    $conec->rollback();
    error_log("Error eliminando documento ID $documento_id: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

// Redirect back to documents list
header("Location: documents.php");
exit;
