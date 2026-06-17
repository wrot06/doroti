<?php
declare(strict_types=1);
ob_start();

require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../rene/conexion3.php';
require_once __DIR__ . '/../services/UserService.php';

AuthMiddleware::initSession();
AuthMiddleware::checkAuth('../login/login.php');

// Verificar rol de admin
if (($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "<h1>Acceso Denegado</h1><p>No tienes permiso para ver esta página.</p><a href='../index.php'>Volver al inicio</a>";
    exit();
}

$user_id = AuthMiddleware::validateUser();
$userService = new UserService($conec);
$userInfo = $userService->getUserInfo($user_id);
$usuario = $userInfo['username'];
$oficina = $userInfo['oficina'];
$userAvatar = '../' . $userService->getUserAvatar($user_id);

// Filtros
$tablaFiltro = trim($_GET['tabla'] ?? 'both'); // both, dep_6, dep_9
$estadoFiltro = trim($_GET['estado'] ?? 'todos'); // todos, pendientes, procesados
$search = trim($_GET['q'] ?? '');

$allowedTablas = ['both', 'dep_6', 'dep_9'];
if (!in_array($tablaFiltro, $allowedTablas)) {
    $tablaFiltro = 'both';
}

$allowedEstados = ['todos', 'pendientes', 'procesados'];
if (!in_array($estadoFiltro, $allowedEstados)) {
    $estadoFiltro = 'todos';
}

// Configuración de paginación
$limit = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Construir consulta dinámicamente
$queries = [];
$countQueries = [];

$tablesToQuery = [];
if ($tablaFiltro === 'both' || $tablaFiltro === 'dep_6') {
    $tablesToQuery[] = [
        'name' => 'indice_documental_dep_6',
        'label' => 'dep_6'
    ];
}
if ($tablaFiltro === 'both' || $tablaFiltro === 'dep_9') {
    $tablesToQuery[] = [
        'name' => 'indice_documental_dep_9',
        'label' => 'dep_9'
    ];
}

foreach ($tablesToQuery as $t) {
    $whereConds = ["CHAR_LENGTH(DescripcionUnidadDocumental) > 300"];
    
    if ($estadoFiltro === 'pendientes') {
        $whereConds[] = "procesado_ia = 0";
    } elseif ($estadoFiltro === 'procesados') {
        $whereConds[] = "procesado_ia = 1";
    }
    
    if ($search !== '') {
        $whereConds[] = "DescripcionUnidadDocumental LIKE ?";
    }
    
    $whereStr = implode(' AND ', $whereConds);
    
    $queries[] = "
        SELECT 
            '{$t['label']}' AS origen_tabla, 
            id, 
            DescripcionUnidadDocumental, 
            procesado_ia, 
            CHAR_LENGTH(DescripcionUnidadDocumental) AS longitud 
        FROM `{$t['name']}`
        WHERE {$whereStr}
    ";
    
    $countQueries[] = "
        SELECT COUNT(*) AS total 
        FROM `{$t['name']}` 
        WHERE {$whereStr}
    ";
}

// Preparar y ejecutar query de conteo total
$totalRows = 0;
$unionCountQuery = "(" . implode(") UNION ALL (", $countQueries) . ")";

if ($search !== '') {
    $searchWildcard = "%{$search}%";
    $stmtCount = $conec->prepare($unionCountQuery);
    
    // Bindear los parámetros de búsqueda según las tablas consultadas
    if (count($tablesToQuery) === 2) {
        $stmtCount->bind_param("ss", $searchWildcard, $searchWildcard);
    } else {
        $stmtCount->bind_param("s", $searchWildcard);
    }
    
    $stmtCount->execute();
    $resCount = $stmtCount->get_result();
    while ($row = $resCount->fetch_assoc()) {
        $totalRows += (int)$row['total'];
    }
    $stmtCount->close();
} else {
    $resCount = $conec->query($unionCountQuery);
    if ($resCount) {
        while ($row = $resCount->fetch_assoc()) {
            $totalRows += (int)$row['total'];
        }
    }
}

$totalPages = max(1, (int)ceil($totalRows / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// Obtener los registros de la página actual
$records = [];
$unionDataQuery = "(" . implode(") UNION ALL (", $queries) . ") ORDER BY longitud DESC LIMIT {$limit} OFFSET {$offset}";

if ($search !== '') {
    $stmtData = $conec->prepare($unionDataQuery);
    
    if (count($tablesToQuery) === 2) {
        $stmtData->bind_param("ss", $searchWildcard, $searchWildcard);
    } else {
        $stmtData->bind_param("s", $searchWildcard);
    }
    
    $stmtData->execute();
    $resData = $stmtData->get_result();
    while ($row = $resData->fetch_assoc()) {
        $records[] = $row;
    }
    $stmtData->close();
} else {
    $resData = $conec->query($unionDataQuery);
    if ($resData) {
        while ($row = $resData->fetch_assoc()) {
            $records[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo IA - Correcciones Masivas</title>
    
    <!-- CSS Bootstrap 5 y Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f4f6f9;
            padding-top: 90px;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .filter-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .ia-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .ia-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }
        .badge-pending {
            background-color: #ffebee;
            color: #c62828;
            font-weight: 600;
        }
        .badge-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            font-weight: 600;
        }
        .badge-origen {
            background-color: #e3f2fd;
            color: #1565c0;
            font-weight: 600;
        }
        .textarea-custom {
            resize: vertical;
            font-size: 1rem;
            line-height: 1.4;
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: border-color 0.2s;
        }
        .textarea-custom:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.15);
        }
        .btn-improve {
            background: linear-gradient(135deg, #02b3e4 0%, #0280a3 100%);
            border: none;
            color: #fff;
            transition: opacity 0.2s;
        }
        .btn-improve:hover {
            opacity: 0.95;
            color: #fff;
        }
        .page-link {
            border-radius: 6px;
            margin: 0 2px;
        }
    </style>
</head>
<body>

<?php
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}
$basePath = '../';
$activePage = 'ia';
require_once __DIR__ . '/../components/navbar.php';
?>

<main class="container py-4">
    <div class="row">
        <!-- Cabecera -->
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1"><i class="bi bi-magic text-info me-2"></i>Módulo de Limpieza con IA</h2>
                <p class="text-muted mb-0">Correcciones ortográficas y resúmenes automáticos de textos mayores a 300 caracteres.</p>
            </div>
            <div class="mt-2 mt-sm-0">
                <span class="badge bg-dark fs-6 py-2 px-3 rounded-pill shadow-sm">Total Coincidencias: <?= $totalRows ?></span>
            </div>
        </div>

        <!-- Filtros -->
        <div class="col-12 mb-4">
            <div class="filter-card p-3">
                <form method="GET" action="" class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <label for="tabla" class="form-label fw-semibold text-muted mb-1">Tabla/Dependencia</label>
                        <select name="tabla" id="tabla" class="form-select form-select-sm">
                            <option value="both" <?= $tablaFiltro === 'both' ? 'selected' : '' ?>>Todas las Dependencias</option>
                            <option value="dep_6" <?= $tablaFiltro === 'dep_6' ? 'selected' : '' ?>>Dependencia 6 (Tabla dep_6)</option>
                            <option value="dep_9" <?= $tablaFiltro === 'dep_9' ? 'selected' : '' ?>>Dependencia 9 (Tabla dep_9)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="estado" class="form-label fw-semibold text-muted mb-1">Estado de Proceso IA</label>
                        <select name="estado" id="estado" class="form-select form-select-sm">
                            <option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Mostrar Todos</option>
                            <option value="pendientes" <?= $estadoFiltro === 'pendientes' ? 'selected' : '' ?>>Solo Pendientes</option>
                            <option value="procesados" <?= $estadoFiltro === 'procesados' ? 'selected' : '' ?>>Solo Procesados por IA</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="q" class="form-label fw-semibold text-muted mb-1">Buscar Texto</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="q" id="q" class="form-control" placeholder="Escribe palabras clave..." value="<?= htmlspecialchars($search) ?>">
                            <?php if ($search !== ''): ?>
                                <a href="?tabla=<?= $tablaFiltro ?>&estado=<?= $estadoFiltro ?>" class="btn btn-outline-secondary" type="button"><i class="bi bi-x-lg"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-2 d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Listado de Tarjetas -->
        <div class="col-12">
            <?php if (empty($records)): ?>
                <div class="alert alert-info text-center py-5 rounded-4 shadow-sm">
                    <i class="bi bi-check-circle fs-2 mb-2 d-block text-success"></i>
                    <h4 class="fw-bold">¡No hay registros pendientes!</h4>
                    <p class="text-muted mb-0">No se encontraron descripciones mayores a 300 caracteres que cumplan con los filtros activos.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($records as $r): ?>
                        <?php 
                        $tNombre = $r['origen_tabla'] === 'dep_6' ? 'indice_documental_dep_6' : 'indice_documental_dep_9';
                        $tLabel = $r['origen_tabla'] === 'dep_6' ? 'Dependencia 6' : 'Dependencia 9';
                        ?>
                        <div class="col-md-6 col-lg-12" id="card-row-<?= $r['origen_tabla'] ?>-<?= $r['id'] ?>">
                            <div class="ia-card p-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 border-bottom pb-2 gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge badge-origen py-2 px-3 rounded-pill"><?= $tLabel ?></span>
                                        <span class="text-muted fw-semibold">ID: <?= $r['id'] ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted small fw-medium text-uppercase char-counter">Caracteres: <?= $r['longitud'] ?></span>
                                        <span class="badge status-badge py-2 px-3 rounded-pill <?= $r['procesado_ia'] == 1 ? 'badge-success' : 'badge-pending' ?>">
                                            <i class="bi <?= $r['procesado_ia'] == 1 ? 'bi-check-circle-fill' : 'bi-clock-history' ?> me-1"></i>
                                            <?= $r['procesado_ia'] == 1 ? 'Procesado' : 'Pendiente' ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <textarea class="form-control textarea-custom" rows="4" style="resize: vertical; min-height: 90px;" placeholder="Descripción de la unidad documental..."><?= htmlspecialchars($r['DescripcionUnidadDocumental']) ?></textarea>
                                </div>
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <small class="text-success save-status-label d-none"><i class="bi bi-check-lg me-1"></i>¡Guardado en base de datos!</small>
                                        <small class="text-danger error-status-label d-none"><i class="bi bi-exclamation-triangle me-1"></i>Error al guardar.</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-improve btn-sm btn-action-improve" type="button" data-id="<?= $r['id'] ?>" data-table="<?= $tNombre ?>">
                                            <i class="bi bi-magic me-1"></i> Mejorar con IA
                                        </button>
                                        <button class="btn btn-success btn-sm btn-action-save" type="button" data-id="<?= $r['id'] ?>" data-table="<?= $tNombre ?>">
                                            <i class="bi bi-save me-1"></i> Guardar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($totalPages > 1): ?>
                    <nav class="d-flex justify-content-center mt-5">
                        <ul class="pagination pagination-sm shadow-sm">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?tabla=<?= $tablaFiltro ?>&estado=<?= $estadoFiltro ?>&q=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" aria-label="Anterior">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php 
                            $startPage = max(1, $page - 3);
                            $endPage = min($totalPages, $page + 3);
                            for ($p = $startPage; $p <= $endPage; $p++): 
                            ?>
                                <li class="page-item <?= $page === $p ? 'active' : '' ?>">
                                    <a class="page-link" href="?tabla=<?= $tablaFiltro ?>&estado=<?= $estadoFiltro ?>&q=<?= urlencode($search) ?>&page=<?= $p ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?tabla=<?= $tablaFiltro ?>&estado=<?= $estadoFiltro ?>&q=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" aria-label="Siguiente">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- jQuery y Bootstrap 5 JS Bundle -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    
    // 1. Manejar Clic en "Mejorar con IA"
    $(document).on("click", ".btn-action-improve", function() {
        const $btn = $(this);
        const $card = $btn.closest(".ia-card");
        const $textarea = $card.find("textarea");
        const $saveBtn = $card.find(".btn-action-save");
        const textoOriginal = $textarea.val().trim();
        
        if (textoOriginal === '') {
            alert("El texto no puede estar vacío.");
            return;
        }
        
        // Estado de cargando
        $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...');
        $saveBtn.prop("disabled", true);
        $textarea.prop("disabled", true);
        
        $.ajax({
            url: '../rene/corregir_descripcion_ia.php',
            method: 'POST',
            data: {
                texto: textoOriginal
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $textarea.val(response.texto_corregido);
                    
                    // Actualizar contador visual de caracteres
                    const newLength = response.texto_corregido.length;
                    $card.find(".char-counter").text("Caracteres: " + newLength);
                } else {
                    alert(response.message || "Error al mejorar el texto.");
                }
            },
            error: function(xhr) {
                console.error("Error AJAX IA:", xhr.responseText);
                alert("Error de conexión al servidor de Inteligencia Artificial.");
            },
            complete: function() {
                // Restaurar botones
                $btn.prop("disabled", false).html('<i class="bi bi-magic me-1"></i> Mejorar con IA');
                $saveBtn.prop("disabled", false);
                $textarea.prop("disabled", false).focus();
            }
        });
    });
    
    // 2. Manejar Clic en "Guardar"
    $(document).on("click", ".btn-action-save", function() {
        const $btn = $(this);
        const $card = $btn.closest(".ia-card");
        const $textarea = $card.find("textarea");
        const $improveBtn = $card.find(".btn-action-improve");
        
        const recordId = $btn.data("id");
        const recordTable = $btn.data("table");
        const currentText = $textarea.val().trim();
        
        if (currentText === '') {
            alert("La descripción no puede estar vacía.");
            return;
        }
        
        // Estado de cargando
        $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...');
        $improveBtn.prop("disabled", true);
        $textarea.prop("disabled", true);
        
        const $labelSuccess = $card.find(".save-status-label").addClass("d-none");
        const $labelError = $card.find(".error-status-label").addClass("d-none");
        
        $.ajax({
            url: 'guardar_descripcion.php',
            method: 'POST',
            data: {
                id: recordId,
                table: recordTable,
                texto: currentText
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Mostrar etiqueta de éxito por 3 segundos
                    $labelSuccess.removeClass("d-none");
                    setTimeout(function() {
                        $labelSuccess.addClass("d-none");
                    }, 4000);
                    
                    // Cambiar badge de estado a "Procesado" (verde)
                    const $badge = $card.find(".status-badge");
                    $badge.removeClass("badge-pending").addClass("badge-success");
                    $badge.html('<i class="bi bi-check-circle-fill me-1"></i>Procesado');
                } else {
                    $labelError.removeClass("d-none").attr("title", response.message || "Error al guardar.");
                }
            },
            error: function(xhr) {
                console.error("Error AJAX Guardar:", xhr.responseText);
                $labelError.removeClass("d-none").attr("title", "Error de red o conexión.");
            },
            complete: function() {
                // Restaurar
                $btn.prop("disabled", false).html('<i class="bi bi-save me-1"></i> Guardar');
                $improveBtn.prop("disabled", false);
                $textarea.prop("disabled", false);
            }
        });
    });
    
    // 3. Actualizar dinámicamente contador al editar texto manualmente
    $(document).on("input", ".textarea-custom", function() {
        const $textarea = $(this);
        const $card = $textarea.closest(".ia-card");
        const len = $textarea.val().length;
        $card.find(".char-counter").text("Caracteres: " + len);
    });
});
</script>

</body>
</html>
<?php ob_end_flush(); ?>
