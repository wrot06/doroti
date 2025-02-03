<?php   
ob_start();
session_start();
require "rene/conexion3.php";

// Configuración de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar autenticación
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit();
}

// Manejar cierre de sesión
if (isset($_POST['cerrar_seccion'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Consulta segura a la base de datos
$sql = "SELECT * FROM Carpetas WHERE Estado = 'A' ORDER BY Caja";
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
    <meta name="description" content="Gestión de Carpetas">
    
    <!-- Bootstrap y dependencias -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    
    <!-- Estilos optimizados -->
    <style>
        :root {
            --main-color: #2c3e50;
            --accent-color: #3498db;
            --hover-transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        body {
            background: #f8f9fa;
            min-height: 100vh;
            padding-top: 70px;
        }
        
        .navbar-custom {
            background: #fff;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            height: 60px;
        }
        
        .folder-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.5rem;
            padding: 20px;
            max-width: 1300px;
            margin: 0 auto;
        }
        
        .folder-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            transition: var(--hover-transition);
            overflow: hidden;
            position: relative;
        }
        
        .folder-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .folder-icon {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin: 1rem auto;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }
        
        .folder-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--accent-color);
            color: white;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 20px;
            z-index: 2;
        }
        
        .search-container {
            max-width: 300px;
            transition: var(--hover-transition);
        }
        
        .stretched-link-overlay::after {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 1;
            content: "";
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="img/Doroti Logo Horizontal.jpg" alt="Logo Doroti" height="30">
        </a>
        
        <div class="d-flex align-items-center gap-3">
            <!-- Botón Buscador -->
            <form method="POST" action="buscador.php" target="_blank">
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
                <input type="text" id="search" 
                       class="form-control form-control-sm shadow-sm" 
                       placeholder="Buscar carpetas..."
                       aria-label="Buscar carpetas"
                       onkeyup="searchItems()">
                <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
            </div>
        </div>
    </div>
</nav>

<main class="container-fluid">
<div class="folder-grid">
    <div class="folder-card" style="background: #28a745; color: white;">
        <form method="POST" action="agregarcarpeta.php" class="h-100">
            <div class="text-center p-3 h-100">
                <img src="img/Carpeta.png" class="folder-icon" alt="Icono de carpeta">
                <h5 class="mb-2 fw-semibold text-light">Crear nueva carpeta</h5>
            </div>
            <button type="submit" class="btn btn-link stretched-link p-0 m-0 border-0" aria-label="Crear nueva carpeta"></button>
        </form>
    </div>
    
    <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
        <?php while ($fila = mysqli_fetch_assoc($resultado)): 
            $nomenclatura = "Carpeta " . htmlspecialchars($fila["Car2"]);
            $nomenclatura2 = "C" . htmlspecialchars($fila["Caja"]) . "-" . htmlspecialchars($fila["Car2"]);
        ?>
        <div class="folder-card">
            <form method="POST" action="indice.php" class="h-100 position-relative">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="caja" value="<?= htmlspecialchars($fila['Caja'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="carpeta" value="<?= htmlspecialchars($fila['Car2'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="folder-badge">Caja <?= htmlspecialchars($fila["Caja"]) ?></div>
                
                <div class="text-center p-3 h-100">
                    <div class="stretched-link-overlay"></div>
                    <img src="img/Carpeta.png" class="folder-icon" alt="Icono de carpeta">
                    <h5 class="mb-2 fw-semibold text-dark"><?= $nomenclatura ?></h5>
                    <div class="text-primary small"><?= $nomenclatura2 ?></div>
                </div>
                
                <button type="submit" class="btn btn-link stretched-link p-0 m-0 border-0" aria-label="Ver detalles de <?= $nomenclatura2 ?>"></button>
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
        const content = [
            ...card.querySelectorAll('h5, .text-primary')
        ].map(el => el.textContent.toLowerCase()).join(' ');
        
        card.style.display = content.includes(searchTerm) ? 'block' : 'none';
    });
}
</script>

</body>
</html>
<?php
ob_end_flush();
?>
