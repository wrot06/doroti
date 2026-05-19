<?php
declare(strict_types=1);
session_start();

// Control de caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirigir si no está autenticado
if (empty($_SESSION['authenticated'])) {
    header('Location: ../login/login.php');
    exit();
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Carpeta</title>
    <link rel="stylesheet" href="css/agregarcarpeta.css">
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const caja = document.getElementById('caja');
        const carpeta = document.getElementById('carpeta');
        const form = document.getElementById('cajaCarpetaForm');
        const mensaje = document.getElementById('mensaje');

        caja.focus();

        form.addEventListener('submit', e => {
            const valCaja = parseInt(caja.value, 10);
            const valCarpeta = parseInt(carpeta.value, 10);

            if (!Number.isInteger(valCaja) || !Number.isInteger(valCarpeta) || valCaja < 1 || valCarpeta < 1) {
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

<div id="mensaje" class="mensaje"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>

<header class="header">
    <h3>Agregar Carpeta</h3>
    <a href="../index.php" class="btn-inicio">Inicio</a>
</header>

<main>
    <!-- Formulario para agregar Carpeta -->
    <form action="guardardatos.php" method="post" id="cajaCarpetaForm" class="formulario">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-inline">
            <label for="caja">C</label>
            <input type="number" id="caja" name="caja" required min="1">
            <label for="carpeta">-</label>
            <input type="number" id="carpeta" name="carpeta" required min="1">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-agregar">Agregar</button>
        </div>
    </form>

    <!-- Listado de últimas carpetas -->
    <?php
    require "../rene/conexion3.php";

    // Función helper definida fuera del flujo lógico condicional
    function getPastelColor(int $caja): string {
        return $caja % 2 === 0 ? '#a3d2ca' : '#f7d9d9';
    }

    $sql = "
        SELECT 
            c.Caja, 
            c.Carpeta, 
            COALESCE(u.username, 'Usuario no registrado') AS username,
            COALESCE(d.nombre, 'Oficina no asignada') AS oficina
        FROM Carpetas c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN dependencias d ON c.dependencia_id = d.id
        ORDER BY c.FechaIngreso DESC
        LIMIT 8
    ";

    if ($resultado = $conec->query($sql)) {
        if ($resultado->num_rows === 0) {
            echo '<p class="no-data">No hay carpetas recientes.</p>';
        } else {
            echo '<ul class="list-group">';
            while ($fila = $resultado->fetch_assoc()) {
                $color = getPastelColor((int)$fila['Caja']);
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <?= "C" . htmlspecialchars((string)$fila['Caja']) . "-" . htmlspecialchars((string)$fila['Carpeta']) ?>
                        <small class="text-muted">[<?= htmlspecialchars($fila['oficina'] ?? '') ?>]</small>
                    </div>
                    <span class="badge" style="background-color: <?= $color ?>;">
                        <?= htmlspecialchars($fila['username'] ?? '') ?>
                    </span>
                </li>
                <?php
            }
            echo '</ul>';
        }
        $resultado->free();
    } else {
        echo '<p class="error">Error en la consulta.</p>';
    }
    ?>
</main>

</body>
</html>
