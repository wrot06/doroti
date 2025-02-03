<?php  
ob_start();
session_start(); 
require "rene/conexion3.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica si el usuario está autenticado
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php'); 
    exit();
}

if (isset($_POST['cerrar_seccion'])) {
    session_destroy();
    header("Location: login.php"); 
    exit();
}

// Obtención de datos de la base de datos
$sql = "SELECT * FROM Carpetas ORDER BY Caja";
$resultado = mysqli_query($conec, $sql);
$carpetas = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doroti</title>
    <meta name="description" content="Roxy">
    <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/select2/select2.min.css">
    <link rel="stylesheet" href="vendor/owlcarousel/owl.carousel.min.css">
    <link rel="stylesheet" href="vendor/lightcase/lightcase.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400|Work+Sans:300,400,700" rel="stylesheet">
    <link rel="stylesheet" href="css/style.min.css">
    <link rel="stylesheet" href="https://cdn.linearicons.com/free/1.0.0/icon-font.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="buscador" style="display: flex; justify-content: flex-end; align-items: center;">
    <form method="POST" style="margin-right: 15px;" action="index.php">
        <input type="submit" name="agregarcarpeta" value="Inicio" style="text-align:left;">
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
                <th style="text-align: center; width: 2%; font-size: 10px; padding: 1px 1px;"></th>
                <th style="text-align: center; width: 4%; font-size: 10px; padding: 5px 2px;">Caja</th>
                <th style="text-align: center; width: 4%; font-size: 10px; padding: 5px 2px;">Carpeta</th>
                <th style="text-align: center; width: 15%; font-size: 14px; padding: 5px 2px;">Serie</th>
                <th style="text-align: center; width: 24%; font-size: 10px; padding: 5px 2px;">Título</th>
                <th style="text-align: center; width: 7%; font-size: 10px; padding: 5px 2px;">Fecha Inicial</th>
                <th style="text-align: center; width: 7%; font-size: 10px; padding: 5px 2px;">Fecha Final</th>
                <th style="text-align: center; width: 4%; font-size: 10px; padding: 5px 2px;">Folios</th>
                <th style="text-align: center; width: 4%; font-size: 10px; padding: 5px 2px;">Rotulos</th>
                <th style="text-align: center; width: 4%; font-size: 10px; padding: 5px 2px;">Rotulo</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php foreach ($carpetas as $fila): 
                $colorAcordeon = ($fila["Caja"] % 2 == 0) ? "#e7f4ff" : "#FFFFFF"; ?>
                <tr style="background-color: <?= $colorAcordeon; ?>;">
                    <td style="text-align: center;"><button class="accordion">v</button></td>
                    <td style="text-align: center;"><?= htmlspecialchars($fila["Caja"]) ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($fila["Car2"]) ?></td>
                    <td>
                        <b><?= htmlspecialchars($fila["Serie"]) ?></b><br>
                        <span style="line-height: 1; font-size: 12px;"><?= htmlspecialchars($fila["Subs"]) ?></span>
                    </td>
                    <td><?= htmlspecialchars($fila["Titulo"]) ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($fila["FInicial"]) ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($fila["FFinal"]) ?></td>
                    <td style="text-align: center;"><b><?= htmlspecialchars($fila["Folios"]) ?></b></td>
                    <td style="text-align: center;">
                        <?php if ($fila["Estado"] == 'A'): ?>                        
                            <form action="indice.php" method="post" target="_blank">
                                <input type="hidden" name="Caja" value="<?= htmlspecialchars($fila["Caja"]) ?>">
                                <input type="hidden" name="Car2" value="<?= htmlspecialchars($fila["Car2"]) ?>">
                                <button type="submit"><?= htmlspecialchars($fila['Estado']) ?> Carpeta <?= htmlspecialchars($fila['Car2']) ?></button>
                            </form>                      
                        <?php else: ?>
                            <form action="pdf/RotuloCarpeta.php" method="post" target="_blank">
                                <button type="submit" name="consulta" value="<?= htmlspecialchars($fila['id']) ?>">Carpeta <?= htmlspecialchars($fila['Car2']) ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <?php if ($fila["Car2"] == 1 && $fila["Estado"] == 'C'): ?>
                    <td style="text-align: center;">
                        <form action="pdf/RotuloCaja.php" method="post" target="_blank">
                            <button type="submit" name="consulta" value="<?= htmlspecialchars($fila['Caja']) ?>">Caja <?= htmlspecialchars($fila['Caja']) ?></button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>

                <tr class="panel" style="display: none;">
                    <td colspan="11" class="alinear-derecha">
                        <form action="pdf/Indice.php" method="post" target="_blank">
                            <input type="hidden" name="Carpeta" value="<?= htmlspecialchars($fila['Car2']) ?>">
                            <button style="margin-right: 25px; height: 18px;" type="submit" name="Caja" value="<?= htmlspecialchars($fila['Caja']) ?>">Indice Carpeta <?= htmlspecialchars($fila['Car2']) ?></button>
                        </form>

                        <div style="width: 90%; display: flex; justify-content: center; padding-right: 5%;">
                            <table class="mi-tabla2">
                                <?php
                                $sql2 = "SELECT * FROM IndiceDocumental WHERE Caja = '{$fila['Caja']}' AND Carpeta = '{$fila['Car2']}'";
                                $resultado2 = mysqli_query($conec, $sql2);
                                while ($row = mysqli_fetch_assoc($resultado2)): ?>
                                    <tr>
                                        <td style='text-align: left;'><i><?= htmlspecialchars($row['DescripcionUnidadDocumental']) ?></i></td>
                                        <td style='text-align: center;'><?= htmlspecialchars($row['NoFolioInicio']) ?></td>
                                        <td style='text-align: center;'><?= htmlspecialchars($row['NoFolioFin']) ?></td>
                                        <td style='text-align: center;'><?= htmlspecialchars($row['Soporte']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </table>
                        </div>
                    </td>
                </tr>

            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function searchTable() {
    const input = document.getElementById('search').value.toLowerCase();
    const rows = document.querySelectorAll('#tableBody tr');

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let match = [...cells].some(cell => cell.textContent.toLowerCase().includes(input));
        row.style.display = match ? '' : 'none';
        if (match && row.classList.contains('panel')) {
            const previousRow = row.previousElementSibling;
            if (previousRow) previousRow.style.display = '';
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
