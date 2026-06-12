<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
AuthMiddleware::initSession();

// Verificar autenticación
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// Verificar rol de admin
if (($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Se requieren permisos de administrador.']);
    exit;
}

// Validar token CSRF para respuesta JSON
$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido o expirado. Por favor recargue la página.']);
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

// Validar que el nombre de la tabla coincida con el patrón de tablas dedicadas
if (!preg_match('/^indice_documental_dep_\d+$/', $table)) {
    echo json_encode(['status' => 'error', 'message' => 'Nombre de tabla inválido o no permitido.']);
    exit;
}

if ($texto === '') {
    echo json_encode(['status' => 'error', 'message' => 'El texto de reemplazo no puede estar vacío.']);
    exit;
}

// Conectar a Base de Datos
require_once __DIR__ . '/../rene/conexion3.php';

if ($conec->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// Ejecutar actualización
$sql = "UPDATE `$table` SET `DescripcionUnidadDocumental` = ? WHERE `id` = ?";
$stmt = $conec->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta de actualización: ' . $conec->error]);
    exit;
}

$stmt->bind_param("si", $texto, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Registro actualizado correctamente.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el registro: ' . $stmt->error]);
}

$stmt->close();
$conec->close();
