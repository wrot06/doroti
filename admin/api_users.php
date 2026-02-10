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
        case 'upload_avatar':
            uploadAvatar($conec);
            break;
        case 'delete_avatar':
            deleteAvatar($conec);
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
 * Helper: Obtener URL del avatar del usuario
 */
function getAvatarUrl($avatar) {
    if ($avatar && file_exists("../uploads/avatars/" . basename($avatar))) {
        return "../uploads/avatars/" . basename($avatar);
    }
    return "../uploads/avatars/default.png";
}

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
            u.avatar,
            COALESCE(d.nombre, 'Sin oficina') as oficina
        FROM users u
        LEFT JOIN dependencias d ON u.dependencia_id = d.id
        ORDER BY u.username ASC
    ";
    
    $res = $conn->query($sql);
    
    $users = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['avatar_url'] = getAvatarUrl($row['avatar']);
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
        SELECT id, username, email, phone, rol, dependencia_id, avatar 
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
    
    $user['avatar_url'] = getAvatarUrl($user['avatar']);
    
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

/**
 * Subir avatar de usuario
 */
function uploadAvatar($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    
    if (!$user_id) {
        throw new Exception("ID de usuario inválido");
    }

    if (empty($_FILES['avatar']['tmp_name'])) {
        throw new Exception("Archivo de imagen requerido");
    }

    $file = $_FILES['avatar'];
    
    // Validar tipo MIME
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception("Formato de imagen no permitido. Use JPG, PNG o WEBP");
    }
    
    // Validar tamaño (2MB máximo)
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception("La imagen es muy grande. Máximo 2MB");
    }
    
    // Verificar que es una imagen válida
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        throw new Exception("El archivo no es una imagen válida");
    }

    $conn->begin_transaction();

    try {
        // Verificar que el usuario existe
        $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Usuario no encontrado");
        }
        
        $currentAvatar = $result->fetch_assoc()['avatar'];
        
        // Determinar extensión
        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg'
        };
        
        $filename = "user_{$user_id}.{$extension}";
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        $targetPath = $uploadDir . $filename;
        
        // Eliminar avatar anterior si existe y no es el mismo archivo
        if ($currentAvatar && $currentAvatar !== $filename) {
            $oldPath = $uploadDir . basename($currentAvatar);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        
        // Redimensionar imagen a 200x200
        $resized = resizeImage($file['tmp_name'], $mimeType, 200, 200);
        
        if (!$resized) {
            throw new Exception("Error al procesar la imagen");
        }
        
        // Guardar imagen redimensionada
        $saved = match($mimeType) {
            'image/jpeg' => imagejpeg($resized, $targetPath, 90),
            'image/png' => imagepng($resized, $targetPath, 8),
            'image/webp' => imagewebp($resized, $targetPath, 90),
            default => false
        };
        
        imagedestroy($resized);
        
        if (!$saved) {
            throw new Exception("Error al guardar la imagen");
        }
        
        chmod($targetPath, 0644);
        
        // Actualizar BD
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $filename, $user_id);
        $stmt->execute();
        
        $conn->commit();
        
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Avatar actualizado correctamente',
            'avatar_url' => "../uploads/avatars/" . $filename
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Eliminar avatar de usuario
 */
function deleteAvatar($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    
    if (!$user_id) {
        throw new Exception("ID de usuario inválido");
    }

    $conn->begin_transaction();

    try {
        // Obtener avatar actual
        $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Usuario no encontrado");
        }
        
        $avatar = $result->fetch_assoc()['avatar'];
        
        // Eliminar archivo si existe
        if ($avatar) {
            $uploadDir = __DIR__ . '/../uploads/avatars/';
            $filePath = $uploadDir . basename($avatar);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Actualizar BD a NULL
        $stmt = $conn->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $conn->commit();
        
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Avatar eliminado correctamente',
            'avatar_url' => "../uploads/avatars/default.png"
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Helper: Redimensionar imagen manteniendo aspecto
 */
function resizeImage($sourcePath, $mimeType, $maxWidth, $maxHeight) {
    // Crear imagen desde archivo
    $source = match($mimeType) {
        'image/jpeg' => imagecreatefromjpeg($sourcePath),
        'image/png' => imagecreatefrompng($sourcePath),
        'image/webp' => imagecreatefromwebp($sourcePath),
        default => false
    };
    
    if (!$source) {
        return false;
    }
    
    $width = imagesx($source);
    $height = imagesy($source);
    
    // Calcular nuevas dimensiones (cuadrado)
    $size = min($width, $height);
    $x = ($width - $size) / 2;
    $y = ($height - $size) / 2;
    
    // Crear imagen cuadrada
    $dest = imagecreatetruecolor($maxWidth, $maxHeight);
    
    // Preservar transparencia para PNG
    if ($mimeType === 'image/png') {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
        imagefilledrectangle($dest, 0, 0, $maxWidth, $maxHeight, $transparent);
    }
    
    // Copiar y redimensionar
    imagecopyresampled($dest, $source, 0, 0, (int)$x, (int)$y, $maxWidth, $maxHeight, (int)$size, (int)$size);
    
    imagedestroy($source);
    
    return $dest;
}
?>
