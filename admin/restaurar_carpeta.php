<?php
declare(strict_types=1);
session_start();

// Validar auth admin
if (empty($_SESSION['authenticated']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "<h1>Acceso Denegado</h1><p>No tienes permiso para ver esta página.</p><a href='../index.php'>Volver al inicio</a>";
    exit();
}

require_once "../rene/conexion3.php";

if (!isset($conec)) {
    die(json_encode(['success' => false, 'message' => 'Error: Fallo al conectar a la base de datos.']));
}

$mensaje = "";

// ==============================
// Lógica POST (Acciones)
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $accion = $_POST['accion'] ?? '';

    switch ($accion) {
        case 'restaurar_carpeta':
            restaurarCarpeta($conec);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
            break;
    }
    exit;
}

// ==============================
// Funciones
// ==============================

function restaurarCarpeta($conec) {
    if (!isset($_POST['id_carpeta'])) {
        echo json_encode(['success' => false, 'message' => 'Falta id_carpeta.']);
        return;
    }

    $id = intval($_POST['id_carpeta']);
    $conec->begin_transaction();

    try {
        // 1. Obtener datos de la carpeta
        $stmt = $conec->prepare("SELECT Caja, Carpeta, dependencia_id FROM Carpetas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        $stmt->close();

        if (!$data) {
            throw new Exception("Carpeta no encontrada.");
        }

        // 2. Mover registros de IndiceDocumental -> IndiceTemp
        // Mapeo manual de campos para evitar errores si las tablas difieren ligeramente
        // IndiceTemp: id2, dependencia_id, Caja, Carpeta, carpeta_id, serie, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, Soporte, FechaIngreso
        // IndiceDocumental: id, dependencia_id, Caja, Carpeta, carpeta_id, Serie, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, Soporte, FechaIngreso
        
        // NOTA: IndiceTemp tiene 'serie' (minuscula?) y IndiceDocumental 'Serie' (mayuscula?). 
        // Vamos a asumir nombres estándar o los que vimos en SHOW COLUMNS.
        // IndiceTemp columns from check: id2, dependencia_id, Caja, Carpeta, carpeta_id, serie, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, Soporte, FechaIngreso
        
        $sqlInsert = "
            INSERT INTO IndiceTemp (
                dependencia_id, Caja, Carpeta, carpeta_id, serie, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, Soporte, FechaIngreso
            )
            SELECT 
                dependencia_id, Caja, Carpeta, carpeta_id, Serie, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, Soporte, NOW()
            FROM IndiceDocumental
            WHERE carpeta_id = ?
        ";
        
        $stmtIns = $conec->prepare($sqlInsert);
        $stmtIns->bind_param("i", $id);
        if (!$stmtIns->execute()) {
             throw new Exception("Error moviendo índices: " . $stmtIns->error);
        }
        $stmtIns->close();

        // 3. Eliminar de IndiceDocumental
        $stmtDelDocs = $conec->prepare("DELETE FROM IndiceDocumental WHERE carpeta_id = ?");
        $stmtDelDocs->bind_param("i", $id);
        $stmtDelDocs->execute();
        $stmtDelDocs->close();

        // 4. Actualizar estado de Carpeta a 'A' (Activo)
        $stmtUpd = $conec->prepare("UPDATE Carpetas SET Estado = 'A' WHERE id = ?");
        $stmtUpd->bind_param("i", $id);
        $stmtUpd->execute();
        $stmtUpd->close();

        $conec->commit();
        echo json_encode(['success' => true, 'message' => 'Carpeta restaurada correctamente.']);

    } catch (Exception $e) {
        $conec->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ==============================
// Lógica GET (Vista HTML)
// ==============================

// Listar carpetas "Cerradas" ('C') para restaurar
// Agrupadas por dependencia si es posible, o simple lista
$sqlListar = "
    SELECT c.id, c.Caja, c.Carpeta, c.FechaIngreso, d.nombre as oficina
    FROM Carpetas c
    LEFT JOIN dependencias d ON c.dependencia_id = d.id
    WHERE c.Estado = 'C'
    ORDER BY c.FechaIngreso DESC
    LIMIT 50
";
$resListar = $conec->query($sqlListar);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurar Carpetas - Doroti Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<nav class="navbar fixed-top" style="background-color: #e3f2fd;" data-bs-theme="light">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="../index.php">
            <img src="../img/Doroti Logo Horizontal.png" alt="Logo Doroti" height="30">
            <span class="ms-2 fw-bold text-primary">ADMIN</span>
        </a>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <a href="admin.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Volver al Panel
            </a>
        </div>
    </div>
</nav>

<main class="container mt-5 pt-5">
    <h1 class="mb-4">Restaurar Carpetas Cerradas</h1>
    
    <div class="alert alert-warning">
        <i class="bi bi-info-circle me-2"></i>
        Las carpetas listadas aquí están en estado <strong>Cerrado (C)</strong>. 
        Al restaurarlas, pasarán a estado <strong>Activo (A)</strong> y sus índices se moverán 
        a la tabla temporal para permitir edición.
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if ($resListar && $resListar->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Caja</th>
                                <th>Carpeta</th>
                                <th>Oficina</th>
                                <th>Fecha</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $resListar->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars((string)$row['Caja']) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars((string)$row['Carpeta']) ?></td>
                                    <td><?= htmlspecialchars($row['oficina'] ?? 'Sin Oficina') ?></td>
                                    <td><?= htmlspecialchars($row['FechaIngreso']) ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-warning btn-sm btn-restaurar" 
                                                data-id="<?= $row['id'] ?>"
                                                data-desc="Caja <?= $row['Caja'] ?> / Carpeta <?= $row['Carpeta'] ?>">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted my-4">No hay carpetas cerradas para restaurar.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-restaurar').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = btn.getAttribute('data-id');
            const desc = btn.getAttribute('data-desc');

            Swal.fire({
                title: '¿Restaurar Carpeta?',
                text: `Se habilitará la edición para: ${desc}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107', // warning color
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, restaurar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    restaurar(id);
                }
            });
        });
    });

    function restaurar(id) {
        const formData = new FormData();
        formData.append('accion', 'restaurar_carpeta');
        formData.append('id_carpeta', id);

        fetch('restaurar_carpeta.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Swal.fire('Restaurada', data.message, 'success')
                    .then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Error de conexión', 'error');
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
