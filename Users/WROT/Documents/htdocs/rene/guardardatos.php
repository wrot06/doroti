<?php 
header('Content-Type: text/html; charset=utf-8');
session_start();
require "conexion3.php";

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['cerrar_seccion'])) {
        session_destroy();
        header("Location: ../login.php");
        exit();
    }

    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $_SESSION['mensaje'] = "Error: Token CSRF inválido.";
        header("Location: ../agregarcarpeta.php");
        exit();
    }

    $caja = filter_input(INPUT_POST, 'caja', FILTER_VALIDATE_INT);
    $car2 = filter_input(INPUT_POST, 'car2', FILTER_VALIDATE_INT);
    $user_id = intval($_SESSION['user_id']);

    if ($caja && $car2) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $checkStmt = $conec->prepare("SELECT 1 FROM Carpetas WHERE Caja = ? AND Car2 = ? LIMIT 1");
            if (!$checkStmt) {
                throw new Exception("Error en prepare (check): " . $conec->error);
            }
            $checkStmt->bind_param("ii", $caja, $car2);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $exists = $result && $result->num_rows > 0;
            $checkStmt->close();

            if ($exists) {
                $_SESSION['mensaje'] = "Esta carpeta ya existe en la base de datos.";
                header("Location: ../agregarcarpeta.php");
                exit();
            }

            $stmt = $conec->prepare("INSERT INTO Carpetas (Caja, Carpeta, CaCa, Car2, Serie, Subs, Titulo, FInicial, FFinal, Folios, Estado, user_id) VALUES (?, NULL, NULL, ?, '', NULL, '', '1970-01-01', '1970-01-01', 0, 'A', ?)");
            if (!$stmt) {
                throw new Exception("Error en prepare (insert): " . $conec->error);
            }

            $stmt->bind_param("iii", $caja, $car2, $user_id);
            $stmt->execute();

            $_SESSION['caja'] = $caja;
            $_SESSION['car2'] = $car2;
            $_SESSION['mensaje'] = "Datos guardados correctamente.";
            header("Location: ../indice.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['mensaje'] = "Error: " . $e->getMessage();
            header("Location: ../agregarcarpeta.php");
            exit();
        } finally {
            if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
            if (isset($conec) && $conec instanceof mysqli) $conec->close();
        }
    } else {
        $_SESSION['mensaje'] = "Ambos campos deben ser números positivos.";
        header("Location: ../agregarcarpeta.php");
        exit();
    }
} else {
    $_SESSION['mensaje'] = "Método no permitido.";
    header("Location: ../agregarcarpeta.php");
    exit();
}
?>
