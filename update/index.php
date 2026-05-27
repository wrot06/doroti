<?php
require_once "../rene/conexion3.php";
require_once "../middlewares/AuthMiddleware.php";

AuthMiddleware::initSession();
AuthMiddleware::checkAuth('../login/login.php');


$sql = "
SELECT 
it.id,
it.dependencia_id,
it.Caja,
it.Carpeta,
it.DescripcionUnidadDocumental,
it.paginas,
d.nombre AS dependencia_nombre
FROM IndiceDocumental it
LEFT JOIN dependencias d ON it.dependencia_id=d.id
WHERE it.serie IS NULL OR TRIM(it.serie)=''
ORDER BY RAND()
LIMIT 1
";

$res = $conec->query($sql);

$registro = $res->fetch_assoc();

if (!$registro) {

    die("
<body style='background:#0f172a;color:white;display:flex;justify-content:center;align-items:center;height:100vh;font-family:Arial'>
<h1>🎉 No hay registros pendientes</h1>
</body>
");
}

$depId = (int)$registro['dependencia_id'];

$sqlTipos = "
SELECT nombre
FROM tipo_documental
WHERE estado=1
AND dependencia_id= ?
ORDER BY nombre ASC
";

$stmtTipos = $conec->prepare($sqlTipos);
$stmtTipos->bind_param("i", $depId);
$stmtTipos->execute();
$resTipos = $stmtTipos->get_result();

$tipos = [];

while ($row = $resTipos->fetch_assoc()) {
    $tipos[] = $row['nombre'];
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

        <div class="descripcion">
            <?= htmlspecialchars($registro['DescripcionUnidadDocumental']) ?>
        </div>

        <div id="serieSeleccionada">
            No has seleccionado una serie
        </div>

        <button
            id="btnAbrirModal"
            class="btn btn-primary btn-game w-100 mb-3"
            data-bs-toggle="modal"
            data-bs-target="#seriesModal">
            📂 Seleccionar Serie
        </button>

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

    <div class="modal fade" id="seriesModal" tabindex="-1">

        <div class="modal-dialog modal-xl modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header border-0">

                    <h3>Selecciona una Serie</h3>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>

                </div>

                <div class="modal-body">

                    <div class="series-grid">

                        <?php foreach ($tipos as $tipo): ?>

                            <button
                                type="button"
                                class="serie-btn"
                                data-serie="<?= htmlspecialchars($tipo) ?>">
                                <?= htmlspecialchars($tipo) ?>
                            </button>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const btnGuardar = document.getElementById('guardar');
        const mensaje = document.getElementById('mensaje');
        const serieVisual = document.getElementById('serieSeleccionada');
        const modalElement = document.getElementById('seriesModal');
        const btnAbrirModal = document.getElementById('btnAbrirModal');

        const botonesSerie = document.querySelectorAll('.serie-btn');

        let serieSeleccionada = '';
        let modalCerrandose = false;

        let puntos = parseInt(localStorage.getItem('puntos') || 0);
        let racha = parseInt(localStorage.getItem('racha') || 0);

        document.getElementById('puntos').innerText = puntos;
        document.getElementById('racha').innerText = racha;

        modalElement.addEventListener('hidden.bs.modal', () => {

            modalCerrandose = false;

            btnAbrirModal.blur();

            btnGuardar.focus();

        });

        botonesSerie.forEach(btn => {

            btn.addEventListener('click', () => {

                serieSeleccionada = btn.dataset.serie;

                serieVisual.innerHTML = '📁 ' + serieSeleccionada;

                btnGuardar.disabled = false;

                modalCerrandose = true;

                const modal = bootstrap.Modal.getInstance(modalElement);

                modal.hide();

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

                if (modalCerrandose) {
                    return;
                }

                const modalAbierto = document.querySelector('.modal.show');

                if (modalAbierto) {
                    return;
                }

                if (serieSeleccionada) {
                    guardarRegistro();
                }

            }

        });
    </script>

</body>

</html>