<?php
declare(strict_types=1);
ob_start();
session_start();

// Control de caché para evitar reenvío de formularios al volver atrás
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Incluir la conexión a la base de datos
require_once "rene/conexion3.php";

/**
 * Escapa cadenas para salida segura en HTML
 *
 * @param mixed $str Cadena o número a escapar
 * @return string
 */
function h(mixed $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirige a la URL especificada y termina la ejecución
 *
 * @param string $url
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

// Si se recibe una solicitud POST sin otra acción, redirige a index.php (patrón PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect('index.php');
}

// Generar token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validar usuario
if (!isset($_SESSION['user_id'])) {
    $_SESSION['mensaje'] = "No se ha definido el ID de usuario. Por favor, inicie sesión nuevamente.";
    redirect('login.php');
}
$user_id = (int)$_SESSION['user_id'];

// Consulta a la base de datos para obtener las carpetas activas
$sql = "SELECT * FROM Carpetas WHERE Estado = 'A' AND user_id = $user_id ORDER BY Caja";
$resultado = mysqli_query($conec, $sql);

if (!$resultado) {
    die("Error en la consulta: " . h(mysqli_error($conec)));
}

$usuario = $_SESSION['username'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/hueso.png">
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
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="img/Doroti Logo Horizontal.png" alt="Logo Doroti" height="30">
        </a>

        <!-- Usuario (alineado a la izquierda, junto al logo) -->
        <div class="ms-3 d-flex align-items-center bg-light px-3 py-1 rounded-pill shadow-sm">
            <i class="bi bi-person-circle me-2 text-secondary"></i>
            <span class="fw-semibold">
                <?= htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>

        <!-- Menú y acciones alineados a la derecha -->
        <div class="d-flex align-items-center gap-3 ms-auto">
            <!-- Búsqueda -->
            <div class="search-container position-relative">
                <input type="text" id="search" class="form-control form-control-sm shadow-sm" 
                       placeholder="Buscar carpetas..." aria-label="Buscar carpetas" onkeyup="searchItems()">
                <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
            </div>

            <!-- Botón Buscador -->
            <form method="POST" action="buscador.php">
                <button type="submit" name="agregarcarpeta" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-search me-2"></i>Buscador
                </button>
            </form>

            <!-- Botón Inventario -->
            <form method="POST" action="pdf/inventario.php" Target="_blank">
                <button type="submit" name="inventario" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-text me-2"></i>Inventario
                </button>
            </form>

            <!-- Botón Documentos -->
            <form method="POST" action="documents.php">
                <button type="submit" name="documents" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-pdf-fill me-2"></i>Digital
                </button>
            </form>

            <!-- Botón Salir -->
            <form method="POST">
                <button type="submit" name="cerrar_seccion" class="btn btn-danger btn-sm">
                    <i class="bi bi-box-arrow-right me-2"></i>Salir
                </button>
            </form>
        </div>
    </div>
</nav>


<main class="container-fluid mt-4">
    <div class="folder-grid">
        <!-- Tarjeta para crear nueva carpeta -->
        <div class="folder-card" style="background:rgb(40, 167, 163); color: white;">
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
                $nomenclatura  = "Carpeta " . h($fila["Car2"]);
                $nomenclatura2 = "C" . h($fila["Caja"]) . "-" . h($fila["Car2"]);
            ?>
            <div class="folder-card">
                <form method="POST" action="indice.php" class="h-100 position-relative">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="Caja" value="<?= h($fila['Caja']) ?>">
                    <input type="hidden" name="carpeta" value="<?= h($fila['Car2']) ?>">

                    <div class="folder-badge">Caja <?= h($fila["Caja"]) ?></div>

                    <div class="text-center p-3 h-100">
                        <div class="stretched-link-overlay"></div>
                        <img src="img/Carpeta.png" class="folder-icon" alt="Icono de carpeta">
                        <h5 class="mb-2 fw-semibold text-dark"><?= $nomenclatura ?></h5>
                        <div class="text-primary fs-5"><?= $nomenclatura2 ?></div>
                    </div>

                    <button type="submit" class="btn btn-link stretched-link p-0 m-0 border-0" aria-label="Ver detalles de <?= h($nomenclatura2) ?>"></button>
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
