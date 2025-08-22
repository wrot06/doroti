<?php
declare(strict_types=1);
session_start();

// Control de caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirigir si no está autenticado
if (empty($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Caja y Carpeta</title>
    <link rel="stylesheet" href="css/agregarcarpeta.css">
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('caja').focus();

        const form = document.getElementById('cajaCarpetaForm');
        form.addEventListener('submit', e => {
            const caja = parseInt(document.getElementById('caja').value, 10);
            const car2 = parseInt(document.getElementById('car2').value, 10);
            const mensaje = document.getElementById('mensaje');

            if (!Number.isInteger(caja) || !Number.isInteger(car2) || caja < 1 || car2 < 1) {
                e.preventDefault();
                mensaje.textContent = "Ambos campos deben ser números enteros positivos.";
            } else {
                mensaje.textContent = "";
            }
        });
    });
    </script>
</head>
<body>

<?php
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>

<div id="mensaje"><?= htmlspecialchars($mensaje) ?></div>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h3 style="margin: 0;">Agregar Carpeta</h3>
    <a href="index.php" style="
        font-size: 14px;
        padding: 4px 10px;
        border-radius: 4px;
        background-color:rgb(50, 136, 26);
        color: white;
        text-decoration: none;
    ">Inicio</a>
</div>

<form action="rene/guardardatos.php" method="post" id="cajaCarpetaForm">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <div class="form-inline">
        <label for="caja">C</label>
        <input type="number" id="caja" name="caja" required min="1" style="font-size: 55px;">
        <label for="car2">-</label>
        <input type="number" id="car2" name="car2" required min="1" style="font-size: 55px;">
    </div>
    <div style="display: flex; justify-content: center;">
        <button type="submit">Agregar</button>
    </div>
</form>

<?php
require "rene/conexion3.php";

$sql = "
    SELECT c.Caja, c.Car2, COALESCE(u.username, 'Usuario no registrado') AS username
    FROM Carpetas c
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY c.FechaIngreso DESC
    LIMIT 8
";

function getPastelColor(int $caja): string {
    return $caja % 2 === 0 ? '#a3d2ca' : '#f7d9d9'; // verde pastel / rosa pastel
}

if ($resultado = $conec->query($sql)):
    if ($resultado->num_rows === 0):
        echo '<p style="text-align:center; color:#666; font-family: Arial, sans-serif;">No hay carpetas recientes.</p>';
    else: ?>
        <ul class="list-group" style="max-width: 400px; margin: 40px auto 0; padding: 0; font-family: Arial, sans-serif;">
            <?php while ($fila = $resultado->fetch_assoc()):
                $color = getPastelColor((int)$fila['Caja']);
            ?>
                <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 6px 12px; border-bottom: 1px solid #ddd; font-size: 14px; line-height: 1.1;">
                    <?= "C" . htmlspecialchars($fila['Caja']) . "-" . htmlspecialchars($fila['Car2']) ?>
                    <span class="badge" style="background-color: <?= $color ?>; color: #333; padding: 3px 8px; border-radius: 9999px; font-weight: 600; font-size: 12px; min-width: 30px; text-align: center;">
                        <?= htmlspecialchars($fila['username']) ?>
                    </span>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php
    endif;
    $resultado->free();
else:
    echo '<p style="text-align:center; color:red; font-family: Arial, sans-serif;">Error en la consulta.</p>';
endif;
?>
</body>
</html>
