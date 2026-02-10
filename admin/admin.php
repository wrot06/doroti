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
$userAvatar = '../uploads/avatars/default.png'; // Default
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
    <title>Panel de Administración - Doroti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>

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
            <a href="../index.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-house me-2"></i>Volver al Inicio
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
    <h1 class="mb-4">Panel de Administración</h1>
    <div class="alert alert-info">
        Bienvenido al panel de administración. Aquí podrás gestionar usuarios y configuraciones del sistema.
    </div>
    
    <div class="row g-4">
        <!-- Ejemplo de tarjeta de administración -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-people fs-1 text-primary mb-3"></i>
                    <h5 class="card-title">Gestionar Usuarios</h5>
                    <p class="card-text">Ver, crear y editar usuarios del sistema.</p>
                    <a href="gestionar_usuarios.php" class="btn btn-primary">Ir a Usuarios</a>
                </div>
            </div>
        </div>

        <!-- Gestionar Dependencias -->
        <div class="col-md-4">
            <div class="card h-100 border-success">
                <div class="card-body text-center">
                    <i class="bi bi-building fs-1 text-success mb-3"></i>
                    <h5 class="card-title">Gestionar Dependencias</h5>
                    <p class="card-text">Agregar y eliminar dependencias del sistema.</p>
                    <a href="gestionar_dependencias.php" class="btn btn-success">Ir a Dependencias</a>
                </div>
            </div>
        </div>


        <!-- Restaurar Carpetas -->
        <div class="col-md-3">
            <div class="card h-100 border-warning">
                <div class="card-body text-center">
                    <i class="bi bi-folder-symlink fs-1 text-warning mb-3"></i>
                    <h5 class="card-title text-dark">Restaurar</h5>
                    <p class="card-text text-muted small">
                        Reabrir carpetas cerradas.
                    </p>
                    <a href="restaurar_carpeta.php" class="btn btn-warning btn-sm fw-bold">Ir a Restaurar</a>
                </div>
            </div>
        </div>

        <!-- Modificar Carpetas -->
        <div class="col-md-3">
            <div class="card h-100 border-info">
                <div class="card-body text-center">
                    <i class="bi bi-pencil-square fs-1 text-info mb-3"></i>
                    <h5 class="card-title text-dark">Modificar</h5>
                    <p class="card-text text-muted small">
                        Cambiar número de Caja/Carpeta.
                    </p>
                    <a href="modificar_carpeta.php" class="btn btn-info btn-sm fw-bold text-white">Ir a Modificar</a>
                </div>
            </div>
        </div>

        <!-- Eliminar Carpetas -->
        <div class="col-md-3">
            <div class="card h-100 border-danger">
                <div class="card-body text-center">
                    <i class="bi bi-folder-x fs-1 text-danger mb-3"></i>
                    <h5 class="card-title text-dark">Eliminar</h5>
                    <p class="card-text text-muted small">
                        Borrar carpetas definitivamente.
                    </p>
                    <a href="eliminar_carpetas.php" class="btn btn-danger btn-sm fw-bold">Ir a Eliminar</a>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
