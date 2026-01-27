<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
header('Content-Type: application/json');

// Verifica si hay salida previa inesperada
if (ob_get_length()) {
    ob_clean();
}

file_put_contents('debug_agregar_capitulo.log', date('Y-m-d H:i:s') . " - Inicio de ejecución\n", FILE_APPEND);

require "conexion3.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $caja = filter_var($_POST['caja'], FILTER_VALIDATE_INT);
        $carpeta = filter_var($_POST['carpeta'], FILTER_VALIDATE_INT);
        $titulo = strip_tags($_POST['titulo']);
        $paginaFinal = filter_var($_POST['paginaFinal'], FILTER_VALIDATE_INT);
        $serie = strip_tags($_POST['serie']);

        if (!$caja || !$carpeta || !$titulo || !$paginaFinal) {
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

        $dependencia_id = $_SESSION['dependencia_id']; // ya viene de la sesión
        $soporte = "F"; // valor fijo

        $id_carpeta = $_SESSION['id_carpeta'] ?? null;
        if (!$id_carpeta) {
            throw new Exception("No se pudo determinar id_carpeta.");
        }

        // INSERT con id_carpeta agregado
        $sql = "INSERT INTO IndiceTemp 
                (id2, Caja, Carpeta, serie, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, dependencia_id, Soporte, carpeta_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conec->prepare($sql);

        if ($stmt === false) {
            throw new Exception("Error en la preparación de la consulta: " . $conec->error);
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

            echo json_encode([
                'status' => 'success',
                'capitulo' => [
                    'id' => $id2,
                    'titulo' => $titulo,
                    'pagina_inicio' => $paginaInicio,
                    'pagina_final' => $paginaFinal,
                    'num_paginas' => $paginas
                ],
                'siguientePagina' => $siguientePagina // ✅ agregado para el frontend
            ]);
        } else {
            throw new Exception("Error al guardar en la base de datos: " . $stmt->error);
        }

        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

$conec->close();
ob_end_flush();
?>
