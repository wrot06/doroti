<?php
// Verifica si se han enviado los cambios
if (isset($_GET['cambios'])) {
    $cambios = json_decode(urldecode($_GET['cambios']), true);
} else {
    $cambios = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Cambios</title>
    <link rel="stylesheet" href="style.css"> <!-- Puedes añadir un estilo CSS si lo deseas -->
</head>
<body>
    <h1>Cambios Realizados</h1>
    <?php if (!empty($cambios)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nuevo ID2</th>
                    <th>Página Inicio</th>
                    <th>Página Final</th>
                    <th>Número de Páginas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cambios as $cambio): ?>
                    <tr>
                        <td><?= htmlspecialchars($cambio['id'], ENT_QUOTES); ?></td>
                        <td><?= htmlspecialchars($cambio['id2'], ENT_QUOTES); ?></td>
                        <td><?= htmlspecialchars($cambio['paginaInicio'], ENT_QUOTES); ?></td>
                        <td><?= htmlspecialchars($cambio['paginaFinal'], ENT_QUOTES); ?></td>
                        <td><?= htmlspecialchars($cambio['paginas'], ENT_QUOTES); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No se realizaron cambios.</p>
    <?php endif; ?>
</body>
</html>
