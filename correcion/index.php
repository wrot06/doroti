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

$user_id = AuthMiddleware::validateUser('../login/login.php');
$userService = new UserService($conec);
$userInfo = $userService->getUserInfo($user_id);
$usuario = $userInfo['username'];
$oficina = $userInfo['oficina'];
$userAvatar = '../' . $userService->getUserAvatar($user_id);

// Generar CSRF token
AuthMiddleware::generateCsrf();

// Parámetros de búsqueda
$search = trim($_GET['q'] ?? '');
$replace = trim($_GET['r'] ?? '');

// Obtener todas las tablas dedicadas (indice_documental_dep_*)
$tables = [];
$resTables = $conec->query("SHOW TABLES LIKE 'indice_documental_dep_%'");
if ($resTables) {
    while ($row = $resTables->fetch_row()) {
        $tables[] = $row[0];
    }
}

// Obtener mapeo de dependencias para nombres bonitos
$depNames = [];
$resDep = $conec->query("SELECT id, nombre FROM dependencias");
if ($resDep) {
    while ($rowDep = $resDep->fetch_assoc()) {
        $depNames["indice_documental_dep_{$rowDep['id']}"] = $rowDep['nombre'];
    }
}

// Paginación
$limit = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$totalRows = 0;
$totalPages = 1;
$records = [];

if ($search !== '' && !empty($tables)) {
    $searchWildcard = "%{$search}%";
    
    // 1. Contar total de coincidencias en todas las tablas (Búsqueda exacta/sensible a mayúsculas)
    $countQueries = [];
    foreach ($tables as $t) {
        $countQueries[] = "SELECT COUNT(*) AS total FROM `$t` WHERE `DescripcionUnidadDocumental` LIKE BINARY ?";
    }
    $unionCountQuery = "(" . implode(") UNION ALL (", $countQueries) . ")";
    
    $stmtCount = $conec->prepare($unionCountQuery);
    if ($stmtCount) {
        $types = str_repeat('s', count($tables));
        $bindParams = array_fill(0, count($tables), $searchWildcard);
        $stmtCount->bind_param($types, ...$bindParams);
        $stmtCount->execute();
        $resCount = $stmtCount->get_result();
        if ($resCount) {
            while ($row = $resCount->fetch_assoc()) {
                $totalRows += (int)$row['total'];
            }
        }
        $stmtCount->close();
    }
    
    $totalPages = max(1, (int)ceil($totalRows / $limit));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $limit;
    
    // 2. Obtener registros paginados
    $dataQueries = [];
    foreach ($tables as $t) {
        $dataQueries[] = "SELECT '{$t}' AS origen_tabla, id, DescripcionUnidadDocumental, CHAR_LENGTH(DescripcionUnidadDocumental) AS longitud FROM `$t` WHERE `DescripcionUnidadDocumental` LIKE BINARY ?";
    }
    $unionDataQuery = "(" . implode(") UNION ALL (", $dataQueries) . ") ORDER BY longitud DESC, id ASC LIMIT {$limit} OFFSET {$offset}";
    
    $stmtData = $conec->prepare($unionDataQuery);
    if ($stmtData) {
        $types = str_repeat('s', count($tables));
        $bindParams = array_fill(0, count($tables), $searchWildcard);
        $stmtData->bind_param($types, ...$bindParams);
        $stmtData->execute();
        $resData = $stmtData->get_result();
        if ($resData) {
            while ($row = $resData->fetch_assoc()) {
                $records[] = $row;
            }
        }
        $stmtData->close();
    }
}

// Función auxiliar de escape seguro local
if (!function_exists('eh')) {
    function eh($str): string {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}

// Función para resaltar la palabra buscada (sensible a mayúsculas)
function resaltarPalabra(string $text, string $search): string {
    if ($search === '') {
        return eh($text);
    }
    $escaped = preg_quote($search, '/');
    return preg_replace('/(' . $escaped . ')/', '<mark class="bg-warning text-dark px-1 rounded">$1</mark>', eh($text));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Módulo Corrección – Búsqueda y reemplazo rápido de términos en descripciones documentales.">
    <title>Corrección de Textos – Doroti</title>
    <link rel="icon" type="image/png" href="../img/hueso.png">
    
    <!-- CSS Bootstrap 5 y Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../css/index.css">
    
    <style>
        :root {
            --clr-bg: #f8fafc;
            --clr-surface: #ffffff;
            --clr-primary: #4f46e5;
            --clr-primary-hover: #4338ca;
            --clr-accent: #0ea5e9;
            --clr-success: #10b981;
            --clr-warning: #f59e0b;
            --clr-danger: #ef4444;
            --clr-muted: #64748b;
            --clr-border: #e2e8f0;
            --radius-lg: 16px;
            --radius-md: 10px;
            --shadow-card: 0 4px 20px rgba(79, 70, 229, 0.04);
            --shadow-hover: 0 10px 30px rgba(79, 70, 229, 0.1);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--clr-bg);
            color: #1e293b;
            padding-top: 85px;
        }

        /* Hero */
        .page-hero {
            background: linear-gradient(135deg, var(--clr-primary) 0%, var(--clr-accent) 100%);
            color: #fff;
            padding: 2.5rem 2rem 3rem;
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .page-hero h1 {
            font-weight: 700;
            letter-spacing: -.5px;
        }

        .page-hero p {
            opacity: .85;
            font-size: .95rem;
        }

        /* Card Filtros / Buscador */
        .search-card {
            background: var(--clr-surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            border: 1px solid var(--clr-border);
            transition: var(--transition);
        }

        .search-card:hover {
            box-shadow: var(--shadow-hover);
        }

        /* Tarjeta de Registro */
        .record-card {
            background: var(--clr-surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            border: 1px solid var(--clr-border);
            transition: transform 0.3s ease, opacity 0.3s ease, margin 0.3s ease, padding 0.3s ease, height 0.3s ease, box-shadow 0.25s ease;
            transform-origin: center;
        }

        .record-card:hover {
            box-shadow: var(--shadow-hover);
        }

        /* Animación para remover tarjetas */
        .record-card.card-saved {
            opacity: 0;
            transform: scale(0.9);
            box-shadow: none;
        }

        /* Mini botón guardar */
        .btn-save-sm {
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Textarea de edición */
        .textarea-edit {
            border-radius: var(--radius-md);
            border: 1px solid var(--clr-border);
            font-size: 0.92rem;
            line-height: 1.45;
            transition: border-color 0.2s, box-shadow 0.2s;
            background-color: #f8fafc;
        }

        .textarea-edit:focus {
            border-color: var(--clr-primary);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .badge-source {
            background-color: #eff6ff;
            color: var(--clr-primary);
            font-weight: 600;
            font-size: 0.8rem;
            border-radius: 50px;
        }

        .badge-id {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 600;
            font-size: 0.8rem;
            border-radius: 50px;
        }

        .preview-box {
            background-color: #fdfdfd;
            border-left: 4px solid var(--clr-warning);
            border-radius: 6px;
            padding: 0.75rem 1rem;
            font-size: 0.92rem;
            color: #334155;
            line-height: 1.5;
        }

        /* Paginación */
        .page-link {
            border-radius: 6px;
            margin: 0 2px;
            color: var(--clr-primary);
            border-color: var(--clr-border);
        }
        
        .page-item.active .page-link {
            background-color: var(--clr-primary);
            border-color: var(--clr-primary);
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <?php
    if (!defined('SECURE_ACCESS')) {
        define('SECURE_ACCESS', true);
    }
    $basePath = '../';
    $activePage = 'correcion';
    require_once __DIR__ . '/../components/navbar.php';
    ?>

    <!-- HERO -->
    <div class="page-hero">
        <div class="container-fluid" style="max-width: 1200px;">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb mb-0" style="font-size: .83rem; opacity: .8;">
                    <li class="breadcrumb-item">
                        <a href="../index.php" class="text-white text-decoration-none">Inicio</a>
                    </li>
                    <li class="breadcrumb-item active text-white">Corrección</li>
                </ol>
            </nav>
            <h1 class="mb-1"><i class="bi bi-pencil-square me-2"></i>Corrección de Índices</h1>
            <p class="mb-0">Corrige y reemplaza rápidamente palabras erróneas en las descripciones documentales de todas las dependencias.</p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <main class="container-fluid py-4" style="max-width: 1200px;">
        <div class="row">
            
            <!-- Buscador y Reemplazo -->
            <div class="col-12 mb-4">
                <div class="search-card p-4">
                    <form method="GET" action="" id="searchForm" class="row g-3">
                        <div class="col-md-5">
                            <label for="q" class="form-label fw-semibold text-muted mb-1">Palabra a buscar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" id="q" class="form-control border-start-0 bg-light-focus" 
                                       placeholder="Escribe la palabra exacta..." value="<?= eh($search) ?>" required autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label for="r" class="form-label fw-semibold text-muted mb-1">Reemplazar por</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-arrow-right-short"></i></span>
                                <input type="text" name="r" id="r" class="form-control border-start-0 bg-light-focus" 
                                       placeholder="Escribe la palabra nueva..." value="<?= eh($replace) ?>" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-2 d-grid align-self-end">
                            <button type="submit" class="btn btn-primary fw-semibold" style="height: 38px;">
                                <i class="bi bi-funnel me-1"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Listado de Coincidencias -->
            <div class="col-12">
                <?php if ($search === ''): ?>
                    <!-- Estado Inicial -->
                    <div class="text-center py-5 rounded-4 shadow-sm border" style="background-color: #fff;">
                        <i class="bi bi-file-earmark-text text-muted fs-1 mb-3 d-block"></i>
                        <h4 class="fw-bold">Buscador de Corrección</h4>
                        <p class="text-muted mb-0">Escribe una palabra en el buscador de arriba para comenzar a buscar en los índices de todas las tablas dedicadas.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">
                                Coincidencias para "<span class="text-primary"><?= eh($search) ?></span>"
                            </h5>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($totalRows > 0): ?>
                                <button type="button" class="btn btn-warning btn-sm fw-bold text-dark px-3 rounded-pill shadow-sm" id="btnBulkCorrect">
                                    <i class="bi bi-lightning-fill me-1"></i> Corrección Masiva
                                </button>
                            <?php endif; ?>
                            <span class="badge bg-dark fs-6 py-2 px-3 rounded-pill shadow-sm" id="total-count-badge">
                                Coincidencias: <span id="total-count"><?= $totalRows ?></span>
                            </span>
                        </div>
                    </div>

                    <?php if (empty($records)): ?>
                        <!-- Sin resultados -->
                        <div class="alert alert-info text-center py-5 rounded-4 shadow-sm">
                            <i class="bi bi-check-circle-fill fs-2 mb-2 d-block text-success"></i>
                            <h4 class="fw-bold">¡Sin Coincidencias!</h4>
                            <p class="text-muted mb-0">No se encontraron registros que contengan la palabra "<?= eh($search) ?>" en ninguna de las tablas dedicadas.</p>
                        </div>
                    <?php else: ?>
                        <!-- Tarjetas de registros -->
                        <div class="row g-4" id="recordsContainer">
                            <?php foreach ($records as $r): 
                                $tableName = $r['origen_tabla'];
                                $depName = $depNames[$tableName] ?? $tableName;
                                $replacedText = str_replace($search, $replace, $r['DescripcionUnidadDocumental']);
                            ?>
                                <div class="col-12 record-item" id="card-<?= eh($tableName) ?>-<?= eh((string)$r['id']) ?>">
                                    <div class="record-card p-4">
                                        
                                        <!-- Header de la tarjeta -->
                                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 border-bottom pb-2 gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge badge-source py-2 px-3 rounded-pill"><i class="bi bi-building me-1"></i><?= eh($depName) ?></span>
                                                <span class="badge badge-id py-2 px-3 rounded-pill">ID: <?= eh((string)$r['id']) ?></span>
                                            </div>
                                            <div>
                                                <small class="text-muted fw-medium text-uppercase">Longitud: <?= $r['longitud'] ?> caracteres</small>
                                            </div>
                                        </div>

                                        <!-- Contenido original con resaltado -->
                                        <div class="mb-3">
                                            <div class="fw-semibold text-muted mb-1" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">Texto Original</div>
                                            <div class="preview-box">
                                                <?= resaltarPalabra($r['DescripcionUnidadDocumental'], $search) ?>
                                            </div>
                                        </div>

                                        <!-- Formulario de Edición -->
                                        <div class="mb-3">
                                            <div class="fw-semibold text-muted mb-1" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">Editar para reemplazar</div>
                                            <textarea class="form-control textarea-edit" rows="3" placeholder="Descripción corregida..."><?= eh($replacedText) ?></textarea>
                                        </div>

                                        <!-- Pie de Tarjeta y Botón Guardar -->
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="text-success status-success d-none"><i class="bi bi-check-circle-fill me-1"></i>¡Guardado correctamente!</span>
                                                <span class="text-danger status-error d-none"><i class="bi bi-exclamation-triangle-fill me-1"></i>Error al guardar</span>
                                            </div>
                                            <div>
                                                <button class="btn btn-success btn-save-sm btn-save-action" 
                                                        data-id="<?= eh((string)$r['id']) ?>" 
                                                        data-table="<?= eh($tableName) ?>">
                                                    <i class="bi bi-save"></i> Guardar
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
                                        <a class="page-link" href="?q=<?= urlencode($search) ?>&r=<?= urlencode($replace) ?>&page=<?= $page - 1 ?>" aria-label="Anterior">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <?php 
                                    $startPage = max(1, $page - 3);
                                    $endPage = min($totalPages, $page + 3);
                                    for ($p = $startPage; $p <= $endPage; $p++): 
                                    ?>
                                        <li class="page-item <?= $page === $p ? 'active' : '' ?>">
                                            <a class="page-link" href="?q=<?= urlencode($search) ?>&r=<?= urlencode($replace) ?>&page=<?= $p ?>"><?= $p ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?q=<?= urlencode($search) ?>&r=<?= urlencode($replace) ?>&page=<?= $page + 1 ?>" aria-label="Siguiente">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- MODAL DE CONFIRMACIÓN MASIVA -->
    <div class="modal fade" id="bulkConfirmModal" tabindex="-1" aria-labelledby="bulkConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-lg);">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="bulkConfirmModalLabel">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Confirmar Corrección Masiva
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="mb-2">¿Está seguro de que desea realizar la corrección masiva en todas las tablas dedicadas?</p>
                    <div class="alert alert-warning border-0 py-2 px-3 small rounded-3 mb-3">
                        <i class="bi bi-info-circle me-1"></i> Esta acción actualizará todos los registros que coinciden con el término de búsqueda exacto.
                    </div>
                    <ul class="list-group list-group-flush mb-0 border rounded-3 p-2 small bg-light">
                        <li class="list-group-item bg-transparent d-flex justify-content-between py-1 border-0">
                            <span class="text-muted">Palabra a buscar:</span>
                            <span class="fw-bold text-danger">"<?= eh($search) ?>"</span>
                        </li>
                        <li class="list-group-item bg-transparent d-flex justify-content-between py-1 border-0">
                            <span class="text-muted">Reemplazar por:</span>
                            <span class="fw-bold text-success">"<?= eh($replace) ?>"</span>
                        </li>
                        <li class="list-group-item bg-transparent d-flex justify-content-between py-1 border-0">
                            <span class="text-muted">Registros estimados:</span>
                            <span class="fw-bold text-dark" id="modalEstimateCount"><?= $totalRows ?></span>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning btn-sm fw-bold text-dark" id="btnConfirmBulkApply">
                        <i class="bi bi-check-lg me-1"></i> Sí, aplicar a todos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery y Bootstrap 5 Bundle -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Mostrar Modal de Confirmación Masiva
            $("#btnBulkCorrect").click(function() {
                const bulkModal = new bootstrap.Modal(document.getElementById('bulkConfirmModal'));
                bulkModal.show();
            });

            // Confirmar y Ejecutar Corrección Masiva
            $("#btnConfirmBulkApply").click(function() {
                const $btnConfirm = $(this);
                const $btnTrigger = $("#btnBulkCorrect");
                const searchVal = $("#q").val().trim();
                const replaceVal = $("#r").val().trim();
                
                // Deshabilitar botón
                $btnConfirm.prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...');
                
                $.ajax({
                    url: 'guardar_masivo.php',
                    method: 'POST',
                    data: {
                        q: searchVal,
                        r: replaceVal,
                        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Cerrar modal
                        bootstrap.Modal.getInstance(document.getElementById('bulkConfirmModal')).hide();
                        
                        if (response.status === 'success') {
                            alert("Corrección masiva completada: " + response.message);
                            
                            // Animación de salida de todas las tarjetas y vaciado
                            $(".record-item").addClass("card-saved");
                            setTimeout(function() {
                                $(".record-item").remove();
                                $("#total-count-badge").addClass("d-none");
                                $btnTrigger.addClass("d-none");
                                $(".pagination").addClass("d-none");
                                $("#recordsContainer").html(
                                    '<div class="col-12 alert alert-info text-center py-5 rounded-4 shadow-sm">' +
                                    '<i class="bi-check-circle-fill fs-2 mb-2 d-block text-success"></i>' +
                                    '<h4 class="fw-bold">¡Correcciones Masivas Completadas!</h4>' +
                                    '<p class="text-muted mb-0">Se han corregido todos los registros en las tablas de la base de datos.</p>' +
                                    '</div>'
                                );
                            }, 500);
                        } else {
                            alert(response.message || "Error al realizar la corrección masiva.");
                        }
                    },
                    error: function(xhr) {
                        bootstrap.Modal.getInstance(document.getElementById('bulkConfirmModal')).hide();
                        console.error("Error AJAX Masivo:", xhr.responseText);
                        alert("Error de conexión al servidor al ejecutar corrección masiva.");
                    },
                    complete: function() {
                        $btnConfirm.prop("disabled", false).html('<i class="bi bi-check-lg me-1"></i> Sí, aplicar a todos');
                    }
                });
            });

            // Guardar registro mediante AJAX
            $(document).on("click", ".btn-save-action", function() {
                const $btn = $(this);
                const $cardItem = $btn.closest(".record-item");
                const $textarea = $cardItem.find(".textarea-edit");
                
                const recordId = $btn.data("id");
                const recordTable = $btn.data("table");
                const newText = $textarea.val().trim();
                
                if (newText === '') {
                    alert("La descripción no puede estar vacía.");
                    return;
                }
                
                // Deshabilitar controles y mostrar cargando
                $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...');
                $textarea.prop("disabled", true);
                
                const $successLabel = $cardItem.find(".status-success").addClass("d-none");
                const $errorLabel = $cardItem.find(".status-error").addClass("d-none");
                
                $.ajax({
                    url: 'guardar_correcion.php',
                    method: 'POST',
                    data: {
                        id: recordId,
                        table: recordTable,
                        texto: newText,
                        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $successLabel.removeClass("d-none");
                            
                            // Animación de desvanecimiento y colapso
                            $cardItem.addClass("card-saved");
                            
                            setTimeout(function() {
                                $cardItem.slideUp(350, function() {
                                    $cardItem.remove();
                                    
                                    // Decrementar contador dinámicamente
                                    let count = parseInt($("#total-count").text());
                                    if (!isNaN(count) && count > 0) {
                                        count--;
                                        $("#total-count").text(count);
                                        
                                        // Si ya no quedan registros visibles en la página actual
                                        if ($(".record-item").length === 0) {
                                            if (count > 0) {
                                                // Hay más páginas, recargar para mostrar nuevos registros
                                                location.reload();
                                            } else {
                                                // Ya no quedan coincidencias en absoluto
                                                $("#total-count-badge").addClass("d-none");
                                                $("#recordsContainer").html(
                                                    '<div class="col-12 alert alert-info text-center py-5 rounded-4 shadow-sm">' +
                                                    '<i class="bi-check-circle-fill fs-2 mb-2 d-block text-success"></i>' +
                                                    '<h4 class="fw-bold">¡Correcciones Completadas!</h4>' +
                                                    '<p class="text-muted mb-0">Se han corregido todos los registros que contenían la palabra buscada.</p>' +
                                                    '</div>'
                                                );
                                            }
                                        }
                                    }
                                });
                            }, 500);
                            
                        } else {
                            $errorLabel.removeClass("d-none").text(response.message || "Error al guardar");
                            $btn.prop("disabled", false).html('<i class="bi bi-save"></i> Guardar');
                            $textarea.prop("disabled", false);
                        }
                    },
                    error: function(xhr) {
                        console.error("Error AJAX:", xhr.responseText);
                        $errorLabel.removeClass("d-none").text("Error de conexión al servidor");
                        $btn.prop("disabled", false).html('<i class="bi bi-save"></i> Guardar');
                        $textarea.prop("disabled", false);
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php
ob_end_flush();
?>
