// Ajuste para evitar error open_basedir
// Si el archivo está en la raíz de htdocs, no puede salir con "../".
// Buscamos en carpetas internas permitidas.
$rutas = [
    'rene/conexion3.php',  // Si la carpeta rene está dentro de htdocs
    'conexion.php'         // Si está en la raíz
];

$encontrado = false;
foreach ($rutas as $ruta) {
    if (file_exists($ruta)) {
        require_once $ruta;
        $encontrado = true;
        break;
    }
}

if (!$encontrado) {
    // Si no se encuentra, intentamos la ruta absoluta si se conoce, o fallamos con mensaje
    die(json_encode(['success' => false, 'message' => 'Error: No se encuentra el archivo de conexión. Verifique que la carpeta "rene" esté dentro de htdocs.']));
}

header('Content-Type: application/json');

// Mover la lógica de recepción de peticiones al inicio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leer el cuerpo de la solicitud JSON si es necesario, o usar $_POST estándar
    // Aquí asumimos $_POST estándar como es común en PHP simple
    
    $accion = $_POST['accion'] ?? '';

    switch ($accion) {
        case 'modificar_caja_carpeta':
            modificarCajaCarpeta($conn);
            break;
        case 'eliminar_carpeta':
            eliminarCarpeta($conn);
            break;
        // Aquí podrías agregar más casos, como 'restaurar_carpeta' si fuera necesario
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}

/**
 * Función para modificar el número de Caja y Carpeta.
 * Verifica que no exista duplicado para la misma Dependencia.
 */
function modificarCajaCarpeta($conn) {
    if (!isset($_POST['id_carpeta'], $_POST['nueva_caja'], $_POST['nueva_carpeta'])) {
        echo json_encode(['success' => false, 'message' => 'Faltan parámetros (id_carpeta, nueva_caja, nueva_carpeta).']);
        return;
    }

    $id_carpeta = intval($_POST['id_carpeta']);
    $nueva_caja = trim($_POST['nueva_caja']);
    $nueva_carpeta = trim($_POST['nueva_carpeta']);

    // 1. Obtener la dependencia actual de la carpeta
    // Importante para mantener la integridad de la dependencia
    $stmt = $conn->prepare("SELECT Dependencia FROM Carpetas WHERE id = ?");
    $stmt->bind_param("i", $id_carpeta);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'La carpeta no existe.']);
        $stmt->close();
        return;
    }

    $fila = $res->fetch_assoc();
    $dependencia = $fila['Dependencia'];
    $stmt->close();

    // 2. Verificar duplicados en la misma dependencia
    // Buscamos si existe OTRA carpeta (id != actual) con la misma Caja y Carpeta en esta Dependencia
    $stmt_check = $conn->prepare("SELECT id FROM Carpetas WHERE Caja = ? AND Carpeta = ? AND Dependencia = ? AND id != ?");
    $stmt_check->bind_param("sssi", $nueva_caja, $nueva_carpeta, $dependencia, $id_carpeta);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Ya existe la Caja $nueva_caja / Carpeta $nueva_carpeta en la dependencia $dependencia."]);
        $stmt_check->close();
        return;
    }
    $stmt_check->close();

    // 3. Realizar la actualización
    $stmt_update = $conn->prepare("UPDATE Carpetas SET Caja = ?, Carpeta = ? WHERE id = ?");
    $stmt_update->bind_param("ssi", $nueva_caja, $nueva_carpeta, $id_carpeta);

    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Carpeta modificada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $conn->error]);
    }
    $stmt_update->close();
}

/**
 * Función para eliminar una carpeta y sus documentos asociados.
 * Elimina de la tabla de Carpetas y de IndiceDocumental.
 */
function eliminarCarpeta($conn) {
    if (!isset($_POST['id_carpeta'])) {
        echo json_encode(['success' => false, 'message' => 'Falta el parámetro id_carpeta.']);
        return;
    }

    $id_carpeta = intval($_POST['id_carpeta']);

    // Iniciamos una transacción para que se borre todo o nada
    $conn->begin_transaction();

    try {
        // Opcional: Obtener datos antes de borrar (por seguridad o logs)
        // También verifica que la carpeta exista
        $stmt_check = $conn->prepare("SELECT Dependencia FROM Carpetas WHERE id = ?");
        $stmt_check->bind_param("i", $id_carpeta);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows === 0) {
            throw new Exception("La carpeta no existe.");
        }
        $stmt_check->close();

        // 1. Eliminar documentos asociados en IndiceDocumental
        // Se asume que IndiceDocumental tiene 'id_carpeta' como clave foránea
        $stmt_docs = $conn->prepare("DELETE FROM IndiceDocumental WHERE id_carpeta = ?");
        $stmt_docs->bind_param("i", $id_carpeta);
        if (!$stmt_docs->execute()) {
            throw new Exception("Error al eliminar documentos: " . $stmt_docs->error);
        }
        $stmt_docs->close();

        // 2. Eliminar la carpeta de la tabla Carpetas
        $stmt_folder = $conn->prepare("DELETE FROM Carpetas WHERE id = ?");
        $stmt_folder->bind_param("i", $id_carpeta);
        if (!$stmt_folder->execute()) {
            throw new Exception("Error al eliminar la carpeta: " . $stmt_folder->error);
        }
        $stmt_folder->close();

        // Confirmar transacción
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Carpeta y documentos eliminados correctamente.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
