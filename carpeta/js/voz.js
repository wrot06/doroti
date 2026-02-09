(function () {
  let grabando = false;
  let recognition;
  const LIMITE = 56;

  // ✅ Mapa de reemplazos global
  const mapaReemplazos = new Map(
    (window.REEMPLAZOS || []).map(([clave, valor]) => [
      clave.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase(),
      valor
    ])
  );
  const regexReemplazos = window.REGEX_REEMPLAZOS || [];

  // Función para normalizar texto
  function normalizar(texto) {
    return texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
  }

  // Función para aplicar reemplazos exactos y por regex
  function aplicarReemplazos(texto) {
    return texto.split(/\s+/).map(palabra => {
      const match = palabra.match(/^([\wáéíóúüñÁÉÍÓÚÜÑ]+)([.,;:]*)$/);
      const base = match ? match[1] : palabra;
      const puntuacion = match ? match[2] : '';

      const clave = normalizar(base);
      if (mapaReemplazos.has(clave)) return mapaReemplazos.get(clave) + puntuacion;

      for (const [re, val] of regexReemplazos) {
        if (re.test(clave)) return val + puntuacion;
      }

      return base + puntuacion;
    }).join(' ');
  }

  // Coloca el cursor al final del textarea
  function colocarCursorAlFinal(textarea) {
    textarea.focus();
    const len = textarea.value.length;
    textarea.setSelectionRange(len, len);
  }

  // Función principal
  window.iniciarVoz = function (textareaId, botonId) {
    const textarea = document.getElementById(textareaId);
    const boton = document.getElementById(botonId);

    if (!textarea || !boton) return console.warn("No se encontró textarea o botón.");

    function iniciarReconocimiento() {
      if (!('webkitSpeechRecognition' in window)) {
        alert("Tu navegador no soporta reconocimiento de voz.");
        return;
      }

      if (textarea.value.length >= LIMITE) {
        alert("Campo lleno. No se puede agregar más texto.");
        return;
      }

      recognition = new webkitSpeechRecognition();
      recognition.lang = 'es-CO';
      recognition.continuous = true;
      recognition.interimResults = false;

      recognition.onresult = evt => {
        const fragmentos = [];
        for (let i = evt.resultIndex; i < evt.results.length; i++) {
          if (evt.results[i].isFinal) {
            const transcrito = evt.results[i][0].transcript.trim();
            fragmentos.push(aplicarReemplazos(transcrito));
          }
        }

        if (fragmentos.length) {
          let textoFinal = (textarea.value.trim() ? textarea.value.trim() + " " : "") + fragmentos.join(" ");
          if (textoFinal.length > LIMITE) textoFinal = textoFinal.substring(0, LIMITE);
          textarea.value = textoFinal;
          colocarCursorAlFinal(textarea);
        }
      };

      recognition.onend = () => grabando && recognition.start();
      recognition.onerror = (e) => { console.error("Error en reconocimiento:", e); detenerReconocimiento(); };

      recognition.start();
      grabando = true;
      boton.classList.add('grabando');
    }

    function detenerReconocimiento() {
      if (recognition) recognition.stop();
      grabando = false;
      boton.classList.remove('grabando');
      colocarCursorAlFinal(textarea);
    }

    boton.addEventListener('click', () => grabando ? detenerReconocimiento() : iniciarReconocimiento());

    document.addEventListener('keydown', e => {
      if ((e.key === 'F2' || e.key === 'F9') && !grabando && boton.offsetParent !== null) iniciarReconocimiento();
    });

    document.addEventListener('keyup', e => {
      if ((e.key === 'F2' || e.key === 'F9') && grabando && boton.offsetParent !== null) detenerReconocimiento();
    });
  };
})();
