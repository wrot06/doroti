<?php
declare(strict_types=1);
ob_start();

require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

// Inicializar la sesión usando nuestra lógica
AuthMiddleware::initSession();

// Incrementar contador para verificar persistencia
$_SESSION['test_counter'] = ($_SESSION['test_counter'] ?? 0) + 1;

$savePath = session_save_path();
$isPathWritable = is_writable(empty($savePath) ? sys_get_temp_dir() : $savePath);
$tempDir = sys_get_temp_dir();
$isTempWritable = is_writable($tempDir);

// Detectar HTTPS y headers
$isSecure = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) 
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Sesiones - Doroti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f5f9; font-family: system-ui, sans-serif; color: #334155; }
        .card { border-radius: 12px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body class="py-5">
    <div class="container" style="max-width: 700px;">
        <div class="text-center mb-4">
            <h1 class="fw-bold">Diagnóstico de Sesiones PHP</h1>
            <p class="text-muted">Carga esta página y luego presiona <strong>F5 (Recargar)</strong> para probar si se guardan los datos.</p>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="card-title fw-semibold mb-4">Estado del Servidor y Cookies</h3>
                
                <table class="table table-bordered align-middle">
                    <tbody>
                        <tr>
                            <th>Contador de Pruebas:</th>
                            <td>
                                <span class="badge bg-primary fs-5"><?= $_SESSION['test_counter'] ?></span>
                                <?php if ($_SESSION['test_counter'] > 1): ?>
                                    <span class="text-success ms-2 fw-semibold">✔ ¡Las sesiones están funcionando!</span>
                                <?php else: ?>
                                    <span class="text-danger ms-2 fw-semibold">✖ Si recargas la página y sigue en 1, las sesiones están rotas.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>ID de Sesión Actual:</th>
                            <td><code><?= session_id() ?></code></td>
                        </tr>
                        <tr>
                            <th>Protocolo de Conexión:</th>
                            <td><?= $isSecure ? '🔒 HTTPS (Seguro)' : '🔓 HTTP (Inseguro)' ?></td>
                        </tr>
                        <tr>
                            <th>Directorio de Sesiones:</th>
                            <td><code><?= htmlspecialchars($savePath) ?></code></td>
                        </tr>
                        <tr>
                            <th>¿Escribible?:</th>
                            <td>
                                <?= $isPathWritable 
                                    ? '<span class="badge bg-success">Sí (Escribible)</span>' 
                                    : '<span class="badge bg-danger">No (Bloqueado)</span>' 
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Directorio Temporal (/tmp):</th>
                            <td><code><?= htmlspecialchars($tempDir) ?></code> (<?= $isTempWritable ? 'Escribible' : 'Bloqueado' ?>)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="card-title fw-semibold mb-3">Cookies Enviadas por tu Navegador</h3>
                <?php if (!empty($_COOKIE)): ?>
                    <pre class="bg-light p-3 rounded border"><code><?php print_r($_COOKIE); ?></code></pre>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">No se detectaron cookies enviadas. Asegúrate de tener las cookies habilitadas en tu navegador.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title fw-semibold text-primary mb-3">Soluciones de Problemas Comunes</h3>
                <ul>
                    <li class="mb-2"><strong>Si el contador no pasa de 1 tras recargar:</strong> Tu navegador está rechazando las cookies (especialmente la cookie de sesión <code>PHPSESSID</code>) o el servidor tiene deshabilitado el guardado de sesiones.</li>
                    <li class="mb-2"><strong>Si estás en modo incógnito:</strong> Algunos navegadores desactivan totalmente el almacenamiento de cookies de sesión locales de terceros o específicas en este modo. Prueba en una pestaña normal con los bloqueadores desactivados.</li>
                    <li><strong>Error de CSRF en login:</strong> Ocurre exactamente porque el navegador no puede enviar la cookie de sesión de vuelta al servidor en la petición POST, por lo que el servidor cree que es una petición maliciosa.</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>
