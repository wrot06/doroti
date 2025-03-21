<?php
session_start();

// Define la contraseña que quieres usar
$correct_password = 'Rene';

// Tiempo de expiración de la sesión en segundos (1 hora = 3600 segundos)
$session_expiration_time = 3600;

// Verifica si la sesión ha expirado
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_expiration_time)) {
    // Si la sesión ha expirado, destruye la sesión y redirige al inicio de sesión
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Actualiza el tiempo de la última actividad
$_SESSION['LAST_ACTIVITY'] = time();

// Si ya hay una sesión activa, redirige al contenido protegido
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: index.php'); // Redirige al contenido del módulo
    exit();
}

// Si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];

    // Verifica si la contraseña es correcta
    if ($password === $correct_password) {
        $_SESSION['authenticated'] = true; // Autenticado correctamente
        $_SESSION['LAST_ACTIVITY'] = time(); // Actualiza el tiempo de la última actividad
        header('Location: index.php'); // Redirige al contenido
        exit();
    } else {
        $error = "Contraseña incorrecta.";
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
        <img src="img/Doroti logo1.jpg">
        </div>

        <h2>Ingresar al módulo CIESJU</h2>
        
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
