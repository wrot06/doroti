<?php     
require "rene/conexion3.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); // Redirige al inicio de sesión si no está autenticado
    exit();
}

if (isset($_POST['cerrar_seccion'])) {
    session_destroy();
    header("Location: login.php"); // Redirige a la página deseada
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doroti</title>
    <meta name="description" content="Roxy">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400|Work+Sans:300,400,700" rel="stylesheet">
    <link rel="stylesheet" href="css/style.min.css">
    <link rel="stylesheet" href="https://cdn.linearicons.com/free/1.0.0/icon-font.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="buscador">
    <form method="POST" action="login.php">
        <input type="submit" name="cerrar_seccion" class="boton-input" value="Salir">
    </form>

    <div class="acciones">
        <form method="POST" action="agregarcarpeta.php">
            <input type="submit" name="agregarcarpeta" value="Agregar Carpeta">
        </form>

        <form method="POST" action="index.php">
            <input type="submit" name="inicio" value="Inicio">
        </form>

        <form method="GET" class="filtro-anio">
            <label for="anio_inicio" class="letra-buscador">Año inicial:</label>
            <input type="number" id="anio_inicio" name="anio_inicio" min="1970" max="2025"
                value="<?= isset($_GET['anio_inicio']) ? htmlspecialchars($_GET['anio_inicio']) : '' ?>">

            <label for="anio_final" class="letra-buscador">Año final:</label>
            <input type="number" id="anio_final" name="anio_final" min="1970" max="2025"
                value="<?= isset($_GET['anio_final']) ? htmlspecialchars($_GET['anio_final']) : '' ?>">

            <button type="submit">Filtrar</button>
            <a href="index.php"><button type="button">Limpiar</button></a>
        </form>

        <input type="text" id="search" placeholder="Buscar..." onkeyup="searchTable()">
        <img src="img/Doroti Logo Horizontal.png" alt="Logo">
    </div>
</div>

<div id="contenedor">   

    <table class="mi-tabla">
        <thead>
            <tr>
                <th></th>
                <th style="text-align: center;">Caja</th>
                <th style="text-align: center;">Carpeta</th>
                <th>Serie</th>
                <th>Título</th>
                <th style="text-align: center;">Fecha Inicial</th>
                <th style="text-align: center;">Fecha Final</th>
                <th style="text-align: center;">Folios</th>
                <th style="text-align: center;">Rotulos</th>
                <th style="text-align: center;">Rotulo</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php
            $anio_inicio = isset($_GET['anio_inicio']) ? intval($_GET['anio_inicio']) : 0;
            $anio_final  = isset($_GET['anio_final']) ? intval($_GET['anio_final']) : 0;

            if ($anio_inicio > 0 && $anio_final > 0 && $anio_final >= $anio_inicio) {
                // Filtrar por años (asumiendo que FInicial y FFinal son campos tipo fecha o texto tipo 'YYYY-MM-DD')
                $sql = "SELECT * FROM Carpetas 
                        WHERE Estado = 'C' 
                        AND YEAR(FInicial) >= $anio_inicio 
                        AND YEAR(FFinal) <= $anio_final
                        ORDER BY Caja DESC, Car2 ASC";
            } else {
                // Sin filtro
                $sql = "SELECT * FROM Carpetas WHERE Estado = 'C' ORDER BY Caja DESC, Car2 ASC";
            }

            $resultado = mysqli_query($conec, $sql);
                        
            while($fila = $resultado->fetch_assoc()) {
                $colorAcordeon = ($fila["Caja"] % 2 == 0) ? "#e7f4ff" : "#FFFFFF";
            ?>
                <tr style="background-color: <?= $colorAcordeon; ?>;">
                    <td style="text-align: center;"><button class="accordion">v</button></td>
                    <td style="text-align: center;"><?= htmlspecialchars($fila["Caja"] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($fila["Car2"] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <b><?= htmlspecialchars($fila["Serie"], ENT_QUOTES, 'UTF-8') ?></b><br>
            <?php if (!empty($fila["Subs"])): ?>
                <span style="line-height: 1; font-size: 12px;">
                    <?= htmlspecialchars($fila["Subs"], ENT_NOQUOTES) ?>
                </span>
            <?php endif; ?>
                    </td>

            <?php
                $titulo = $fila["Titulo"];
                if ($titulo === null) $titulo = '';
                ?>

                    <td><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></td>                    
                    <td style="text-align: center;"><?= htmlspecialchars($fila["FInicial"] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($fila["FFinal"] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align: center;"><b><?= htmlspecialchars($fila["Folios"] ?? '', ENT_QUOTES, 'UTF-8') ?></b></td>
                    <td style="text-align: center;">
                        <?php if ($fila["Estado"] == 'A'): ?>                        
                            <form action="indice.php" method="post" target="_blank">
                                <input type="hidden" name="Caja" value="<?= htmlspecialchars($fila["Caja"] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="Car2" value="<?= htmlspecialchars($fila["Car2"] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit"><?= htmlspecialchars($fila['Estado'], ENT_QUOTES, 'UTF-8') ?> Carpeta <?= htmlspecialchars($fila['Car2'], ENT_QUOTES, 'UTF-8') ?></button>
                            </form>                      
                        <?php else: ?>
                            <form action="pdf/RotuloCarpeta.php" method="post" target="_blank">
                                <button type="submit" name="consulta" value="<?= htmlspecialchars($fila['id'], ENT_QUOTES, 'UTF-8') ?>">Carpeta <?= htmlspecialchars($fila['Car2'], ENT_QUOTES, 'UTF-8') ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <?php if ($fila["Car2"] == 1 and $fila["Estado"] == 'C'): ?>
                    <td style="text-align: center;">
                        <form action="pdf/RotuloCaja.php" method="post" target="_blank">
                            <button type="submit" name="consulta" value="<?= htmlspecialchars($fila['Caja'] ?? '', ENT_QUOTES, 'UTF-8') ?>">Caja <?= htmlspecialchars($fila['Caja'] ?? '', ENT_QUOTES, 'UTF-8') ?></button>
                        </form>
                    </td>
                    <?php else: ?>
                        <td style="text-align: center;"></td>
                    <?php endif; ?>
                </tr>

                <tr class="panel" style="display: none;">
                <td colspan="11" class="alinear-derecha">

                            <?php
                            
                            $sql3 = "SELECT * FROM IndiceDocumental WHERE Caja = '" . $fila['Caja'] . "' AND Carpeta = '" . $fila['Car2'] . "'";
                            $resultado3 = mysqli_query($conec, $sql3);
                            
                            if ($resultado3) {
                                while ($row = mysqli_fetch_assoc($resultado3)) {                                   
                                    $Carpeta = $row['Carpeta'] ?? '';
                                    $Caja = $row['Caja'] ?? '';         
                                }
                            }
                            ?>
                


                <form action="pdf/Indice.php" method="post" target="_blank">
                            <input type="hidden" name="Carpeta" value="<?= htmlspecialchars($Carpeta, ENT_QUOTES, 'UTF-8') ?>">
                            <button style="margin-right: 25px; height: 18px;" type="submit" name="Caja" value="<?= htmlspecialchars($Caja, ENT_QUOTES, 'UTF-8') ?>">Indice Carpeta <?= htmlspecialchars($fila['Car2'], ENT_QUOTES, 'UTF-8') ?></button>
                </form>
 
<div style="width: 90%; display: flex; justify-content: center; padding-right: 5%;">
    <table class="mi-tabla2">
        <?php
        $sql2 = "SELECT * FROM IndiceDocumental WHERE Caja = " . intval($fila['Caja']) . " AND Carpeta = " . intval($fila['Car2']);
        $resultado2 = mysqli_query($conec, $sql2);

        if ($resultado2) {
            while ($row = mysqli_fetch_assoc($resultado2)) {
                ?>
                <tr>
                    <td style="text-align: left;"><i><?= htmlspecialchars($row['DescripcionUnidadDocumental']) ?></i></td>
                    <td style="text-align: center;"><?= htmlspecialchars($row['NoFolioInicio']) ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($row['NoFolioFin']) ?></td>                    
                    <td style="text-align: center;">
                        <?php if (!empty($row['archivo_pdf'])): ?>
                            <form action="download2.php" method="get" target="_blank">
                                <input type="hidden" name="id2" value="<?= intval($row['id']) ?>">
                                <button type="submit" style="height: 18px;">Ver PDF</button>
                            </form>
                    <?php else: ?>
                        <form action="idcargar.php" method="post" target="_blank">
                            <input type="hidden" name="id" value="<?= intval($row['id']) ?>">
                            <!-- Token CSRF -->
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <button type="submit" style="height: 18px;">Subir</button>
                        </form>
                    <?php endif; ?>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
    </table>
</div>



                </td>
                </tr>

            <?php } ?>
        </tbody>
    </table>
</div>

<script src="js/buscador.js"></script>

</body>
</html>