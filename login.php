<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Configuración de la aplicación
$correct_password = 'Rene';
$session_expiration_time = 3600; // 1 hora

// Redirección simplificada
function redirect($url) {
    header("Location: $url");
    exit();
}

// Verifica si la sesión ha expirado
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_expiration_time)) {
    session_unset();
    session_destroy();
    redirect('login.php');
}

// Actualiza la última actividad de la sesión
$_SESSION['LAST_ACTIVITY'] = time();

// Redirige al contenido protegido si ya está autenticado
if (!empty($_SESSION['authenticated'])) {
    redirect('index.php');
}

// Maneja el envío del formulario
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    // Registro de la contraseña recibida para depuración
    error_log("Contraseña recibida: " . $password); // Para propósitos de depuración

    if ($password === $correct_password) {
        $_SESSION['authenticated'] = true;
        $_SESSION['LAST_ACTIVITY'] = time();
        redirect('index.php');
    } else {
        $error = "Contraseña incorrecta.";
        // Registro de error de autenticación
        error_log("Error de autenticación: " . $error); // Para propósitos de depuración
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            margin-top: 0;
        }
        .error {
            color: red;
        }
        .logo {
            text-align: center; /* Centrará el contenido dentro del div */
        }

        .logo img {
            width: 300px;
            border-radius: 5px; /* Ajusta el ancho de la imagen */
        }

        .container h2 {
            text-align: center;
            font-size: 10px;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="img/Doroti logo1.jpg" alt="Logo">
        </div>
        <h2>Ingresar al módulo CIESJU</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
