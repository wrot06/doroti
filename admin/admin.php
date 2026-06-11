<?php
declare(strict_types=1);
ob_start();
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
AuthMiddleware::initSession();

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
function h($str): string
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

require_once "../services/UserService.php";

$userService = new UserService($conec);
$userInfo = $userService->getUserInfo((int)$_SESSION['user_id']);
$usuario = $userInfo['username'];
$oficina = $userInfo['oficina'];
$userAvatar = '../' . $userService->getUserAvatar((int)$_SESSION['user_id']);
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

    <?php
    if (!defined('SECURE_ACCESS')) {
        define('SECURE_ACCESS', true);
    }
    $basePath = '../';
    $activePage = 'admin';
    require_once "../components/navbar.php";
    ?>

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

            <!-- Gestionar Tipos Documentales -->
            <div class="col-md-4">
                <div class="card h-100 border-primary">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-text fs-1 text-primary mb-3"></i>
                        <h5 class="card-title">Gestionar Tipos Documentales</h5>
                        <p class="card-text">Administrar tipos documentales por dependencia.</p>
                        <a href="gestionar_tipos_documentales.php" class="btn btn-primary">Ir a Tipos Documentales</a>
                    </div>
                </div>
            </div>

            <!-- Gestionar Series -->
            <div class="col-md-4">
                <div class="card h-100 border-warning">
                    <div class="card-body text-center">
                        <i class="bi bi-archive fs-1 text-warning mb-3"></i>
                        <h5 class="card-title">Gestionar Series</h5>
                        <p class="card-text">Administrar series documentales y asignarlas a oficinas.</p>
                        <a href="gestionar_series.php" class="btn btn-warning text-dark">Ir a Series</a>
                    </div>
                </div>
            </div>

            <!-- Gestionar Subseries -->
            <div class="col-md-4">
                <div class="card h-100 border-primary" style="border-color: #0dcaf0 !important;">
                    <div class="card-body text-center">
                        <i class="bi bi-folder2-open fs-1 text-info mb-3"></i>
                        <h5 class="card-title">Gestionar Subseries</h5>
                        <p class="card-text">Administrar subseries por serie y dependencia.</p>
                        <a href="gestionar_subseries.php" class="btn btn-info text-dark">Ir a Subseries</a>
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

            <!-- Asignar Usuario a Carpeta -->
            <div class="col-md-3">
                <div class="card h-100" style="border-color: #6f42c1;">
                    <div class="card-body text-center">
                        <i class="bi bi-person-workspace fs-1 mb-3" style="color: #6f42c1;"></i>
                        <h5 class="card-title text-dark">Asignar Usuario</h5>
                        <p class="card-text text-muted small">
                            Cambiar usuario de una carpeta.
                        </p>
                        <a href="asignar_usuario_carpeta.php" class="btn btn-sm fw-bold text-white" style="background-color: #6f42c1;">Ir a Asignar</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php ob_end_flush(); ?>