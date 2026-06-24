<?php
declare(strict_types=1);
ob_start();
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
AuthMiddleware::initSession();
require_once __DIR__ . '/../rene/conexion3.php';

ini_set('display_errors', '0');
error_reporting(E_ALL);

/* ================== AUTENTICACIÓN ================== */
if (empty($_SESSION['authenticated'])) {
    header('Location: ../login/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthMiddleware::checkCsrf();
    if (isset($_POST['cerrar_seccion'])) {
        session_destroy();
        header("Location: ../login/login.php");
        exit;
    }
}

$oficina  = $_SESSION['oficina']  ?? null;
$dependencia_id  = $_SESSION['dependencia_id']  ?? null;

/* ================== CONSULTA CARPETAS ================== */
$sql = "SELECT * FROM carpetas WHERE Estado='C' AND dependencia_id = ? ORDER BY Caja DESC, Carpeta ASC";
$stmt = $conec->prepare($sql);
$stmt->bind_param("i", $dependencia_id);
$stmt->execute();
$resultado = $stmt->get_result();

/* ================== SESIÓN ================== */
require_once __DIR__ . "/../services/UserService.php";
$userService = new UserService($conec);
$user_id = (int)($_SESSION['user_id'] ?? 0);
$userInfo = $userService->getUserInfo($user_id);
$usuario = $userInfo['username'];
$oficina = $userInfo['oficina'];
$userAvatar = '../' . $userService->getUserAvatar($user_id);

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rotulos</title>
    <link rel="stylesheet" href="estilos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            padding-top: 50px
        }

        .hidden {
            display: none !important
        }
        
        .transition-icon {
            transition: transform 0.3s ease;
            display: inline-block;
        }
        
        .transition-icon.rotated {
            transform: rotate(180deg);
        }
    </style>
</head>

<body>

    <?php
    if (!defined('SECURE_ACCESS')) {
        define('SECURE_ACCESS', true);
    }
    $basePath = '../';
    $activePage = 'rotulos';
    require_once __DIR__ . "/../components/navbar.php";
    ?>



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
            <tbody>

                <?php while ($f = $resultado->fetch_assoc()):
                    $bg = ($f['Caja'] % 2 == 0) ? "#e7f4ff" : "#fff";
                    $caja = (int)$f['Caja'];
                    $carpeta = (int)$f['Carpeta'];
                ?>
                    <tr style="background:<?= $bg ?>">
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary accordion-btn d-flex align-items-center justify-content-center mx-auto"
                                    style="width: 28px; height: 28px; padding: 0; border-radius: 50%;"
                                    onclick="toggleAccordion(this, <?= $f['id'] ?>)">
                                <i class="bi bi-chevron-down transition-icon"></i>
                            </button>
                        </td>
                        <td><?= h($caja) ?></td>
                        <td><?= h($carpeta) ?></td>
                        <td>
                            <b><?= h($f['Serie']) ?></b>
                            <?php if ($f['Subs']): ?><br><small><?= h($f['Subs']) ?></small><?php endif; ?>
                        </td>
                        <td><?= h($f['Titulo'] ?? '') ?></td>
                        <td><?= h($f['FInicial']) ?></td>
                        <td><?= h($f['FFinal']) ?></td>
                        <td><b><?= h($f['Folios']) ?></b></td>

                        <td>
                            <form action="../pdf/RotuloCarpeta.php" method="post" target="_blank">
                                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token'] ?? '') ?>">
                                <button name="consulta" value="<?= $f['id'] ?>" class="btn btn-primary btn-sm">
                                    Carpeta <?= $carpeta ?>
                                </button>
                            </form>
                        </td>

                        <td>
                            <?php if ($carpeta === 1): ?>
                                <form action="../pdf/RotuloCaja.php" method="post" target="_blank">
                                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token'] ?? '') ?>">
                                    <button name="consulta" value="<?= $caja ?>" class="btn btn-primary btn-sm">
                                        Caja <?= $caja ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr class="panel" style="display:none">
                        <td colspan="11" class="p-3 bg-light">
                            <div class="border rounded bg-white shadow-sm p-3">
                                <!-- Botón para descargar PDF del índice -->
                                <form action="../pdf/Indice.php" method="post" target="_blank" class="mb-3">
                                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="Carpeta" value="<?= $carpeta ?>">
                                    <div class="d-grid gap-2 col-md-4 mx-auto">
                                        <button name="Caja" value="<?= $caja ?>" class="btn btn-info text-white btn-sm fw-bold">
                                            <i class="bi bi-file-earmark-pdf me-2"></i>Descargar PDF del Índice
                                        </button>
                                    </div>
                                </form>

                                <!-- Contenedor dinámico del índice documental -->
                                <div class="indice-contenido" id="indice-contenido-<?= $f['id'] ?>">
                                    <!-- Se cargará mediante AJAX -->
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>

            </tbody>
        </table>
    </div>


    <script>
        function toggleAccordion(button, carpetaId) {
            const row = button.closest('tr');
            if (!row) return;
            
            // Buscar el siguiente elemento hermano que tenga la clase 'panel'
            // Esto evita que se rompa si InfinityFree inyecta scripts o anuncios entre las filas
            let panel = row.nextElementSibling;
            while (panel && !panel.classList.contains('panel')) {
                panel = panel.nextElementSibling;
            }
            if (!panel) return;
            
            const icon = button.querySelector('.transition-icon');
            const contentDiv = document.getElementById('indice-contenido-' + carpetaId);
            
            // Si el panel está cerrado, abrirlo
            if (panel.style.display === "none" || panel.style.display === "") {
                panel.style.display = "table-row";
                if (icon) icon.classList.add('rotated');
                
                // Cargar datos por AJAX si aún no se han cargado
                if (contentDiv && contentDiv.getAttribute('data-loaded') !== 'true') {
                    loadIndiceData(carpetaId, contentDiv);
                }
            } else {
                // Si está abierto, cerrarlo
                panel.style.display = "none";
                if (icon) icon.classList.remove('rotated');
            }
        }

        function loadIndiceData(carpetaId, container) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    <span class="fw-semibold">Cargando índice documental...</span>
                </div>
            `;
            
            fetch('get_indice_ajax.php?carpeta_id=' + carpetaId)
                .then(response => {
                    if (!response.ok) throw new Error('Error al cargar datos');
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        container.innerHTML = `<div class="alert alert-danger py-2 m-0">${data.error}</div>`;
                        return;
                    }
                    
                    const docs = data.documentos;
                    if (docs.length === 0) {
                        container.innerHTML = `
                            <div class="text-center py-3 text-muted bg-light border rounded">
                                <i class="bi bi-info-circle me-2"></i>No hay documentos registrados en esta carpeta.
                            </div>
                        `;
                        container.setAttribute('data-loaded', 'true');
                        return;
                    }
                    
                    let html = `
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-sm align-middle bg-white m-0" style="font-size: 0.85rem;">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th class="px-3 py-2">Unidad Documental</th>
                                        <th class="text-center py-2" style="width: 100px;">Folio Inicio</th>
                                        <th class="text-center py-2" style="width: 100px;">Folio Fin</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    docs.forEach(doc => {
                        html += `
                            <tr>
                                <td class="text-start px-3 text-dark"><i>${escapeHtml(doc.descripcion)}</i></td>
                                <td class="text-center fw-bold text-secondary">${escapeHtml(doc.folio_inicio)}</td>
                                <td class="text-center fw-bold text-secondary">${escapeHtml(doc.folio_fin)}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    
                    container.innerHTML = html;
                    container.setAttribute('data-loaded', 'true');
                })
                .catch(err => {
                    container.innerHTML = `
                        <div class="alert alert-danger py-2 m-0 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-exclamation-triangle-fill me-2"></i>Error al cargar los documentos.</span>
                            <button class="btn btn-sm btn-danger fw-bold" onclick="retryLoad(${carpetaId})">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reintentar
                            </button>
                        </div>
                    `;
                });
        }

        function retryLoad(carpetaId) {
            const container = document.getElementById('indice-contenido-' + carpetaId);
            if (container) loadIndiceData(carpetaId, container);
        }

        function escapeHtml(string) {
            return String(string).replace(/[&<>"']/g, function (s) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[s];
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>