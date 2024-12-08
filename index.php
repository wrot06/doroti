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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doroti</title>
    <meta name="description" content="Roxy">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- External CSS -->
    <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/select2/select2.min.css">
    <link rel="stylesheet" href="vendor/owlcarousel/owl.carousel.min.css">
    <link rel="stylesheet" href="vendor/lightcase/lightcase.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400|Work+Sans:300,400,700" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="css/style.min.css">
    <link rel="stylesheet" href="https://cdn.linearicons.com/free/1.0.0/icon-font.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">

    <!-- Modernizr JS for IE8 support of HTML5 elements and media queries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>

    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="buscador" style="display: flex; justify-content: flex-end; align-items: center;">
    <form method="POST" style="margin-right: 15px;" action="agregarcarpeta.php">
        <input type="submit" name="agregarcarpeta" value="Agregar Carpeta" style="text-align:left;">
    </form>
    
    <form method="POST" style="margin-right: 15px;">
        <input type="submit" name="cerrar_seccion" value="Salir" style="text-align:left;">
    </form>

    <img src="img/Doroti Logo Horizontal.jpg" style="margin-right: 15px; height: 18px;">
    <input type="text" id="search" placeholder="Buscar..." onkeyup="searchTable()">
</div>

<div id="contenedor">
    <table class="mi-tabla">
        <thead>
            <tr>
                <th></th>
                <th style="text-align: center;">Caja</th>
                <th style="text-align: center;">Carpeta</th>
                <th>Serie</th>
                <th>Sub-serie</th>
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
            $sql = "SELECT * FROM Carpetas ORDER BY Caja";
            $resultado = mysqli_query($conec, $sql);
                        
            while($fila = $resultado->fetch_assoc()) {
                $colorAcordeon = ($fila["Caja"] % 2 == 0) ? "#e7f4ff" : "#FFFFFF";
            ?>
                <tr style="background-color: <?= $colorAcordeon; ?>;">
                    <td style="text-align: center;"><button class="accordion">v</button></td>
                    <td style="text-align: center;"><?= htmlspecialchars($fila["Caja"] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($fila["Car2"] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><b><?= htmlspecialchars($fila["Serie"] ?? '', ENT_QUOTES, 'UTF-8') ?></b></td>
                    <td><?= htmlspecialchars($fila["Subs"] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($fila["Titulo"] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
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
                        
                        $sql2 = "SELECT * FROM IndiceDocumental WHERE Caja = '" . $fila['Caja'] . "' AND Carpeta = '" . $fila['Car2'] . "'";
                        $resultado2 = mysqli_query($conec, $sql2);
                        
                        if ($resultado2) {
                            while ($row = mysqli_fetch_assoc($resultado2)) {
                                echo "<tr>";
                                echo "<td style='text-align: left;'><i>{$row['DescripcionUnidadDocumental']}</i></td>";
                                echo "<td style='text-align: center;'>{$row['NoFolioInicio']}</td>";
                                echo "<td style='text-align: center;'>{$row['NoFolioFin']}</td>";
                                echo "<td style='text-align: center;'>{$row['Soporte']}</td>";
                                echo "</tr>";
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

<script>
function searchTable() {
    const input = document.getElementById('search').value.toLowerCase();
    const rows = document.querySelectorAll('#tableBody tr');

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let match = false;

        cells.forEach(cell => {
            if (cell.textContent.toLowerCase().includes(input)) {
                match = true;
            }
        });

        if (match) {
            row.style.display = '';
            if (row.classList.contains('panel')) {
                const previousRow = row.previousElementSibling;
                if (previousRow) {
                    previousRow.style.display = '';
                }
            }
        } else {
            row.style.display = 'none';
        }
    });
}

document.querySelectorAll('.accordion').forEach(button => {
    button.addEventListener('click', function() {
        const panel = this.closest('tr').nextElementSibling;
        panel.style.display = (panel.style.display === 'table-row') ? 'none' : 'table-row';
    });
});
</script>

</body>
</html>
