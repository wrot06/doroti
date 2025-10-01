// ==============================
// voice.js optimizado y corregido
// ==============================

(function () {
  let grabando = false;
  let recognition = null;
  window.textoFinal = '';
  let lastFinal = ""; // ✅ último bloque final reconocido

  // ------- Utilidades -------
  function normalizarTexto(texto, quitarPuntuacion = false) {
    let t = texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
    if (quitarPuntuacion) t = t.replace(/[.,;:¡!¿?]/g, "");
    return t.trim();
  }

  const tokenRegex = /([\w\u00C0-\u017F]+)([.,;:]*)/g;

  // Indexar reemplazos simples en un Map normalizado
  const mapaReemplazos = (() => {
    const base = Array.isArray(window.REEMPLAZOS) ? window.REEMPLAZOS : [];
    const map = new Map(base.map(([k, v]) => [normalizarTexto(k), v]));
    return map;
  })();

  const regexReemplazos = Array.isArray(window.REGEX_REEMPLAZOS) ? window.REGEX_REEMPLAZOS : [];

  function aplicarReemplazos(texto) {
    return texto.replace(tokenRegex, (match, palabra, signo) => {
      const clave = normalizarTexto(palabra);
      if (mapaReemplazos.has(clave)) {
        return mapaReemplazos.get(clave) + (signo || '');
      }
      for (const [re, val] of regexReemplazos) {
        if (re.test(clave)) {
          return val + (signo || '');
        }
      }
      return palabra + (signo || '');
    });
  }

  // ------- Reconocimiento -------
  function iniciarReconocimiento() {
    if (grabando) return;

    const btn = document.getElementById('grabarBoton');
    const ta  = document.getElementById('titulo');
    const inter = document.getElementById('interimTexto');

    if (!('webkitSpeechRecognition' in window)) {
      if (btn) {
        btn.disabled = true;
        btn.title = "Reconocimiento de voz no soportado en este navegador";
      }
      return;
    }

    recognition = new webkitSpeechRecognition();
    recognition.lang = 'es-CO';
    recognition.continuous = true;
    recognition.interimResults = true;

    if (btn) {
      btn.innerHTML = '<i class="bi bi-stop-circle me-1"></i> Detener';
      btn.classList.add('grabando');
    }

if (ta) {
  window.textoFinal = ta.value.trim();
  const partes = window.textoFinal.split(/\s+/);
  lastFinal = normalizarTexto(partes[partes.length - 1] || "", true);

  // 👇 Siempre poner el cursor al final al iniciar grabación
  ta.focus();
  ta.setSelectionRange(ta.value.length, ta.value.length);
}


    recognition.onresult = evt => {
      let interim = '';
      for (let i = evt.resultIndex; i < evt.results.length; i++) {
        const r = evt.results[i];
        const raw = r[0].transcript.trim();
        const textoProcesado = aplicarReemplazos(raw);

if (r.isFinal) {
  const plusNorm = normalizarTexto(textoProcesado, true);
  const textoNormCompleto = normalizarTexto(window.textoFinal, true);

  // ✅ Evita duplicados exactos o repetidos dentro del texto
  if (plusNorm && !textoNormCompleto.includes(plusNorm)) {
    window.textoFinal = (window.textoFinal ? window.textoFinal + " " : "") + textoProcesado;
  }

  lastFinal = plusNorm;
} else {
          interim += textoProcesado + ' ';
        }
      }

      // ✅ Mostrar final + provisional en tiempo real
      const ta = document.getElementById('titulo');
      if (ta) {
        let valor = window.textoFinal;
        if (interim) valor += (valor ? " " : "") + interim.trim();
        ta.value = aplicarReemplazos(valor);
        ta.setSelectionRange(ta.value.length, ta.value.length);
      }

      if (inter) inter.textContent = interim.trim();
    };

    recognition.onend = () => {
      if (grabando) {
        // Reinicia con un pequeño delay para no atascar
        setTimeout(() => {
          try { recognition.start(); } catch (e) {}
        }, 50);
      }
    };

    try { recognition.start(); } catch (e) {}
    grabando = true;
  }

  function detenerReconocimiento() {
    grabando = false;
    if (recognition) {
      try { recognition.stop(); } catch (e) {}
      recognition = null;
    }
    const btn = document.getElementById('grabarBoton');
    const inter = document.getElementById('interimTexto');
    if (btn) {
      btn.innerHTML = '<i class="bi bi-mic me-1"></i> Grabar (F2-F9)';
      btn.classList.remove('grabando');
    }
    if (inter) inter.textContent = '';
  }

  // ------- Eventos -------
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('grabarBoton');
    const form = document.getElementById('capituloForm');
    const tituloInput = document.getElementById('titulo');
    const paginaInput = document.getElementById('paginaFinal');
    const textoInput = document.getElementById('textoInput');

    // Botón de grabación con fallback
    if (btn) {
      if (!('webkitSpeechRecognition' in window)) {
        btn.disabled = true;
        btn.title = "Reconocimiento de voz no soportado en este navegador";
        btn.innerHTML = '<i class="bi bi-mic-mute me-1"></i> No disponible';
      } else {
        btn.innerHTML = '<i class="bi bi-mic me-1"></i> Grabar (F2-F9)';
        btn.addEventListener('click', () => {
          grabando ? detenerReconocimiento() : iniciarReconocimiento();
        });
      }
    }

    // Atajos
    window.addEventListener('keydown', e => {
      if (e.repeat) return;

      if ((e.key === 'F2' || e.key === 'F9') && ('webkitSpeechRecognition' in window)) {
        e.preventDefault();
        grabando ? detenerReconocimiento() : iniciarReconocimiento();
      }

      if (e.key === '+') {
        if (!textoInput || !textoInput.value.trim()) return;

        e.preventDefault();

        const estabaGrabando = grabando;
        if (estabaGrabando) detenerReconocimiento();

        // Inserta al textarea
        if (typeof window.agregarAlTextarea === 'function') {
          window.agregarAlTextarea();
        }

        // ✅ Sincronizar estado interno con el texto actual
        const ta = document.getElementById('titulo');
        if (ta) {
          window.textoFinal = ta.value.trim();
          lastFinal = normalizarTexto(window.textoFinal, true);
        }

        if (estabaGrabando) iniciarReconocimiento();
      }
    });

    // Sincronizar textoFinal si se escribe a mano
    if (tituloInput) {
      tituloInput.addEventListener('input', () => {
        window.textoFinal = tituloInput.value.trim() || '';
        lastFinal = normalizarTexto(window.textoFinal, true);
      });

      // Enter durante grabación → detener y pasar a página
      tituloInput.addEventListener('keydown', e => {
        if (e.key === 'Enter' && grabando) {
          e.preventDefault();
          detenerReconocimiento();
          if (paginaInput) paginaInput.focus();
        }
      });
    }

    // Enter en página → submit
    if (paginaInput) {
      paginaInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
          e.preventDefault();
          if (form && typeof form.requestSubmit === 'function') form.requestSubmit();
          else if (form) form.submit();
        }
      });
    }
  });
})();
