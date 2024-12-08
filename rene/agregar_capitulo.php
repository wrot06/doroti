<?php
require "conexion3.php"; // Asegúrate de que la ruta sea correcta

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $caja = $_POST['caja'];
    $carpeta = $_POST['carpeta'];
    $titulo = $_POST['titulo'];
    $paginaFinal = $_POST['paginaFinal'];

    // Validar entradas
    if (empty($caja) || empty($carpeta) || empty($titulo) || empty($paginaFinal)) {
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    // Obtener la última página final para determinar la siguiente página inicial
    $sql_last_page = "SELECT MAX(NoFolioFin) as ultima_pagina FROM IndiceTemp WHERE Caja = ? AND Carpeta = ?";
    $stmt_last_page = $conec->prepare($sql_last_page);
    $stmt_last_page->bind_param("ii", $caja, $carpeta);
    $stmt_last_page->execute();
    $result_last_page = $stmt_last_page->get_result();
    $ultima_pagina = $result_last_page->fetch_assoc()['ultima_pagina'] ?? 0;

    $paginaInicio = $ultima_pagina + 1; // Página inicial del nuevo capítulo

    // Validar que la página final sea mayor o igual que la página inicial
    if ($paginaFinal < $paginaInicio) {
        echo json_encode(['status' => 'error', 'message' => "La página final debe ser mayor o igual que $paginaInicio."]);
        exit;
    }

    // Calcular el número de páginas
    $paginas = $paginaFinal - $paginaInicio + 1;

    // Obtener el último id2 para la combinación de Caja y Carpeta
    $sql_last_id = "SELECT MAX(id2) as last_id FROM IndiceTemp WHERE Caja = ? AND Carpeta = ?";
    $stmt_last_id = $conec->prepare($sql_last_id);
    $stmt_last_id->bind_param("ii", $caja, $carpeta);
    $stmt_last_id->execute();
    $result_last_id = $stmt_last_id->get_result();
    $last_id = $result_last_id->fetch_assoc()['last_id'] ?? 0; // Si no hay, comenzamos desde 0
    $id2 = $last_id + 1; 

    // Consulta de inserción con el campo "paginas"
    $sql = "INSERT INTO IndiceTemp (id2, Caja, Carpeta, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conec->prepare($sql);
    
    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la preparación de la consulta: ' . $conec->error]);
        exit;
    }

    // Bind de parámetros
    $stmt->bind_param("iiissii", $id2, $caja, $carpeta, $titulo, $paginaInicio, $paginaFinal, $paginas);

    // Ejecutar y manejar la respuesta
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar en la base de datos: ' . $stmt->error]);
    }

    $stmt->close();
    $conec->close();
}
?>