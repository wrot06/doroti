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

    // Incluir conexión (usamos @ para silenciar warnings del include, aunque throw lo maneja)
    // PERO conexion3.php podría tener ini_set('display_errors', 1). 
    // Lo restauramos inmediatamente después.
    require_once $conexionPath;
    ini_set('display_errors', '0'); // Restaurar silencio
    
    // Verificar si la conexión fue exitosa (por si conexion3.php no hizo die)
    if (!isset($conec) || $conec->connect_error) {
        throw new Exception("Error al conectar con la base de datos");
    }
    
    $conec->set_charset("utf8mb4");

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'list_offices':
            listOffices($conec);
            break;
        case 'list_folders':
            listFolders($conec);
            break;
        case 'delete_folder':
            deleteFolder($conec);
            break;
        case 'get_folder':
            getFolder($conec);
            break;
        case 'update_folder':
            updateFolder($conec);
            break;
        default:
            throw new Exception("Acción no válida");
    }

} catch (Throwable $e) {
    // Catch-all para Errores y Excepciones
    if (ob_get_length()) ob_clean();
    
    // Loggear error real (opcional)
    // error_log($e->getMessage());

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Finalizar buffer (importante para enviar contenido)
if (ob_get_length()) ob_end_flush();


/**
 * Listar todas las dependencias
 */
function listOffices($conn) {
    $sql = "SELECT id, nombre FROM dependencias ORDER BY nombre ASC";
    $res = $conn->query($sql);
    
    $offices = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $offices[] = $row;
        }
    }
    
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'data' => $offices]);
}

/**
 * Listar carpetas
 */
function listFolders($conn) {
    $depId = filter_input(INPUT_GET, 'dependencia_id', FILTER_VALIDATE_INT);
    
    if (!$depId) {
        throw new Exception("ID de dependencia inválido");
    }

    $stmt = $conn->prepare("
        SELECT id, Caja, Carpeta, Estado, FechaIngreso 
        FROM Carpetas 
        WHERE dependencia_id = ? 
        ORDER BY Caja ASC, Carpeta ASC
    ");
    $stmt->bind_param("i", $depId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $folders = [];
    while ($row = $res->fetch_assoc()) {
        $folders[] = $row;
    }
    
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'data' => $folders]);
}

/**
 * Eliminar carpeta
 */
function deleteFolder($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $folderId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if (!$folderId) {
        throw new Exception("ID de carpeta inválido");
    }

    $conn->begin_transaction();

    try {
        // Datos carpeta (incluyendo Estado)
        $stmtGet = $conn->prepare("SELECT Caja, Carpeta, dependencia_id, Estado FROM Carpetas WHERE id = ?");
        $stmtGet->bind_param("i", $folderId);
        $stmtGet->execute();
        $resGet = $stmtGet->get_result();
        $folderData = $resGet->fetch_assoc();
        $stmtGet->close();

        if (!$folderData) {
            throw new Exception("La carpeta no existe");
        }

        // Eliminar registros según el estado de la carpeta
        // Estado 'A' (Activo) = registros en IndiceTemp
        // Estado 'C' (Cerrado) = registros en IndiceDocumental
        if ($folderData['Estado'] === 'A') {
            // Carpeta activa: eliminar de IndiceTemp
            $stmt1 = $conn->prepare("DELETE FROM IndiceTemp WHERE Caja = ? AND Carpeta = ? AND dependencia_id = ?");
            $stmt1->bind_param("iii", $folderData['Caja'], $folderData['Carpeta'], $folderData['dependencia_id']);
            $stmt1->execute();
            $stmt1->close();
        } elseif ($folderData['Estado'] === 'C') {
            // Carpeta cerrada: eliminar de IndiceDocumental
            $stmt1 = $conn->prepare("DELETE FROM IndiceDocumental WHERE Caja = ? AND Carpeta = ? AND dependencia_id = ?");
            $stmt1->bind_param("iii", $folderData['Caja'], $folderData['Carpeta'], $folderData['dependencia_id']);
            $stmt1->execute();
            $stmt1->close();
        }

        // Eliminar Carpeta usando Caja, Carpeta y dependencia_id para mayor seguridad
        $stmt2 = $conn->prepare("DELETE FROM Carpetas WHERE Caja = ? AND Carpeta = ? AND dependencia_id = ?");
        $stmt2->bind_param("iii", $folderData['Caja'], $folderData['Carpeta'], $folderData['dependencia_id']);
        $stmt2->execute();
        
        if ($stmt2->affected_rows === 0) {
            throw new Exception("Error al eliminar la carpeta");
        }
        $stmt2->close();

        $conn->commit();
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => true, 'message' => 'Carpeta eliminada correctamente']);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Obtener carpeta
 */
function getFolder($conn) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception("ID inválido");
    }

    $stmt = $conn->prepare("SELECT id, Caja, Carpeta, dependencia_id FROM Carpetas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    
    if (!$data) {
        throw new Exception("Carpeta no encontrada");
    }
    
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Actualizar carpeta
 */
function updateFolder($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $caja = filter_input(INPUT_POST, 'caja', FILTER_VALIDATE_INT);
    $carpeta = filter_input(INPUT_POST, 'carpeta', FILTER_VALIDATE_INT);

    if (!$id || !$caja || !$carpeta) {
        throw new Exception("Datos incompleto");
    }

    $conn->begin_transaction();

    try {
        // 1. Obtener datos actuales
        $stmtGet = $conn->prepare("SELECT dependencia_id FROM Carpetas WHERE id = ?");
        $stmtGet->bind_param("i", $id);
        $stmtGet->execute();
        $resGet = $stmtGet->get_result();
        $current = $resGet->fetch_assoc();
        
        if (!$current) {
            throw new Exception("Carpeta no existe");
        }
        
        $depId = $current['dependencia_id'];

        // 2. Verificar duplicados
        $stmtCheck = $conn->prepare("SELECT id FROM Carpetas WHERE Caja = ? AND Carpeta = ? AND dependencia_id = ? AND id != ?");
        $stmtCheck->bind_param("iiii", $caja, $carpeta, $depId, $id);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            throw new Exception("Ya existe la Caja $caja Carpeta $carpeta en esta oficina.");
        }

        // 3. Actualizar
        $stmtUpd = $conn->prepare("UPDATE Carpetas SET Caja = ?, Carpeta = ? WHERE id = ?");
        $stmtUpd->bind_param("iii", $caja, $carpeta, $id);
        $stmtUpd->execute();

        // 4. Actualizar índices (solo si existen columnas)
        // IndiceDocumental
        $stmtUpdDocs = $conn->prepare("UPDATE IndiceDocumental SET Caja = ?, Carpeta = ? WHERE carpeta_id = ?");
        $stmtUpdDocs->bind_param("iii", $caja, $carpeta, $id);
        $stmtUpdDocs->execute();

        // IndiceTemp (intentar update por dependencia)
        $stmtUpdTemp = $conn->prepare("UPDATE IndiceTemp SET Caja = ?, Carpeta = ? WHERE dependencia_id = ? AND carpeta_id = ?"); 
        // Nota: carpeta_id podría no existir en IndiceTemp en versiones viejas, pero update fallaría graceful sin excepción si columna existe pero 0 rows
        // Si columna NO existe, lanza error.
        // Asumo carpeta_id existe en IndiceTemp (vimos schema antes).
        $stmtUpdTemp->bind_param("iiii", $caja, $carpeta, $depId, $id);
        $stmtUpdTemp->execute();

        $conn->commit();
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => true, 'message' => 'Actualizado correctamente']);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
?>
