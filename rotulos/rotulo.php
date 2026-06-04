<?php
require_once __DIR__ . '/../rene/conexion3.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();


$dependencia_id  = $_SESSION['dependencia_id']  ?? null;
/* ================== AUTENTICACIÓN ================== */
if (empty($_SESSION['authenticated'])) {
    header('Location: ../login/login.php');
    exit;
}

if (isset($_POST['cerrar_seccion'])) {
    session_destroy();
    header("Location: ../login/login.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$oficina  = $_SESSION['oficina']  ?? null;
$dependencia_id  = $_SESSION['dependencia_id']  ?? null;

/* ================== CONSULTA CARPETAS ================== */
$sql = "SELECT * FROM carpetas WHERE Estado='C' AND dependencia_id = ? ORDER BY Caja DESC, Carpeta ASC";
$stmt = $conec->prepare($sql);
$stmt->bind_param("i", $dependencia_id);
$stmt->execute();
$resultado = $stmt->get_result();

/* ================== ÍNDICES DOCUMENTALES ================== */
$indices = [];
$tableName = getIndiceTableName($conec, (int)$dependencia_id);
$stmt2 = $conec->prepare("
    SELECT i.*, c.Caja, c.Carpeta
    FROM `$tableName` i
    INNER JOIN carpetas c ON c.id = i.carpeta_id
    WHERE c.dependencia_id = ?
");
$stmt2->bind_param("i", $dependencia_id);
$stmt2->execute();
$res = $stmt2->get_result();
while ($r = $res->fetch_assoc()) {
    $indices[$r['Caja']][$r['Carpeta']][] = $r;
}
/* ================== SESIÓN ================== */
require_once "../services/UserService.php";
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
    </style>
</head>

<body>

    <?php
    if (!defined('SECURE_ACCESS')) {
        define('SECURE_ACCESS', true);
    }
    $basePath = '../';
    $activePage = 'rotulos';
    require_once "../components/navbar.php";
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
                        <td><button class="accordion">v</button></td>
                        <td><?= htmlspecialchars($caja) ?></td>
                        <td><?= htmlspecialchars($carpeta) ?></td>
                        <td>
                            <b><?= htmlspecialchars($f['Serie']) ?></b>
                            <?php if ($f['Subs']): ?><br><small><?= htmlspecialchars($f['Subs']) ?></small><?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($f['Titulo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($f['FInicial']) ?></td>
                        <td><?= htmlspecialchars($f['FFinal']) ?></td>
                        <td><b><?= htmlspecialchars($f['Folios']) ?></b></td>

                        <td>
                            <form action="../pdf/RotuloCarpeta.php" method="post" target="_blank">
                                <button name="consulta" value="<?= $f['id'] ?>" class="btn btn-primary btn-sm">
                                    Carpeta <?= $carpeta ?>
                                </button>
                            </form>
                        </td>

                        <td>
                            <?php if ($carpeta === 1): ?>
                                <form action="../pdf/RotuloCaja.php" method="post" target="_blank">
                                    <button name="consulta" value="<?= $caja ?>" class="btn btn-primary btn-sm">
                                        Caja <?= $caja ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr class="panel" style="display:none">
                        <td colspan="11">
                            <form action="../pdf/Indice.php" method="post" target="_blank">
                                <input type="hidden" name="Carpeta" value="<?= $carpeta ?>">
                                <div class="d-grid gap-2 col-6 mx-auto">
                                    <button name="Caja" value="<?= $caja ?>" class="btn btn-info btn-sm">
                                        Índice Carpeta <?= $carpeta ?>
                                    </button>
                                </div>
                            </form>

                            <table class="mi-tabla2">
                                <?php foreach ($indices[$caja][$carpeta] ?? [] as $doc): ?>
                                    <tr>
                                        <td><i><?= htmlspecialchars($doc['DescripcionUnidadDocumental']) ?></i></td>
                                        <td><?= htmlspecialchars($doc['NoFolioInicio']) ?></td>
                                        <td><?= htmlspecialchars($doc['NoFolioFin']) ?></td>
                                        <td>
                                            <?php if (is_file(__DIR__ . '/../uploads/' . $doc['id'] . '.pdf') && $doc['Soporte'] === 'FD'): ?>
                                                <form action="download.php" method="get" target="_blank">
                                                    <button name="id2" value="<?= $doc['id'] ?>" class="btn btn-success btn-sm">
                                                        Ver PDF
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="idcargar.php" method="post" target="_blank">
                                                    <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <button class="btn btn-primary btn-sm">
                                                        Subir
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </td>
                    </tr>
                <?php endwhile; ?>

            </tbody>
        </table>
    </div>


    <script src="rotulo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>