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

$search = trim($_POST['q'] ?? '');
$replace = trim($_POST['r'] ?? '');

if ($search === '') {
    echo json_encode(['status' => 'error', 'message' => 'La palabra a buscar no puede estar vacía.']);
    exit;
}

// Conectar a Base de Datos
require_once __DIR__ . '/../rene/conexion3.php';

if ($conec->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// Obtener todas las tablas dedicadas (indice_documental_dep_*)
$tables = [];
$resTables = $conec->query("SHOW TABLES LIKE 'indice_documental_dep_%'");
if ($resTables) {
    while ($row = $resTables->fetch_row()) {
        $tables[] = $row[0];
    }
}

if (empty($tables)) {
    echo json_encode(['status' => 'success', 'message' => 'No hay tablas dedicadas en el sistema.', 'affected' => 0]);
    exit;
}

$totalAffected = 0;
$searchWildcard = "%{$search}%";

foreach ($tables as $table) {
    // UPDATE con REPLACE sensible a mayúsculas utilizando LIKE BINARY
    $sql = "UPDATE `$table` 
            SET `DescripcionUnidadDocumental` = REPLACE(`DescripcionUnidadDocumental`, ?, ?) 
            WHERE `DescripcionUnidadDocumental` LIKE BINARY ?";
            
    $stmt = $conec->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sss", $search, $replace, $searchWildcard);
        if ($stmt->execute()) {
            $totalAffected += $stmt->affected_rows;
        }
        $stmt->close();
    }
}

$conec->close();

echo json_encode([
    'status' => 'success',
    'message' => "Se actualizaron {$totalAffected} registros en total de forma masiva.",
    'affected' => $totalAffected
]);
