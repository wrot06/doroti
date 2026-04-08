<?php

declare(strict_types=1);
session_start();

// Verificar autenticación
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: ../login/login.php");
    exit();
}

// Verificar rol de admin
if (($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "<h1>Acceso Denegado</h1><p>No tienes permiso para ver esta página.</p><a href='../index.php'>Volver al inicio</a>";
    exit();
}

require_once "../rene/conexion3.php";

// Escapar HTML
function h(mixed $str): string
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

$usuario = $_SESSION['username'] ?? 'Admin';

// Obtener avatar
$userAvatar = '../uploads/avatars/default.png';
if (!empty($_SESSION['user_id'])) {
    $stmt = $conec->prepare("SELECT avatar FROM users WHERE id = ?");
    $userId = (int)$_SESSION['user_id'];
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['avatar'] && file_exists('../uploads/avatars/' . basename($row['avatar']))) {
            $userAvatar = '../uploads/avatars/' . basename($row['avatar']);
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Series - Doroti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .table-actions {
            white-space: nowrap;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg fixed-top shadow-sm" style="background-color: #e3f2fd;" data-bs-theme="light">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="admin.php">
                <img src="../img/Doroti Logo Horizontal.png" alt="Logo Doroti" height="30">
                <span class="ms-2 fw-bold text-primary">ADMIN - Series</span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="d-flex align-items-center gap-2 bg-light px-3 py-1 rounded-pill shadow-sm ms-lg-3 my-2 my-lg-0 me-auto" style="width: fit-content;">
                    <img src="<?= h($userAvatar) ?>"
                        class="rounded-circle"
                        width="35"
                        height="35"
                        style="object-fit: cover; border: 2px solid #0d6efd;"
                        alt="Avatar de <?= h($usuario) ?>">
                    <div class="d-flex flex-column lh-sm">
                        <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= h($usuario) ?></span>
                        <span class="text-muted" style="font-size: 0.75rem;"><?= h($_SESSION['dependencia'] ?? 'Admin') ?></span>
                    </div>
                </div>
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 ms-auto w-100 justify-content-lg-end">
                    <a href="admin.php" class="btn btn-outline-secondary btn-sm text-start text-lg-center">
                        <i class="bi bi-arrow-left me-2"></i>Volver al Panel
                    </a>
                    <form method="POST" action="../index.php" class="m-0">
                        <button type="submit" name="cerrar_seccion" class="btn btn-danger btn-sm w-100 text-start text-lg-center">
                            <i class="bi bi-box-arrow-right me-1"></i>Salir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mt-5 pt-5 pb-5">
        <h1 class="mb-4"><i class="bi bi-archive text-primary"></i> Gestionar Series</h1>

        <div id="messageArea"></div>

        <!-- Pestañas de navegación -->
        <ul class="nav nav-tabs mb-4" id="seriesTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="vinculos-tab" data-bs-toggle="tab" data-bs-target="#vinculos" type="button" role="tab" aria-controls="vinculos" aria-selected="true">
                    <i class="bi bi-building"></i> Series por Oficina
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="global-tab" data-bs-toggle="tab" data-bs-target="#global" type="button" role="tab" aria-controls="global" aria-selected="false">
                    <i class="bi bi-globe"></i> Series Globales
                </button>
            </li>
        </ul>

        <div class="tab-content" id="seriesTabsContent">
            
            <!-- PESTAÑA: SERIES POR OFICINA -->
            <div class="tab-pane fade show active" id="vinculos" role="tabpanel" aria-labelledby="vinculos-tab">
                <div class="card mb-4 shadow-sm border-0">
                    <div class="card-body bg-light rounded">
                        <h5 class="card-title text-primary"><i class="bi bi-link-45deg"></i> Asignar Series a Oficinas</h5>
                        <p class="text-muted small">Selecciona una oficina (dependencia) para ver y administrar las series que tiene asignadas.</p>
                        
                        <div class="row align-items-end">
                            <div class="col-md-6 mb-3">
                                <label for="oficina_select" class="form-label fw-bold">Seleccionar Oficina / Dependencia:</label>
                                <select class="form-select" id="oficina_select">
                                    <option value="">Seleccionar...</option>
                                    <!-- Dinámico -->
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="areaGestionOficina" class="d-none">
                    <div class="row">
                        <!-- Añadir vínculo -->
                        <div class="col-lg-5 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0 fs-6"><i class="bi bi-plus-circle"></i> Asignar Nueva Serie</h5>
                                </div>
                                <div class="card-body">
                                    <form id="formLinkSerie">
                                        <div class="mb-3">
                                            <label for="serie_unassigned" class="form-label">Series Disponibles:</label>
                                            <select class="form-select" id="serie_unassigned" required>
                                                <option value="">Cargando...</option>
                                            </select>
                                            <div class="form-text">Solo se muestran las series que aún no están asignadas a esta oficina.</div>
                                        </div>
                                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Asignar a la Oficina</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de vínculos activos -->
                        <div class="col-lg-7 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0 fs-6"><i class="bi bi-list-check"></i> Series Asignadas a esta Oficina</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="ps-3">Serie</th>
                                                    <th class="text-end pe-3">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbodyVinculos">
                                                <tr><td colspan="2" class="text-center text-muted">Selecciona una oficina</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA: SERIES GLOBALES -->
            <div class="tab-pane fade" id="global" role="tabpanel" aria-labelledby="global-tab">
                <div class="card shadow-sm mb-4">
                    <div class="card-body bg-light rounded">
                        <h5 class="card-title text-primary"><i class="bi bi-bookmark-plus"></i> Crear o Editar Serie Global</h5>
                        <p class="text-muted small">Crea las series documentales aquí. Una vez creadas, podrás asignarlas a las oficinas en la otra pestaña.</p>
                        
                        <form id="formSerieGlobal" class="row align-items-end">
                            <input type="hidden" id="serie_global_id">
                            <div class="col-md-8 mb-3">
                                <label for="nombre_serie" class="form-label fw-bold">Nombre de la Serie:</label>
                                <input type="text" class="form-control" id="nombre_serie" required placeholder="Ej: Contratos, Historias Clínicas...">
                            </div>
                            <div class="col-md-4 mb-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="btnGuardarSerie"><i class="bi bi-save"></i> Guardar Serie</button>
                                <button type="button" class="btn btn-secondary d-none" id="btnCancelarSerie"><i class="bi bi-x-circle"></i> Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-table"></i> Listado Global de Series</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-secondary">
                                    <tr>
                                        <th class="ps-3">ID</th>
                                        <th>Nombre de la Serie</th>
                                        <th class="text-end pe-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyGlobal">
                                    <tr><td colspan="3" class="text-center text-muted">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </main>

    <!-- Modal Confirmar Desvincular -->
    <div class="modal fade" id="modalDesvincular" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Desvincular Serie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Desvincular la serie <strong id="nombreDesvincular"></strong> de la oficina seleccionada?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" id="btnConfirmarDesvincular">Desvincular</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminar Global -->
    <div class="modal fade" id="modalEliminarGlobal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-x-octagon"></i> Eliminar Serie Global</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar la serie <strong id="nombreEliminarGlobal"></strong>?</p>
                    <p class="text-muted small">Esta acción solo será posible si la serie no está vinculada a ninguna oficina ni tiene subseries dependientes.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminarGlobal">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Utilidades y Variables Globales
        const API_URL = 'api_series.php';
        let dependencias = [];
        let seriesGlobales = [];
        let idDesvincular = null;
        let idEliminarGlobal = null;
        
        const modalDesv = new bootstrap.Modal(document.getElementById('modalDesvincular'));
        const modalElim = new bootstrap.Modal(document.getElementById('modalEliminarGlobal'));

        document.addEventListener('DOMContentLoaded', () => {
            cargarDependencias();
            cargarSeriesGlobales();
            
            // Eventos
            document.getElementById('oficina_select').addEventListener('change', function() {
                if(this.value) {
                    mostrarPanelOficina(this.value);
                } else {
                    ocultarPanelOficina();
                }
            });

            document.getElementById('formLinkSerie').addEventListener('submit', function(e) {
                e.preventDefault();
                vincularSerie();
            });

            document.getElementById('formSerieGlobal').addEventListener('submit', function(e) {
                e.preventDefault();
                guardarSerieGlobal();
            });

            document.getElementById('btnCancelarSerie').addEventListener('click', cancelarEdicionGlobal);

            document.getElementById('btnConfirmarDesvincular').addEventListener('click', confirmarDesvincular);
            document.getElementById('btnConfirmarEliminarGlobal').addEventListener('click', confirmarEliminarGlobal);

            // Refrescar paneles al cambiar de pestaña si es necesario
            document.getElementById('global-tab').addEventListener('shown.bs.tab', () => {
                cargarSeriesGlobales();
            });
        });

        function mostrarMensaje(texto, tipo) {
            const area = document.getElementById('messageArea');
            area.innerHTML = `<div class="alert alert-${tipo} alert-dismissible fade show">
                ${texto} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            setTimeout(() => { area.innerHTML = ''; }, 5000);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // ==========================================
        //  Pestaña: Series Globales
        // ==========================================
        function cargarSeriesGlobales() {
            fetch(`${API_URL}?action=list_global`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        seriesGlobales = data.data;
                        renderTablaGlobales();
                    } else {
                        mostrarMensaje("Error listando series globales: " + data.message, 'danger');
                    }
                }).catch(err => mostrarMensaje("Error de red: " + err, 'danger'));
        }

        function renderTablaGlobales() {
            const tbody = document.getElementById('tbodyGlobal');
            if(seriesGlobales.length === 0){
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">No hay series creadas.</td></tr>';
                return;
            }
            tbody.innerHTML = seriesGlobales.map(s => `
                <tr>
                    <td class="ps-3">${s.id}</td>
                    <td class="fw-bold">${escapeHtml(s.nombre)}</td>
                    <td class="text-end pe-3 table-actions">
                        <button class="btn btn-sm btn-outline-primary" onclick="editarSerieGlobal(${s.id}, '${escapeHtml(s.nombre)}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="abrirModalEliminarGlobal(${s.id}, '${escapeHtml(s.nombre)}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function guardarSerieGlobal() {
            const id = document.getElementById('serie_global_id').value;
            const nombre = document.getElementById('nombre_serie').value;
            
            const formData = new FormData();
            formData.append('nombre', nombre);
            
            let action = 'add_serie';
            if(id) {
                action = 'update_serie';
                formData.append('id', id);
            }
            formData.append('action', action);

            fetch(API_URL, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        mostrarMensaje(data.message, 'success');
                        cancelarEdicionGlobal();
                        cargarSeriesGlobales();
                        // Refrescar selects si hubiera oficina elegida
                        const of = document.getElementById('oficina_select').value;
                        if(of) mostrarPanelOficina(of);
                    } else {
                        mostrarMensaje("Error: " + data.message, 'danger');
                    }
                }).catch(err => mostrarMensaje("Error de red: " + err, 'danger'));
        }

        function editarSerieGlobal(id, nombre) {
            document.getElementById('serie_global_id').value = id;
            document.getElementById('nombre_serie').value = nombre;
            document.getElementById('btnGuardarSerie').innerHTML = '<i class="bi bi-save"></i> Actualizar Serie';
            document.getElementById('btnGuardarSerie').className = 'btn btn-warning text-dark';
            document.getElementById('btnCancelarSerie').classList.remove('d-none');
            // Hacer scroll hasta el formulario
            document.getElementById('formSerieGlobal').scrollIntoView({behavior: "smooth", block: "center"});
            document.getElementById('nombre_serie').focus();
        }

        function cancelarEdicionGlobal() {
            document.getElementById('formSerieGlobal').reset();
            document.getElementById('serie_global_id').value = '';
            document.getElementById('btnGuardarSerie').innerHTML = '<i class="bi bi-save"></i> Guardar Serie';
            document.getElementById('btnGuardarSerie').className = 'btn btn-primary';
            document.getElementById('btnCancelarSerie').classList.add('d-none');
        }

        function abrirModalEliminarGlobal(id, nombre) {
            idEliminarGlobal = id;
            document.getElementById('nombreEliminarGlobal').textContent = nombre;
            modalElim.show();
        }

        function confirmarEliminarGlobal() {
            if(!idEliminarGlobal) return;
            const fd = new FormData();
            fd.append('action', 'delete_serie');
            fd.append('id', idEliminarGlobal);

            fetch(API_URL, { method:'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    modalElim.hide();
                    if(data.success){
                        mostrarMensaje(data.message, 'success');
                        cargarSeriesGlobales();
                        // Refrescar panel oficina si está abierto
                        const of = document.getElementById('oficina_select').value;
                        if(of) mostrarPanelOficina(of);
                    } else {
                        mostrarMensaje("Error: " + data.message, 'danger');
                    }
                }).catch(e => {
                    modalElim.hide();
                    mostrarMensaje("Error: " + e, 'danger');
                });
        }


        // ==========================================
        //  Pestaña: Series por Oficina
        // ==========================================
        function cargarDependencias() {
            fetch('api_dependencias.php?action=list')
                .then(res => res.json())
                .then(data => {
                    if(data.success){
                        const select = document.getElementById('oficina_select');
                        data.data.forEach(d => {
                            if(d.estado == 1){
                                const opt = document.createElement('option');
                                opt.value = d.id;
                                opt.textContent = `${d.nombre} ${d.acronimo ? '('+d.acronimo+')' : ''}`;
                                select.appendChild(opt);
                            }
                        });
                    }
                });
        }

        function ocultarPanelOficina() {
            document.getElementById('areaGestionOficina').classList.add('d-none');
        }

        function mostrarPanelOficina(dependenciaId) {
            document.getElementById('areaGestionOficina').classList.remove('d-none');
            cargarVinculadas(dependenciaId);
            cargarDisponibles(dependenciaId);
        }

        function cargarVinculadas(dependenciaId) {
            const tbody = document.getElementById('tbodyVinculos');
            tbody.innerHTML = '<tr><td colspan="2" class="text-center"><div class="spinner-border text-primary spinner-border-sm" role="status"></div></td></tr>';
            
            fetch(`${API_URL}?action=list_by_oficina&dependencia_id=${dependenciaId}`)
                .then(r => r.json())
                .then(data => {
                    if(data.success){
                        if(data.data.length === 0){
                            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-3">Ninguna serie asignada.</td></tr>';
                        } else {
                            tbody.innerHTML = data.data.map(v => `
                                <tr>
                                    <td class="ps-3 align-middle"><i class="bi bi-folder-check text-success me-2"></i> ${escapeHtml(v.nombre)}</td>
                                    <td class="text-end pe-3 align-middle">
                                        <button class="btn btn-sm btn-outline-danger" onclick="abrirModalDesvincular(${v.vinculo_id}, '${escapeHtml(v.nombre)}')">
                                            <i class="bi bi-x-circle"></i> Quitar
                                        </button>
                                    </td>
                                </tr>
                            `).join('');
                        }
                    }
                });
        }

        function cargarDisponibles(dependenciaId) {
            const select = document.getElementById('serie_unassigned');
            select.innerHTML = '<option value="">Cargando...</option>';
            
            fetch(`${API_URL}?action=list_unassigned&dependencia_id=${dependenciaId}`)
                .then(r => r.json())
                .then(data => {
                    if(data.success){
                        if(data.data.length === 0){
                            select.innerHTML = '<option value="">(No hay series disponibles para añadir)</option>';
                        } else {
                            select.innerHTML = '<option value="">Seleccione una serie...</option>' + 
                                data.data.map(s => `<option value="${s.id}">${escapeHtml(s.nombre)}</option>`).join('');
                        }
                    }
                });
        }

        function vincularSerie() {
            const dependencia_id = document.getElementById('oficina_select').value;
            const serie_id = document.getElementById('serie_unassigned').value;

            if(!dependencia_id || !serie_id) return;

            const fd = new FormData();
            fd.append('action', 'link_serie');
            fd.append('dependencia_id', dependencia_id);
            fd.append('serie_id', serie_id);

            fetch(API_URL, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if(data.success){
                        mostrarPanelOficina(dependencia_id);
                    } else {
                        mostrarMensaje("Error: " + data.message, 'danger');
                    }
                }).catch(e => mostrarMensaje("Error:" + e, 'danger'));
        }

        function abrirModalDesvincular(vinculo_id, nombre) {
            idDesvincular = vinculo_id;
            document.getElementById('nombreDesvincular').textContent = nombre;
            modalDesv.show();
        }

        function confirmarDesvincular() {
            if(!idDesvincular) return;
            const fd = new FormData();
            fd.append('action', 'unlink_serie');
            fd.append('vinculo_id', idDesvincular);

            fetch(API_URL, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    modalDesv.hide();
                    if(data.success){
                        const of = document.getElementById('oficina_select').value;
                        if(of) mostrarPanelOficina(of);
                    } else {
                        mostrarMensaje("Error: " + data.message, 'danger');
                    }
                }).catch(e => {
                    modalDesv.hide();
                    mostrarMensaje("Error:" + e, 'danger');
                });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
