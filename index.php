<?php

declare(strict_types=1);

// Acceso seguro para componentes
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Cargar dependencias
require_once "rene/conexion3.php";
require_once "middlewares/AuthMiddleware.php";
require_once "services/UserService.php";
require_once "services/FolderService.php";

// Inicializar sesión y headers
AuthMiddleware::initSession();

// Verificar autenticación
AuthMiddleware::checkAuth();

// Manejar cierre de sesión
AuthMiddleware::handleLogout();

// Generar token CSRF
AuthMiddleware::generateCsrf();

// Validar usuario
$user_id = AuthMiddleware::validateUser();

// Inicializar servicios
$userService = new UserService($conec);
$folderService = new FolderService($conec);

// Obtener carpetas del usuario
$resultado = $folderService->getUserFolders($user_id);

// Obtener información del usuario
$userInfo = $userService->getUserInfo($user_id);
$usuario = $userInfo['username'];
$oficina = $userInfo['oficina'];

// Obtener avatar del usuario
$userAvatar = $userService->getUserAvatar($user_id);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doroti</title>
    <link rel="stylesheet" href="css/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>

<body>


    <?php
    $basePath = './';
    $activePage = 'index';
    require_once "components/navbar.php";
    ?>

    <main class="container-fluid mt-4">
        <div class="folder-grid">
            <!-- Crear nueva carpeta -->
            <div class="folder-card" style="background:rgb(40, 167, 163); color:white;">
                <form method="POST" action="carpeta/agregarcarpeta.php" class="h-100">
                    <div class="text-center p-3 h-100">
                        <img src="img/Carpeta.png" class="folder-icon" alt="Icono de carpeta">
                        <h5 class="mb-2 fw-semibold text-light">Crear nueva carpeta</h5>
                    </div>
                    <button type="submit" class="btn btn-link stretched-link p-0 m-0 border-0" aria-label="Crear nueva carpeta"></button>
                </form>
            </div>

            <!-- Mostrar carpetas -->
            <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                <?php while ($fila = mysqli_fetch_assoc($resultado)):
                    $nomenclatura  = "Carpeta " . ResponseHelper::h($fila["Carpeta"]);
                    $nomenclatura2 = "C" . ResponseHelper::h($fila["Caja"]) . "-" . ResponseHelper::h($fila["Carpeta"]);
                    $color = $fila['Caja'] % 2 === 0 ? '#a3d2ca' : '#f7d9d9';
                ?>
                    <div class="folder-card">
                        <form method="POST" action="carpeta/indice.php" class="h-100 position-relative">
                            <input type="hidden" name="csrf_token" value="<?= ResponseHelper::h($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="Caja" value="<?= ResponseHelper::h($fila['Caja']) ?>">
                            <input type="hidden" name="carpeta" value="<?= ResponseHelper::h($fila['Carpeta']) ?>">
                            <input type="hidden" name="oficina" value="<?= ResponseHelper::h($fila['oficina']) ?>">
                            <input type="hidden" name="dependencia_id" value="<?= ResponseHelper::h($fila['dependencia_id']) ?>">


                            <div class="folder-badge">Caja <?= ResponseHelper::h($fila["Caja"]) ?></div>

                            <div class="text-center p-3 h-100">
                                <div class="stretched-link-overlay"></div>
                                <img src="img/Carpeta.png" class="folder-icon" alt="Icono de carpeta">
                                <h5 class="mb-2 fw-semibold text-dark"><?= $nomenclatura ?></h5>
                                <div class="text-primary fs-6">
                                    <?= ResponseHelper::h($fila['oficina']) ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-link stretched-link p-0 m-0 border-0"></button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="alert alert-info shadow-sm"><i class="bi bi-folder-x me-2"></i>No se encontraron carpetas disponibles</div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function searchItems() {
            const searchTerm = document.getElementById('search').value.toLowerCase();
            const cards = document.querySelectorAll('.folder-card');
            cards.forEach(card => {
                const content = Array.from(card.querySelectorAll('h5, .text-primary'))
                    .map(el => el.textContent.toLowerCase())
                    .join(' ');
                card.style.display = content.includes(searchTerm) ? 'block' : 'none';
            });
        }
    </script>

</body>

</html>
<?php ob_end_flush(); ?>