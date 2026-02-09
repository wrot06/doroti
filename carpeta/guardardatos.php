<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
session_start();

require "../rene/conexion3.php";

// ==========================
// AUTENTICACIÓN
// ==========================
if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// ==========================
// SOLO POST
// ==========================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['mensaje'] = "Método no permitido.";
    header("Location: ../agregarcarpeta.php");
    exit();
}

// ==========================
// CSRF
// ==========================
$csrfToken = $_POST['csrf_token'] ?? '';
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['mensaje'] = "Token CSRF inválido.";
    header("Location: agregarcarpeta.php");
    exit();
}

// ==========================
// VALIDAR ENTRADA
// ==========================
$caja    = filter_input(INPUT_POST, 'caja', FILTER_VALIDATE_INT);
$carpeta = filter_input(INPUT_POST, 'carpeta', FILTER_VALIDATE_INT);
$user_id = (int)$_SESSION['user_id'];

if (!$caja || !$carpeta || $caja < 1 || $carpeta < 1) {
    $_SESSION['mensaje'] = "Caja y Carpeta deben ser números positivos.";
    header("Location: agregarcarpeta.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    // ==========================
    // OBTENER OFICINA DEL USUARIO
    // ==========================
    $stmt = $conec->prepare("
        SELECT dependencia_id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    if ($res->num_rows !== 1) {
        throw new Exception("El usuario no tiene oficina asignada.");
    }

    $dependencia_id = (int)$res->fetch_assoc()['dependencia_id'];

    // ==========================
    // VERIFICAR DUPLICADO (POR OFICINA)
    // ==========================
    $stmt = $conec->prepare("
        SELECT 1
        FROM Carpetas
        WHERE Caja = ?
          AND Carpeta = ?
          AND dependencia_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("iii", $caja, $carpeta, $dependencia_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['mensaje'] =
            "La Caja $caja Carpeta $carpeta ya existe en esta oficina.";
        $stmt->close();
        header("Location: agregarcarpeta.php");
        exit();
    }
    $stmt->close();

    // ==========================
    // INSERT
    // ==========================
    $stmt = $conec->prepare("
        INSERT INTO Carpetas (
            Caja,
            Carpeta,
            user_id,
            dependencia_id,
            Estado,
            FechaIngreso,
            Serie,
            FInicial,
            FFinal,
            Folios
        ) VALUES (?, ?, ?, ?, 'A', NOW(), '', CURDATE(), CURDATE(), 0)
    ");
    $stmt->bind_param(
        "iiii",
        $caja,
        $carpeta,
        $user_id,
        $dependencia_id
    );
    $stmt->execute();
    $stmt->close();

    // ==========================
    // OK
    // ==========================
    $_SESSION['caja']    = $caja;
    $_SESSION['carpeta'] = $carpeta;
    $_SESSION['mensaje'] = "Carpeta creada correctamente.";

    header("Location: indice.php");
    exit();

} catch (mysqli_sql_exception $e) {

    // Error por clave única (respaldo)
    if ($e->getCode() === 1062) {
        $_SESSION['mensaje'] =
            "Registro duplicado: la carpeta ya existe en esta oficina.";
        header("Location: agregarcarpeta.php");
        exit();
    }

    $_SESSION['mensaje'] = "Error en base de datos: " . $e->getMessage();
    header("Location: agregarcarpeta.php");
    exit();

} catch (Exception $e) {

    $_SESSION['mensaje'] = $e->getMessage();
    header("Location: agregarcarpeta.php");
    exit();

} finally {
    $conec->close();
}
