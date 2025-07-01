/**
 * Agrega el texto del input al final del textarea.
 * - Si el input está vacío o contiene solo espacios, no se agrega nada.
 * - Si el textarea ya tiene contenido, se agrega el texto separado por un espacio.
 * - Luego, coloca el foco en el textarea y posiciona el cursor al final del contenido.
 * 
 * Requisitos:
 * - Un input con ID: 'textoInput'
 * - Un textarea con ID: 'titulo'
 * 
 * Además, permite activar la función con las teclas '+' o '0'.
 */
  // Recuperar datos guardados al cargar la página
  window.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('textoInput');
    const textarea = document.getElementById('titulo');

    const inputGuardado = localStorage.getItem('textoInput');   

    if (inputGuardado !== null) input.value = inputGuardado;
    
  });

  function agregarAlTextarea() {
    const input = document.getElementById('textoInput');
    const textarea = document.getElementById('titulo');
    const texto = input.value.trim();

    if (texto === "") return;

    if (textarea.value.trim() !== "") {
      textarea.value += " " + texto;
    } else {
      textarea.value = texto;
    }

    // Guardar en localStorage
    localStorage.setItem('titulo', textarea.value);

    // Enfocar el textarea y colocar el cursor al final
    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
  }

  // Guardar el contenido del input en tiempo real
  document.addEventListener('input', function (event) {
    if (event.target.id === 'textoInput') {
      localStorage.setItem('textoInput', event.target.value);
    }
  });

  // Activar la función con las teclas '+' o '0'
  window.addEventListener('keydown', function (event) {
    if (event.key === "+") {
      const input = document.getElementById('textoInput');
      if (input.value.trim() !== "") {
        event.preventDefault();
        agregarAlTextarea();
      }
    }
  });