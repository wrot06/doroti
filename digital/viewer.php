<?php
declare(strict_types=1);
ob_start();
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
AuthMiddleware::initSession();
require_once "../rene/conexion3.php";

/* ================== AUTH ================== */
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: ../login/login.php");
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header("Location: ../login/login.php");
    exit;
}

/* ================== PARAMETERS ================== */
$doc_id = (int)($_GET['id'] ?? 0);
if ($doc_id <= 0) {
    die("ID de documento no válido.");
}

// Obtener detalles del documento para el título y validación
$stmt = $conec->prepare("
    SELECT d.id, d.titulo_documento, d.tipo, td.nombre AS tipo_nombre
    FROM documentos d
    LEFT JOIN tipo_documental td ON d.tipo = td.id
    WHERE d.id = ? AND d.user_id = ? AND d.estado = 'activo'
    LIMIT 1
");
$stmt->bind_param("ii", $doc_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$doc = $res->fetch_assoc();
$stmt->close();

if (!$doc) {
    die("El documento no existe o no tiene permisos para visualizarlo.");
}

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Libro - <?= h($doc['titulo_documento']) ?></title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #1e293b;
            color: #f1f5f9;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header-bar {
            background-color: #0f172a;
            border-bottom: 1px solid #334155;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }

        .header-title {
            font-size: 1.1rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 60%;
        }

        .viewer-container {
            flex-grow: 1;
            display: flex;
            position: relative;
            padding: 20px;
            overflow: auto;
            min-height: 0;
        }

        .viewer-container.zoom-active {
            cursor: grab;
        }

        .viewer-container.zoom-grabbing {
            cursor: grabbing !important;
        }
        
        /* Flipbook styling */
        #flipbook-wrapper {
            margin: auto;
            position: relative;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
            border-radius: 4px;
            background-color: #334155;
            transition: none;
        }

        #flipbook {
            margin: 0 auto;
        }

        .page {
            background-color: #ffffff;
            width: 100%;
            height: 100%;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        .canvas-container {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        canvas {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Controls bar */
        .controls-bar {
            background-color: #0f172a;
            border-top: 1px solid #334155;
            padding: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            z-index: 1000;
            flex-wrap: wrap;
        }

        .btn-control-icon {
            border-radius: 50%;
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.1rem;
            color: #94a3b8;
            border-color: #334155;
            background: transparent;
        }

        .btn-control-icon:hover:not(:disabled) {
            color: #f1f5f9;
            background-color: #334155;
            border-color: #475569;
        }

        .btn-control-icon:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .zoom-counter {
            font-size: 0.95rem;
            font-weight: 500;
            color: #94a3b8;
            min-width: 55px;
            text-align: center;
        }

        .page-counter {
            font-size: 0.95rem;
            font-weight: 500;
            color: #94a3b8;
            min-width: 80px;
            text-align: center;
        }

        /* Loader */
        #loader-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            transition: opacity 0.4s ease;
        }

        .loader-spinner {
            width: 3rem;
            height: 3rem;
            border: 4px solid #334155;
            border-top: 4px solid #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn-control {
            border-radius: 20px;
            padding: 6px 16px;
            font-weight: 600;
        }

        /* Sound effect player */
        #page-flip-sound {
            display: none;
        }
    </style>
</head>
<body>

    <!-- Bar superior -->
    <header class="header-bar">
        <div class="header-title">
            <span class="badge bg-primary me-2"><?= h($doc['tipo_nombre'] ?? 'Documento') ?></span>
            <?= h($doc['titulo_documento']) ?>
        </div>
        <div class="d-flex gap-2">
            <a href="download.php?id=<?= $doc_id ?><?= isset($_GET['version']) ? '&version=' . (int)$_GET['version'] : '' ?>&download=1" class="btn btn-primary btn-sm btn-control" title="Descargar documento PDF original">
                <i class="bi bi-download me-1"></i> Descargar
            </a>
            <a href="documents.php" class="btn btn-outline-light btn-sm btn-control">
                <i class="bi bi-arrow-left me-1"></i> Volver a listado
            </a>
        </div>
    </header>

    <!-- Área de Visualización -->
    <div class="viewer-container">
        <!-- Pantalla de Carga -->
        <div id="loader-overlay">
            <div class="loader-spinner"></div>
            <h5 class="text-white mb-1">Cargando libro animado...</h5>
            <p id="loader-status" class="text-muted small">Descargando documento PDF...</p>
        </div>

        <!-- Contenedor del Libro -->
        <div id="flipbook-wrapper" class="d-none">
            <div id="flipbook">
                <!-- Las páginas se inyectarán dinámicamente -->
            </div>
        </div>
    </div>

    <!-- Barra de Controles -->
    <footer class="controls-bar">
        <!-- Controles de Navegación -->
        <div class="d-flex align-items-center gap-2">
            <button id="btn-prev" class="btn btn-primary btn-sm btn-control" disabled>
                <i class="bi bi-chevron-left me-1"></i> Anterior
            </button>
            <div class="page-counter mx-2">
                Pág. <span id="current-page">0</span> / <span id="total-pages">0</span>
            </div>
            <button id="btn-next" class="btn btn-primary btn-sm btn-control" disabled>
                Siguiente <i class="bi bi-chevron-right ms-1"></i>
            </button>
        </div>

        <!-- Divisor vertical (solo escritorio) -->
        <div class="vr bg-secondary mx-2 d-none d-md-block" style="height: 24px; width: 1px;"></div>

        <!-- Controles de Zoom -->
        <div class="d-flex align-items-center gap-2">
            <button id="btn-zoom-out" class="btn btn-control-icon" title="Alejar" disabled>
                <i class="bi bi-zoom-out"></i>
            </button>
            <span id="zoom-percent" class="zoom-counter">100%</span>
            <button id="btn-zoom-in" class="btn btn-control-icon" title="Acercar">
                <i class="bi bi-zoom-in"></i>
            </button>
            <button id="btn-zoom-reset" class="btn btn-outline-secondary btn-sm btn-control ms-1" title="Ajustar a Pantalla">
                Ajustar
            </button>
        </div>
    </footer>

    <!-- Audio del paso de página -->
    <audio id="page-sound" preload="auto">
        <source src="https://assets.mixkit.co/active_storage/sfx/2568/2568-84.wav" type="audio/wav">
    </audio>

    <!-- Scripts (jQuery, jQuery Migrate, Turn.js, PDF.js) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/jquery-migrate-3.4.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>

    <script>
        // Capturar errores JS en vivo para retroalimentación visual directa
        window.onerror = function(message, source, lineno, colno, error) {
            const status = document.getElementById('loader-status');
            if (status) {
                status.innerHTML = '<span class="text-danger">Fallo en script: ' + message + ' (' + source.split("/").pop() + ':' + lineno + ')</span>';
            }
        };
    </script>

    <script>
        // Configurar PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

        const docId = <?= $doc_id ?>;
        const version = <?= isset($_GET['version']) ? (int)$_GET['version'] : 0 ?>;
        const pdfUrl = 'download.php?id=' + docId + (version > 0 ? '&version=' + version : '');
        const pageSound = document.getElementById('page-sound');
        
        let pdfDoc = null;
        let flipbookInitialized = false;

        // Cargar el documento PDF
        pdfjsLib.getDocument(pdfUrl).promise.then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            const totalPages = pdfDoc.numPages;
            document.getElementById('total-pages').textContent = totalPages;
            document.getElementById('loader-status').textContent = 'Renderizando páginas (0 de ' + totalPages + ')...';
            
            // Creamos los contenedores para cada página en el DOM
            const flipbookDiv = $('#flipbook');
            for (let i = 1; i <= totalPages; i++) {
                const pageDiv = $('<div />', { 'class': 'page' });
                const containerDiv = $('<div />', { 'class': 'canvas-container' });
                const canvas = $('<canvas />', { 'id': 'canvas-page-' + i });
                
                containerDiv.append(canvas);
                pageDiv.append(containerDiv);
                flipbookDiv.append(pageDiv);
            }
            
            // Comenzamos la renderización secuencial
            renderPage(1);
        }).catch(function(error) {
            console.error('Error al cargar el PDF: ', error);
            document.getElementById('loader-status').innerHTML = '<span class="text-danger">Error al cargar o renderizar el PDF. Asegúrese de que el archivo existe y sea válido.</span>';
        });

        // Función secuencial para renderizar páginas en Canvas
        function renderPage(pageNum) {
            document.getElementById('loader-status').textContent = 'Renderizando página ' + pageNum + ' de ' + pdfDoc.numPages + '...';
            
            pdfDoc.getPage(pageNum).then(function(page) {
                const canvas = document.getElementById('canvas-page-' + pageNum);
                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                
                // Establecer una escala adecuada para la pantalla (1.5x o 2.0x para nitidez)
                const viewport = page.getViewport({ scale: 1.5 });
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                
                page.render(renderContext).promise.then(function() {
                    if (pageNum < pdfDoc.numPages) {
                        renderPage(pageNum + 1);
                    } else {
                        // Todas las páginas listas, inicializar turn.js
                        initFlipbook();
                    }
                });
            });
        }

        // Variables de Zoom y escala
        let currentZoom = 1.0;
        const zoomStep = 0.25;
        const minZoom = 1.0;
        const maxZoom = 3.0;
        let baseWidth = 0;
        let baseHeight = 0;

        // Inicializar el flipbook animado
        function initFlipbook() {
            // Obtener dimensiones ideales de la primera página renderizada para dimensionar el libro
            const sampleCanvas = document.getElementById('canvas-page-1');
            const pageWidth = sampleCanvas.width / 1.5; // reducir de la escala 1.5 a la visual
            const pageHeight = sampleCanvas.height / 1.5;
            
            // Dimensionar contenedor (una página a la vez)
            let displayMode = 'single';
            let widthMultiplier = 1;
            let containerWidth = pageWidth * widthMultiplier;
            let containerHeight = pageHeight;
            
            // Ajustar si la ventana es más pequeña
            const maxWidth = window.innerWidth - 60;
            const maxHeight = window.innerHeight - 180;
            
            let scale = Math.min(maxWidth / containerWidth, maxHeight / containerHeight);
            if (scale < 1) {
                containerWidth = Math.floor(containerWidth * scale);
                containerHeight = Math.floor(containerHeight * scale);
            }

            // Guardar dimensiones base para el zoom
            baseWidth = containerWidth;
            baseHeight = containerHeight;
            
            // Inicializar turn.js
            $('#flipbook').turn({
                width: containerWidth,
                height: containerHeight,
                elevation: 50,
                gradients: true,
                autoCenter: true,
                display: 'single', // Mostrar una sola página a la vez
                when: {
                    turning: function(event, page, view) {
                        // Reproducir sonido al cambiar de página
                        if (flipbookInitialized) {
                            pageSound.currentTime = 0;
                            pageSound.play().catch(e => console.log('Bloqueado audio auto-play'));
                        }
                    },
                    turned: function(event, page, view) {
                        document.getElementById('current-page').textContent = page;
                        
                        // Actualizar botones de navegación
                        document.getElementById('btn-prev').disabled = (page === 1);
                        document.getElementById('btn-next').disabled = (page === pdfDoc.numPages);
                    }
                }
            });
            
            // Mostrar visor y ocultar loader
            $('#loader-overlay').css('opacity', '0');
            setTimeout(() => {
                $('#loader-overlay').addClass('d-none');
                $('#flipbook-wrapper').removeClass('d-none');
                flipbookInitialized = true;
                
                // Forzar primer renderizado de controles
                document.getElementById('current-page').textContent = 1;
                document.getElementById('btn-prev').disabled = true;
                document.getElementById('btn-next').disabled = (pdfDoc.numPages <= 1);
            }, 400);
        }

        // Aplicar el factor de zoom
        function applyZoom() {
            const newWidth = Math.floor(baseWidth * currentZoom);
            const newHeight = Math.floor(baseHeight * currentZoom);
            
            // Actualizar tamaño en turn.js
            $('#flipbook').turn('size', newWidth, newHeight);
            
            // Actualizar interfaz
            document.getElementById('zoom-percent').textContent = Math.round(currentZoom * 100) + '%';
            
            // Habilitar/deshabilitar botones
            document.getElementById('btn-zoom-out').disabled = (currentZoom <= minZoom);
            document.getElementById('btn-zoom-in').disabled = (currentZoom >= maxZoom);
            
            // Actualizar clases de cursor en el contenedor
            const viewerContainer = document.querySelector('.viewer-container');
            if (currentZoom > 1.0) {
                viewerContainer.classList.add('zoom-active');
            } else {
                viewerContainer.classList.remove('zoom-active');
                // Restablecer posición de scroll al volver a 100%
                viewerContainer.scrollLeft = 0;
                viewerContainer.scrollTop = 0;
            }
        }

        // Controladores de zoom
        document.getElementById('btn-zoom-in').addEventListener('click', function() {
            if (currentZoom < maxZoom) {
                currentZoom = Math.min(maxZoom, currentZoom + zoomStep);
                applyZoom();
            }
        });

        document.getElementById('btn-zoom-out').addEventListener('click', function() {
            if (currentZoom > minZoom) {
                currentZoom = Math.max(minZoom, currentZoom - zoomStep);
                applyZoom();
            }
        });

        document.getElementById('btn-zoom-reset').addEventListener('click', function() {
            if (currentZoom !== 1.0) {
                currentZoom = 1.0;
                applyZoom();
            }
        });

        // Arrastrar para desplazar (Pan / Grab to scroll)
        let isDragging = false;
        let startX, startY;
        let scrollLeft, scrollTop;
        const viewerContainer = document.querySelector('.viewer-container');

        viewerContainer.addEventListener('mousedown', (e) => {
            if (currentZoom > 1.0) {
                isDragging = true;
                viewerContainer.classList.add('zoom-grabbing');
                startX = e.pageX - viewerContainer.offsetLeft;
                startY = e.pageY - viewerContainer.offsetTop;
                scrollLeft = viewerContainer.scrollLeft;
                scrollTop = viewerContainer.scrollTop;
            }
        });

        viewerContainer.addEventListener('mouseleave', () => {
            isDragging = false;
            viewerContainer.classList.remove('zoom-grabbing');
        });

        viewerContainer.addEventListener('mouseup', () => {
            isDragging = false;
            viewerContainer.classList.remove('zoom-grabbing');
        });

        viewerContainer.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            const x = e.pageX - viewerContainer.offsetLeft;
            const y = e.pageY - viewerContainer.offsetTop;
            const walkX = (x - startX);
            const walkY = (y - startY);
            viewerContainer.scrollLeft = scrollLeft - walkX;
            viewerContainer.scrollTop = scrollTop - walkY;
        });

        // Controladores de botones de navegación
        document.getElementById('btn-prev').addEventListener('click', function() {
            $('#flipbook').turn('previous');
        });

        document.getElementById('btn-next').addEventListener('click', function() {
            $('#flipbook').turn('next');
        });

        // Soporte para flechas del teclado
        document.addEventListener('keydown', function(e) {
            if (!flipbookInitialized) return;
            if (e.key === 'ArrowLeft') {
                $('#flipbook').turn('previous');
            } else if (e.key === 'ArrowRight') {
                $('#flipbook').turn('next');
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
