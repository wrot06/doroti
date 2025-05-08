<?php
declare(strict_types=1);
session_start();
require_once 'rene/conexion3.php'; // Conexión a la BD (variable $conec)

define('SESSION_EXPIRATION_TIME', 14400); // 4 horas

/**
 * Redirige a una URL dada y termina la ejecución.
 */
function redirect(string $url): void {
    header("Location: $url");
    exit();
}

/**
 * Verifica si la sesión ha expirado y, de ser así, la reinicia.
 */
function checkSessionExpiration(): void {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_EXPIRATION_TIME)) {
        session_unset();
        session_destroy();
        redirect('login.php');
    }
}

// Actualizar la sesión y redirigir si ya está autenticado
checkSessionExpiration();
$_SESSION['LAST_ACTIVITY'] = time();
if (!empty($_SESSION['authenticated'])) {
    redirect('index.php');
}

$error = null;

// Manejo del formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $conec->prepare("SELECT password FROM users WHERE username = ?");
        if (!$stmt) {
            die("Error en la consulta: " . $conec->error);
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

// Suponiendo que la consulta retorna el id del usuario
$stmt = $conec->prepare("SELECT id, password FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->bind_result($user_id, $db_password);
    $stmt->fetch();
    if ($password === $db_password) { // O usa password_verify() si se usan hashes
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $user_id;  // Se guarda el ID del usuario en la sesión
        $_SESSION['LAST_ACTIVITY'] = time();
        redirect('index.php');
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inicio de sesión - CIESJU</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body style="background: linear-gradient(135deg, #4e54c8, #8f94fb);">
  <div class="container min-vh-100 d-flex flex-column justify-content-center align-items-center">
    <div class="card shadow-sm" style="width: 100%; max-width: 400px;">
      <div class="card-body">
        <div class="text-center mb-4">
          <img src="img/Doroti logo1.jpg" alt="Logo" class="img-fluid" style="max-width: 120px;">
        </div>    
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
          <div class="mb-3">
            <label for="username" class="form-label">Usuario</label>
            <input type="text" class="form-control" id="username" name="username" placeholder="Ingresa tu usuario" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Ingresa tu contraseña" required>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
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
  <!-- Bootstrap JS Bundle (incluye Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
