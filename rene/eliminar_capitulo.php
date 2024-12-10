<?php
require "conexion3.php"; // Archivo de conexión a la base de datos

session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

// Verifica si el ID del capítulo, caja y carpeta fueron enviados
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['caja']) && isset($_POST['carpeta'])) {
    $idCapitulo = intval($_POST['id']); // Sanitiza el ID recibido
    $caja = intval($_POST['caja']); // Sanitiza la caja
    $carpeta = intval($_POST['carpeta']); // Sanitiza la carpeta

    // Verifica la conexión
    if ($conec->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'Error de conexión']);
        exit();
    }

    // Prepara la consulta de eliminación
    $sql_delete = "DELETE FROM IndiceTemp WHERE id2 = ? AND Caja = ? AND Carpeta = ?";
    $stmt_delete = $conec->prepare($sql_delete);

    // Asegúrate de que la declaración fue preparada correctamente
    if ($stmt_delete === false) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la preparación de la consulta: ' . $conec->error]);
        exit();
    }

    // Vincula los parámetros
    $stmt_delete->bind_param("iii", $idCapitulo, $caja, $carpeta);

    // Ejecuta la consulta
    if ($stmt_delete->execute()) {
        // Responde con éxito
        echo json_encode(['status' => 'success', 'message' => 'Capítulo eliminado correctamente']);
    } else {
        // Responde con error
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el capítulo: ' . $stmt_delete->error]);
    }

    // Cierra la declaración
    $stmt_delete->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida']);
    exit();
}

// Cierra la conexión
$conec->close();
?>
