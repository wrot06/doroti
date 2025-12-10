<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
session_start();
require "conexion3.php";

// Verificar autenticación
if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Solo aceptar POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['mensaje'] = "Método no permitido.";
    header("Location: ../agregarcarpeta.php");
    exit();
}

// Cierre de sesión (aunque no debería venir aquí)
if (isset($_POST['cerrar_seccion'])) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// Validar CSRF
$csrfToken = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    $_SESSION['mensaje'] = "Error: Token CSRF inválido.";
    header("Location: ../agregarcarpeta.php");
    exit();
}

// Validar datos
$caja = filter_input(INPUT_POST, 'caja', FILTER_VALIDATE_INT);
$car2 = filter_input(INPUT_POST, 'car2', FILTER_VALIDATE_INT);
$user_id = (int)$_SESSION['user_id'];

if (!$caja || !$car2 || $caja < 1 || $car2 < 1) {
    $_SESSION['mensaje'] = "Ambos campos deben ser números enteros positivos.";
    header("Location: ../agregarcarpeta.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Verificar duplicado
    $checkStmt = $conec->prepare("SELECT 1 FROM Carpetas WHERE Caja = ? AND Car2 = ? LIMIT 1");
    $checkStmt->bind_param("ii", $caja, $car2);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();

    if ($exists) {
        $_SESSION['mensaje'] = "Esta carpeta ya existe en la base de datos.";
        header("Location: ../agregarcarpeta.php");
        exit();
    }

    // Insertar carpeta
    $stmt = $conec->prepare("
        INSERT INTO Carpetas (Caja, Carpeta, CaCa, Car2, Serie, Subs, Titulo, FInicial, FFinal, Folios, Estado, user_id)
        VALUES (?, NULL, NULL, ?, '', NULL, '', '1970-01-01', '1970-01-01', 0, 'A', ?)
    ");
    $stmt->bind_param("iii", $caja, $car2, $user_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['caja'] = $caja;
    $_SESSION['car2'] = $car2;
    $_SESSION['mensaje'] = "Datos guardados correctamente.";
    header("Location: ../indice.php");
    exit();

} catch (Exception $e) {
    $_SESSION['mensaje'] = "Error al guardar: " . $e->getMessage();
    header("Location: ../agregarcarpeta.php");
    exit();
} finally {
    $conec->close();
}
