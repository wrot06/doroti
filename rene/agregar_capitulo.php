<?php
// Habilitar la visualización de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Asegurarte de que el contenido devuelto sea JSON
header('Content-Type: application/json');

// Limpia cualquier salida previa
if (ob_get_length()) {
    ob_clean();
}

// Registro de depuración
file_put_contents('debug_agregar_capitulo.log', date('Y-m-d H:i:s') . " - Inicio de ejecución\n", FILE_APPEND);

require "conexion3.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $caja = filter_var($_POST['caja'], FILTER_VALIDATE_INT);
        $carpeta = filter_var($_POST['carpeta'], FILTER_VALIDATE_INT);
        $titulo = strip_tags($_POST['titulo']);
        $paginaFinal = filter_var($_POST['paginaFinal'], FILTER_VALIDATE_INT);

        // Registro de depuración de entradas
        file_put_contents('debug_agregar_capitulo.log', date('Y-m-d H:i:s') . " - Caja: $caja, Carpeta: $carpeta, Titulo: $titulo, Pagina Final: $paginaFinal\n", FILE_APPEND);

        if (!$caja || !$carpeta || !$titulo || !$paginaFinal) {
            throw new Exception("Todos los campos son obligatorios y deben ser válidos.");
        }

        // Obtener la última página final
        $sql_last_page = "SELECT MAX(NoFolioFin) as ultima_pagina FROM IndiceTemp WHERE Caja = ? AND Carpeta = ?";
        $stmt_last_page = $conec->prepare($sql_last_page);
        $stmt_last_page->bind_param("ii", $caja, $carpeta);
        $stmt_last_page->execute();
        $result_last_page = $stmt_last_page->get_result();
        $ultima_pagina = $result_last_page->fetch_assoc()['ultima_pagina'] ?? 0;
        $stmt_last_page->close(); // Cierra el statement

        $paginaInicio = $ultima_pagina + 1;

        if ($paginaFinal < $paginaInicio) {
            throw new Exception("La página final debe ser mayor o igual que $paginaInicio.");
        }

        $paginas = $paginaFinal - $paginaInicio + 1;

        $sql_last_id = "SELECT MAX(id2) as last_id FROM IndiceTemp WHERE Caja = ? AND Carpeta = ?";
        $stmt_last_id = $conec->prepare($sql_last_id);
        $stmt_last_id->bind_param("ii", $caja, $carpeta);
        $stmt_last_id->execute();
        $result_last_id = $stmt_last_id->get_result();
        $last_id = $result_last_id->fetch_assoc()['last_id'] ?? 0;
        $stmt_last_id->close(); // Cierra el statement
        $id2 = $last_id + 1;

        $sql = "INSERT INTO IndiceTemp (id2, Caja, Carpeta, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conec->prepare($sql);

        if ($stmt === false) {
            throw new Exception("Error en la preparación de la consulta: " . $conec->error);
        }

        $stmt->bind_param("iiissii", $id2, $caja, $carpeta, $titulo, $paginaInicio, $paginaFinal, $paginas);

        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'capitulo' => [
                    'id' => $id2,
                    'titulo' => $titulo,
                    'pagina_inicio' => $paginaInicio,
                    'pagina_final' => $paginaFinal,
                    'num_paginas' => $paginas
                ]
            ]);
        } else {
            throw new Exception("Error al guardar en la base de datos: " . $stmt->error);
        }
        
        $stmt->close(); // Cerrar el statement de inserción
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

$conec->close(); // Cerrar la conexión
?>
