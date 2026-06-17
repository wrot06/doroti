<?php
declare(strict_types=1);
ob_start();

require_once "../rene/conexion3.php";
require_once "../middlewares/AuthMiddleware.php";
require_once "../services/UserService.php";
require_once "../config/version.php";

AuthMiddleware::initSession();
AuthMiddleware::checkAuth('../login/login.php');

$user_id = AuthMiddleware::validateUser();
$userService = new UserService($conec);
$userInfo = $userService->getUserInfo($user_id);
$usuario = $userInfo['username'];
$oficina = $userInfo['oficina'];
$userAvatar = '../' . $userService->getUserAvatar($user_id);

// Auto-registro de la nueva versión en la tabla de actualizaciones (self-healing)
try {
    $current_version = APP_VERSION;
    $check_sql = "SELECT id FROM actualizaciones WHERE version = ?";
    $stmt_check = $conec->prepare($check_sql);
    if ($stmt_check) {
        $stmt_check->bind_param("s", $current_version);
        $stmt_check->execute();
        $stmt_check->store_result();
        $exists = $stmt_check->num_rows > 0;
        $stmt_check->close();
        
        if (!$exists && $current_version === '1.4.3') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Resolución de errores de sesión y despliegue";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se corrigió el error HTTP 500 al iniciar sesión tras subir el proyecto a servidores de hosting compartido como InfinityFree.</li>
                    <li>Se implementó tolerancia a fallos en el sistema de tokens ('Recordarme') y rehasheo de contraseñas.</li>
                    <li>Se mejoró la captura y visualización de errores de base de datos en el formulario de login.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.4.4') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Corrección de avatar y diseño de migración de BD";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se corrigió la ruta del avatar del usuario conectado en la barra de navegación del módulo de Tablas.</li>
                    <li>Se diseñó el plan de migración de datos y optimización de base de datos a motores InnoDB con almacenamiento en archivos físicos.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.4.5') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Migración a InnoDB, almacenamiento en disco y auditoría";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se migró la base de datos a motores InnoDB y nombres de tablas normalizados en minúsculas.</li>
                    <li>Se eliminó el almacenamiento de PDFs binarios de la BD, extrayéndolos físicamente y registrando rutas relativas.</li>
                    <li>Se implementó seguridad de archivos .htaccess en la carpeta de documentos, previniendo descargas directas.</li>
                    <li>Se creó un sistema de trazabilidad de auditoría (tabla historial_acciones) integrado en subidas y descargas.</li>
                    <li>Se refactorizaron las consultas SQL de carpetas, índices y reportes mediante INNER JOINs para soportar el nuevo esquema.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.4.6') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Compatibilidad con servidores Linux y base de datos sensible a mayúsculas";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se corrigieron todas las consultas SQL que hacían referencia a tablas con nombres capitalizados para soportar la sensibilidad a mayúsculas y minúsculas en servidores Linux.</li>
                    <li>Se normalizó el uso de las tablas 'carpetas', 'indice_temp', 'indice_documental', 'documentos', 'serie', 'oficina_serie' y 'subs' en todas las consultas de la aplicación.</li>
                    <li>Se solucionó la visualización del archivo PDF de inventario de carpetas y el correcto funcionamiento de los módulos de administración y rótulos.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.4.7') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Visor de Libro Animado Interactivo, Controles de Zoom y Descarga Segura con Renombrado";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se implementó un visor de libro animado interactivo auto-alojado de una página (Turn.js + PDF.js).</li>
                    <li>Se agregaron controles interactivos de Zoom en el pie de página de 100% a 300% con navegación fija y soporte de arrastre para desplazamiento.</li>
                    <li>Se añadió un botón de descarga directa en la cabecera fija del visor.</li>
                    <li>Se habilitó la descarga segura de versiones históricas específicas mediante parámetros validados.</li>
                    <li>Se implementó el renombrado dinámico y sanitizado automático del archivo descargado bajo el formato '[Tipo Documental] [Título].pdf'.</li>
                    <li>Se eliminó target='_blank' en la vista previa del listado y versiones para navegar fluidamente dentro de la misma pestaña.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.4.8') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Corrección del Clasificador (Juego) y Filtro Dinámico por Dependencia de Usuario";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se corrigió el error SQL que impedía abrir la pantalla de juego del clasificador mediante un INNER JOIN con la tabla de carpetas.</li>
                    <li>Se implementó el filtrado dinámico en la selección de tipos documentales (series) según la dependencia asignada al usuario conectado en la sesión.</li>
                    <li>Se restringió la asignación de registros a clasificar para que pertenezcan a la misma dependencia que el usuario logueado.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.4.9') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Unificación de Avatares en Admin y Restauración de Asignación de Usuarios";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se corrigió la carga del avatar del usuario actual y su información de oficina en todas las vistas de administración mediante la reutilización homogénea de UserService.</li>
                    <li>Se restauró y diseñó completamente la interfaz interactiva para asignar o reasignar usuarios a carpetas (asignar_usuario_carpeta.php) conectándola con su API respectiva.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.5.0') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Unificación Global de Carga de Avatares y Datos de Sesión";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se eliminaron las consultas y lógica manual de carga de fotos de perfil en los módulos de buscador, rótulos, subida de documentos y listado de documentos digitales.</li>
                    <li>Se centralizó de forma definitiva la carga de avatares y oficinas a través del método optimizado UserService en todo el proyecto.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.5.1') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Segmentación de Índices por Dependencia y Tablas Dinámicas";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se dividió la tabla global 'indice_documental' en tablas dedicadas por dependencia (ej. 'indice_documental_dep_6', 'indice_documental_dep_9') para mejorar la velocidad de exportación y la escalabilidad del sistema.</li>
                    <li>Se implementaron funciones de enrutamiento dinámico en 'conexion3.php' para resolver automáticamente la tabla a consultar basándose en la dependencia, el ID de carpeta o el ID de documento.</li>
                    <li>Se refactorizaron 12 archivos de la aplicación (administración, rótulos, PDF, buscador, clasificador, estadísticas) para operar dinámicamente con las nuevas tablas.</li>
                    <li>Se integró la opción en la creación de dependencias para decidir si se crea y asocia una tabla de índices dedicada a la nueva oficina.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.5.2') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Simplificación de Rótulos y Limpieza de Subida de Documentos";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se eliminó por completo la opción y el botón para subir documentos ('Subir') en la visualización de Rótulos (rotulo.php).</li>
                    <li>Se retiraron flujos asociados hacia el script idcargar.php para simplificar el módulo y mantenerlo solo de lectura y descarga de PDFs existentes.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.5.3') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Filtro de Búsqueda por Tipo Documental en el Buscador Global";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se agregó un filtro opcional por Tipo Documental en la barra de búsqueda de 'buscador/buscador.php'.</li>
                    <li>Se optimizó la consulta UNION para incluir la serie y filtrar de forma condicional, con un límite de seguridad de 250 resultados para prevenir sobrecargas.</li>
                    <li>Se rediseñó el formulario utilizando Bootstrap 5 para una disposición visual más limpia en cuadrícula.</li>
                    <li>Se añadió la columna 'Tipo Documental' en la tabla de resultados con un badge descriptivo.</li>
                    <li>Se implementó validación en JavaScript para limpiar correctamente el dropdown y evitar búsquedas completamente vacías.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.5.4') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Restricción de Acceso Administrador al Módulo Digital";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se restringió el acceso a la vista 'digital/documents.php' de forma exclusiva para usuarios con rol 'admin'.</li>
                    <li>Se ocultó el enlace a 'Digital' en el menú de navegación (navbar.php) para aquellos usuarios que no posean rol administrativo.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.5.5') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Auditoría de Seguridad y Protección contra Redirecciones de iFastNet";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se implementó control estricto del búfer de salida (ob_start / ob_end_flush) al inicio de todos los scripts principales.</li>
                    <li>Se unificó el inicio de sesión a través de AuthMiddleware configurando cookies compatibles (httponly, samesite y secure dinámico).</li>
                    <li>Se añadió validación CSRF a todas las peticiones POST y llamadas AJAX FormData.</li>
                    <li>Se corrigieron redirecciones y errores de sintaxis en el visor digital, rótulos, tablas, juego y actualizaciones.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.5.6') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Compatibilidad con PHP 7.4 y Normalización de Índices Temporales";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se habilitó compatibilidad con versiones anteriores de PHP (PHP 7.4+) eliminando los constructores match, tipo de unión (union types) e indicación de tipo mixed de PHP 8.</li>
                    <li>Se normalizó la tabla de índices temporales eliminando columnas redundantes (Caja, Carpeta, dependencia_id) en las consultas SQL e insertando registros únicamente asociados a carpeta_id.</li>
                    <li>Se mejoró la robustez de las conexiones a base de datos aplicando límites de tiempo de espera (timeout de 5 segundos) y unificando el inicio de sesión con AuthMiddleware en los reportes e índices PDF.</li>
                    <li>Se corrigieron errores menores de sintaxis en el clasificador de rótulos y validación de consultas UNION vacías en el buscador global.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.5.7') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Diagnóstico CSRF Inteligente y Control de Cookies de Sesión";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se implementó detección proactiva de bloqueo de cookies y sesiones inactivas en el inicio de sesión y recuperación de contraseña.</li>
                    <li>Se añadieron advertencias específicas y detalladas para el uso de dominios locales sin puntos (como http://doroti/), guiando al usuario a utilizar http://localhost/ o http://127.0.0.1/ para garantizar la compatibilidad con navegadores modernos como Chrome.</li>
                    <li>Se optimizó el flujo de inicio de sesión de los usuarios Rene y Mariana mediante la normalización de contraseñas seguras y búsquedas insensibles a mayúsculas.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.5.8') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Integración de IA (Gemini API) para Corrección Ortográfica y Resúmenes";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se implementó un script de consola (CLI) para la limpieza masiva por lotes (batching) de la ortografía y el resumen de textos de las descripciones documentales.</li>
                    <li>Se integró un botón interactivo 'Corregir con IA' en el modal de edición del índice de carpetas (carpeta/indice.php).</li>
                    <li>Se creó un endpoint API interno (rene/corregir_descripcion_ia.php) conectado de forma segura con la API de Gemini (modelo gemini-2.5-flash).</li>
                    <li>Se definieron reglas de IA estrictas para omitir puntos y dos puntos en las correcciones y mantener intactos los prefijos antes del primer carácter ':'.</li>
                    <li>Se agregaron parámetros de versión a la inclusión de scripts frontend para evitar problemas de caché del navegador.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.5.9') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Módulo de Corrección y Búsqueda Multitabla para Administradores";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se implementó el nuevo módulo 'Corrección' para buscar palabras erróneas en el campo DescripciónUnidadDocumental de todas las tablas dedicadas.</li>
                    <li>Se automatizó la consulta dinámica para identificar todas las tablas del tipo 'indice_documental_dep_*' presentes y futuras.</li>
                    <li>Se restringió la visualización del módulo en el menú de navegación y su acceso en el servidor de forma exclusiva para usuarios con rol 'admin'.</li>
                    <li>Se desarrolló una interfaz de usuario optimizada con resaltado de términos buscados, reemplazo asíncrono vía AJAX con micro-botón de guardado y animaciones de desvanecimiento para registros completados.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.6.0') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Clasificador Inline y Corrección Directa de Descripciones";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se reemplazó el modal de selección de series por un grupo de radios circulares ('checkbox-round') integrados directamente en la pantalla de juego del clasificador.</li>
                    <li>Se implementó el destaque visual interactivo (color celeste, negrita y subrayado) para la opción elegida.</li>
                    <li>Se habilitó la corrección directa del texto de descripción en pantalla a través de la propiedad contenteditable.</li>
                    <li>Se optimizó el script de guardado asíncrono para capturar y almacenar las correcciones descritas en la base de datos.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.6.1') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Restricción de Acceso Administrador al Módulo IA";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se restringió el acceso al Módulo IA de corrección masiva (ia/index.php y ia/guardar_descripcion.php) de forma exclusiva para usuarios con rol 'admin'.</li>
                    <li>Se ocultó el enlace al Módulo IA en la barra de navegación (navbar.php) para aquellos usuarios que no posean rol administrativo.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        if (!$exists && $current_version === '1.6.2') {
            $insert_sql = "INSERT INTO actualizaciones (titulo, version, fecha_lanzamiento, descripcion, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conec->prepare($insert_sql);
            if ($stmt_insert) {
                $titulo = "Límite Estricto de Longitud IA y Ocultación Asíncrona";
                $fecha = date('Y-m-d');
                $descripcion = "<ul>
                    <li>Se modificó el prompt del modelo de IA (Gemini) en corregir_descripcion_ia.php para garantizar que la descripción corregida final tenga un límite estricto de 300 caracteres.</li>
                    <li>Se implementó animación de salida (slideUp y fadeOut) para las tarjetas procesadas y guardadas del listado en el Módulo IA.</li>
                    <li>Se automatizó el decremento en tiempo real del contador total de coincidencias y la recarga automática del listado al quedar vacío.</li>
                </ul>";
                $estado = 1;
                $stmt_insert->bind_param("ssssi", $titulo, $current_version, $fecha, $descripcion, $estado);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
    }
} catch (Throwable $e) {
    error_log("Error al auto-registrar la actualización: " . $e->getMessage());
}

// Obtener actualizaciones
$sql = "SELECT * FROM actualizaciones WHERE estado = 1 ORDER BY fecha_lanzamiento DESC, id DESC";
$resultado = $conec->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizaciones - Doroti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 80px;
        }
        .timeline {
            position: relative;
            padding: 20px 0;
            margin: 0;
            list-style: none;
        }
        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 20px;
            width: 4px;
            background: #e9ecef;
            border-radius: 2px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding-left: 50px;
        }
        .timeline-marker {
            position: absolute;
            top: 0;
            left: 10px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #0d6efd;
            border: 4px solid #fff;
            box-shadow: 0 0 0 2px #0d6efd;
        }
        .timeline-content {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .timeline-content h3 {
            margin-top: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #2b3440;
        }
        .timeline-content .date {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .version-badge {
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 20px;
            background: #e3f2fd;
            color: #0d6efd;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .description ul {
            padding-left: 20px;
            margin-bottom: 0;
        }
        .description li {
            margin-bottom: 8px;
            color: #4b5563;
        }
        .description li:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <?php
    if (!defined('SECURE_ACCESS')) {
        define('SECURE_ACCESS', true);
    }
    $basePath = '../';
    $activePage = 'novedades';
    require_once "../components/navbar.php";
    ?>

    <main class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-5 mt-3">
                    <h2 class="fw-bold mb-0 text-dark" style="font-size: 2rem;"><i class="bi bi-stars text-warning me-2"></i>Registro de Actualizaciones</h2>
                    <span class="text-muted">Historial de cambios de Doroti</span>
                </div>

                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <ul class="timeline">
                        <?php while ($row = $resultado->fetch_assoc()): ?>
                            <li class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                                        <h3 class="mb-0"><?= htmlspecialchars($row['titulo']) ?></h3>
                                        <span class="version-badge">🚀 <?= htmlspecialchars($row['version']) ?></span>
                                    </div>
                                    <div class="date"><i class="bi bi-calendar-event me-1"></i> <?= date('d M Y', strtotime($row['fecha_lanzamiento'])) ?></div>
                                    <div class="description mt-3 pt-3 border-top">
                                        <?= $row['descripcion'] ?>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info text-center py-4 rounded-4 shadow-sm">
                        <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                        <span class="fs-5">No hay actualizaciones registradas aún.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>
