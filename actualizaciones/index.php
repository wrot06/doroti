<?php
declare(strict_types=1);

require_once "../rene/conexion3.php";
require_once "../middlewares/AuthMiddleware.php";
require_once "../services/UserService.php";
require_once "../config/version.php";

AuthMiddleware::initSession();
AuthMiddleware::checkAuth('../login/login.php');

$user_id = AuthMiddleware::validateUser();
$userService = new UserService($conec);
$userInfo = $userService->getUserInfo($user_id);
$usuario = $userInfo['username'];
$oficina = $userInfo['oficina'];
$userAvatar = '../' . $userService->getUserAvatar($user_id);

// Obtener actualizaciones
$sql = "SELECT * FROM actualizaciones WHERE estado = 1 ORDER BY fecha_lanzamiento DESC, id DESC";
$resultado = $conec->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizaciones - Doroti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 80px;
        }
        .timeline {
            position: relative;
            padding: 20px 0;
            margin: 0;
            list-style: none;
        }
        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 20px;
            width: 4px;
            background: #e9ecef;
            border-radius: 2px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding-left: 50px;
        }
        .timeline-marker {
            position: absolute;
            top: 0;
            left: 10px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #0d6efd;
            border: 4px solid #fff;
            box-shadow: 0 0 0 2px #0d6efd;
        }
        .timeline-content {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .timeline-content h3 {
            margin-top: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #2b3440;
        }
        .timeline-content .date {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .version-badge {
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 20px;
            background: #e3f2fd;
            color: #0d6efd;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .description ul {
            padding-left: 20px;
            margin-bottom: 0;
        }
        .description li {
            margin-bottom: 8px;
            color: #4b5563;
        }
        .description li:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top shadow-sm" style="background-color: #e3f2fd;" data-bs-theme="light">
        <div class="container-fluid">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../img/Doroti Logo Horizontal.png" alt="Logo Doroti" height="30">
            </a>

            <!-- Botón Hamburguesa -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="d-flex align-items-center bg-light px-3 py-1 rounded-pill shadow-sm me-auto mt-2 mt-lg-0 mb-2 mb-lg-0" style="width: fit-content;">
                    <img src="<?= htmlspecialchars($userAvatar) ?>" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover; border: 2px solid #0d6efd;" alt="Avatar">
                    <div class="d-flex flex-column lh-sm">
                        <span class="fw-semibold"><?= htmlspecialchars($usuario) ?></span>
                        <small class="text-muted"><?= htmlspecialchars($oficina) ?></small>
                    </div>
                </div>

                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 gap-lg-3 ms-auto">
                    <span class="badge bg-primary rounded-pill me-2">Versión <?= APP_VERSION ?></span>
                    <a href="../index.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-house me-2"></i>Inicio</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-5 mt-3">
                    <h2 class="fw-bold mb-0 text-dark" style="font-size: 2rem;"><i class="bi bi-stars text-warning me-2"></i>Registro de Actualizaciones</h2>
                    <span class="text-muted">Historial de cambios de Doroti</span>
                </div>

                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <ul class="timeline">
                        <?php while ($row = $resultado->fetch_assoc()): ?>
                            <li class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                                        <h3 class="mb-0"><?= htmlspecialchars($row['titulo']) ?></h3>
                                        <span class="version-badge">🚀 <?= htmlspecialchars($row['version']) ?></span>
                                    </div>
                                    <div class="date"><i class="bi bi-calendar-event me-1"></i> <?= date('d M Y', strtotime($row['fecha_lanzamiento'])) ?></div>
                                    <div class="description mt-3 pt-3 border-top">
                                        <?= $row['descripcion'] ?>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info text-center py-4 rounded-4 shadow-sm">
                        <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                        <span class="fs-5">No hay actualizaciones registradas aún.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
