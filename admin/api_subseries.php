<?php

declare(strict_types=1);

// Configuración de errores: Silenciar salida HTML
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Iniciar buffer de salida inmediatamente para capturar cualquier echo/error temprano
if (ob_get_level() === 0) {
    ob_start();
}

session_start();

// Validar sesión de admin
if (empty($_SESSION['authenticated']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

try {
    // Verificar archivo de conexión antes de incluirlo
    $conexionPath = __DIR__ . '/../rene/conexion3.php';
    if (!file_exists($conexionPath)) {
        throw new Exception("Archivo de conexión no encontrado: $conexionPath");
    }

    require_once $conexionPath;
    ini_set('display_errors', '0'); // Restaurar silencio

    // Verificar si la conexión fue exitosa
    if (!isset($conec) || $conec->connect_error) {
        throw new Exception("Error al conectar con la base de datos");
    }

    $conec->set_charset("utf8mb4");

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'list':
            listSubseries($conec);
            break;
        case 'list_series':
            listSeriesByDependencia($conec);
            break;
        case 'add':
            addSubserie($conec);
            break;
        case 'update':
            updateSubserie($conec);
            break;
        case 'delete':
            deleteSubserie($conec);
            break;
        default:
            throw new Exception("Acción no válida");
    }
} catch (Throwable $e) {
    // Catch-all para Errores y Excepciones
    if (ob_get_length()) ob_clean();

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Finalizar buffer (importante para enviar contenido)
if (ob_get_length()) ob_end_flush();


/**
 * Listar todas las subseries con información de dependencia
 */
function listSubseries($conn)
{
    $sql = "SELECT s.id, s.serie_nombre, s.serie_id, s.dependencia_id, s.Subs as nombre, 
                   d.nombre as dependencia_nombre
            FROM Subs s
            LEFT JOIN dependencias d ON s.dependencia_id = d.id
            ORDER BY d.nombre ASC, s.serie_nombre ASC, s.Subs ASC";
    $res = $conn->query($sql);

    $subseries = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $subseries[] = $row;
        }
    }

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'data' => $subseries]);
}

/**
 * Listar series activas por dependencia
 */
function listSeriesByDependencia($conn)
{
    $dependencia_id = filter_var($_GET['dependencia_id'] ?? '', FILTER_VALIDATE_INT);
    if (!$dependencia_id) {
        throw new Exception("Dependencia ID requerido");
    }

    $sql = "SELECT s.id, s.nombre 
            FROM Serie s 
            INNER JOIN OficinaSerie os ON os.serie_id = s.id 
            WHERE os.dependencia_id = ? 
            ORDER BY s.nombre ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $dependencia_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $series = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $series[] = $row;
        }
    }
    $stmt->close();

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'data' => $series]);
}

/**
 * Agregar nueva subserie
 */
function addSubserie($conn)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    // Obtener y validar datos
    $dependencia_id = filter_var($_POST['dependencia_id'] ?? '', FILTER_VALIDATE_INT);
    $serie_id = filter_var($_POST['serie_id'] ?? '', FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nombre'] ?? '');

    // Validaciones
    if (!$dependencia_id) throw new Exception("La dependencia es requerida");
    if (!$serie_id) throw new Exception("La serie es requerida");
    if (empty($nombre)) throw new Exception("El nombre de la subserie es requerido");

    // Verificar vinculación Dependencia-Serie
    $stmtCh = $conn->prepare("SELECT id FROM OficinaSerie WHERE dependencia_id = ? AND serie_id = ?");
    $stmtCh->bind_param("ii", $dependencia_id, $serie_id);
    $stmtCh->execute();
    if ($stmtCh->get_result()->num_rows === 0) {
        throw new Exception("La dependencia no tiene asignada esta serie documental.");
    }
    $stmtCh->close();

    // Obtener serie_nombre (requerido por constraint)
    $stmtS = $conn->prepare("SELECT nombre FROM Serie WHERE id = ?");
    $stmtS->bind_param("i", $serie_id);
    $stmtS->execute();
    $resS = $stmtS->get_result();
    if ($resS->num_rows === 0) throw new Exception("Serie no encontrada.");
    $serie_nombre = $resS->fetch_assoc()['nombre'];
    $stmtS->close();

    // Verificar que el nombre de la subserie no exista en esa serie o dependencia
    $stmtDupl = $conn->prepare("SELECT id FROM Subs WHERE dependencia_id = ? AND serie_id = ? AND Subs = ?");
    $stmtDupl->bind_param("iis", $dependencia_id, $serie_id, $nombre);
    $stmtDupl->execute();
    if ($stmtDupl->get_result()->num_rows > 0) {
        throw new Exception("Ya existe esta subserie en esta serie y dependencia");
    }
    $stmtDupl->close();

    // Insertar
    $stmt = $conn->prepare("INSERT INTO Subs (serie_nombre, serie_id, dependencia_id, Subs) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siis", $serie_nombre, $serie_id, $dependencia_id, $nombre);

    if (!$stmt->execute()) {
        throw new Exception("Error al crear la subserie: " . $stmt->error);
    }

    $newId = $conn->insert_id;
    $stmt->close();

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Subserie creada exitosamente',
        'id' => $newId
    ]);
}

/**
 * Actualizar subserie existente
 */
function updateSubserie($conn)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    $dependencia_id = filter_var($_POST['dependencia_id'] ?? '', FILTER_VALIDATE_INT);
    $serie_id = filter_var($_POST['serie_id'] ?? '', FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nombre'] ?? '');

    if (!$id) throw new Exception("ID inválido");
    if (!$dependencia_id) throw new Exception("La dependencia es requerida");
    if (!$serie_id) throw new Exception("La serie es requerida");
    if (empty($nombre)) throw new Exception("El nombre de la subserie es requerido");

    $stmtCheck = $conn->prepare("SELECT id FROM Subs WHERE id = ?");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows === 0) {
        throw new Exception("La subserie no existe");
    }
    $stmtCheck->close();

    // Verificar vinculación Dependencia-Serie
    $stmtCh = $conn->prepare("SELECT id FROM OficinaSerie WHERE dependencia_id = ? AND serie_id = ?");
    $stmtCh->bind_param("ii", $dependencia_id, $serie_id);
    $stmtCh->execute();
    if ($stmtCh->get_result()->num_rows === 0) {
        throw new Exception("La dependencia no tiene asignada esta serie documental.");
    }
    $stmtCh->close();

    // Obtener serie_nombre
    $stmtS = $conn->prepare("SELECT nombre FROM Serie WHERE id = ?");
    $stmtS->bind_param("i", $serie_id);
    $stmtS->execute();
    $resS = $stmtS->get_result();
    if ($resS->num_rows === 0) throw new Exception("Serie no encontrada.");
    $serie_nombre = $resS->fetch_assoc()['nombre'];
    $stmtS->close();

    // Verificar duplicado excluyendo a sí mismo
    $stmtDupl = $conn->prepare("SELECT id FROM Subs WHERE dependencia_id = ? AND serie_id = ? AND Subs = ? AND id != ?");
    $stmtDupl->bind_param("iisi", $dependencia_id, $serie_id, $nombre, $id);
    $stmtDupl->execute();
    if ($stmtDupl->get_result()->num_rows > 0) {
        throw new Exception("Ya existe esta subserie en esta serie y dependencia");
    }
    $stmtDupl->close();

    $stmt = $conn->prepare("UPDATE Subs SET serie_nombre = ?, serie_id = ?, dependencia_id = ?, Subs = ? WHERE id = ?");
    $stmt->bind_param("siisi", $serie_nombre, $serie_id, $dependencia_id, $nombre, $id);

    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar la subserie: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("No se realizaron cambios (los datos son idénticos)");
    }

    $stmt->close();

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'message' => "Subserie actualizada correctamente"
    ]);
}

/**
 * Eliminar subserie
 */
function deleteSubserie($conn)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception("ID de subserie inválido");
    }

    // Verificar que existe
    $stmtCheck = $conn->prepare("SELECT Subs FROM Subs WHERE id = ?");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("La subserie no existe");
    }

    $sub = $result->fetch_assoc();
    $stmtCheck->close();

    // Eliminar
    $stmtDelete = $conn->prepare("DELETE FROM Subs WHERE id = ?");
    $stmtDelete->bind_param("i", $id);

    if (!$stmtDelete->execute()) {
        throw new Exception("Error al eliminar la subserie: " . $stmtDelete->error);
    }

    if ($stmtDelete->affected_rows === 0) {
        throw new Exception("No se pudo eliminar la subserie");
    }

    $stmtDelete->close();

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'message' => "Subserie '{$sub['Subs']}' eliminada correctamente"
    ]);
}
