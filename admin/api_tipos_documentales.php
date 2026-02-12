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
            listTiposDocumentales($conec);
            break;
        case 'add':
            addTipoDocumental($conec);
            break;
        case 'update':
            updateTipoDocumental($conec);
            break;
        case 'delete':
            deleteTipoDocumental($conec);
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
 * Listar todos los tipos documentales con información de dependencia
 */
function listTiposDocumentales($conn) {
    $sql = "SELECT td.id, td.nombre, td.descripcion, td.dependencia_id, td.estado, 
                   td.created_at, td.updated_at, d.nombre as dependencia_nombre
            FROM tipo_documental td
            LEFT JOIN dependencias d ON td.dependencia_id = d.id
            ORDER BY d.nombre ASC, td.nombre ASC";
    $res = $conn->query($sql);
    
    $tipos = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $tipos[] = $row;
        }
    }
    
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'data' => $tipos]);
}

/**
 * Agregar nuevo tipo documental
 */
function addTipoDocumental($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    // Obtener y validar datos
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $dependencia_id = filter_var($_POST['dependencia_id'] ?? '', FILTER_VALIDATE_INT);
    $estado = isset($_POST['estado']) ? 1 : 0;

    // Validaciones
    if (empty($nombre)) {
        throw new Exception("El nombre es requerido");
    }

    if (!$dependencia_id) {
        throw new Exception("La dependencia es requerida");
    }

    // Validar que la dependencia existe
    $stmtDep = $conn->prepare("SELECT id FROM dependencias WHERE id = ?");
    $stmtDep->bind_param("i", $dependencia_id);
    $stmtDep->execute();
    if ($stmtDep->get_result()->num_rows === 0) {
        throw new Exception("La dependencia seleccionada no existe");
    }
    $stmtDep->close();

    // Validar que el nombre no exista en la misma dependencia
    $stmtCheck = $conn->prepare("SELECT id FROM tipo_documental WHERE nombre = ? AND dependencia_id = ?");
    $stmtCheck->bind_param("si", $nombre, $dependencia_id);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        throw new Exception("Ya existe un tipo documental con ese nombre en esta dependencia");
    }
    $stmtCheck->close();

    // Insertar
    $stmt = $conn->prepare("INSERT INTO tipo_documental (nombre, descripcion, dependencia_id, estado) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $nombre, $descripcion, $dependencia_id, $estado);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al crear el tipo documental: " . $stmt->error);
    }

    $newId = $conn->insert_id;
    $stmt->close();

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true, 
        'message' => 'Tipo documental creado exitosamente',
        'id' => $newId
    ]);
}

/**
 * Actualizar tipo documental existente
 */
function updateTipoDocumental($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    // Obtener y validar datos
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $dependencia_id = filter_var($_POST['dependencia_id'] ?? '', FILTER_VALIDATE_INT);
    $estado = isset($_POST['estado']) ? 1 : 0;

    // Validaciones
    if (!$id) {
        throw new Exception("ID inválido");
    }

    if (empty($nombre)) {
        throw new Exception("El nombre es requerido");
    }

    if (!$dependencia_id) {
        throw new Exception("La dependencia es requerida");
    }

    // Verificar que el tipo documental existe
    $stmtCheck = $conn->prepare("SELECT id FROM tipo_documental WHERE id = ?");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows === 0) {
        throw new Exception("El tipo documental no existe");
    }
    $stmtCheck->close();

    // Validar que la dependencia existe
    $stmtDep = $conn->prepare("SELECT id FROM dependencias WHERE id = ?");
    $stmtDep->bind_param("i", $dependencia_id);
    $stmtDep->execute();
    if ($stmtDep->get_result()->num_rows === 0) {
        throw new Exception("La dependencia seleccionada no existe");
    }
    $stmtDep->close();

    // Validar que el nombre no exista en la misma dependencia (excluyendo el registro actual)
    $stmtDupl = $conn->prepare("SELECT id FROM tipo_documental WHERE nombre = ? AND dependencia_id = ? AND id != ?");
    $stmtDupl->bind_param("sii", $nombre, $dependencia_id, $id);
    $stmtDupl->execute();
    if ($stmtDupl->get_result()->num_rows > 0) {
        throw new Exception("Ya existe un tipo documental con ese nombre en esta dependencia");
    }
    $stmtDupl->close();

    // Actualizar
    $stmt = $conn->prepare("UPDATE tipo_documental SET nombre = ?, descripcion = ?, dependencia_id = ?, estado = ? WHERE id = ?");
    $stmt->bind_param("ssiii", $nombre, $descripcion, $dependencia_id, $estado, $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar el tipo documental: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("No se realizaron cambios (los datos son idénticos)");
    }

    $stmt->close();

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true, 
        'message' => "Tipo documental '$nombre' actualizado correctamente"
    ]);
}

/**
 * Eliminar tipo documental
 */
function deleteTipoDocumental($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        throw new Exception("ID de tipo documental inválido");
    }

    // Verificar que el tipo documental existe
    $stmtCheck = $conn->prepare("SELECT nombre FROM tipo_documental WHERE id = ?");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("El tipo documental no existe");
    }
    
    $tipoDoc = $result->fetch_assoc();
    $stmtCheck->close();

    // Aquí podrías agregar validaciones adicionales si este tipo documental
    // está siendo usado en otras tablas (por ejemplo, en documentos)
    // Por ahora, permitimos la eliminación directa

    // Eliminar
    $stmtDelete = $conn->prepare("DELETE FROM tipo_documental WHERE id = ?");
    $stmtDelete->bind_param("i", $id);
    
    if (!$stmtDelete->execute()) {
        throw new Exception("Error al eliminar el tipo documental: " . $stmtDelete->error);
    }

    if ($stmtDelete->affected_rows === 0) {
        throw new Exception("No se pudo eliminar el tipo documental");
    }

    $stmtDelete->close();

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true, 
        'message' => "Tipo documental '{$tipoDoc['nombre']}' eliminado correctamente"
    ]);
}
?>
