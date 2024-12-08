<?php
require "conexion3.php"; // Archivo de conexión a la base de datos

session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

// Verifica si el ID del capítulo fue enviado
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $idCapitulo = intval($_POST['id']); // Sanitiza el ID recibido

    // Verifica la conexión
    if ($conec->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'Error de conexión']);
        exit();
    }

    // Prepara la consulta de eliminación
    $sql_delete = "DELETE FROM IndiceTemp WHERE id2 = ?";
    $stmt_delete = $conec->prepare($sql_delete);
    $stmt_delete->bind_param("i", $idCapitulo);

    if ($stmt_delete->execute()) {
        // Responde con éxito
        echo json_encode(['status' => 'success', 'message' => 'Capítulo eliminado correctamente']);
    } else {
        // Responde con error
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el capítulo']);
    }

    $stmt_delete->close();
    $conec->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida']);
    exit();
}
?>