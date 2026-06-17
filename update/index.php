<?php
declare(strict_types=1);
ob_start();
require_once "../rene/conexion3.php";
require_once "../middlewares/AuthMiddleware.php";

AuthMiddleware::initSession();
AuthMiddleware::checkAuth('../login/login.php');


$userDepId = isset($_SESSION['dependencia_id']) ? (int)$_SESSION['dependencia_id'] : 0;

if ($userDepId > 0) {
    $tableName = getIndiceTableName($conec, $userDepId);
    $sql = "
        SELECT 
            it.id,
            c.dependencia_id,
            c.Caja,
            c.Carpeta,
            it.DescripcionUnidadDocumental,
            it.paginas,
            d.nombre AS dependencia_nombre
        FROM `$tableName` it
        INNER JOIN carpetas c ON it.carpeta_id = c.id
        LEFT JOIN dependencias d ON c.dependencia_id = d.id
        WHERE (it.serie IS NULL OR TRIM(it.serie) = '')
          AND c.dependencia_id = ?
        ORDER BY RAND()
        LIMIT 1
    ";
    
    $stmt = $conec->prepare($sql);
    $stmt->bind_param("i", $userDepId);
    $stmt->execute();
    $res = $stmt->get_result();
    $registro = $res->fetch_assoc();
    $stmt->close();
} else {
    $unionQuery = getIndiceUnionQuery($conec, ["id", "carpeta_id", "serie", "DescripcionUnidadDocumental", "paginas"]);
    $sql = "
        SELECT 
            it.id,
            c.dependencia_id,
            c.Caja,
            c.Carpeta,
            it.DescripcionUnidadDocumental,
            it.paginas,
            d.nombre AS dependencia_nombre
        FROM $unionQuery it
        INNER JOIN carpetas c ON it.carpeta_id = c.id
        LEFT JOIN dependencias d ON c.dependencia_id = d.id
        WHERE it.serie IS NULL OR TRIM(it.serie) = ''
        ORDER BY RAND()
        LIMIT 1
    ";
    $res = $conec->query($sql);
    $registro = $res->fetch_assoc();
}

if (!$registro) {
    die("
<body style='background:#0f172a;color:white;display:flex;justify-content:center;align-items:center;height:100vh;font-family:Arial'>
<h1>🎉 No hay registros pendientes</h1>
</body>
");
}

$depId = ($userDepId > 0) ? $userDepId : (int)($registro['dependencia_id'] ?? 0);
$tipos = [];

if ($depId > 0) {
    $sqlTipos = "
        SELECT nombre
        FROM tipo_documental
        WHERE estado = 1
          AND dependencia_id = ?
        ORDER BY nombre ASC
    ";

    $stmtTipos = $conec->prepare($sqlTipos);
    $stmtTipos->bind_param("i", $depId);
    $stmtTipos->execute();
    $resTipos = $stmtTipos->get_result();

    while ($row = $resTipos->fetch_assoc()) {
        $tipos[] = $row['nombre'];
    }
    $stmtTipos->close();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Clasificador</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial;
            color: white;
            padding: 20px;
        }

        .game-card {
            width: 850px;
            background: #1e293b;
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .4);
        }

        .stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .badge-custom {
            background: #334155;
            padding: 10px 14px;
            border-radius: 10px;
            margin-right: 10px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .descripcion {
            background: #0f172a;
            padding: 25px;
            border-radius: 18px;
            margin: 25px 0;
            font-size: 20px;
            line-height: 1.7;
            border: 1px solid rgba(255, 255, 255, .05);
        }

        .btn-game {
            height: 60px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 16px;
        }

        #serieSeleccionada {
            background: #334155;
            padding: 18px;
            border-radius: 16px;
            font-size: 20px;
            text-align: center;
            margin-bottom: 20px;
            min-height: 65px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #1e293b;
            color: white;
            border-radius: 24px;
        }

        .series-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .serie-btn {
            background: #334155;
            border: none;
            color: white;
            padding: 25px;
            border-radius: 18px;
            font-size: 18px;
            font-weight: bold;
            transition: .2s;
            cursor: pointer;
        }

        .serie-btn:hover {
            background: #475569;
            transform: translateY(-2px);
        }

        #mensaje {
            margin-top: 20px;
            text-align: center;
            font-size: 17px;
            font-weight: bold;
            min-height: 30px;
        }

        /* Estilos para destacar el radio seleccionado (checkbox-round) */
        input[name="serie_radio"]:checked + label {
            font-weight: bold !important;
            color: #38bdf8 !important; /* Celeste brillante */
            text-decoration: underline !important;
        }

        /* Estilo para la descripción editable */
        .descripcion[contenteditable="true"] {
            outline: none;
            transition: all 0.2s;
            cursor: text;
        }
        .descripcion[contenteditable="true"]:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.2);
            background: #1e293b !important;
        }
    </style>

</head>

<body>

    <a href="../index.php" class="btn btn-secondary" style="position: absolute; top: 20px; left: 20px;">
        🏠 Inicio
    </a>

    <div class="game-card">

        <div class="stats">
            <div>🔥 Racha: <span id="racha">0</span></div>
            <div>⭐ Puntos: <span id="puntos">0</span></div>
        </div>

        <h2 class="mb-4">
            📜 Registro #<?= $registro['id'] ?>
        </h2>

        <div>
            <span class="badge-custom"><?= htmlspecialchars($registro['dependencia_nombre']) ?></span>
            <span class="badge-custom">N° de Caja <?= $registro['Caja'] ?></span>
            <span class="badge-custom">N° de Carpeta <?= $registro['Carpeta'] ?></span>
        </div>

        <div class="mb-2 text-start" style="font-size: 0.9rem; color: #94a3b8;">
            ✏️ Puedes corregir la descripción directamente abajo:
        </div>
        <div class="descripcion" contenteditable="true" id="descripcionTexto" style="text-align: left;">
            <?= htmlspecialchars($registro['DescripcionUnidadDocumental']) ?>
        </div>

        <div id="serieSeleccionada">
            No has seleccionado una serie
        </div>

        <!-- Selección de Series (checkbox-round) -->
        <div class="etiquetas mb-4" style="font-size: 1rem; background: #0f172a; padding: 20px; border-radius: 18px; border: 1px solid rgba(255, 255, 255, .05);">
            <div class="etiquetas-container d-flex flex-wrap align-items-center justify-content-center" style="gap: 12px;">
                <?php if (!empty($tipos)): ?>
                    <?php foreach ($tipos as $tipo): ?>
                        <?php $id = 'serie-' . preg_replace('/[^a-z0-9_-]/i', '_', $tipo); ?>
                        <div class="form-check form-check-inline" style="margin-bottom: 2px;">
                            <input class="form-check-input" type="radio" name="serie_radio" value="<?= htmlspecialchars($tipo) ?>" id="<?= htmlspecialchars($id) ?>" style="width: 1.1rem; height: 1.1rem; cursor: pointer;">
                            <label class="form-check-label text-white" for="<?= htmlspecialchars($id) ?>" style="margin-left: 5px; cursor: pointer;">
                                <?= htmlspecialchars(ucfirst($tipo)) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-muted">No hay series configuradas.</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex gap-3">

            <button
                id="guardar"
                class="btn btn-success btn-game w-100"
                disabled>
                ✅ Guardar
            </button>

            <button
                id="saltar"
                class="btn btn-secondary btn-game w-100">
                ⏭️ Saltar
            </button>

        </div>

        <div id="mensaje"></div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const btnGuardar = document.getElementById('guardar');
        const mensaje = document.getElementById('mensaje');
        const serieVisual = document.getElementById('serieSeleccionada');

        const radiosSerie = document.querySelectorAll('input[name="serie_radio"]');

        let serieSeleccionada = '';

        let puntos = parseInt(localStorage.getItem('puntos') || 0);
        let racha = parseInt(localStorage.getItem('racha') || 0);

        document.getElementById('puntos').innerText = puntos;
        document.getElementById('racha').innerText = racha;

        radiosSerie.forEach(radio => {
            radio.addEventListener('change', () => {
                if (radio.checked) {
                    serieSeleccionada = radio.value;
                    serieVisual.innerHTML = '📁 ' + serieSeleccionada;
                    btnGuardar.disabled = false;
                    btnGuardar.focus();
                }
            });
        });

        async function guardarRegistro() {

            if (!serieSeleccionada) {

                mensaje.innerHTML = '⚠️ Debes seleccionar una serie';

                return;

            }

            btnGuardar.disabled = true;

            btnGuardar.innerHTML = 'Guardando...';

            try {

                const fd = new FormData();

                fd.append('id', '<?= $registro['id'] ?>');
                fd.append('serie', serieSeleccionada);
                fd.append('descripcion', document.getElementById('descripcionTexto').innerText.trim());
                fd.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');

                const response = await fetch('assign_serie.php', {
                    method: 'POST',
                    body: fd
                });

                const text = await response.text();

                const data = JSON.parse(text);

                if (data.success) {

                    puntos += 10;
                    racha += 1;

                    localStorage.setItem('puntos', puntos);
                    localStorage.setItem('racha', racha);

                    mensaje.innerHTML = '✅ Registro guardado';

                    setTimeout(() => {
                        location.reload();
                    }, 300);

                } else {

                    mensaje.innerHTML = '❌ ' + data.message;

                    btnGuardar.disabled = false;

                    btnGuardar.innerHTML = '✅ Guardar';

                }

            } catch (error) {

                console.error(error);

                mensaje.innerHTML = '❌ Error de conexión';

                btnGuardar.disabled = false;

                btnGuardar.innerHTML = '✅ Guardar';

            }

        }

        btnGuardar.addEventListener('click', guardarRegistro);

        document.getElementById('saltar').addEventListener('click', () => {

            racha = 0;

            localStorage.setItem('racha', 0);

            location.reload();

        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (serieSeleccionada) {
                    guardarRegistro();
                }
            }
        });
    </script>

</body>

</html>
<?php ob_end_flush(); ?>