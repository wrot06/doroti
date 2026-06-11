<?php
declare(strict_types=1);
ob_start();

require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
AuthMiddleware::initSession();
AuthMiddleware::generateCsrf();

require_once __DIR__ . '/../rene/conexion3.php';

// Auto-creación de la tabla password_resets si no existe (self-healing)
try {
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            code VARCHAR(6) NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conec->query($create_table_sql);
} catch (Throwable $e) {
    error_log("Error al crear la tabla password_resets: " . $e->getMessage());
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$token = trim($_GET['token'] ?? '');
$code_param = trim($_GET['code'] ?? '');

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Token de seguridad (CSRF) inválido. Por favor, intente de nuevo.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'request_reset') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if ($username !== '' && $email !== '') {
                try {
                    // Buscar usuario
                    $stmt = $conec->prepare("SELECT id FROM users WHERE username = ? AND email = ? LIMIT 1");
                    $stmt->bind_param("ss", $username, $email);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $user = $res->fetch_assoc();
                    $stmt->close();

                    if ($user) {
                        $user_id = (int)$user['id'];

                        // Generar código y token
                        $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                        $token = bin2hex(random_bytes(32));
                        $expires_at = date('Y-m-d H:i:s', time() + 15 * 60); // 15 minutos

                        // Eliminar resets anteriores del mismo usuario
                        $stmtDel = $conec->prepare("DELETE FROM password_resets WHERE user_id = ?");
                        $stmtDel->bind_param("i", $user_id);
                        $stmtDel->execute();
                        $stmtDel->close();

                        // Guardar en la tabla
                        $stmtIns = $conec->prepare("INSERT INTO password_resets (user_id, code, token, expires_at) VALUES (?, ?, ?, ?)");
                        $stmtIns->bind_param("isss", $user_id, $code, $token, $expires_at);
                        $stmtIns->execute();
                        $stmtIns->close();

                        // Escribir en error.log para pruebas locales (de forma que sea visible en desarrollo)
                        error_log("[RECOVERY_DEBUG] Código de recuperación generado para el usuario {$username}: {$code} (Token: {$token})");

                        // Intentar enviar email
                        $to = $email;
                        $subject = "Restablecer contraseña - Doroti";
                        $message = "Hola, {$username}.\r\n\r\nTu código de recuperación es: {$code}\r\n\r\nEste código expira en 15 minutos.\r\nSi no solicitaste este cambio, por favor ignora este correo.";
                        $headers = "From: no-reply@doroti.com\r\n" .
                                   "Reply-To: no-reply@doroti.com\r\n" .
                                   "Content-Type: text/plain; charset=UTF-8\r\n" .
                                   "X-Mailer: PHP/" . phpversion();
                        @mail($to, $subject, $message, $headers);

                        // Redirigir al paso 2
                        header("Location: recuperar.php?step=2&token={$token}");
                        exit();
                    } else {
                        $error = "El usuario o correo electrónico ingresado no coincide con nuestros registros.";
                    }
                } catch (Throwable $e) {
                    $error = "Error al procesar la solicitud: " . $e->getMessage();
                }
            } else {
                $error = "Por favor, completa todos los campos.";
            }
        } 
        
        elseif ($action === 'verify_code') {
            $input_code = trim($_POST['code'] ?? '');
            
            if ($input_code !== '' && $token !== '') {
                try {
                    $stmt = $conec->prepare("
                        SELECT pr.user_id 
                        FROM password_resets pr
                        WHERE pr.token = ? AND pr.code = ? AND pr.expires_at > NOW()
                        LIMIT 1
                    ");
                    $stmt->bind_param("ss", $token, $input_code);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $reset = $res->fetch_assoc();
                    $stmt->close();

                    if ($reset) {
                        // Código verificado con éxito, redirigir al paso 3
                        header("Location: recuperar.php?step=3&token={$token}&code={$input_code}");
                        exit();
                    } else {
                        $error = "El código ingresado es incorrecto o ha expirado. Por favor, verifícalo.";
                    }
                } catch (Throwable $e) {
                    $error = "Error al verificar el código: " . $e->getMessage();
                }
            } else {
                $error = "Por favor, ingresa el código de verificación.";
            }
        } 
        
        elseif ($action === 'reset_password') {
            $new_pass = $_POST['new_password'] ?? '';
            $confirm_pass = $_POST['confirm_password'] ?? '';

            if (strlen($new_pass) < 6) {
                $error = "La nueva contraseña debe tener al menos 6 caracteres.";
            } elseif ($new_pass !== $confirm_pass) {
                $error = "Las contraseñas no coinciden.";
            } else {
                try {
                    // Validar token y código nuevamente por seguridad
                    $stmt = $conec->prepare("
                        SELECT pr.user_id, pr.id 
                        FROM password_resets pr
                        WHERE pr.token = ? AND pr.code = ? AND pr.expires_at > NOW()
                        LIMIT 1
                    ");
                    $stmt->bind_param("ss", $token, $code_param);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $reset = $res->fetch_assoc();
                    $stmt->close();

                    if ($reset) {
                        $user_id = (int)$reset['user_id'];
                        $reset_id = (int)$reset['id'];

                        // Hash y actualizar contraseña
                        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                        $stmtUpd = $conec->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmtUpd->bind_param("si", $hashed, $user_id);
                        $stmtUpd->execute();
                        $stmtUpd->close();

                        // Limpiar registros de restablecimiento de este usuario
                        $stmtDel = $conec->prepare("DELETE FROM password_resets WHERE user_id = ?");
                        $stmtDel->bind_param("i", $user_id);
                        $stmtDel->execute();
                        $stmtDel->close();

                        // Redirigir a login con éxito
                        header("Location: login.php?reset=success");
                        exit();
                    } else {
                        $error = "Solicitud inválida o expirada. Por favor, inicie el proceso de nuevo.";
                    }
                } catch (Throwable $e) {
                    $error = "Error al restablecer la contraseña: " . $e->getMessage();
                }
            }
        }
    }
}

// Validaciones básicas de carga por pasos
if ($step === 2 && empty($token)) {
    header("Location: recuperar.php?step=1");
    exit();
}
if ($step === 3 && (empty($token) || empty($code_param))) {
    header("Location: recuperar.php?step=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar Contraseña - CIESJU</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>

<body style="background: linear-gradient(135deg, #4e54c8, #8f94fb); min-height: 100vh;">
    <div class="container min-vh-100 d-flex flex-column justify-content-center align-items-center py-4">
        <div class="card shadow-sm" style="width: 100%; max-width: 450px; border-radius: 16px;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="../img/Doroti logo1.png" alt="Logo" class="img-fluid mb-3" style="max-width: 100px;">
                    <h4 class="fw-bold mb-1 text-dark">Recuperar Contraseña</h4>
                    <p class="text-muted small">Flujo de restablecimiento de seguridad</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center py-2 px-3 small border-0 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($step === 1): ?>
                    <!-- PASO 1: Solicitud de código -->
                    <p class="text-muted text-center small mb-4">
                        Ingresa tu nombre de usuario y correo electrónico registrado. Te enviaremos un código de verificación para restablecer tu contraseña.
                    </p>
                    <form method="POST" action="recuperar.php?step=1">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="request_reset">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label small fw-semibold">Nombre de Usuario</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control border-start-0 bg-light-focus" id="username" name="username"
                                    placeholder="Ej. Rene" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="form-label small fw-semibold">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control border-start-0" id="email" name="email"
                                    placeholder="usuario@dominio.com" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">Enviar Código <i class="bi bi-arrow-right ms-1"></i></button>
                            <a href="login.php" class="btn btn-light py-2 text-muted fw-semibold">Cancelar</a>
                        </div>
                    </form>

                <?php elseif ($step === 2): ?>
                    <!-- PASO 2: Verificación de código -->
                    <p class="text-muted text-center small mb-4">
                        Si los datos coinciden, se ha enviado un código a su dirección de correo. Por favor, ingréselo a continuación.
                    </p>
                    <form method="POST" action="recuperar.php?step=2&token=<?= htmlspecialchars($token) ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="verify_code">
                        
                        <div class="mb-4">
                            <label for="code" class="form-label small fw-semibold text-center d-block">Código de Verificación (6 dígitos)</label>
                            <input type="text" class="form-control text-center fs-3 fw-bold letter-spacing-lg" id="code" name="code"
                                maxlength="6" pattern="\d{6}" placeholder="000000" inputmode="numeric" autocomplete="one-time-code" required autofocus>
                            <div class="form-text text-center text-muted small mt-2">
                                <i class="bi bi-clock me-1"></i> Este código expira en 15 minutos.
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">Verificar Código <i class="bi bi-check-circle ms-1"></i></button>
                            <a href="recuperar.php?step=1" class="btn btn-light py-2 text-muted fw-semibold">Regresar</a>
                        </div>
                    </form>

                <?php elseif ($step === 3): ?>
                    <!-- PASO 3: Nueva contraseña -->
                    <p class="text-muted text-center small mb-4">
                        Código verificado con éxito. Por favor ingrese su nueva contraseña a continuación.
                    </p>
                    <form method="POST" action="recuperar.php?step=3&token=<?= htmlspecialchars($token) ?>&code=<?= htmlspecialchars($code_param) ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="reset_password">
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label small fw-semibold">Nueva Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control border-start-0" id="new_password" name="new_password"
                                    placeholder="Mínimo 6 caracteres" minlength="6" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label small fw-semibold">Confirmar Nueva Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control border-start-0" id="confirm_password" name="confirm_password"
                                    placeholder="Repita la contraseña" minlength="6" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success py-2 fw-semibold shadow-sm">Restablecer Contraseña <i class="bi bi-shield-check ms-1"></i></button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-3 text-center">
            <p class="text-white-50 small mb-0">Doroti - Software de Gestión Documental</p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .letter-spacing-lg {
            letter-spacing: 0.25rem;
        }
        .bg-light-focus:focus {
            background-color: #fff !important;
        }
    </style>
</body>

</html>
<?php ob_end_flush(); ?>
