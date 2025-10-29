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
    <link rel="icon" type="image/png" href="img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Caja y Carpeta</title>
    <link rel="stylesheet" href="css/agregarcarpeta.css">
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const caja = document.getElementById('caja');
        const car2 = document.getElementById('car2');
        const form = document.getElementById('cajaCarpetaForm');
        const mensaje = document.getElementById('mensaje');

        caja.focus();

        form.addEventListener('submit', e => {
            const valCaja = parseInt(caja.value, 10);
            const valCar2 = parseInt(car2.value, 10);

            if (!Number.isInteger(valCaja) || !Number.isInteger(valCar2) || valCaja < 1 || valCar2 < 1) {
                e.preventDefault();
                mensaje.textContent = "Ambos campos deben ser números enteros positivos.";
                mensaje.classList.add("error");
            } else {
                mensaje.textContent = "";
                mensaje.classList.remove("error");
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
<div id="mensaje" class="mensaje"><?= htmlspecialchars($mensaje) ?></div>

<header class="header">
    <h3>Agregar Carpeta</h3>
    <a href="index.php" class="btn-inicio">Inicio</a>
</header>

<main>
    <form action="rene/guardardatos.php" method="post" id="cajaCarpetaForm" class="formulario">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-inline">
            <label for="caja">C</label>
            <input type="number" id="caja" name="caja" required min="1">
            <label for="car2">-</label>
            <input type="number" id="car2" name="car2" required min="1">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-agregar">Agregar</button>
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
        return $caja % 2 === 0 ? '#a3d2ca' : '#f7d9d9';
    }

    if ($resultado = $conec->query($sql)):
        if ($resultado->num_rows === 0):
            echo '<p class="no-data">No hay carpetas recientes.</p>';
        else: ?>
            <ul class="list-group">
                <?php while ($fila = $resultado->fetch_assoc()):
                    $color = getPastelColor((int)$fila['Caja']); ?>
                    <li class="list-group-item">
                        <?= "C" . htmlspecialchars($fila['Caja']) . "-" . htmlspecialchars($fila['Car2']) ?>
                        <span class="badge" style="background-color: <?= $color ?>;">
                            <?= htmlspecialchars($fila['username']) ?>
                        </span>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php
        endif;
        $resultado->free();
    else:
        echo '<p class="error">Error en la consulta.</p>';
    endif;
    ?>
</main>
</body>
</html>
