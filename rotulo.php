<?php
require "rene/conexion3.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Autenticación
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['cerrar_seccion'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Parámetros de filtro por año
$anio_inicio = isset($_GET['anio_inicio']) ? intval($_GET['anio_inicio']) : 0;
$anio_final  = isset($_GET['anio_final']) ? intval($_GET['anio_final']) : 0;

// Consulta optimizada a Carpetas
$sqlStr = "SELECT * FROM Carpetas WHERE Estado='C'";
if ($anio_inicio > 0) $sqlStr .= " AND YEAR(FInicial) >= $anio_inicio";
if ($anio_final > 0) $sqlStr .= " AND YEAR(FFinal) <= $anio_final";
$sqlStr .= " ORDER BY Caja DESC, Car2 ASC";

$resultado = $conec->query($sqlStr);

// Cargar todos los índices de una sola vez
$indices = [];
$queryIndices = "SELECT * FROM IndiceDocumental";
$resIndices = $conec->query($queryIndices);

while ($row = $resIndices->fetch_assoc()) {
    $indices[$row['Caja']][$row['Carpeta']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Doroti</title>
    <meta name="description" content="Roxy">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="img/hueso.png">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400|Work+Sans:300,400,700" rel="stylesheet">
    <link rel="stylesheet" href="css/style.min.css">
    <link rel="stylesheet" href="https://cdn.linearicons.com/free/1.0.0/icon-font.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="buscador">
    <form method="POST">
        <input type="submit" name="cerrar_seccion" class="boton-input" value="Salir">
    </form>

    <div class="acciones">
        <form method="POST" action="agregarcarpeta.php">
            <input type="submit" value="Agregar Carpeta">
        </form>

        <form method="POST" action="index.php">
            <input type="submit" value="Inicio">
        </form>

        <form method="GET" class="filtro-anio">
            <label class="letra-buscador">Año inicial:</label>
            <input type="number" name="anio_inicio" min="1970" max="2025"
                value="<?= $anio_inicio ?: '' ?>">

            <label class="letra-buscador">Año final:</label>
            <input type="number" name="anio_final" min="1970" max="2025"
                value="<?= $anio_final ?: '' ?>">

            <button type="submit">Filtrar</button>
            <a href="buscador.php"><button type="button">Limpiar</button></a>
        </form>

        <img src="img/Doroti Logo Horizontal.png" alt="Logo">
    </div>
</div>

<div id="contenedor">
<table class="mi-tabla">
    <thead>
        <tr>
            <th></th>
            <th>Caja</th>
            <th>Carpeta</th>
            <th>Serie</th>
            <th>Título</th>
            <th>Fecha Inicial</th>
            <th>Fecha Final</th>
            <th>Folios</th>
            <th>Rótulos</th>
            <th>Rótulo</th>
        </tr>
    </thead>

    <tbody id="tableBody">
<?php while ($fila = $resultado->fetch_assoc()): ?>
    <?php $color = ($fila["Caja"] % 2 == 0) ? "#e7f4ff" : "#FFFFFF"; ?>

    <tr style="background-color: <?= $color ?>;">
        <td><button class="accordion">v</button></td>
        <td><?= htmlspecialchars($fila["Caja"]) ?></td>
        <td><?= htmlspecialchars($fila["Car2"]) ?></td>
        <td>
            <b><?= htmlspecialchars($fila["Serie"]) ?></b>
            <?php if (!empty($fila["Subs"])): ?>
                <br><span style="font-size:12px"><?= htmlspecialchars($fila["Subs"]) ?></span>
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($fila["Titulo"] ?? '') ?></td>
        <td><?= htmlspecialchars($fila["FInicial"]) ?></td>
        <td><?= htmlspecialchars($fila["FFinal"]) ?></td>
        <td><b><?= htmlspecialchars($fila["Folios"]) ?></b></td>

        <td>
            <?php if ($fila["Estado"] == 'A'): ?>
                <form action="indice.php" method="post" target="_blank">
                    <input type="hidden" name="Caja" value="<?= $fila["Caja"] ?>">
                    <input type="hidden" name="Car2" value="<?= $fila["Car2"] ?>">
                    <button type="submit">A Carpeta <?= $fila['Car2'] ?></button>
                </form>
            <?php else: ?>
                <form action="pdf/RotuloCarpeta.php" method="post" target="_blank">
                    <button type="submit" name="consulta" value="<?= $fila['id'] ?>">
                        Carpeta <?= $fila['Car2'] ?>
                    </button>
                </form>
            <?php endif; ?>
        </td>

        <td>
            <?php if ($fila["Car2"] == 1): ?>
                <form action="pdf/RotuloCaja.php" method="post" target="_blank">
                    <button type="submit" name="consulta" value="<?= $fila['Caja'] ?>">
                        Caja <?= $fila['Caja'] ?>
                    </button>
                </form>
            <?php endif; ?>
        </td>
    </tr>

    <!-- PANEL -->
    <tr class="panel" style="display:none;">
        <td colspan="11" class="alinear-derecha">
            <?php 
            $carpeta = $fila['Car2'];
            $caja = $fila['Caja'];
            ?>
            <form action="pdf/Indice.php" method="post" target="_blank">
                <input type="hidden" name="Carpeta" value="<?= $carpeta ?>">
                <button type="submit" name="Caja" value="<?= $caja ?>">
                    Índice Carpeta <?= $carpeta ?>
                </button>
            </form>

            <div style="width:90%;display:flex;justify-content:center;padding-right:5%;">
            <table class="mi-tabla2">
<?php if (isset($indices[$caja][$carpeta])): ?>
    <?php foreach ($indices[$caja][$carpeta] as $doc): ?>
        <tr>
            <td><i><?= htmlspecialchars($doc['DescripcionUnidadDocumental']) ?></i></td>
            <td><?= htmlspecialchars($doc['NoFolioInicio']) ?></td>
            <td><?= htmlspecialchars($doc['NoFolioFin']) ?></td>
            <td>
                <?php if (!empty($doc['archivo_pdf'])): ?>
                    <form action="download2.php" method="get" target="_blank">
                        <input type="hidden" name="id2" value="<?= $doc['id'] ?>">
                        <button>Ver PDF</button>
                    </form>
                <?php else: ?>
                    <form action="idcargar.php" method="post" target="_blank">
                        <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button>Subir</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
            </table>
            </div>
        </td>
    </tr>
<?php endwhile; ?>
    </tbody>
</table>
</div>

<script src="js/buscador.js"></script>
<script src="js/rotulo.js"></script>
</body>
</html>
