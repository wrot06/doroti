<?php
/**
 * Plantilla de configuración de Base de Datos para doroti.
 * Copia este archivo como `db_config.php` y coloca tus credenciales reales.
 * 
 * ¡IMPORTANTE! Asegúrate de que `db_config.php` esté excluido de tu control de versiones
 * y de los análisis de SonarCloud.
 */

if (!defined('SECURE_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access forbidden.');
}

return [
    'db_host' => 'localhost',
    'db_user' => 'TU_USUARIO_DB',
    'db_pass' => 'TU_CONTRASEÑA_DB',
    'db_name' => 'TU_BASE_DATOS_DB',
];
