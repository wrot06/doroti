<?php
ob_start();
session_start();

// Control de caché para evitar el reenvío del formulario al usar "Atrás"
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.



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

/*
 * Nota: Para evitar el error "Confirmar reenvío del formulario" al regresar con la flecha
 * del navegador, es esencial aplicar el patrón POST/Redirect/GET (PRG) en el procesamiento 
 * del formulario. Es decir, en el archivo "guardarDatos.php" se debe procesar la solicitud POST 
 * y luego redirigir al usuario (por ejemplo, con header("Location: ..."); exit();).
 */
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Caja y Carpeta</title>
    <link rel="stylesheet" href="css/agregarcarpeta.css">
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

    <h1>Agregar Carpeta</h1>
    <form action="rene/guardardatos.php" method="post" id="cajaCarpetaForm" onsubmit="validarFormulario(event)">
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
<?php
ob_end_flush();
?>
