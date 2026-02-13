<?php
session_start();
// Desactivar display_errors para evitar corromper JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Buffer de salida para capturar cualquier echo imprevisto
ob_start();

header('Content-Type: application/json');

try {
    require "conexion3.php";

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método no permitido");
    }

    $caja = filter_input(INPUT_POST, 'caja', FILTER_VALIDATE_INT);
    $carpeta = filter_input(INPUT_POST, 'carpeta', FILTER_VALIDATE_INT);
    $titulo = trim(strip_tags($_POST['titulo'] ?? ''));
    $paginaFinal = filter_input(INPUT_POST, 'paginaFinal', FILTER_VALIDATE_INT);
    $serie = trim(strip_tags($_POST['serie'] ?? ''));

    if (!$caja || !$carpeta || empty($titulo) || !$paginaFinal) {
        throw new Exception("Todos los campos son obligatorios y deben ser válidos.");
    }

    if ($paginaFinal > 200) {
        throw new Exception("La página final no puede ser mayor a 200.");
    }

    // Obtener la última página final
    $sql_last_page = "SELECT MAX(NoFolioFin) as ultima_pagina FROM IndiceTemp WHERE Caja = ? AND Carpeta = ?";
    $stmt_last_page = $conec->prepare($sql_last_page);
    $stmt_last_page->bind_param("ii", $caja, $carpeta);
    $stmt_last_page->execute();
    $result_last_page = $stmt_last_page->get_result();
    $ultima_pagina = $result_last_page->fetch_assoc()['ultima_pagina'] ?? 0;
    $stmt_last_page->close();

    $paginaInicio = $ultima_pagina + 1;

    if ($paginaFinal < $paginaInicio) {
        throw new Exception("La página final ($paginaFinal) no puede ser menor que la página inicial ($paginaInicio).");
    }

    $paginas = $paginaFinal - $paginaInicio + 1;

    // Obtener el próximo id2
    $sql_last_id = "SELECT MAX(id2) as last_id FROM IndiceTemp WHERE Caja = ? AND Carpeta = ?";
    $stmt_last_id = $conec->prepare($sql_last_id);
    $stmt_last_id->bind_param("ii", $caja, $carpeta);
    $stmt_last_id->execute();
    $result_last_id = $stmt_last_id->get_result();
    $last_id = $result_last_id->fetch_assoc()['last_id'] ?? 0;
    $stmt_last_id->close();

    $id2 = $last_id + 1;

    // Priorizar POST sobre sesión para evitar problemas de sesión expirada
    $dependencia_id = filter_input(INPUT_POST, 'dependencia_id', FILTER_VALIDATE_INT);
    
    // Fallback a sesión si no viene por POST
    if (!$dependencia_id) {
        $dependencia_id = $_SESSION['dependencia_id'] ?? 0;
    }
    
    // Validación: no permitir dependencia_id = 0
    if (!$dependencia_id || $dependencia_id <= 0) {
        throw new Exception("dependencia_id es requerido y no puede ser 0. Verifique la sesión.");
    }
    
    $soporte = "F"; 

    $id_carpeta = $_SESSION['id_carpeta'] ?? null;
    
    // Si no está en sesión, intentar recuperarlo de DB (fallback)
    if (!$id_carpeta) {
        $stmtId = $conec->prepare("SELECT id FROM Carpetas WHERE Caja = ? AND Carpeta = ? LIMIT 1");
        $stmtId->bind_param("ii", $caja, $carpeta);
        $stmtId->execute();
        $resId = $stmtId->get_result();
        if ($rowId = $resId->fetch_assoc()) {
            $id_carpeta = $rowId['id'];
        }
        $stmtId->close();
    }

    if (!$id_carpeta) {
        throw new Exception("No se pudo determinar id_carpeta. Reinicie sesión o verifique la carpeta.");
    }

    // INSERT con id_carpeta agregado
    $sql = "INSERT INTO IndiceTemp 
            (id2, Caja, Carpeta, serie, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, dependencia_id, Soporte, carpeta_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conec->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Error en la preparación SQL: " . $conec->error);
    }

    $stmt->bind_param(
        "iiissiiiisi", 
        $id2,
        $caja,
        $carpeta,
        $serie,
        $titulo,
        $paginaInicio,
        $paginaFinal,
        $paginas,
        $dependencia_id,
        $soporte,
        $id_carpeta
    );

    if ($stmt->execute()) {
        $siguientePagina = $paginaFinal + 1;
        
        // Limpiar buffer antes de enviar JSON
        ob_clean();
        
        echo json_encode([
            'status' => 'success',
            'capitulo' => [
                'id' => $id2,
                'titulo' => $titulo,
                'pagina_inicio' => $paginaInicio,
                'pagina_final' => $paginaFinal,
                'num_paginas' => $paginas
            ],
            'siguientePagina' => $siguientePagina
        ]);
    } else {
        throw new Exception("Error al guardar en BD: " . $stmt->error);
    }

    $stmt->close();
    $conec->close();

} catch (Exception $e) {
    // Limpiar buffer antes de enviar error
    ob_clean();
    http_response_code(500); // Enviar error 500 para que ajax vaya a 'error' callback si queremos, o 200 con status error
    // El JS espera json con status error o success, pero el callback 'error' se activa con 500.
    // Vamos a enviar 200 OK con mensaje de error en JSON para que el JS lo maneje mejor si está preparado, 
    // pero el JS de capituloForm.js tiene un bloque 'error: function...' que muestra "Fallo la solicitud".
    // Si queremos que muestre el mensaje específico, deberíamos devolver JSON válido.
    // El JS tiene: `if (response.status === 'success') ... else alert(response.message)`
    // Así que mejor devolvemos 200 OK.
    http_response_code(200);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Finalizar buffer y enviar
ob_end_flush();
?>
