<?php
/**
 * Componente Reutilizable de Navbar de Doroti
 * 
 * Parámetros esperados:
 * - $basePath: Ruta relativa a la raíz (ej. './' o '../')
 * - $userAvatar: Ruta al avatar del usuario
 * - $usuario: Nombre de usuario
 * - $oficina: Nombre de la oficina/dependencia
 * - $activePage: Nombre de la página activa para resaltar (ej. 'buscador', 'admin', 'index', etc.)
 */

// Prevenir acceso directo a este archivo
if (!defined('SECURE_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access forbidden.');
}

// Valores por defecto para evitar warnings
$basePath = $basePath ?? './';
$userAvatar = $userAvatar ?? ($basePath . 'uploads/avatars/default.png');
$usuario = $usuario ?? 'Usuario';
$oficina = $oficina ?? 'Sin oficina';
$activePage = $activePage ?? '';

// Función auxiliar de escape seguro local
if (!function_exists('nh')) {
    function nh(mixed $str): string {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}
?>
<nav class="navbar navbar-expand-lg fixed-top shadow-sm" style="background-color: #e3f2fd;" data-bs-theme="light">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="<?= nh($basePath) ?>index.php">
            <img src="<?= nh($basePath) ?>img/Doroti Logo Horizontal.png" alt="Logo Doroti" height="30">
        </a>

        <!-- Botón Hamburguesa -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Usuario + Oficina -->
            <div class="d-flex align-items-center bg-light px-3 py-1 rounded-pill shadow-sm me-auto mt-2 mt-lg-0 mb-2 mb-lg-0" style="width: fit-content;">
                <img src="<?= nh($userAvatar) ?>"
                    class="rounded-circle me-2"
                    width="32"
                    height="32"
                    style="object-fit: cover; border: 2px solid #0d6efd;"
                    alt="Avatar de <?= nh($usuario) ?>">
                <div class="d-flex flex-column lh-sm">
                    <span class="fw-semibold"><?= nh($usuario) ?></span>
                    <small class="text-muted"><?= nh($oficina) ?></small>
                </div>
            </div>

            <!-- Menú -->
            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 gap-lg-3 ms-auto">
                <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
                    <a href="<?= nh($basePath) ?>admin/admin.php" class="btn btn-warning btn-sm fw-bold <?= $activePage === 'admin' ? 'active' : '' ?>">
                        <i class="bi bi-shield-lock-fill me-2"></i>Admin
                    </a>
                <?php endif; ?>

                <a href="<?= nh($basePath) ?>buscador/buscador.php" class="btn btn-outline-primary btn-sm text-start text-lg-center <?= $activePage === 'buscador' ? 'active' : '' ?>">
                    <i class="bi bi-search me-2"></i>Buscador
                </a>

                <a href="<?= nh($basePath) ?>rotulos/rotulo.php" class="btn btn-outline-primary btn-sm text-start text-lg-center <?= $activePage === 'rotulos' ? 'active' : '' ?>">
                    <i class="bi bi-check me-2"></i>Rótulos
                </a>

                <a href="<?= nh($basePath) ?>pdf/inventario.php" class="btn btn-outline-primary btn-sm text-start text-lg-center <?= $activePage === 'inventario' ? 'active' : '' ?>" target="_blank">
                    <i class="bi bi-file-earmark-text me-2"></i>Inventario
                </a>

                <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
                    <a href="<?= nh($basePath) ?>digital/documents.php" class="btn btn-outline-primary btn-sm text-start text-lg-center <?= $activePage === 'digital' ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-pdf-fill me-2"></i>Digital
                    </a>
                <?php endif; ?>

                <a href="<?= nh($basePath) ?>tablas/index.php" class="btn btn-outline-primary btn-sm text-start text-lg-center <?= $activePage === 'tablas' ? 'active' : '' ?>">
                    <i class="bi bi-table me-2"></i>Tablas
                </a>

                <a href="<?= nh($basePath) ?>update/index.php" class="btn btn-outline-success btn-sm text-start text-lg-center <?= $activePage === 'juego' ? 'active' : '' ?>">
                    <i class="bi bi-controller me-2"></i>Juego
                </a>

                <a href="<?= nh($basePath) ?>actualizaciones/index.php" class="btn btn-outline-primary btn-sm text-start text-lg-center <?= $activePage === 'novedades' ? 'active' : '' ?>">
                    <i class="bi bi-stars me-2"></i>Novedades
                </a>

                <form method="POST" action="<?= nh($basePath) ?>index.php" class="m-0">
                    <button type="submit" name="cerrar_seccion" class="btn btn-danger btn-sm w-100 text-start text-lg-center"><i class="bi bi-box-arrow-right me-1"></i>Salir</button>
                </form>
            </div>
        </div>
    </div>
</nav>
