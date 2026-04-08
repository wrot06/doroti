import { initVoz } from './voz.js';
import { configurarValidacionFechas } from './validacionesFecha.js';
import { configurarSubseries } from './subseries.js';

document.addEventListener('DOMContentLoaded', () => {
    const textarea = document.getElementById('tituloCarpeta');
    const grabarBtn = document.getElementById('grabarBoton');

    // Capitalizar y limitar texto
    textarea.addEventListener('input', () => {
        const maxLength = 56;
        textarea.value = textarea.value.slice(0, maxLength);
        textarea.value = textarea.value.charAt(0).toUpperCase() + textarea.value.slice(1);
    });

    // Enfocar al cargar
    textarea.focus();

    // Inicializar módulos
    configurarSubseries();
    configurarValidacionFechas();
    initVoz(textarea, grabarBtn);
});
