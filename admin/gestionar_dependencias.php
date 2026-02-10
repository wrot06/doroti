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
    require_once '../rene/conexion3.php';
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
    <title>Gestionar Dependencias - Doroti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .table-actions {
            white-space: nowrap;
        }
        .badge-parent {
            font-size: 0.7rem;
        }
        .form-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<nav class="navbar fixed-top" style="background-color: #e3f2fd;" data-bs-theme="light">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="admin.php">
            <img src="../img/Doroti Logo Horizontal.png" alt="Logo Doroti" height="30">
            <span class="ms-2 fw-bold text-primary">ADMIN - Dependencias</span>
        </a>
        
        <div class="d-flex align-items-center gap-3 ms-auto">
            <div class="d-flex align-items-center gap-2">
                <img src="<?= htmlspecialchars($userAvatar) ?>" 
                     class="rounded-circle" 
                     width="35" 
                     height="35" 
                     style="object-fit: cover; border: 2px solid #0d6efd;"
                     alt="Avatar de <?= htmlspecialchars($usuario) ?>">
                <div class="d-flex flex-column">
                    <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($usuario) ?></span>
                    <span class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($_SESSION['dependencia'] ?? 'Admin') ?></span>
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
    <h1 class="mb-4"><i class="bi bi-building text-success"></i> Gestionar Dependencias</h1>
    
    <!-- Área de mensajes -->
    <div id="messageArea"></div>

    <!-- Formulario para agregar dependencia -->
    <div class="form-section">
        <h3 class="mb-3"><i class="bi bi-plus-circle"></i> Agregar Nueva Dependencia</h3>
        <form id="formAgregarDependencia">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="">Seleccionar...</option>
                        <option value="universidad">Universidad</option>
                        <option value="sede">Sede</option>
                        <option value="oficina">Oficina</option>
                        <option value="departamento">Departamento</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="acronimo" class="form-label">Acrónimo</label>
                    <input type="text" class="form-control" id="acronimo" name="acronimo" maxlength="50">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="parent_id" class="form-label">Dependencia Padre (opcional)</label>
                    <select class="form-select" id="parent_id" name="parent_id">
                        <option value="">Sin padre (raíz)</option>
                        <!-- Se llenará dinámicamente -->
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label d-block">Estado</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="estado" name="estado" checked>
                        <label class="form-check-label" for="estado">Activo</label>
                    </div>
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-lg me-1"></i>Agregar Dependencia
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabla de dependencias -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-list-ul"></i> Lista de Dependencias</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tablaDependencias">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Acrónimo</th>
                            <th>Padre</th>
                            <th>Estado</th>
                            <th class="table-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDependencias">
                        <tr>
                            <td colspan="7" class="text-center">
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
                <p>¿Está seguro de que desea eliminar la dependencia <strong id="nombreEliminar"></strong>?</p>
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle"></i> Esta acción no se puede deshacer. 
                    Solo se puede eliminar si no tiene carpetas ni dependencias hijas asociadas.
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
let dependenciasData = [];
let idEliminar = null;
const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));

// Cargar dependencias al iniciar
document.addEventListener('DOMContentLoaded', function() {
    cargarDependencias();
});

// Cargar lista de dependencias
function cargarDependencias() {
    fetch('api_dependencias.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                dependenciasData = data.data;
                renderizarTabla(data.data);
                llenarSelectParent(data.data);
            } else {
                mostrarMensaje('Error al cargar dependencias: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            mostrarMensaje('Error de conexión: ' + error.message, 'danger');
        });
}

// Renderizar tabla
function renderizarTabla(dependencias) {
    const tbody = document.getElementById('tbodyDependencias');
    
    if (dependencias.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No hay dependencias registradas</td></tr>';
        return;
    }

    // Crear mapa de dependencias para buscar nombres de padres
    const depMap = {};
    dependencias.forEach(dep => {
        depMap[dep.id] = dep.nombre;
    });

    tbody.innerHTML = dependencias.map(dep => {
        const parentName = dep.parent_id ? (depMap[dep.parent_id] || 'N/A') : '-';
        const estadoBadge = dep.estado == 1 
            ? '<span class="badge bg-success">Activo</span>' 
            : '<span class="badge bg-secondary">Inactivo</span>';
        const tipoBadge = getTipoBadge(dep.tipo);
        
        return `
            <tr>
                <td>${dep.id}</td>
                <td><strong>${escapeHtml(dep.nombre)}</strong></td>
                <td>${tipoBadge}</td>
                <td>${dep.acronimo || '-'}</td>
                <td>
                    ${dep.parent_id ? `<span class="badge badge-parent bg-info">${escapeHtml(parentName)}</span>` : '-'}
                </td>
                <td>${estadoBadge}</td>
                <td class="table-actions">
                    <button class="btn btn-danger btn-sm" onclick="mostrarModalEliminar(${dep.id}, '${escapeHtml(dep.nombre)}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// Llenar select de parent_id
function llenarSelectParent(dependencias) {
    const select = document.getElementById('parent_id');
    const optionsHtml = dependencias.map(dep => 
        `<option value="${dep.id}">${escapeHtml(dep.nombre)} (${dep.tipo})</option>`
    ).join('');
    
    select.innerHTML = '<option value="">Sin padre (raíz)</option>' + optionsHtml;
}

// Agregar dependencia
document.getElementById('formAgregarDependencia').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add');
    
    fetch('api_dependencias.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMensaje(data.message, 'success');
            this.reset();
            cargarDependencias();
        } else {
            mostrarMensaje('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        mostrarMensaje('Error de conexión: ' + error.message, 'danger');
    });
});

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
    
    fetch('api_dependencias.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        modalEliminar.hide();
        if (data.success) {
            mostrarMensaje(data.message, 'success');
            cargarDependencias();
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

// Obtener badge de tipo
function getTipoBadge(tipo) {
    const badges = {
        'universidad': '<span class="badge bg-primary">Universidad</span>',
        'sede': '<span class="badge bg-info">Sede</span>',
        'oficina': '<span class="badge bg-warning text-dark">Oficina</span>',
        'departamento': '<span class="badge bg-secondary">Departamento</span>',
        'otro': '<span class="badge bg-dark">Otro</span>'
    };
    return badges[tipo] || `<span class="badge bg-light text-dark">${tipo}</span>`;
}

// Escapar HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

</body>
</html>
