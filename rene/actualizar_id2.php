<?php
require "conexion3.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cambiosId2 = $_POST['cambios'];

    // Primero, se ordenan los cambios por el nuevo id2
    usort($cambiosId2, function($a, $b) {
        return $a['nuevoId2'] - $b['nuevoId2'];
    });

    // Luego se actualizan los id2 en la base de datos
    foreach ($cambiosId2 as $cambio) {
        $id = $cambio['id']; // id original del capítulo
        $nuevoId2 = $cambio['nuevoId2']; // Nueva posición (id2)

        // Actualizar id2 en la base de datos
        $sql = "UPDATE IndiceTemp SET id2 = ? WHERE id = ?"; // Cambia la condición para que busque por id en lugar de id2
        $stmt = $conec->prepare($sql);
        $stmt->bind_param("ii", $nuevoId2, $id);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['status' => 'success']);
}
$conec->close();
