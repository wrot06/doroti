<?php
declare(strict_types=1);

// Configuración de errores: Silenciar salida HTML
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Buffer de salida
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
    require_once "../rene/conexion3.php";
    ini_set('display_errors', '0'); // Restaurar silencio después de include
    
    if (!isset($conec) || $conec->connect_error) {
        throw new Exception("Error al conectar con la base de datos");
    }
    
    $conec->set_charset("utf8mb4");

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'list_users':
            listUsers($conec);
            break;
        case 'get_user':
            getUser($conec);
            break;
        case 'create_user':
            createUser($conec);
            break;
        case 'update_user':
            updateUser($conec);
            break;
        case 'delete_user':
            deleteUser($conec);
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
 * Listar todos los usuarios con información de oficina
 */
function listUsers($conn) {
    $sql = "
        SELECT 
            u.id, 
            u.username, 
            u.email, 
            u.phone, 
            u.rol,
            u.dependencia_id,
            COALESCE(d.nombre, 'Sin oficina') as oficina
        FROM users u
        LEFT JOIN dependencias d ON u.dependencia_id = d.id
        ORDER BY u.username ASC
    ";
    
    $res = $conn->query($sql);
    
    $users = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'data' => $users]);
}

/**
 * Obtener un usuario específico
 */
function getUser($conn) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        throw new Exception("ID de usuario inválido");
    }

    $stmt = $conn->prepare("
        SELECT id, username, email, phone, rol, dependencia_id 
        FROM users 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    
    if (!$user) {
        throw new Exception("Usuario no encontrado");
    }
    
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'data' => $user]);
}

/**
 * Crear nuevo usuario
 */
function createUser($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    // Validar entrada
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $rol = $_POST['rol'] ?? 'operario';
    $dependencia_id = filter_input(INPUT_POST, 'dependencia_id', FILTER_VALIDATE_INT);

    // Validaciones
    if (empty($username)) {
        throw new Exception("El nombre de usuario es obligatorio");
    }
    
    if (strlen($username) > 50) {
        throw new Exception("El nombre de usuario no puede exceder 50 caracteres");
    }
    
    if (empty($password)) {
        throw new Exception("La contraseña es obligatoria");
    }
    
    if (strlen($password) < 6) {
        throw new Exception("La contraseña debe tener al menos 6 caracteres");
    }
    
    if (empty($email)) {
        throw new Exception("El email es obligatorio");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("El email no es válido");
    }
    
    if (!in_array($rol, ['admin', 'operario'])) {
        throw new Exception("Rol inválido");
    }

    $conn->begin_transaction();

    try {
        // Verificar username duplicado
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmtCheck->bind_param("s", $username);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            throw new Exception("El nombre de usuario ya existe");
        }
        
        // Verificar email duplicado
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmtCheck->bind_param("s", $email);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            throw new Exception("El email ya está registrado");
        }

        // Hash de contraseña
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insertar usuario
        $stmt = $conn->prepare("
            INSERT INTO users (username, password, email, phone, rol, dependencia_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("sssssi", $username, $hashedPassword, $email, $phone, $rol, $dependencia_id);
        $stmt->execute();

        $conn->commit();
        
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Usuario creado correctamente',
            'user_id' => $conn->insert_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Actualizar usuario existente
 */
function updateUser($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $dependencia_id = filter_input(INPUT_POST, 'dependencia_id', FILTER_VALIDATE_INT);

    if (!$id) {
        throw new Exception("ID de usuario inválido");
    }
    
    if (empty($email)) {
        throw new Exception("El email es obligatorio");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("El email no es válido");
    }
    
    if (!in_array($rol, ['admin', 'operario'])) {
        throw new Exception("Rol inválido");
    }

    $conn->begin_transaction();

    try {
        // Verificar que el usuario existe
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmtCheck->bind_param("i", $id);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows === 0) {
            throw new Exception("Usuario no encontrado");
        }

        // Verificar email duplicado (excluyendo el usuario actual)
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmtCheck->bind_param("si", $email, $id);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            throw new Exception("El email ya está registrado por otro usuario");
        }

        // Actualizar usuario
        $stmt = $conn->prepare("
            UPDATE users 
            SET email = ?, phone = ?, rol = ?, dependencia_id = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param("sssii", $email, $phone, $rol, $dependencia_id, $id);
        $stmt->execute();

        $conn->commit();
        
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Eliminar usuario
 */
function deleteUser($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        throw new Exception("ID de usuario inválido");
    }

    // No permitir eliminar el usuario actual
    if ($id === ($_SESSION['user_id'] ?? 0)) {
        throw new Exception("No puedes eliminar tu propio usuario");
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Usuario no encontrado o ya eliminado");
    }
    
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
}
?>
