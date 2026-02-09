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

$usuario = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Carpetas - Doroti Admin</title>
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
            <span class="text-muted">Hola, <?= htmlspecialchars($usuario) ?></span>
            <a href="admin.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Volver al Panel
            </a>
        </div>
    </div>
</nav>

<main class="container mt-5 pt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Modificar Carpetas</h1>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Filtrar por Oficina</h5>
            <select id="selectOficina" class="form-select mb-3">
                <option value="">Seleccione una oficina...</option>
                <!-- Opciones cargadas via AJAX -->
            </select>
            <button id="btnBuscar" class="btn btn-primary" disabled>
                <i class="bi bi-search me-2"></i>Listar Carpetas
            </button>
        </div>
    </div>

    <div id="resultados" class="row g-4">
        <!-- Carpetas cargadas aquí -->
    </div>
</main>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Números</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <div class="mb-3">
                    <label for="editCaja" class="form-label">Número de Caja</label>
                    <input type="number" class="form-control" id="editCaja" required min="1">
                </div>
                <div class="mb-3">
                    <label for="editCarpeta" class="form-label">Número de Carpeta</label>
                    <input type="number" class="form-control" id="editCarpeta" required min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardar">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectOficina = document.getElementById('selectOficina');
    const btnBuscar = document.getElementById('btnBuscar');
    const contenedorResultados = document.getElementById('resultados');
    const modalEditar = new bootstrap.Modal(document.getElementById('modalEditar'));
    const btnGuardar = document.getElementById('btnGuardar');

    // 1. Cargar oficinas
    fetch('api_carpetas.php?action=list_offices')
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                data.data.forEach(oficina => {
                    const opt = document.createElement('option');
                    opt.value = oficina.id;
                    opt.textContent = oficina.nombre;
                    selectOficina.appendChild(opt);
                });
            } else {
                alert("Error cargando oficinas: " + data.message);
            }
        });

    // Habilitar botón buscar al seleccionar
    selectOficina.addEventListener('change', () => {
        btnBuscar.disabled = !selectOficina.value;
    });

    // 2. Buscar carpetas
    btnBuscar.addEventListener('click', listarCarpetas);

    function listarCarpetas() {
        const idOficina = selectOficina.value;
        if(!idOficina) return;

        contenedorResultados.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>';

        fetch(`api_carpetas.php?action=list_folders&dependencia_id=${idOficina}`)
            .then(res => res.json())
            .then(data => {
                contenedorResultados.innerHTML = '';
                
                if(!data.success) {
                    contenedorResultados.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    return;
                }

                if(data.data.length === 0) {
                    contenedorResultados.innerHTML = `<div class="alert alert-info">No se encontraron carpetas para esta oficina.</div>`;
                    return;
                }

                data.data.forEach(carpeta => {
                    const col = document.createElement('div');
                    col.className = 'col-md-4 col-lg-3';
                    
                    const card = document.createElement('div');
                    card.className = 'card h-100 border-info';
                    
                    const estadoTexto = carpeta.Estado === 'A' ? 'Activo' : (carpeta.Estado === 'C' ? 'Cerrado' : carpeta.Estado);
                    
                    card.innerHTML = `
                        <div class="card-body text-center">
                            <i class="bi bi-pencil-square fs-1 text-info mb-2"></i>
                            <h5 class="card-title">Caja ${carpeta.Caja} / Carpeta ${carpeta.Carpeta}</h5>
                            <p class="card-text small text-muted mb-3">${estadoTexto}</p>
                            
                            <button class="btn btn-info btn-sm w-100 btn-editar text-white" 
                                data-id="${carpeta.id}" 
                                data-caja="${carpeta.Caja}" 
                                data-carpeta="${carpeta.Carpeta}">
                                <i class="bi bi-pencil me-2"></i>Modificar
                            </button>
                        </div>
                    `;
                    col.appendChild(card);
                    contenedorResultados.appendChild(col);
                });

                // Attach events
                document.querySelectorAll('.btn-editar').forEach(btn => {
                    btn.addEventListener('click', abrirModalEditar);
                });
            });
    }

    function abrirModalEditar(e) {
        const btn = e.target.closest('button');
        document.getElementById('editId').value = btn.dataset.id;
        document.getElementById('editCaja').value = btn.dataset.caja;
        document.getElementById('editCarpeta').value = btn.dataset.carpeta;
        modalEditar.show();
    }

    // 3. Guardar Cambios
    btnGuardar.addEventListener('click', () => {
        const id = document.getElementById('editId').value;
        const caja = document.getElementById('editCaja').value;
        const carpeta = document.getElementById('editCarpeta').value;

        if(!caja || !carpeta) {
            Swal.fire('Error', 'Todos los campos son obligatorios', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('id', id);
        formData.append('caja', caja);
        formData.append('carpeta', carpeta);

        fetch('api_carpetas.php?action=update_folder', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                modalEditar.hide();
                Swal.fire('Actualizado', 'Carpeta modificada correctamente.', 'success')
                    .then(() => listarCarpetas());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Error de conexión', 'error');
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
