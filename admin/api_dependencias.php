<?php
declare(strict_types=1);

// Configuración de errores: Silenciar salida HTML
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Iniciar buffer de salida inmediatamente para capturar cualquier echo/error temprano
if (ob_get_level() === 0) {
    ob_start();
}

session_start();

// Validar sesión de admin
if (empty($_SESSION['authenticated']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

try {
    // Verificar archivo de conexión antes de incluirlo
    $conexionPath = __DIR__ . '/../rene/conexion3.php';
    if (!file_exists($conexionPath)) {
        throw new Exception("Archivo de conexión no encontrado: $conexionPath");
    }

    require_once $conexionPath;
    ini_set('display_errors', '0'); // Restaurar silencio
    
    // Verificar si la conexión fue exitosa
    if (!isset($conec) || $conec->connect_error) {
        throw new Exception("Error al conectar con la base de datos");
    }
    
    $conec->set_charset("utf8mb4");

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'list':
            listDependencias($conec);
            break;
        case 'add':
            addDependencia($conec);
            break;
        case 'delete':
            deleteDependencia($conec);
            break;
        default:
            throw new Exception("Acción no válida");
    }

} catch (Throwable $e) {
    // Catch-all para Errores y Excepciones
    if (ob_get_length()) ob_clean();
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Finalizar buffer (importante para enviar contenido)
if (ob_get_length()) ob_end_flush();


/**
 * Listar todas las dependencias
 */
function listDependencias($conn) {
    $sql = "SELECT id, nombre, parent_id, tipo, estado, acronimo, logo 
            FROM dependencias 
            ORDER BY tipo ASC, nombre ASC";
    $res = $conn->query($sql);
    
    $dependencias = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $dependencias[] = $row;
        }
    }
    
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'data' => $dependencias]);
}

/**
 * Agregar nueva dependencia
 */
function addDependencia($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    // Obtener y validar datos
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? filter_var($_POST['parent_id'], FILTER_VALIDATE_INT) : null;
    $acronimo = trim($_POST['acronimo'] ?? '');
    $estado = isset($_POST['estado']) ? 1 : 0;

    // Validaciones
    if (empty($nombre)) {
        throw new Exception("El nombre es requerido");
    }

    if (empty($tipo)) {
        throw new Exception("El tipo es requerido");
    }

    // Validar que el nombre no exista ya
    $stmtCheck = $conn->prepare("SELECT id FROM dependencias WHERE nombre = ?");
    $stmtCheck->bind_param("s", $nombre);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        throw new Exception("Ya existe una dependencia con ese nombre");
    }
    $stmtCheck->close();

    // Validar que parent_id existe si se especifica
    if ($parent_id !== null) {
        $stmtParent = $conn->prepare("SELECT id FROM dependencias WHERE id = ?");
        $stmtParent->bind_param("i", $parent_id);
        $stmtParent->execute();
        if ($stmtParent->get_result()->num_rows === 0) {
            throw new Exception("La dependencia padre no existe");
        }
        $stmtParent->close();
    }

    // Insertar
    $stmt = $conn->prepare("INSERT INTO dependencias (nombre, tipo, parent_id, acronimo, estado) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisi", $nombre, $tipo, $parent_id, $acronimo, $estado);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al crear la dependencia: " . $stmt->error);
    }

    $newId = $conn->insert_id;
    $stmt->close();

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true, 
        'message' => 'Dependencia creada exitosamente',
        'id' => $newId
    ]);
}

/**
 * Eliminar dependencia
 */
function deleteDependencia($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        throw new Exception("ID de dependencia inválido");
    }

    // Verificar que la dependencia existe
    $stmtCheck = $conn->prepare("SELECT nombre FROM dependencias WHERE id = ?");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("La dependencia no existe");
    }
    
    $dependencia = $result->fetch_assoc();
    $stmtCheck->close();

    // Verificar que no tenga carpetas asociadas
    $stmtCarpetas = $conn->prepare("SELECT COUNT(*) as total FROM Carpetas WHERE dependencia_id = ?");
    $stmtCarpetas->bind_param("i", $id);
    $stmtCarpetas->execute();
    $carpetasResult = $stmtCarpetas->get_result();
    $carpetasData = $carpetasResult->fetch_assoc();
    $totalCarpetas = (int)$carpetasData['total'];
    $stmtCarpetas->close();

    if ($totalCarpetas > 0) {
        throw new Exception("No se puede eliminar esta dependencia porque tiene {$totalCarpetas} carpeta(s) asociada(s). Primero elimine o reasigne las carpetas.");
    }

    // Verificar que no tenga dependencias hijas
    $stmtHijos = $conn->prepare("SELECT COUNT(*) as total FROM dependencias WHERE parent_id = ?");
    $stmtHijos->bind_param("i", $id);
    $stmtHijos->execute();
    $hijosResult = $stmtHijos->get_result();
    $hijosData = $hijosResult->fetch_assoc();
    $totalHijos = (int)$hijosData['total'];
    $stmtHijos->close();

    if ($totalHijos > 0) {
        throw new Exception("No se puede eliminar esta dependencia porque tiene {$totalHijos} dependencia(s) hija(s). Primero elimine o reasigne las dependencias hijas.");
    }

    // Eliminar
    $stmtDelete = $conn->prepare("DELETE FROM dependencias WHERE id = ?");
    $stmtDelete->bind_param("i", $id);
    
    if (!$stmtDelete->execute()) {
        throw new Exception("Error al eliminar la dependencia: " . $stmtDelete->error);
    }

    if ($stmtDelete->affected_rows === 0) {
        throw new Exception("No se pudo eliminar la dependencia");
    }

    $stmtDelete->close();

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true, 
        'message' => "Dependencia '{$dependencia['nombre']}' eliminada correctamente"
    ]);
}
?>
