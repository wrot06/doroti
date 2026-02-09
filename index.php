<?php
declare(strict_types=1);
ob_start();
session_start();

// Control de caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Conexión a la base de datos
require_once "rene/conexion3.php";

// Escapar HTML
function h(mixed $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// Redirección
function redirect(string $url): void {
    header("Location: $url");
    exit();
}

// Verificar autenticación
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    redirect('login/login.php');
}

// Manejar cierre de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_seccion'])) {
    session_destroy();
    redirect('login/login.php');
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validar usuario
if (!isset($_SESSION['user_id'])) {
    $_SESSION['mensaje'] = "No se ha definido el ID de usuario. Por favor, inicie sesión nuevamente.";
    redirect('login.php');
}
$user_id = (int)$_SESSION['user_id'];

// Consulta para traer carpetas con la oficina
$sql = "
SELECT 
    c.id AS carpeta_id,
    c.Caja,
    c.Carpeta,
    COALESCE(u.username, 'Usuario no registrado') AS username,
    dep.nombre AS oficina,
    c.dependencia_id
FROM Carpetas c
LEFT JOIN users u ON c.user_id = u.id
LEFT JOIN dependencias dep ON dep.id = c.dependencia_id
WHERE c.Estado = 'A'
  AND c.dependencia_id = (
        SELECT dependencia_id
        FROM users
        WHERE id = ?
  )
ORDER BY c.Caja DESC, c.Carpeta DESC
LIMIT 20
";


$stmt = $conec->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultado = $stmt->get_result();

// Traer datos de usuario actual
$sql_dep = "
SELECT 
    u.username,
    dep.nombre AS oficina
FROM users u
LEFT JOIN dependencias dep ON dep.id = u.dependencia_id
WHERE u.id = ?
LIMIT 1
";

$stmt2 = $conec->prepare($sql_dep);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$res_dep = $stmt2->get_result();

$usuario = 'Usuario';
$oficina = '';

if ($res_dep && mysqli_num_rows($res_dep) === 1) {
    $dep = $res_dep->fetch_assoc();
    $usuario = $dep['username'] ?? 'Usuario';
    $oficina = $dep['oficina'] ?? '';
}

$stmt->close();
$stmt2->close();
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


<nav class="navbar fixed-top" style="background-color: #e3f2fd;" data-bs-theme="light">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="img/Doroti Logo Horizontal.png" alt="Logo Doroti" height="30">
        </a>

        <!-- Usuario + Oficina -->
        <div class="ms-3 d-flex align-items-center bg-light px-3 py-1 rounded-pill shadow-sm">
            <i class="bi bi-person-circle me-2 text-secondary fs-4"></i>
            <div class="d-flex flex-column lh-sm">
                <span class="fw-semibold"><?= h($usuario) ?></span>
                <small class="text-muted"><?= h($oficina) ?></small>
            </div>
        </div>

        
        <!-- Menú -->
        <div class="d-flex align-items-center gap-3 ms-auto">

            <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
            <a href="admin/admin.php" class="btn btn-warning btn-sm fw-bold">
                <i class="bi bi-shield-lock-fill me-2"></i>Admin
            </a>
            <?php endif; ?>

            <a href="buscador/buscador.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-search me-2"></i>Buscador
            </a>

            <a href="rotulos/rotulo.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-check me-2"></i>Rotulos</button>
            </a>

            <a href="pdf/inventario.php" class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="bi bi-file-earmark-text me-2"></i>Inventario
            </a>

            <a href="digital/documents.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-file-earmark-pdf-fill me-2"></i>Digital
            </a>

            <form method="POST">
                <button type="submit" name="cerrar_seccion" class="btn btn-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Salir</button>
            </form>
        </div>
    </div>
</nav>

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
                $nomenclatura  = "Carpeta " . h($fila["Carpeta"]);
                $nomenclatura2 = "C" . h($fila["Caja"]) . "-" . h($fila["Carpeta"]);
                $color = $fila['Caja'] % 2 === 0 ? '#a3d2ca' : '#f7d9d9';
            ?>
            <div class="folder-card">
                <form method="POST" action="carpeta/indice.php" class="h-100 position-relative">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="Caja" value="<?= h($fila['Caja']) ?>">
                    <input type="hidden" name="carpeta" value="<?= h($fila['Carpeta']) ?>">
                    <input type="hidden" name="oficina" value="<?= h($fila['oficina']) ?>">
                    <input type="hidden" name="dependencia_id" value="<?= h($fila['dependencia_id']) ?>">


                    <div class="folder-badge">Caja <?= h($fila["Caja"]) ?></div>

                    <div class="text-center p-3 h-100">
                        <div class="stretched-link-overlay"></div>
                        <img src="img/Carpeta.png" class="folder-icon" alt="Icono de carpeta">
                        <h5 class="mb-2 fw-semibold text-dark"><?= $nomenclatura ?></h5>
                        <div class="text-primary fs-6">                            
                            <?= h($fila['oficina']) ?>
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
