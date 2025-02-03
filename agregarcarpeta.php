<?php   
ob_start();
session_start();
require 'rene/head.php';  


// Redirige si el usuario no está autenticado
if (empty($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Cierra sesión si se solicita
if (isset($_POST['cerrar_seccion'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Genera un token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Caja y Carpeta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        label {
            margin: 10px 0 5px;
            display: block;
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            font-weight: bold;
            border: none;
        }
        button:hover {
            background-color: #0056b3;
        }
        #mensaje {
            text-align: center;
            color: red;
            font-size: 14px;
        }
    </style>
    <script>
        function validarFormulario(event) {
            const caja = parseInt(document.getElementById('caja').value, 10);
            const car2 = parseInt(document.getElementById('car2').value, 10);
            const mensaje = document.getElementById('mensaje');

            if (caja <= 0 || car2 <= 0) {
                event.preventDefault();
                mensaje.textContent = "Ambos campos deben ser números positivos.";
            } else {
                mensaje.textContent = ""; // Limpiar mensaje de error
            }
        }
    </script>
</head>
<body>
    <!-- Mostrar mensajes desde $_SESSION -->
    <?php
    if (isset($_SESSION['mensaje'])) {
        echo "<div id='mensaje'>" . htmlspecialchars($_SESSION['mensaje']) . "</div>";
        unset($_SESSION['mensaje']); // Limpia el mensaje después de mostrarlo
    }
    ?>

    <h1>Agregar Caja y Carpeta</h1>
    <form action="rene/guardarDatos.php" method="post" id="cajaCarpetaForm" onsubmit="validarFormulario(event)">
        <!-- Campo CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <label for="caja">Número de Caja</label>
        <input type="number" id="caja" name="caja" required min="1" placeholder="Ingrese el número de Caja">
        
        <label for="car2">Número de Car2</label>
        <input type="number" id="car2" name="car2" required min="1" placeholder="Ingrese el número de Car2">
        
        <button type="submit">Agregar</button>
    </form>
</body>
</html>
