<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
AuthMiddleware::initSession();

// Verificar autenticación y rol de admin
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true || ($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// Recibir datos
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$table = trim($_POST['table'] ?? '');
$texto = trim($_POST['texto'] ?? '');

// Validar parámetros
if (!$id || $id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de registro inválido.']);
    exit;
}

$allowedTables = ['indice_documental_dep_6', 'indice_documental_dep_9'];
if (!in_array($table, $allowedTables)) {
    echo json_encode(['status' => 'error', 'message' => 'Nombre de tabla inválido o no permitido.']);
    exit;
}

if ($texto === '') {
    echo json_encode(['status' => 'error', 'message' => 'La descripción no puede estar vacía.']);
    exit;
}

// Conectar a Base de Datos
require_once __DIR__ . '/../rene/conexion3.php';

if ($conec->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// Ejecutar actualización
$sql = "UPDATE `$table` SET `DescripcionUnidadDocumental` = ?, `procesado_ia` = 1 WHERE `id` = ?";
$stmt = $conec->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta: ' . $conec->error]);
    exit;
}

$stmt->bind_param("si", $texto, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Registro guardado correctamente.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el registro: ' . $stmt->error]);
}

$stmt->close();
$conec->close();
