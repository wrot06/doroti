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

// Obtener avatar del usuario actual
$userAvatar = '../uploads/avatars/default.png'; // Default
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
    <title>Gestionar Usuarios - Doroti Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .avatar-preview {
            object-fit: cover;
            border: 2px solid #dee2e6;
        }
        .avatar-table {
            object-fit: cover;
            border: 2px solid #e9ecef;
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
        </div>
    </div>
</nav>

<main class="container mt-5 pt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-people me-2"></i>Gestionar Usuarios</h1>
        <button id="btnNuevoUsuario" class="btn btn-primary">
            <i class="bi bi-person-plus me-2"></i>Nuevo Usuario
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tablaUsuarios">
                    <thead>
                        <tr>
                            <th style="width: 60px;"></th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Rol</th>
                            <th>Oficina</th>
                            <th style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
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

<!-- Modal Crear Usuario -->
<div class="modal fade" id="modalCrear" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formCrear">
                    <div class="mb-3">
                        <label for="createUsername" class="form-label">Nombre de Usuario *</label>
                        <input type="text" class="form-control" id="createUsername" required maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label for="createPassword" class="form-label">Contraseña *</label>
                        <input type="password" class="form-control" id="createPassword" required minlength="6">
                        <div class="form-text">Mínimo 6 caracteres</div>
                    </div>
                    <div class="mb-3">
                        <label for="createEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="createEmail" required>
                    </div>
                    <div class="mb-3">
                        <label for="createPhone" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="createPhone" maxlength="15">
                    </div>
                    <div class="mb-3">
                        <label for="createRol" class="form-label">Rol *</label>
                        <select class="form-select" id="createRol" required>
                            <option value="operario">Operario</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="createOficina" class="form-label">Oficina</label>
                        <select class="form-select" id="createOficina">
                            <option value="">Sin asignar</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="createAvatar" class="form-label">Foto de Perfil</label>
                        <input type="file" class="form-control" id="createAvatar" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">JPG, PNG o WEBP. Máximo 2MB</div>
                        <div class="mt-3 text-center">
                            <img id="createAvatarPreview" src="../uploads/avatars/default.png" 
                                 class="rounded-circle avatar-preview" width="100" height="100" alt="Preview">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarCrear">Crear Usuario</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditar">
                    <input type="hidden" id="editId">
                    <div class="mb-3">
                        <label for="editUsername" class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="editUsername" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="editEmail" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPhone" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="editPhone" maxlength="15">
                    </div>
                    <div class="mb-3">
                        <label for="editRol" class="form-label">Rol *</label>
                        <select class="form-select" id="editRol" required>
                            <option value="operario">Operario</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editOficina" class="form-label">Oficina</label>
                        <select class="form-select" id="editOficina">
                            <option value="">Sin asignar</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto de Perfil</label>
                        <div class="d-flex align-items-center gap-3">
                            <img id="editAvatarPreview" src="../uploads/avatars/default.png" 
                                 class="rounded-circle avatar-preview" width="100" height="100" alt="Avatar">
                            <div class="flex-grow-1">
                                <input type="file" class="form-control mb-2" id="editAvatar" 
                                       accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">JPG, PNG o WEBP. Máximo 2MB</div>
                                <button type="button" class="btn btn-sm btn-danger mt-2" id="btnDeleteAvatar">
                                    <i class="bi bi-trash"></i> Eliminar Foto
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarEditar">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tablaBody = document.querySelector('#tablaUsuarios tbody');
    const modalCrear = new bootstrap.Modal(document.getElementById('modalCrear'));
    const modalEditar = new bootstrap.Modal(document.getElementById('modalEditar'));
    
    let oficinas = [];

    // Cargar oficinas
    fetch('api_carpetas.php?action=list_offices')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                oficinas = data.data;
                poblarSelectOficinas();
            }
        });

    function poblarSelectOficinas() {
        const selectCreate = document.getElementById('createOficina');
        const selectEdit = document.getElementById('editOficina');
        
        oficinas.forEach(oficina => {
            const opt1 = document.createElement('option');
            opt1.value = oficina.id;
            opt1.textContent = oficina.nombre;
            selectCreate.appendChild(opt1);
            
            const opt2 = document.createElement('option');
            opt2.value = oficina.id;
            opt2.textContent = oficina.nombre;
            selectEdit.appendChild(opt2);
        });
    }

    // Cargar usuarios
    function cargarUsuarios() {
        fetch('api_users.php?action=list_users')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    mostrarUsuarios(data.data);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Error al cargar usuarios', 'error');
            });
    }

    function mostrarUsuarios(users) {
        tablaBody.innerHTML = '';
        
        if (users.length === 0) {
            tablaBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No hay usuarios registrados</td></tr>';
            return;
        }

        users.forEach(user => {
            const tr = document.createElement('tr');
            
            const rolBadge = user.rol === 'admin' 
                ? '<span class="badge bg-danger">Admin</span>'
                : '<span class="badge bg-info">Operario</span>';
            
            tr.innerHTML = `
                <td>
                    <img src="${user.avatar_url}" class="rounded-circle avatar-table" width="40" height="40" alt="${user.username}">
                </td>
                <td><strong>${user.username}</strong></td>
                <td>${user.email}</td>
                <td>${user.phone || '-'}</td>
                <td>${rolBadge}</td>
                <td>${user.oficina}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary btn-editar" data-id="${user.id}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger btn-eliminar" data-id="${user.id}" data-username="${user.username}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            tablaBody.appendChild(tr);
        });

        // Eventos
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', abrirModalEditar);
        });

        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', eliminarUsuario);
        });
    }

    // Preview de avatar al crear
    document.getElementById('createAvatar').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire('Error', 'La imagen es muy grande. Máximo 2MB', 'error');
                this.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('createAvatarPreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Preview de avatar al editar
    document.getElementById('editAvatar').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire('Error', 'La imagen es muy grande. Máximo 2MB', 'error');
                this.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('editAvatarPreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Nuevo usuario
    document.getElementById('btnNuevoUsuario').addEventListener('click', () => {
        document.getElementById('formCrear').reset();
        document.getElementById('createAvatarPreview').src = '../uploads/avatars/default.png';
        modalCrear.show();
    });

    document.getElementById('btnGuardarCrear').addEventListener('click', async () => {
        const formData = new FormData();
        formData.append('username', document.getElementById('createUsername').value);
        formData.append('password', document.getElementById('createPassword').value);
        formData.append('email', document.getElementById('createEmail').value);
        formData.append('phone', document.getElementById('createPhone').value);
        formData.append('rol', document.getElementById('createRol').value);
        formData.append('dependencia_id', document.getElementById('createOficina').value);

        try {
            const res = await fetch('api_users.php?action=create_user', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                // Subir avatar si se seleccionó uno
                const avatarFile = document.getElementById('createAvatar').files[0];
                if (avatarFile) {
                    const avatarData = new FormData();
                    avatarData.append('user_id', data.user_id);
                    avatarData.append('avatar', avatarFile);
                    
                    await fetch('api_users.php?action=upload_avatar', {
                        method: 'POST',
                        body: avatarData
                    });
                }
                
                modalCrear.hide();
                Swal.fire('Creado', data.message, 'success');
                cargarUsuarios();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Error al crear usuario', 'error');
        }
    });

    // Editar usuario
    function abrirModalEditar(e) {
        const id = e.target.closest('button').dataset.id;
        
        fetch(`api_users.php?action=get_user&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const user = data.data;
                    document.getElementById('editId').value = user.id;
                    document.getElementById('editUsername').value = user.username;
                    document.getElementById('editEmail').value = user.email;
                    document.getElementById('editPhone').value = user.phone || '';
                    document.getElementById('editRol').value = user.rol;
                    document.getElementById('editOficina').value = user.dependencia_id || '';
                    document.getElementById('editAvatarPreview').src = user.avatar_url;
                    document.getElementById('editAvatar').value = '';
                    modalEditar.show();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
    }

    document.getElementById('btnGuardarEditar').addEventListener('click', async () => {
        const userId = document.getElementById('editId').value;
        const formData = new FormData();
        formData.append('id', userId);
        formData.append('email', document.getElementById('editEmail').value);
        formData.append('phone', document.getElementById('editPhone').value);
        formData.append('rol', document.getElementById('editRol').value);
        formData.append('dependencia_id', document.getElementById('editOficina').value);

        try {
            const res = await fetch('api_users.php?action=update_user', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                // Subir avatar si se seleccionó uno nuevo
                const avatarFile = document.getElementById('editAvatar').files[0];
                if (avatarFile) {
                    console.log('Uploading avatar for user:', userId);
                    const avatarData = new FormData();
                    avatarData.append('user_id', userId);
                    avatarData.append('avatar', avatarFile);
                    
                    try {
                        const avatarRes = await fetch('api_users.php?action=upload_avatar', {
                            method: 'POST',
                            body: avatarData
                        });
                        const avatarResult = await avatarRes.json();
                        console.log('Avatar upload response:', avatarResult);
                        
                        if (!avatarResult.success) {
                            console.error('Avatar upload failed:', avatarResult.message);
                            Swal.fire('Advertencia', `Usuario actualizado pero avatar falló: ${avatarResult.message}`, 'warning');
                            modalEditar.hide();
                            cargarUsuarios();
                            return;
                        }
                    } catch (avatarErr) {
                        console.error('Avatar upload error:', avatarErr);
                        Swal.fire('Advertencia', 'Usuario actualizado pero hubo un error al subir el avatar', 'warning');
                        modalEditar.hide();
                        cargarUsuarios();
                        return;
                    }
                }
                
                modalEditar.hide();
                Swal.fire('Actualizado', data.message, 'success');
                cargarUsuarios();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Error al actualizar usuario', 'error');
        }
    });

    // Eliminar avatar
    document.getElementById('btnDeleteAvatar').addEventListener('click', async () => {
        const userId = document.getElementById('editId').value;
        
        const result = await Swal.fire({
            title: '¿Eliminar foto de perfil?',
            text: 'Se restaurará la foto por defecto',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });
        
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('user_id', userId);
            
            try {
                const res = await fetch('api_users.php?action=delete_avatar', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    document.getElementById('editAvatarPreview').src = data.avatar_url;
                    document.getElementById('editAvatar').value = '';
                    Swal.fire('Eliminado', data.message, 'success');
                    cargarUsuarios();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Error al eliminar avatar', 'error');
            }
        }
    });

    // Eliminar usuario
    function eliminarUsuario(e) {
        const id = e.target.closest('button').dataset.id;
        const username = e.target.closest('button').dataset.username;
        
        Swal.fire({
            title: '¿Eliminar usuario?',
            text: `Se eliminará el usuario "${username}"`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);

                fetch('api_users.php?action=delete_user', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Eliminado', data.message, 'success');
                        cargarUsuarios();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Error al eliminar usuario', 'error');
                });
            }
        });
    }

    // Cargar inicial
    cargarUsuarios();
});
</script>
</body>
</html>
