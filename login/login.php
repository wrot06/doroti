<?php
declare(strict_types=1);
ob_start();
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
AuthMiddleware::initSession();
require_once '../rene/conexion3.php';

// Definir constante de acceso seguro para archivos incluidos
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// ----------------- SISTEMA DE AUTO-LOGIN SEGURO (REMEMBER ME SPLIT-TOKEN) -----------------
function rotateRememberMeToken(mysqli $conec, int $tokenId, int $userId): void {
    try {
        // Eliminar token usado
        $stmt = $conec->prepare("DELETE FROM user_tokens WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $tokenId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Generar uno nuevo (Selector/Validator Split-Token)
        $newSelector = bin2hex(random_bytes(8));
        $newValidator = bin2hex(random_bytes(32));
        $newHashedValidator = hash('sha256', $newValidator);
        $newExpires = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
        
        $stmtInsert = $conec->prepare("
            INSERT INTO user_tokens (user_id, selector, hashed_validator, expires_at)
            VALUES (?, ?, ?, ?)
        ");
        if ($stmtInsert) {
            $stmtInsert->bind_param("isss", $userId, $newSelector, $newHashedValidator, $newExpires);
            $stmtInsert->execute();
            $stmtInsert->close();
            
            $cookieValue = $newSelector . ':' . $newValidator;
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            setcookie('remember_me', $cookieValue, time() + 30 * 24 * 60 * 60, "/", "", $isSecure, true);
        }
    } catch (Throwable $e) {
        error_log("Error al rotar token de recordar sesión: " . $e->getMessage());
    }
}

function clearRememberMeCookie(): void {
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, "/");
    }
}

function attemptCookieAutoLogin(mysqli $conec): bool {
    $cookie = $_COOKIE['remember_me'] ?? '';
    if (empty($cookie) || strpos($cookie, ':') === false) {
        return false;
    }
    
    list($selector, $validator) = explode(':', $cookie, 2);
    
    try {
        $stmt = $conec->prepare("
            SELECT ut.id, ut.user_id, ut.hashed_validator, ut.expires_at, 
                   u.username, u.dependencia_id, d.nombre AS oficina, u.rol
            FROM user_tokens ut
            INNER JOIN users u ON ut.user_id = u.id
            LEFT JOIN dependencias d ON d.id = u.dependencia_id
            WHERE ut.selector = ? AND ut.expires_at > NOW()
            LIMIT 1
        ");
        if (!$stmt) return false;
        
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            // Validación en tiempo constante para evitar ataques de temporización
            if (hash_equals($row['hashed_validator'], hash('sha256', $validator))) {
                $_SESSION['authenticated'] = true;
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['dependencia_id'] = $row['dependencia_id'];
                $_SESSION['oficina'] = $row['oficina'];
                $_SESSION['rol'] = $row['rol'];
                $_SESSION['LAST_ACTIVITY'] = time();
                
                rotateRememberMeToken($conec, (int)$row['id'], (int)$row['user_id']);
                return true;
            }
        }
    } catch (Throwable $e) {
        error_log("Error en auto-login por cookie: " . $e->getMessage());
    }
    
    clearRememberMeCookie();
    return false;
}

// Intentar auto-login si no está autenticado y tiene la cookie remember_me
if (empty($_SESSION['authenticated']) && isset($_COOKIE['remember_me'])) {
    if (attemptCookieAutoLogin($conec)) {
        header("Location: ../index.php");
        exit();
    }
}
// -----------------------------------------------------------------------------------------

define('SESSION_EXPIRATION_TIME', 14400); // 4 horas

function redirect(string $url): void
{
    header("Location: $url");
    exit();
}

// Verificar expiración de sesión
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_EXPIRATION_TIME)) {
    session_unset();
    session_destroy();
    redirect('login.php');
}
$_SESSION['LAST_ACTIVITY'] = time();

// Si ya está autenticado, redirigir al index
if (!empty($_SESSION['authenticated'])) {
    redirect('../index.php');
}

// Generar CSRF token si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = null;

// Mantener compatibilidad con el formulario sin exponer contraseñas en texto plano
$rememberedUsername = '';
$rememberedPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Token CSRF inválido. Por favor, recarga la página.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $rememberMe = isset($_POST['rememberMe']);

        if ($username !== '' && $password !== '') {
            try {
                $stmt = $conec->prepare("
                    SELECT u.id, u.password, u.dependencia_id, d.nombre AS oficina, u.rol
                    FROM users u
                    LEFT JOIN dependencias d ON d.id = u.dependencia_id
                    WHERE u.username = ?
                    LIMIT 1
                ");
                if (!$stmt) {
                    throw new Exception("Error al preparar la consulta de usuario: " . $conec->error);
                }

                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($user_id, $db_password, $dep_id, $dep_nombre, $rol);
                    $stmt->fetch();

                    $password_valid = false;
                    
                    // Si es un hash válido
                    if (password_verify($password, $db_password)) {
                        $password_valid = true;
                        try {
                            if (password_needs_rehash($db_password, PASSWORD_DEFAULT)) {
                                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                                $upd = $conec->prepare("UPDATE users SET password = ? WHERE id = ?");
                                if ($upd) {
                                    $upd->bind_param("si", $new_hash, $user_id);
                                    $upd->execute();
                                    $upd->close();
                                }
                            }
                        } catch (Throwable $rehashError) {
                            error_log("Error al rehashear contraseña: " . $rehashError->getMessage());
                        }
                    } 
                    // Soporte temporal para texto plano (Migración automática)
                    elseif ($password === $db_password) {
                        $password_valid = true;
                        try {
                            $new_hash = password_hash($password, PASSWORD_DEFAULT);
                            $upd = $conec->prepare("UPDATE users SET password = ? WHERE id = ?");
                            if ($upd) {
                                $upd->bind_param("si", $new_hash, $user_id);
                                $upd->execute();
                                $upd->close();
                            }
                        } catch (Throwable $migrationError) {
                            error_log("Error al migrar contraseña de texto plano: " . $migrationError->getMessage());
                        }
                    }

                    if ($password_valid) {
                        $_SESSION['authenticated'] = true;
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['dependencia_id'] = $dep_id;
                        $_SESSION['oficina'] = $dep_nombre;
                        $_SESSION['rol'] = $rol;
                        $_SESSION['LAST_ACTIVITY'] = time();

                        if ($rememberMe) {
                            try {
                                // Generar selector/validator Split-Token seguro
                                $selector = bin2hex(random_bytes(8));
                                $validator = bin2hex(random_bytes(32));
                                $hashedValidator = hash('sha256', $validator);
                                $expires = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
                                
                                // Almacenar el token en la base de datos
                                $stmtToken = $conec->prepare("
                                    INSERT INTO user_tokens (user_id, selector, hashed_validator, expires_at)
                                    VALUES (?, ?, ?, ?)
                                ");
                                if ($stmtToken) {
                                    $stmtToken->bind_param("isss", $user_id, $selector, $hashedValidator, $expires);
                                    $stmtToken->execute();
                                    $stmtToken->close();
                                    
                                    // Guardar cookie unificada de manera segura y HttpOnly
                                    $cookieValue = $selector . ':' . $validator;
                                    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                                    setcookie('remember_me', $cookieValue, time() + 30 * 24 * 60 * 60, "/", "", $isSecure, true);
                                }
                            } catch (Throwable $tokenError) {
                                // Si falla la tabla 'user_tokens' por no existir u otro motivo, no impedimos el login normal
                                error_log("Error en sistema de recordar sesión (posiblemente falta la tabla user_tokens): " . $tokenError->getMessage());
                            }
                        } else {
                            // Limpiar cookies de sesión si desmarca la opción
                            clearRememberMeCookie();
                        }

                        redirect('../index.php');
                    } else {
                        $error = "Contraseña incorrecta.";
                    }
                } else {
                    $error = "Usuario no encontrado.";
                }
                $stmt->close();
            } catch (Throwable $dbError) {
                $error = "Error al intentar iniciar sesión. Por favor, intente de nuevo. (Detalle: " . $dbError->getMessage() . ")";
                error_log("Error general de login en DB: " . $dbError->getMessage());
            }
        } else {
            $error = "Por favor, completa todos los campos.";
        }
    } // fin else CSRF
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio de sesión - CIESJU</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body style="background: linear-gradient(135deg, #4e54c8, #8f94fb);">
    <div class="container min-vh-100 d-flex flex-column justify-content-center align-items-center">
        <div class="card shadow-sm" style="width: 100%; max-width: 400px;">
            <div class="card-body">
                <div class="text-center mb-4">
                    <img src="../img/Doroti logo1.png" alt="Logo" class="img-fluid mb-3" style="max-width: 120px;">
                    <p class="text-muted small mb-0 px-2">
                        <strong>Doroti</strong> es un software de <strong>Gestión Documental</strong> diseñado para organizar, preservar y buscar rápidamente los archivo de tu dependencia.
                    </p>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="username" name="username"
                            placeholder="Ingresa tu usuario" value="<?= htmlspecialchars($rememberedUsername) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Ingresa tu contraseña" value="<?= htmlspecialchars($rememberedPassword) ?>" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe"
                            <?php if ($rememberedUsername && $rememberedPassword) echo 'checked'; ?>>
                        <label class="form-check-label" for="rememberMe">Recordarme</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Ingresar</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="mt-3">
            <a href="#" class="text-white text-decoration-underline">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php ob_end_flush(); ?>