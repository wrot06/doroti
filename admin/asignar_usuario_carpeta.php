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
require_once "../services/UserService.php";

$usuario = $_SESSION['username'] ?? 'Admin';

// Obtener avatar e info del usuario
$user_id = (int)($_SESSION['user_id'] ?? 0);
$userService = new UserService($conec);
$userInfo = $userService->getUserInfo($user_id);
$usuario = $userInfo['username'];
$oficina = $userInfo['oficina'];
$userAvatar = '../' . $userService->getUserAvatar($user_id);
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
        body {
            background-color: #f8f9fa;
        }
        .form-section {
            background-color: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .folder-card {
            transition: all 0.2s ease-in-out;
            border-radius: 12px;
        }
        .folder-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
        }
    </style>
</head>
<body>

<?php
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}
$basePath = '../';
$activePage = 'admin';
require_once "../components/navbar.php";
?>

<main class="container mt-5 pt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-person-workspace text-primary me-2"></i>Asignar Usuario a Carpeta</h1>
    </div>

    <!-- Filtro por Oficina -->
    <div class="form-section">
        <h5 class="mb-3"><i class="bi bi-funnel"></i> Filtrar por Oficina</h5>
        <div class="row align-items-end">
            <div class="col-md-9 mb-3 mb-md-0">
                <label for="selectOficina" class="form-label">Seleccione una Oficina / Dependencia</label>
                <select id="selectOficina" class="form-select">
                    <option value="">Seleccione una oficina...</option>
                    <!-- Opciones cargadas por AJAX -->
                </select>
            </div>
            <div class="col-md-3">
                <button id="btnBuscar" class="btn btn-primary w-100" disabled>
                    <i class="bi bi-search me-2"></i>Buscar Carpetas
                </button>
            </div>
        </div>
    </div>

    <!-- Resultados -->
    <div id="resultados" class="row g-4">
        <div class="col-12 text-center text-muted my-5">
            <i class="bi bi-folder2 fs-1 d-block mb-2"></i>
            <span>Seleccione una oficina para listar las carpetas.</span>
        </div>
    </div>
</main>

<!-- Modal de Asignación -->
<div class="modal fade" id="modalAsignar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-check me-2"></i>Asignar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalFolderId">
                <div class="alert alert-info py-2" id="modalFolderDesc"></div>
                <div class="mb-3">
                    <label for="selectUsuario" class="form-label">Seleccione el Usuario Destinatario</label>
                    <select id="selectUsuario" class="form-select">
                        <!-- Llenado dinámico -->
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarAsignacion">Guardar Asignación</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectOficina = document.getElementById('selectOficina');
    const btnBuscar = document.getElementById('btnBuscar');
    const contenedorResultados = document.getElementById('resultados');
    const modalAsignar = new bootstrap.Modal(document.getElementById('modalAsignar'));
    const selectUsuario = document.getElementById('selectUsuario');
    
    let usuariosOficina = [];

    // 1. Cargar oficinas
    fetch('api_carpetas.php?action=list_offices')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                data.data.forEach(oficina => {
                    const opt = document.createElement('option');
                    opt.value = oficina.id;
                    opt.textContent = oficina.nombre;
                    selectOficina.appendChild(opt);
                });
            } else {
                Swal.fire('Error', 'No se pudieron cargar las oficinas', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Error de conexión al cargar oficinas', 'error');
        });

    // Habilitar botón de buscar
    selectOficina.addEventListener('change', () => {
        btnBuscar.disabled = !selectOficina.value;
    });

    // 2. Buscar carpetas y usuarios de la oficina seleccionada
    btnBuscar.addEventListener('click', () => {
        const idOficina = selectOficina.value;
        if (!idOficina) return;

        contenedorResultados.innerHTML = '<div class="col-12 text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>';

        // Cargar usuarios de la oficina para tenerlos listos en el modal
        fetch(`api_asignar_usuario.php?action=list_users_by_office&dependencia_id=${idOficina}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    usuariosOficina = data.data;
                } else {
                    usuariosOficina = [];
                }
            })
            .catch(err => console.error('Error cargando usuarios:', err));

        // Cargar carpetas
        fetch(`api_asignar_usuario.php?action=list_folders_with_users&dependencia_id=${idOficina}`)
            .then(res => res.json())
            .then(data => {
                contenedorResultados.innerHTML = '';
                if (!data.success) {
                    contenedorResultados.innerHTML = `<div class="col-12"><div class="alert alert-danger">${data.message}</div></div>`;
                    return;
                }

                if (data.data.length === 0) {
                    contenedorResultados.innerHTML = '<div class="col-12"><div class="alert alert-info">No hay carpetas creadas en esta oficina.</div></div>';
                    return;
                }

                data.data.forEach(carpeta => {
                    const col = document.createElement('div');
                    col.className = 'col-md-6 col-lg-4';

                    const card = document.createElement('div');
                    card.className = 'card h-100 shadow-sm border-0 folder-card';

                    const badgeClase = carpeta.estado === 'A' ? 'bg-success' : 'bg-secondary';
                    const badgeTexto = carpeta.estado === 'A' ? 'Activa' : 'Cerrada';

                    card.innerHTML = `
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge ${badgeClase}">${badgeTexto}</span>
                                <i class="bi bi-folder-fill text-warning fs-3"></i>
                            </div>
                            <h5 class="card-title fw-bold">Caja ${carpeta.caja} / Carpeta ${carpeta.carpeta}</h5>
                            <p class="text-muted small mb-3">Creado: ${carpeta.fecha_ingreso}</p>
                            
                            <div class="mt-auto pt-3 border-top">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-person text-primary me-2 fs-5"></i>
                                    <div>
                                        <div class="text-muted small">Usuario Asignado:</div>
                                        <div class="fw-semibold text-dark">${escapeHtml(carpeta.usuario_actual)}</div>
                                    </div>
                                </div>
                                <button class="btn btn-outline-primary btn-sm w-100 btn-cambiar" 
                                    data-id="${carpeta.id}" 
                                    data-caja="${carpeta.caja}" 
                                    data-carpeta="${carpeta.carpeta}"
                                    data-userid="${carpeta.user_id || ''}">
                                    <i class="bi bi-arrow-left-right me-1"></i>Asignar / Cambiar
                                </button>
                            </div>
                        </div>
                    `;
                    col.appendChild(card);
                    contenedorResultados.appendChild(col);
                });

                // Registrar eventos de botones cambiar
                document.querySelectorAll('.btn-cambiar').forEach(btn => {
                    btn.addEventListener('click', abrirModalAsignar);
                });
            })
            .catch(err => {
                console.error(err);
                contenedorResultados.innerHTML = '<div class="col-12"><div class="alert alert-danger">Error de conexión al cargar carpetas.</div></div>';
            });
    });

    // 3. Abrir Modal de Asignación
    function abrirModalAsignar(e) {
        const btn = e.target.closest('button');
        const id = btn.dataset.id;
        const caja = btn.dataset.caja;
        const carpeta = btn.dataset.carpeta;
        const userIdActual = btn.dataset.userid;

        document.getElementById('modalFolderId').value = id;
        document.getElementById('modalFolderDesc').innerHTML = `<strong>Carpeta seleccionada:</strong> Caja ${caja} / Carpeta ${carpeta}`;

        // Llenar select de usuarios
        selectUsuario.innerHTML = '';

        // Opción desasignar / sin usuario
        const optDefault = document.createElement('option');
        optDefault.value = '';
        optDefault.textContent = '-- Sin asignar (Ninguno) --';
        selectUsuario.appendChild(optDefault);

        usuariosOficina.forEach(user => {
            const opt = document.createElement('option');
            opt.value = user.id;
            opt.textContent = `${user.username} (${user.rol === 'admin' ? 'Admin' : 'Operario'})`;
            if (user.id == userIdActual) {
                opt.selected = true;
            }
            selectUsuario.appendChild(opt);
        });

        modalAsignar.show();
    }

    // 4. Guardar Asignación
    document.getElementById('btnGuardarAsignacion').addEventListener('click', () => {
        const folderId = document.getElementById('modalFolderId').value;
        const userId = selectUsuario.value;

        const formData = new FormData();
        formData.append('folder_id', folderId);
        formData.append('user_id', userId);

        fetch('api_asignar_usuario.php?action=assign_user_to_folder', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                modalAsignar.hide();
                Swal.fire('Guardado', data.message, 'success')
                    .then(() => {
                        // Recargar lista de carpetas
                        btnBuscar.click();
                    });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Error de conexión al guardar asignación', 'error');
        });
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>