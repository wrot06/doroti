<?php

declare(strict_types=1);

// Configuración de errores: Silenciar salida HTML
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Iniciar buffer de salida inmediatamente
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
    require_once __DIR__ . '/../rene/conexion3.php';
    ini_set('display_errors', '0');

    if (!isset($conec) || $conec->connect_error) {
        throw new Exception("Error al conectar con la base de datos");
    }

    $conec->set_charset("utf8mb4");

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'list_folders_with_users':
            listFoldersWithUsers($conec);
            break;
        case 'list_users_by_office':
            listUsersByOffice($conec);
            break;
        case 'assign_user_to_folder':
            assignUserToFolder($conec);
            break;
        case 'get_folder_info':
            getFolderInfo($conec);
            break;
        default:
            throw new Exception("Acción no válida");
    }
} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (ob_get_length()) ob_end_flush();


/**
 * Listar carpetas con información de usuarios
 */
function listFoldersWithUsers($conn)
{
    $depId = filter_input(INPUT_GET, 'dependencia_id', FILTER_VALIDATE_INT);

    if (!$depId) {
        throw new Exception("ID de dependencia inválido");
    }

    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.Caja,
            c.Carpeta,
            c.Estado,
            c.FechaIngreso,
            c.user_id,
            u.username as usuario_actual,
            d.nombre as dependencia
        FROM Carpetas c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN dependencias d ON c.dependencia_id = d.id
        WHERE c.dependencia_id = ?
        ORDER BY c.Caja ASC, c.Carpeta ASC
    ");
    $stmt->bind_param("i", $depId);
    $stmt->execute();
    $res = $stmt->get_result();

    $folders = [];
    while ($row = $res->fetch_assoc()) {
        $folders[] = [
            'id' => $row['id'],
            'caja' => $row['Caja'],
            'carpeta' => $row['Carpeta'],
            'estado' => $row['Estado'],
            'fecha_ingreso' => $row['FechaIngreso'],
            'user_id' => $row['user_id'],
            'usuario_actual' => $row['usuario_actual'] ?? 'Sin asignar',
            'dependencia' => $row['dependencia']
        ];
    }

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'data' => $folders]);
}

/**
 * Listar usuarios por oficina/dependencia
 */
function listUsersByOffice($conn)
{
    $depId = filter_input(INPUT_GET, 'dependencia_id', FILTER_VALIDATE_INT);

    // Si no se especifica dependencia, listar todos los usuarios
    if ($depId) {
        $stmt = $conn->prepare("
            SELECT id, username, email, rol 
            FROM users 
            WHERE dependencia_id = ?
            ORDER BY username ASC
        ");
        $stmt->bind_param("i", $depId);
    } else {
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.email, u.rol, d.nombre as dependencia
            FROM users u
            LEFT JOIN dependencias d ON u.dependencia_id = d.id
            ORDER BY u.username ASC
        ");
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $users = [];
    while ($row = $res->fetch_assoc()) {
        $users[] = $row;
    }

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'data' => $users]);
}

/**
 * Asignar/cambiar usuario a una carpeta
 * Verifica integridad con IndiceTemp (Estado A) o IndiceDocumental (Estado C)
 */
function assignUserToFolder($conn)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $folderId = filter_input(INPUT_POST, 'folder_id', FILTER_VALIDATE_INT);
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if (!$folderId) {
        throw new Exception("ID de carpeta inválido");
    }

    // userId puede ser 0 o NULL para desasignar
    $conn->begin_transaction();

    try {
        // Verificar que la carpeta existe y obtener su estado
        $stmtCheck = $conn->prepare("
            SELECT id, Caja, Carpeta, Estado, dependencia_id, user_id as user_id_actual 
            FROM Carpetas 
            WHERE id = ?
        ");
        $stmtCheck->bind_param("i", $folderId);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("La carpeta no existe");
        }

        $folderData = $result->fetch_assoc();
        $stmtCheck->close();

        $estado = $folderData['Estado'];
        $caja = (int)$folderData['Caja'];
        $carpeta = (int)$folderData['Carpeta'];
        $dependencia_id = (int)$folderData['dependencia_id'];

        // Verificar datos relacionados según el estado
        $registrosRelacionados = 0;
        $tablaRelacionada = '';

        if ($estado === 'A') {
            // Carpeta Activa: verificar datos en IndiceTemp
            $stmtCount = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM IndiceTemp 
                WHERE carpeta_id = ? AND Caja = ? AND Carpeta = ? AND dependencia_id = ?
            ");
            $stmtCount->bind_param("iiii", $folderId, $caja, $carpeta, $dependencia_id);
            $stmtCount->execute();
            $countResult = $stmtCount->get_result();
            $countData = $countResult->fetch_assoc();
            $registrosRelacionados = (int)$countData['total'];
            $tablaRelacionada = 'IndiceTemp';
            $stmtCount->close();
        } elseif ($estado === 'C') {
            // Carpeta Cerrada: verificar datos en IndiceDocumental
            $stmtCount = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM IndiceDocumental 
                WHERE carpeta_id = ? AND Caja = ? AND Carpeta = ? AND dependencia_id = ?
            ");
            $stmtCount->bind_param("iiii", $folderId, $caja, $carpeta, $dependencia_id);
            $stmtCount->execute();
            $countResult = $stmtCount->get_result();
            $countData = $countResult->fetch_assoc();
            $registrosRelacionados = (int)$countData['total'];
            $tablaRelacionada = 'IndiceDocumental';
            $stmtCount->close();
        }

        // Si se especifica un usuario, verificar que existe
        $nuevoUsuarioNombre = 'Sin asignar';
        if ($userId) {
            $stmtUser = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmtUser->bind_param("i", $userId);
            $stmtUser->execute();
            $userResult = $stmtUser->get_result();

            if ($userResult->num_rows === 0) {
                throw new Exception("El usuario no existe");
            }

            $userData = $userResult->fetch_assoc();
            $nuevoUsuarioNombre = $userData['username'];
            $stmtUser->close();
        }

        // Actualizar user_id de la carpeta
        if ($userId) {
            $stmt = $conn->prepare("UPDATE Carpetas SET user_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $userId, $folderId);
        } else {
            // Desasignar usuario (set to NULL)
            $stmt = $conn->prepare("UPDATE Carpetas SET user_id = NULL WHERE id = ?");
            $stmt->bind_param("i", $folderId);
        }

        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            // Verificar si realmente no hubo cambio o si falló
            $stmtVerify = $conn->prepare("SELECT user_id FROM Carpetas WHERE id = ?");
            $stmtVerify->bind_param("i", $folderId);
            $stmtVerify->execute();
            $verifyResult = $stmtVerify->get_result();
            $verifyData = $verifyResult->fetch_assoc();
            $stmtVerify->close();

            // Si el user_id ya era el mismo, no es un error
            if (($userId && $verifyData['user_id'] == $userId) ||
                (!$userId && $verifyData['user_id'] === null)
            ) {
                // Ya estaba asignado al mismo usuario, no es error
            } else {
                throw new Exception("No se pudo actualizar la carpeta");
            }
        }

        $stmt->close();
        $conn->commit();

        // Construir mensaje detallado
        $estadoTexto = $estado === 'A' ? 'Activa' : ($estado === 'C' ? 'Cerrada' : $estado);

        $message = $userId
            ? "Usuario '{$nuevoUsuarioNombre}' asignado correctamente a la carpeta Caja {$caja} Carpeta {$carpeta}"
            : "Usuario desasignado de la carpeta Caja {$caja} Carpeta {$carpeta}";

        $detalles = [
            'estado' => $estadoTexto,
            'registros_relacionados' => $registrosRelacionados,
            'tabla_relacionada' => $tablaRelacionada
        ];

        if ($registrosRelacionados > 0) {
            $message .= ". La carpeta tiene {$registrosRelacionados} registro(s) en {$tablaRelacionada}.";
        }

        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true,
            'message' => $message,
            'detalles' => $detalles
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}


/**
 * Obtener información de una carpeta
 */
function getFolderInfo($conn)
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception("ID inválido");
    }

    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.Caja,
            c.Carpeta,
            c.user_id,
            c.dependencia_id,
            u.username as usuario_actual,
            d.nombre as dependencia
        FROM Carpetas c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN dependencias d ON c.dependencia_id = d.id
        WHERE c.id = ?
    ");
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
