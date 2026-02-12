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
function h(mixed $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

$usuario = $_SESSION['username'] ?? 'Admin';

// Obtener avatar del usuario actual
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
    <title>Gestionar Tipos Documentales - Doroti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .table-actions {
            white-space: nowrap;
        }
        .form-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .descripcion-truncada {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>

<nav class="navbar fixed-top" style="background-color: #e3f2fd;" data-bs-theme="light">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="admin.php">
            <img src="../img/Doroti Logo Horizontal.png" alt="Logo Doroti" height="30">
            <span class="ms-2 fw-bold text-primary">ADMIN - Tipos Documentales</span>
        </a>
        
        <div class="d-flex align-items-center gap-3 ms-auto">
            <div class="d-flex align-items-center gap-2">
                <img src="<?= h($userAvatar) ?>" 
                     class="rounded-circle" 
                     width="35" 
                     height="35" 
                     style="object-fit: cover; border: 2px solid #0d6efd;"
                     alt="Avatar de <?= h($usuario) ?>">
                <div class="d-flex flex-column">
                    <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= h($usuario) ?></span>
                    <span class="text-muted" style="font-size: 0.75rem;"><?= h($_SESSION['dependencia'] ?? 'Admin') ?></span>
                </div>
            </div>
            <a href="admin.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Volver al Panel
            </a>
            <form method="POST" action="../index.php" class="d-inline">
                <button type="submit" name="cerrar_seccion" class="btn btn-danger btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>Salir
                </button>
            </form>
        </div>
    </div>
</nav>

<main class="container mt-5 pt-5">
    <h1 class="mb-4"><i class="bi bi-file-earmark-text text-primary"></i> Gestionar Tipos Documentales</h1>
    
    <!-- Área de mensajes -->
    <div id="messageArea"></div>

    <!-- Formulario para agregar/editar tipo documental -->
    <div class="form-section">
        <h3 class="mb-3" id="formTitle"><i class="bi bi-plus-circle"></i> Agregar Nuevo Tipo Documental</h3>
        <form id="formTipoDocumental">
            <input type="hidden" id="tipoId" name="id" value="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="dependencia_id" class="form-label">Dependencia <span class="text-danger">*</span></label>
                    <select class="form-select" id="dependencia_id" name="dependencia_id" required>
                        <option value="">Seleccionar...</option>
                        <!-- Se llenará dinámicamente -->
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-9 mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label d-block">Estado</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="estado" name="estado" checked>
                        <label class="form-check-label" for="estado">Activo</label>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="btnSubmit">
                    <i class="bi bi-plus-lg me-1"></i><span id="btnSubmitText">Agregar Tipo Documental</span>
                </button>
                <button type="button" class="btn btn-secondary d-none" id="btnCancelar">
                    <i class="bi bi-x-lg me-1"></i>Cancelar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de tipos documentales -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-list-ul"></i> Lista de Tipos Documentales</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tablaTipos">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Dependencia</th>
                            <th>Estado</th>
                            <th class="table-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyTipos">
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar el tipo documental <strong id="nombreEliminar"></strong>?</p>
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle"></i> Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let tiposData = [];
let dependenciasData = [];
let idEliminar = null;
let modoEdicion = false;
const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));

// Cargar datos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    cargarDependencias();
    cargarTiposDocumentales();
});

// Cargar dependencias para el select
function cargarDependencias() {
    fetch('api_dependencias.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                dependenciasData = data.data;
                llenarSelectDependencias(data.data);
            } else {
                mostrarMensaje('Error al cargar dependencias: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            mostrarMensaje('Error de conexión al cargar dependencias: ' + error.message, 'danger');
        });
}

// Llenar select de dependencias
function llenarSelectDependencias(dependencias) {
    const select = document.getElementById('dependencia_id');
    const optionsHtml = dependencias
        .filter(dep => dep.estado == 1) // Solo dependencias activas
        .map(dep => 
            `<option value="${dep.id}">${escapeHtml(dep.nombre)} ${dep.acronimo ? '(' + escapeHtml(dep.acronimo) + ')' : ''}</option>`
        ).join('');
    
    select.innerHTML = '<option value="">Seleccionar...</option>' + optionsHtml;
}

// Cargar lista de tipos documentales
function cargarTiposDocumentales() {
    fetch('api_tipos_documentales.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tiposData = data.data;
                renderizarTabla(data.data);
            } else {
                mostrarMensaje('Error al cargar tipos documentales: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            mostrarMensaje('Error de conexión: ' + error.message, 'danger');
        });
}

// Renderizar tabla
function renderizarTabla(tipos) {
    const tbody = document.getElementById('tbodyTipos');
    
    if (tipos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay tipos documentales registrados</td></tr>';
        return;
    }

    tbody.innerHTML = tipos.map(tipo => {
        const estadoBadge = tipo.estado == 1 
            ? '<span class="badge bg-success">Activo</span>' 
            : '<span class="badge bg-secondary">Inactivo</span>';
        const descripcionTexto = tipo.descripcion 
            ? `<span class="descripcion-truncada" title="${escapeHtml(tipo.descripcion)}">${escapeHtml(tipo.descripcion)}</span>`
            : '<span class="text-muted">-</span>';
        
        return `
            <tr>
                <td>${tipo.id}</td>
                <td><strong>${escapeHtml(tipo.nombre)}</strong></td>
                <td>${descripcionTexto}</td>
                <td><span class="badge bg-info">${escapeHtml(tipo.dependencia_nombre || 'N/A')}</span></td>
                <td>${estadoBadge}</td>
                <td class="table-actions">
                    <button class="btn btn-sm btn-warning" onclick="editarTipo(${tipo.id})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="mostrarModalEliminar(${tipo.id}, '${escapeHtml(tipo.nombre)}')" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// Agregar o actualizar tipo documental
document.getElementById('formTipoDocumental').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const action = modoEdicion ? 'update' : 'add';
    formData.append('action', action);
    
    fetch('api_tipos_documentales.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMensaje(data.message, 'success');
            resetearFormulario();
            cargarTiposDocumentales();
        } else {
            mostrarMensaje('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        mostrarMensaje('Error de conexión: ' + error.message, 'danger');
    });
});

// Editar tipo documental
function editarTipo(id) {
    const tipo = tiposData.find(t => t.id == id);
    if (!tipo) return;
    
    // Llenar formulario
    document.getElementById('tipoId').value = tipo.id;
    document.getElementById('nombre').value = tipo.nombre;
    document.getElementById('descripcion').value = tipo.descripcion || '';
    document.getElementById('dependencia_id').value = tipo.dependencia_id;
    document.getElementById('estado').checked = tipo.estado == 1;
    
    // Cambiar a modo edición
    modoEdicion = true;
    document.getElementById('formTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Editar Tipo Documental';
    document.getElementById('btnSubmitText').textContent = 'Actualizar Tipo Documental';
    document.getElementById('btnSubmit').className = 'btn btn-warning';
    document.getElementById('btnCancelar').classList.remove('d-none');
    
    // Scroll al formulario
    document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Cancelar edición
document.getElementById('btnCancelar').addEventListener('click', function() {
    resetearFormulario();
});

// Resetear formulario
function resetearFormulario() {
    document.getElementById('formTipoDocumental').reset();
    document.getElementById('tipoId').value = '';
    modoEdicion = false;
    document.getElementById('formTitle').innerHTML = '<i class="bi bi-plus-circle"></i> Agregar Nuevo Tipo Documental';
    document.getElementById('btnSubmitText').textContent = 'Agregar Tipo Documental';
    document.getElementById('btnSubmit').className = 'btn btn-primary';
    document.getElementById('btnCancelar').classList.add('d-none');
}

// Mostrar modal de eliminar
function mostrarModalEliminar(id, nombre) {
    idEliminar = id;
    document.getElementById('nombreEliminar').textContent = nombre;
    modalEliminar.show();
}

// Confirmar eliminación
document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
    if (!idEliminar) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', idEliminar);
    
    fetch('api_tipos_documentales.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        modalEliminar.hide();
        if (data.success) {
            mostrarMensaje(data.message, 'success');
            cargarTiposDocumentales();
        } else {
            mostrarMensaje('Error: ' + data.message, 'danger');
        }
        idEliminar = null;
    })
    .catch(error => {
        modalEliminar.hide();
        mostrarMensaje('Error de conexión: ' + error.message, 'danger');
        idEliminar = null;
    });
});

// Mostrar mensaje
function mostrarMensaje(texto, tipo) {
    const messageArea = document.getElementById('messageArea');
    const alert = document.createElement('div');
    alert.className = `alert alert-${tipo} alert-dismissible fade show`;
    alert.innerHTML = `
        ${texto}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    messageArea.innerHTML = '';
    messageArea.appendChild(alert);
    
    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        alert.remove();
    }, 5000);
    
    // Scroll al mensaje
    messageArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Escapar HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

</body>
</html>
