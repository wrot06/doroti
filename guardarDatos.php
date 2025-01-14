<<<<<<< HEAD
<?php 
header('Content-Type: text/html; charset=utf-8');
session_start();
require "rene/conexion3.php";

// Verifica si el usuario está autenticado
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Redirige al inicio de sesión si no está autenticado
    exit();
}

// Manejo del cierre de sesión
if (isset($_POST['cerrar_seccion'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Procesar el formulario solo si es POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Verifica el token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['mensaje'] = "Error: Token CSRF inválido.";
        header("Location: agregarcarpeta.php");
        exit();
    }

    // Validación de campos
    $caja = isset($_POST['caja']) ? intval($_POST['caja']) : 0;
    $car2 = isset($_POST['car2']) ? intval($_POST['car2']) : 0;

    if ($caja > 0 && $car2 > 0) {
        // Conexión a la base de datos
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Habilitar excepciones en caso de error
        try {
            $conec = new mysqli($servername, $username, $password, $dbname);

            // Verificar si la combinación de Caja y Car2 ya existe
            $checkStmt = $conec->prepare("SELECT COUNT(*) FROM Carpetas WHERE Caja = ? AND Car2 = ?");
            $checkStmt->bind_param("ii", $caja, $car2);
            $checkStmt->execute();
            $checkStmt->bind_result($count);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($count > 0) {
                $_SESSION['mensaje'] = "Esta carpeta ya existe en la base de datos.";
                header("Location: agregarcarpeta.php");
                exit();
            }

            // Insertar datos si no existen
            $stmt = $conec->prepare("INSERT INTO Carpetas (Caja, Carpeta, CaCa, Car2, Serie, Subs, Titulo, FInicial, FFinal, Folios, Estado) VALUES (?, NULL, NULL, ?, '', NULL, '', '1970-01-01', '1970-01-01', 0, 'A')");
            $stmt->bind_param("ii", $caja, $car2);

            if ($stmt->execute()) {
                // Guardar las variables en la sesión para redirigir a indice2.php
                $_SESSION['caja'] = $caja;
                $_SESSION['car2'] = $car2;
                
                $_SESSION['mensaje'] = "Datos guardados correctamente.";
                header("Location: indice.php"); // Redirigir a indice2.php
                exit();
            } else {
                $_SESSION['mensaje'] = "Error al guardar los datos.";
                header("Location: agregarcarpeta.php");
                exit();
            }
        } catch (Exception $e) {
            error_log("Error de base de datos: " . $e->getMessage());
            $_SESSION['mensaje'] = "Error inesperado. Por favor, inténtalo de nuevo.";
            header("Location: agregarcarpeta.php");
            exit();
        } finally {
            $stmt->close();
            $conec->close();
        }
    } else {
        $_SESSION['mensaje'] = "Ambos campos deben ser números positivos.";
        header("Location: agregarcarpeta.php");
        exit();
    }
} else {
    $_SESSION['mensaje'] = "Método no permitido.";
    header("Location: agregarcarpeta.php");
    exit();
}
?>
=======
<?php 
header('Content-Type: text/html; charset=utf-8');
session_start();
require "rene/conexion3.php";

// Verifica si el usuario está autenticado
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Redirige al inicio de sesión si no está autenticado
    exit();
}

// Manejo del cierre de sesión
if (isset($_POST['cerrar_seccion'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Procesar el formulario solo si es POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Verifica el token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['mensaje'] = "Error: Token CSRF inválido.";
        header("Location: agregarcarpeta.php");
        exit();
    }

    // Validación de campos
    $caja = isset($_POST['caja']) ? intval($_POST['caja']) : 0;
    $car2 = isset($_POST['car2']) ? intval($_POST['car2']) : 0;

    if ($caja > 0 && $car2 > 0) {
        // Conexión a la base de datos
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Habilitar excepciones en caso de error
        try {
            $conec = new mysqli($servername, $username, $password, $dbname);

            // Verificar si la combinación de Caja y Car2 ya existe
            $checkStmt = $conec->prepare("SELECT COUNT(*) FROM Carpetas WHERE Caja = ? AND Car2 = ?");
            $checkStmt->bind_param("ii", $caja, $car2);
            $checkStmt->execute();
            $checkStmt->bind_result($count);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($count > 0) {
                $_SESSION['mensaje'] = "Esta carpeta ya existe en la base de datos.";
                header("Location: agregarcarpeta.php");
                exit();
            }

            // Insertar datos si no existen
            $stmt = $conec->prepare("INSERT INTO Carpetas (Caja, Carpeta, CaCa, Car2, Serie, Subs, Titulo, FInicial, FFinal, Folios, Estado) VALUES (?, NULL, NULL, ?, '', NULL, '', '1970-01-01', '1970-01-01', 0, 'A')");
            $stmt->bind_param("ii", $caja, $car2);

            if ($stmt->execute()) {
                // Guardar las variables en la sesión para redirigir a indice2.php
                $_SESSION['caja'] = $caja;
                $_SESSION['car2'] = $car2;
                
                $_SESSION['mensaje'] = "Datos guardados correctamente.";
                header("Location: indice.php"); // Redirigir a indice2.php
                exit();
            } else {
                $_SESSION['mensaje'] = "Error al guardar los datos.";
                header("Location: agregarcarpeta.php");
                exit();
            }
        } catch (Exception $e) {
            error_log("Error de base de datos: " . $e->getMessage());
            $_SESSION['mensaje'] = "Error inesperado. Por favor, inténtalo de nuevo.";
            header("Location: agregarcarpeta.php");
            exit();
        } finally {
            $stmt->close();
            $conec->close();
        }
    } else {
        $_SESSION['mensaje'] = "Ambos campos deben ser números positivos.";
        header("Location: agregarcarpeta.php");
        exit();
    }
} else {
    $_SESSION['mensaje'] = "Método no permitido.";
    header("Location: agregarcarpeta.php");
    exit();
}
?>
>>>>>>> a53bbbd (Configuración inicial y subida de archivos)
