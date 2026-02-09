<?php
require "conexion3.php"; // Asegúrate de incluir tu archivo de conexión

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cambios = $_POST['cambios'];

    foreach ($cambios as $cambio) {
        $id2 = $cambio['id']; // Utilizar id2 como identificador
        $paginaInicio = $cambio['paginaInicio'];
        $paginaFinal = $cambio['paginaFinal'];

        // Actualizar la consulta para usar id2
        $sql = "UPDATE IndiceTemp SET NoFolioInicio = ?, NoFolioFin = ? WHERE id2 = ?";
        $stmt = $conec->prepare($sql);
        $stmt->bind_param("iii", $paginaInicio, $paginaFinal, $id2);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['status' => 'success']);
}
$conec->close();
?>
