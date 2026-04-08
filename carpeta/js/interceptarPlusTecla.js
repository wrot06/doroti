// js/interceptarPlusTecla.js
// Intercepta la tecla "+" en el textarea #titulo y agrega texto de #textoInput
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('titulo');
    
    if (!textarea) {
      console.warn('interceptarPlusTecla: textarea #titulo no encontrado');
      return;
    }

    textarea.addEventListener('keydown', function (event) {
      // Detectar tecla "+" (puede ser Shift + "=" en algunos teclados)
      // keyCode 187 = tecla "=" (que con shift es "+")
      // key = "+" para detección moderna
      if (event.key === '+' || (event.key === '=' && event.shiftKey)) {
        // Prevenir que se escriba el símbolo "+"
        event.preventDefault();
        
        // Llamar a la función que agrega texto desde textoInput
        if (typeof window.agregarAlTextarea === 'function') {
          window.agregarAlTextarea();
        } else {
          console.warn('interceptarPlusTecla: función agregarAlTextarea no disponible');
        }
        
        return false;
      }
    });
  });
})();
