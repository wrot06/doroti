<?php
declare(strict_types=1);
session_start();
require_once '../rene/conexion3.php';

define('SESSION_EXPIRATION_TIME', 14400); // 4 horas

function redirect(string $url): void {
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

$error = null;

// Autocompletar desde cookies
$rememberedUsername = $_COOKIE['remember_username'] ?? '';
$rememberedPassword = $_COOKIE['remember_password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $rememberMe = isset($_POST['rememberMe']);

    if ($username !== '' && $password !== '') {
            $stmt = $conec->prepare("
                SELECT u.id, u.password, u.dependencia_id, d.nombre AS oficina, u.rol
                FROM users u
                LEFT JOIN dependencias d ON d.id = u.dependencia_id
                WHERE u.username = ?
                LIMIT 1
            ");
        if (!$stmt) die("Error en la consulta: " . $conec->error);

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $db_password, $dep_id, $dep_nombre, $rol);
            $stmt->fetch();

            // Aquí puedes usar password_verify si la contraseña está hasheada
            if ($password === $db_password) {
                $_SESSION['authenticated'] = true;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['dependencia_id'] = $dep_id;
                $_SESSION['oficina'] = $dep_nombre;
                $_SESSION['rol'] = $rol;
                $_SESSION['LAST_ACTIVITY'] = time();

                if ($rememberMe) {
                    setcookie('remember_username', $username, time() + 30*24*60*60, "/");
                    setcookie('remember_password', $password, time() + 30*24*60*60, "/");
                } else {
                    setcookie('remember_username', '', time() - 3600, "/");
                    setcookie('remember_password', '', time() - 3600, "/");
                }

                redirect('../index.php');
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "Usuario no encontrado.";
        }
        $stmt->close();
    } else {
        $error = "Por favor, completa todos los campos.";
    }
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
                <img src="../img/Doroti logo1.png" alt="Logo" class="img-fluid" style="max-width: 120px;">
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php">
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
