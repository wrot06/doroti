// rotulo.js

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
                                <th class="text-center py-2" style="width: 120px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            docs.forEach(doc => {
                let actionHtml = '';
                if (doc.has_pdf) {
                    actionHtml = `
                        <form action="download.php" method="get" target="_blank" class="m-0">
                            <button name="id2" value="${doc.id}" class="btn btn-success btn-sm py-1 px-2 fw-semibold w-100">
                                <i class="bi bi-file-earmark-pdf me-1"></i>Ver PDF
                            </button>
                        </form>
                    `;
                } else {
                    actionHtml = `<span class="text-muted small">Físico (No PDF)</span>`;
                }
                
                html += `
                    <tr>
                        <td class="text-start px-3 text-dark"><i>${escapeHtml(doc.descripcion)}</i></td>
                        <td class="text-center fw-bold text-secondary">${escapeHtml(doc.folio_inicio)}</td>
                        <td class="text-center fw-bold text-secondary">${escapeHtml(doc.folio_fin)}</td>
                        <td class="text-center px-2">${actionHtml}</td>
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
