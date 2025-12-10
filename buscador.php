<?php
// Conexión y configuración
require "rene/conexion3.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Verificar autenticación
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit();
}

// Inicializar variables
$search = '';
$resultados = [];

// Procesar búsqueda
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search = trim($_GET['search']);
    $likeSearch = "%$search%";

    $stmt = $conec->prepare("
        SELECT Caja, Carpeta, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin 
        FROM IndiceDocumental 
        WHERE DescripcionUnidadDocumental LIKE ? 
        ORDER BY Caja DESC, Carpeta ASC
    ");
    $stmt->bind_param("s", $likeSearch);
    $stmt->execute();
    $resultados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscador de Índices</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.4/css/bulma.min.css">
    <link rel="stylesheet" href="css/buscador.css">
</head>
<body>
<nav class="navbar" role="navigation" aria-label="main navigation">
    <div class="navbar-brand">
        <!-- Logo -->
        <a class="navbar-item" href="index.php">
            <figure class="image">
                <img src="img/Doroti Logo Horizontal.png" alt="Doroti Logo" />
            </figure>
        </a>


    </div>

    <div id="navbarBasicExample" class="navbar-menu">
        <div class="navbar-start"></div>

        <div class="navbar-end">
            <div class="navbar-item">
                <!-- Formulario de búsqueda -->
                <form method="GET" action="buscador.php" class="field has-addons" id="form-busqueda">
                    <div class="control">
                        <input class="input" type="text" name="search" placeholder="Buscar..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="control">
                        <button type="submit" class="button is-info">Buscar</button>
                    </div>
                    <div class="control">
                        <button type="button" id="btn-limpiar" class="button is-success">Limpiar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</nav>

<div class="section">
    <div class="container" id="contenedor">
        <?php if(empty($resultados) && $search === ''): ?>
            <div id="mensaje-inicio">
                Para empezar la búsqueda, escribe una palabra y haz clic en el botón "<b>Buscar</b>".
            </div>
        <?php elseif(!empty($resultados)): ?>
            <table class="table is-fullwidth is-striped is-hoverable">
                <thead>
                    <tr>
                        <th>Caja</th>
                        <th>Carpeta</th>
                        <th>Descripción</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($resultados as $fila): ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['Caja']) ?></td>
                            <td><?= htmlspecialchars($fila['Carpeta']) ?></td>
                            <td><?= htmlspecialchars($fila['DescripcionUnidadDocumental']) ?></td>
                            <td><?= htmlspecialchars($fila['NoFolioInicio']) ?></td>
                            <td><?= htmlspecialchars($fila['NoFolioFin']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No se encontraron resultados para "<b><?= htmlspecialchars($search) ?></b>".</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Funcionalidad del navbar burger (Bulma)
    const $navbarBurgers = Array.from(document.querySelectorAll('.navbar-burger'));
    $navbarBurgers.forEach(el => {
        el.addEventListener('click', () => {
            const target = el.dataset.target;
            const $target = document.getElementById(target);
            el.classList.toggle('is-active');
            $target.classList.toggle('is-active');
        });
    });

    // Botón Limpiar
    const btnLimpiar = document.getElementById('btn-limpiar');
    btnLimpiar.addEventListener('click', () => {
        document.querySelector('input[name="search"]').value = '';
        // Mostrar mensaje inicial
        const contenedor = document.getElementById('contenedor');
        contenedor.innerHTML = `<div id="mensaje-inicio">
            Para empezar la búsqueda, escribe una palabra y haz clic en el botón "<b>Buscar</b>".
        </div>`;
    });
});
</script>
</body>
</html>