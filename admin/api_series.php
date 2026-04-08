<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

if (ob_get_level() === 0) {
    ob_start();
}

session_start();

if (empty($_SESSION['authenticated']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

try {
    $conexionPath = __DIR__ . '/../rene/conexion3.php';
    if (!file_exists($conexionPath)) {
        throw new Exception("Archivo de conexión no encontrado");
    }

    require_once $conexionPath;
    ini_set('display_errors', '0');

    if (!isset($conec) || $conec->connect_error) {
        throw new Exception("Error al conectar con la base de datos");
    }

    $conec->set_charset("utf8mb4");

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'list_global':
            listGlobalSeries($conec);
            break;
        case 'add_serie':
            addSerie($conec);
            break;
        case 'update_serie':
            updateSerie($conec);
            break;
        case 'delete_serie':
            deleteSerie($conec);
            break;
        case 'list_by_oficina':
            listSeriesByOficina($conec);
            break;
        case 'list_unassigned':
            listUnassignedSeries($conec);
            break;
        case 'link_serie':
            linkSerieToOficina($conec);
            break;
        case 'unlink_serie':
            unlinkSerieFromOficina($conec);
            break;
        default:
            throw new Exception("Acción no válida");
    }
} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (ob_get_length()) ob_end_flush();

function listGlobalSeries($conn)
{
    $sql = "SELECT id, nombre FROM Serie ORDER BY nombre ASC";
    $res = $conn->query($sql);
    $series = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $series[] = $row;
        }
    }
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'data' => $series]);
}

function addSerie($conn)
{
    $nombre = trim($_POST['nombre'] ?? '');
    if (empty($nombre)) throw new Exception("El nombre de la serie es requerido");

    $stmtCheck = $conn->prepare("SELECT id FROM Serie WHERE nombre = ?");
    $stmtCheck->bind_param("s", $nombre);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        throw new Exception("Ya existe una serie con este nombre");
    }
    $stmtCheck->close();

    $stmt = $conn->prepare("INSERT INTO Serie (nombre) VALUES (?)");
    $stmt->bind_param("s", $nombre);
    if (!$stmt->execute()) {
        throw new Exception("Error al crear la serie");
    }
    $newId = $conn->insert_id;
    $stmt->close();

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'message' => 'Serie creada exitosamente', 'id' => $newId]);
}

function updateSerie($conn)
{
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nombre'] ?? '');
    
    if (!$id) throw new Exception("ID inválido");
    if (empty($nombre)) throw new Exception("El nombre es requerido");

    $stmtCheck = $conn->prepare("SELECT id FROM Serie WHERE nombre = ? AND id != ?");
    $stmtCheck->bind_param("si", $nombre, $id);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        throw new Exception("Ya existe otra serie con este nombre");
    }
    $stmtCheck->close();

    $stmt = $conn->prepare("UPDATE Serie SET nombre = ? WHERE id = ?");
    $stmt->bind_param("si", $nombre, $id);
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar");
    }
    
    // Si se actualizó el nombre, deberíamos actualizar las referencias en la tabla Subs (si es que guarda string)
    // Sabemos por api_subseries que Subs tiene serie_nombre.
    if ($stmt->affected_rows > 0) {
        $stmtUpdateSubs = $conn->prepare("UPDATE Subs SET serie_nombre = ? WHERE serie_id = ?");
        $stmtUpdateSubs->bind_param("si", $nombre, $id);
        $stmtUpdateSubs->execute();
        $stmtUpdateSubs->close();
    }
    
    $stmt->close();

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'message' => 'Serie actualizada exitosamente']);
}

function deleteSerie($conn)
{
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    if (!$id) throw new Exception("ID inválido");

    $stmtCheck = $conn->prepare("SELECT id FROM OficinaSerie WHERE serie_id = ? LIMIT 1");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        throw new Exception("No se puede eliminar la serie porque está asignada a al menos una oficina");
    }
    $stmtCheck->close();

    $stmtCheck2 = $conn->prepare("SELECT id FROM Subs WHERE serie_id = ? LIMIT 1");
    $stmtCheck2->bind_param("i", $id);
    $stmtCheck2->execute();
    if ($stmtCheck2->get_result()->num_rows > 0) {
        throw new Exception("No se puede eliminar la serie porque tiene subseries asociadas");
    }
    $stmtCheck2->close();

    $stmt = $conn->prepare("DELETE FROM Serie WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception("Error al eliminar la serie");
    }
    if ($stmt->affected_rows === 0) {
        throw new Exception("La serie no existe");
    }
    $stmt->close();

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'message' => 'Serie eliminada exitosamente']);
}

function listSeriesByOficina($conn)
{
    $dependencia_id = filter_var($_GET['dependencia_id'] ?? '', FILTER_VALIDATE_INT);
    if (!$dependencia_id) throw new Exception("Dependencia requerida");

    $sql = "SELECT os.id as vinculo_id, s.id, s.nombre 
            FROM OficinaSerie os
            INNER JOIN Serie s ON os.serie_id = s.id
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

function listUnassignedSeries($conn)
{
    $dependencia_id = filter_var($_GET['dependencia_id'] ?? '', FILTER_VALIDATE_INT);
    if (!$dependencia_id) throw new Exception("Dependencia requerida");

    $sql = "SELECT id, nombre FROM Serie 
            WHERE id NOT IN (SELECT serie_id FROM OficinaSerie WHERE dependencia_id = ?)
            ORDER BY nombre ASC";
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

function linkSerieToOficina($conn)
{
    $dependencia_id = filter_var($_POST['dependencia_id'] ?? '', FILTER_VALIDATE_INT);
    $serie_id = filter_var($_POST['serie_id'] ?? '', FILTER_VALIDATE_INT);

    if (!$dependencia_id) throw new Exception("Dependencia requerida");
    if (!$serie_id) throw new Exception("Serie requerida");

    $stmtCheck = $conn->prepare("SELECT id FROM OficinaSerie WHERE dependencia_id = ? AND serie_id = ?");
    $stmtCheck->bind_param("ii", $dependencia_id, $serie_id);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        throw new Exception("Esta serie ya está asignada a esta oficina");
    }
    $stmtCheck->close();

    $stmt = $conn->prepare("INSERT INTO OficinaSerie (dependencia_id, serie_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $dependencia_id, $serie_id);
    if (!$stmt->execute()) {
        throw new Exception("Error al asignar la serie: " . $stmt->error);
    }
    $stmt->close();

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'message' => 'Serie asignada exitosamente']);
}

function unlinkSerieFromOficina($conn)
{
    $vinculo_id = filter_var($_POST['vinculo_id'] ?? '', FILTER_VALIDATE_INT);
    if (!$vinculo_id) throw new Exception("ID del vínculo requerido");

    // Requerimos que no haya subseries de esta oficina vinculadas a esta serie, de lo contrario la DB puede quedar huérfana
    $stmtData = $conn->prepare("SELECT dependencia_id, serie_id FROM OficinaSerie WHERE id = ?");
    $stmtData->bind_param("i", $vinculo_id);
    $stmtData->execute();
    $result = $stmtData->get_result();
    
    if($result->num_rows === 0) {
        throw new Exception("El vínculo no existe");
    }
    $data = $result->fetch_assoc();
    $stmtData->close();
    
    $stmtCheck = $conn->prepare("SELECT id FROM Subs WHERE dependencia_id = ? AND serie_id = ? LIMIT 1");
    $stmtCheck->bind_param("ii", $data['dependencia_id'], $data['serie_id']);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        throw new Exception("No puedes desvincular esta serie porque la oficina ya tiene subseries creadas bajo ella.");
    }
    $stmtCheck->close();

    $stmt = $conn->prepare("DELETE FROM OficinaSerie WHERE id = ?");
    $stmt->bind_param("i", $vinculo_id);
    if (!$stmt->execute()) {
        throw new Exception("Error al desvincular la serie");
    }
    $stmt->close();

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'message' => 'Serie desvinculada exitosamente']);
}
