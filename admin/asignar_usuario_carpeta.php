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

function h(mixed $str): string
{
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
    <title>Asignar Usuario a Carpeta - Doroti Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .purple-badge {
            background-color: #6f42c1;
            color: white;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar fixed-top" style="background-color: #e3f2fd;" data-bs-theme="light">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../img/Doroti Logo Horizontal.png" alt="Logo Doroti" height="30">
                <span class="ms-2 fw-bold text-primary">ADMIN</span>
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
            </div>
        </div>
    </nav>

    <main class="container mt-5 pt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-person-workspace me-2" style="color: #6f42c1;"></i>Asignar Usuario a Carpeta</h1>
        </div>

        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Selecciona una dependencia para ver las carpetas y cambiar el usuario responsable.
        </div>

        <!-- Filtro por Dependencia -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label for="filterDependencia" class="form-label fw-bold">Dependencia/Oficina</label>
                        <select class="form-select" id="filterDependencia">
                            <option value="">Seleccione una dependencia...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <button id="btnBuscar" class="btn btn-primary" disabled>
                            <i class="bi bi-search me-2"></i>Buscar Carpetas
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Carpetas -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tablaCarpetas">
                        <thead>
                            <tr>
                                <th>Caja</th>
                                <th>Carpeta</th>
                                <th>Estado</th>
                                <th>Usuario Actual</th>
                                <th>Dependencia</th>
                                <th style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Seleccione una dependencia para ver las carpetas
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Asignar Usuario -->
    <div class="modal fade" id="modalAsignar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #6f42c1; color: white;">
                    <h5 class="modal-title">
                        <i class="bi bi-person-workspace me-2"></i>Asignar Usuario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="assignFolderId">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Carpeta</label>
                        <p class="form-control-plaintext" id="assignFolderInfo">-</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Usuario Actual</label>
                        <p class="form-control-plaintext text-muted" id="assignCurrentUser">-</p>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label for="assignNewUser" class="form-label fw-bold">Nuevo Usuario *</label>
                        <select class="form-select" id="assignNewUser" required>
                            <option value="">Cargando usuarios...</option>
                        </select>
                        <div class="form-text">Seleccione el nuevo usuario responsable de esta carpeta</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn text-white" style="background-color: #6f42c1;" id="btnGuardarAsignar">
                        <i class="bi bi-check-circle me-1"></i>Asignar Usuario
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterDependencia = document.getElementById('filterDependencia');
            const btnBuscar = document.getElementById('btnBuscar');
            const tablaCarpetas = document.querySelector('#tablaCarpetas tbody');
            const modalAsignar = new bootstrap.Modal(document.getElementById('modalAsignar'));

            let currentDependenciaId = null;
            let oficinas = [];

            // Cargar oficinas
            fetch('api_carpetas.php?action=list_offices')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        oficinas = data.data;
                        poblarSelectDependencias();
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Error al cargar dependencias', 'error');
                });

            function poblarSelectDependencias() {
                filterDependencia.innerHTML = '<option value="">Seleccione una dependencia...</option>';
                oficinas.forEach(oficina => {
                    const opt = document.createElement('option');
                    opt.value = oficina.id;
                    opt.textContent = oficina.nombre;
                    filterDependencia.appendChild(opt);
                });
            }

            // Habilitar botón buscar cuando se selecciona dependencia
            filterDependencia.addEventListener('change', () => {
                btnBuscar.disabled = !filterDependencia.value;
            });

            // Buscar carpetas
            btnBuscar.addEventListener('click', () => {
                const depId = filterDependencia.value;
                if (!depId) return;

                currentDependenciaId = depId;
                cargarCarpetas(depId);
            });

            function cargarCarpetas(depId) {
                tablaCarpetas.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';

                fetch(`api_asignar_usuario.php?action=list_folders_with_users&dependencia_id=${depId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            mostrarCarpetas(data.data);
                        } else {
                            Swal.fire('Error', data.message, 'error');
                            tablaCarpetas.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar carpetas</td></tr>';
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'Error al cargar carpetas', 'error');
                        tablaCarpetas.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar carpetas</td></tr>';
                    });
            }

            function mostrarCarpetas(carpetas) {
                tablaCarpetas.innerHTML = '';

                if (carpetas.length === 0) {
                    tablaCarpetas.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay carpetas en esta dependencia</td></tr>';
                    return;
                }

                carpetas.forEach(carpeta => {
                    const tr = document.createElement('tr');

                    const estadoBadge = carpeta.estado === 'A' ?
                        '<span class="badge bg-success status-badge">Activo</span>' :
                        '<span class="badge bg-secondary status-badge">Cerrado</span>';

                    const usuarioBadge = carpeta.usuario_actual === 'Sin asignar' ?
                        '<span class="badge bg-warning text-dark status-badge">Sin asignar</span>' :
                        `<span class="badge purple-badge status-badge">${carpeta.usuario_actual}</span>`;

                    tr.innerHTML = `
                <td><strong>${carpeta.caja}</strong></td>
                <td><strong>${carpeta.carpeta}</strong></td>
                <td>${estadoBadge}</td>
                <td>${usuarioBadge}</td>
                <td>${carpeta.dependencia}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary btn-asignar" 
                            data-id="${carpeta.id}"
                            data-caja="${carpeta.caja}"
                            data-carpeta="${carpeta.carpeta}"
                            data-usuario="${carpeta.usuario_actual}"
                            data-userid="${carpeta.user_id || ''}">
                        <i class="bi bi-person-check"></i> Asignar
                    </button>
                </td>
            `;

                    tablaCarpetas.appendChild(tr);
                });

                // Agregar eventos
                document.querySelectorAll('.btn-asignar').forEach(btn => {
                    btn.addEventListener('click', abrirModalAsignar);
                });
            }

            function abrirModalAsignar(e) {
                const btn = e.target.closest('button');
                const folderId = btn.dataset.id;
                const caja = btn.dataset.caja;
                const carpeta = btn.dataset.carpeta;
                const usuarioActual = btn.dataset.usuario;

                document.getElementById('assignFolderId').value = folderId;
                document.getElementById('assignFolderInfo').textContent = `Caja ${caja} - Carpeta ${carpeta}`;
                document.getElementById('assignCurrentUser').textContent = usuarioActual;

                // Cargar usuarios de la misma dependencia
                cargarUsuarios(currentDependenciaId);

                modalAsignar.show();
            }

            function cargarUsuarios(depId) {
                const selectUser = document.getElementById('assignNewUser');
                selectUser.innerHTML = '<option value="">Cargando...</option>';

                // Cargar TODOS los usuarios del sistema (sin filtrar por dependencia)
                fetch(`api_asignar_usuario.php?action=list_users_by_office`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            selectUser.innerHTML = '<option value="">-- Seleccione un usuario --</option>';
                            selectUser.innerHTML += '<option value="0">** Desasignar usuario **</option>';

                            data.data.forEach(user => {
                                const opt = document.createElement('option');
                                opt.value = user.id;
                                // Mostrar username, email y dependencia para mejor identificación
                                const userInfo = user.dependencia ?
                                    `${user.username} - ${user.dependencia} (${user.email})` :
                                    `${user.username} (${user.email})`;
                                opt.textContent = userInfo;
                                selectUser.appendChild(opt);
                            });
                        } else {
                            selectUser.innerHTML = '<option value="">Error al cargar usuarios</option>';
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        selectUser.innerHTML = '<option value="">Error al cargar usuarios</option>';
                    });
            }

            // Guardar asignación
            document.getElementById('btnGuardarAsignar').addEventListener('click', async () => {
                const folderId = document.getElementById('assignFolderId').value;
                const newUserId = document.getElementById('assignNewUser').value;

                if (!newUserId) {
                    Swal.fire('Error', 'Debe seleccionar un usuario', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('folder_id', folderId);
                formData.append('user_id', newUserId);

                try {
                    const res = await fetch('api_asignar_usuario.php?action=assign_user_to_folder', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.success) {
                        modalAsignar.hide();

                        // Construir mensaje detallado con información de integridad
                        let mensajeDetallado = data.message;

                        if (data.detalles) {
                            const {
                                estado,
                                registros_relacionados,
                                tabla_relacionada
                            } = data.detalles;

                            let htmlExtra = '<div class="mt-3 text-start">';
                            htmlExtra += '<p class="mb-2"><strong>Detalles de la carpeta:</strong></p>';
                            htmlExtra += `<ul class="mb-0">`;
                            htmlExtra += `<li>Estado: <strong>${estado}</strong></li>`;

                            if (tabla_relacionada) {
                                htmlExtra += `<li>Tabla de datos: <strong>${tabla_relacionada}</strong></li>`;
                                htmlExtra += `<li>Registros asociados: <strong>${registros_relacionados}</strong></li>`;
                            } else {
                                htmlExtra += `<li>Sin datos asociados actualmente</li>`;
                            }

                            htmlExtra += '</ul></div>';

                            Swal.fire({
                                icon: 'success',
                                title: 'Usuario Asignado',
                                html: mensajeDetallado + htmlExtra,
                                confirmButtonText: 'Aceptar',
                                confirmButtonColor: '#6f42c1'
                            });
                        } else {
                            Swal.fire('Éxito', data.message, 'success');
                        }

                        cargarCarpetas(currentDependenciaId);
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (err) {
                    console.error(err);
                    Swal.fire('Error', 'Error al asignar usuario', 'error');
                }
            });
        });
    </script>
</body>

</html>