<?php
declare(strict_types=1);
ob_start();
session_start();

// Control de caché para evitar reenvío de formularios al volver atrás
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Incluir la conexión a la base de datos
require_once "rene/conexion3.php";

/**
 * Redirige a la URL especificada y termina la ejecución.
 *
 * @param string $url La URL a la que redirigir.
 * @return void
 */
function redirect(string $url): void {
    header("Location: $url");
    exit();
}

// Verificar autenticación
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    redirect('login.php');
}

// Manejar cierre de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_seccion'])) {
    session_destroy();
    redirect('login.php');
}

// Si se recibe una solicitud POST sin otra acción (para evitar que la última solicitud sea POST),
// redirige inmediatamente a index.php (aplicando el patrón POST/Redirect/GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect('index.php');
}

// Generar token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['mensaje'] = "No se ha definido el ID de usuario. Por favor, inicie sesión nuevamente.";
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Consulta a la base de datos para obtener las carpetas activas
$sql = "SELECT * FROM Carpetas WHERE Estado = 'A' and user_id= ".$user_id." ORDER BY Caja";
$resultado = mysqli_query($conec, $sql);

if (!$resultado) {
    die("Error en la consulta: " . mysqli_error($conec));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doroti</title>
    <link rel="stylesheet" href="css/index.css">
    <meta name="description" content="Gestión de Carpetas">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="img/Doroti Logo Horizontal.jpg" alt="Logo Doroti" height="30">
        </a>
        <div class="d-flex align-items-center gap-3">
            <!-- Botón Buscador -->
            <form method="POST" action="buscador.php">
                <button type="submit" name="agregarcarpeta" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-search me-2"></i>Buscador
                </button>
            </form>
            <!-- Botón Salir -->
            <form method="POST">
                <button type="submit" name="cerrar_seccion" class="btn btn-danger btn-sm">
                    <i class="bi bi-box-arrow-right me-2"></i>Salir
                </button>
            </form>
            <!-- Búsqueda -->
            <div class="search-container position-relative">
                <input type="text" id="search" class="form-control form-control-sm shadow-sm" 
                       placeholder="Buscar carpetas..." aria-label="Buscar carpetas" onkeyup="searchItems()">
                <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
            </div>
        </div>
    </div>
</nav>

<main class="container-fluid">
    <div class="folder-grid">
        <!-- Tarjeta para crear nueva carpeta -->
        <div class="folder-card" style="background: #28a745; color: white;">
            <form method="POST" action="agregarcarpeta.php" class="h-100">
                <div class="text-center p-3 h-100">
                    <img src="img/Carpeta.png" class="folder-icon" alt="Icono de carpeta">
                    <h5 class="mb-2 fw-semibold text-light">Crear nueva carpeta</h5>
                </div>
                <button type="submit" class="btn btn-link stretched-link p-0 m-0 border-0" aria-label="Crear nueva carpeta"></button>
            </form>
        </div>
        
        <!-- Mostrar las carpetas registradas -->
        <?php if (mysqli_num_rows($resultado) > 0): ?>
            <?php while ($fila = mysqli_fetch_assoc($resultado)): 
                $nomenclatura  = "Carpeta " . htmlspecialchars($fila["Car2"], ENT_QUOTES, 'UTF-8');
                $nomenclatura2 = "C" . htmlspecialchars($fila["Caja"], ENT_QUOTES, 'UTF-8') . "-" . htmlspecialchars($fila["Car2"], ENT_QUOTES, 'UTF-8');
            ?>
            <div class="folder-card">
                
            <form method="POST" action="indice.php" class="h-100 position-relative">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="consulta" value="<?= htmlspecialchars($fila['Caja'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="carpeta" value="<?= htmlspecialchars($fila['Car2'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="folder-badge">Caja <?= htmlspecialchars($fila["Caja"], ENT_QUOTES, 'UTF-8') ?></div>

                <div class="text-center p-3 h-100">
                    <div class="stretched-link-overlay"></div>
                    <img src="img/Carpeta.png" class="folder-icon" alt="Icono de carpeta">
                    <h5 class="mb-2 fw-semibold text-dark"><?= $nomenclatura ?></h5>
                    <div class="text-primary small"><?= $nomenclatura2 ?></div>
                </div>

                <button type="submit" class="btn btn-link stretched-link p-0 m-0 border-0" aria-label="Ver detalles de <?= htmlspecialchars($nomenclatura2, ENT_QUOTES, 'UTF-8') ?>"></button>
            </form>


            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="alert alert-info shadow-sm">
                    <i class="bi bi-folder-x me-2"></i>No se encontraron carpetas disponibles
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Scripts -->
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
<?php
ob_end_flush();
?>
