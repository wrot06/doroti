(function () {
  let grabando = false;
  let recognition;
  window.textoFinal = '';

  // ========================================
  // Utilidades de normalización y reemplazos
  // ========================================
  function normalizarTexto(texto, quitarPuntuacion = false) {
    let t = texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
    if (quitarPuntuacion) t = t.replace(/[.,;:¡!¿?]/g, "");
    return t.trim();
  }

  const reemplazos = [
    ["albanely", "Albanely"],
    ["alirio", "Alirio"], ["arles", "Arles"], ["alier", "Alier"], ["arcos", "Arcos"],
    ["ardila", "Ardila"], ["argotti", "Argoty"], ["anuario", "Anuario"], ["aponte", "Aponte"], ["aura", "Aura"], ["asula", "Azula"],
    ["barreiro", "Barreiro"], ["bastidas", "Bastidas"],
    ["belalcázar", "Belalcázar"], ["bravo", "Bravo"], ["brisueno", "Risueño"], ["burbano", "Burbano"],
    ["calpa", "Calpa"], ["cuadros", "Cuadros"], ["calvache", "Calvache"], ["calvacci", "Calvachy"], ["coyez", "Goyes"], ["coyes", "Goyes"], ["constanza", "Constanza"],
    ["calvachi", "Calvachy"], ["calvachí", "Calvachy"], ["cedeño", "Cedeño"], ["chicaiza", "Chicaiza"], ["cangrejos", "Cangrejos"],
    ["collés", "Goyes"], ["canal", "Canal"], ["coral", "Coral"], ["correa", "Correa"], ["cortés", "Cortés"], ["cadena", "Cadena"], ["cierra", "Sierra"],
    ["cobo", "Cobo"], ["delgado", "Delgado"], ["derecho", "Derecho"], ["dolores", "Dolores"],
    ["edilma", "Edilma"], ["escudero", "Escudero"], ["eulises", "Eulises"],
    ["especialización", "Especialización"], ["erazo", "Erazo"],
    ["estupiñán", "Estupiñán"],
    ["flor", "Flor"], ["flores", "Flores"], ["forero", "Forero"], ["familia", "Familia"], ["fresneda", "Fresneda"],
    ["galán", "Galán"], ["giraldo", "Giraldo"], ["goiles", "Goyes"], ["goyes", "Goyes"],
    ["goyés", "Goyes"], ["goyez", "Goyes"], ["goyis", "Goyes"], ["goiz", "Goyes"], ["guerra", "Guerra"], ["guido", "Guido"], ["greeicy", "Greeicy"],
    ["gloria", "Gloria"], ["hoyos", "Hoyos"],
    ["insuasti", "Insuasty"], ["izquierdo", "Izquierdo"],
    ["jojoa", "Jojoa"],
    ["llano", "Llano"], ["lafon", "Lafon"], ["lagos", "Lagos"], ["leyton", "Leyton"],
    ["legis", "LEGIS"], ["legarda", "Legarda"], ["libardo", "Libardo"], ["liborio", "Liborio"], ["livorio", "Livorio"], ["lizcano", "Lizcano"],
    ["luz", "Luz"], ["luna", "Luna"],
    ["mafla", "Mafla"], ["madroñero", "Madroñero"], ["maya", "Maya"],
    ["maturana", "Maturana"], ["marco", "Marco"], ["materón", "Materón"], ["mesa", "Mesa"], ["miriam", "Myriam"], ["manrique", "Manrique"],
    ["morasurco", "Morasurco"], ["monsalve", "Monsalve"], ["monsalvo", "Monsalvo"], ["morillo", "Morillo"], ["montanchez", "Montanchez"], ["montúfar", "Montúfar"],
    ["meza", "Mesa"],
    ["munera", "Munera"], ["maigual", "Maigual"], ["moncayo", "Moncayo"], ["montilla", "Montilla"],
    ["nariño", "Nariño"], ["navia", "Navia"], ["niño", "Niño"],
    ["ocaña", "Ocaña"], ["oliva", "Oliva"], ["osejo", "Osejo"], ["ocejo", "Osejo"], ["ocara", "OCARA"], ["omaira", "Omaira"],
    ["obando", "Obando"], ["oquendo", "Oquendo"],
    ["palacios", "Palacios"], ["para", "para"], ["paredes", "Paredes"], ["pasos", "Pasos"], ["peñafiel", "Peñafiel"], ["pinilla", "Pinilla"], ["poveda", "Poveda"], ["patarroyo", "Patarroyo"],
     ["perdomo", "Perdomo"],
    ["ramos", "Ramos"], ["reina", "Reina"], ["realpe", "Realpe"],
    ["risueño", "Risueño"], ["revelo", "Revelo"], ["riascos", "Riascos"], ["ríos", "Ríos"], ["rosa", "Rosa"], ["rojas", "Rojas"], ["rugeles", "Rugeles"],
    ["ruales", "Rúales"], ["sabogal", "Sabogal"], ["sarama", "Zarama"],
    ["sevillano", "Sevillano"], ["solarte", "Solarte"], ["sotelo", "Sotelo"], ["segura", "Segura"], ["segundo", "Segundo"],
    ["tajumbina", "Tajumbina"], ["tazcón", "Tascón"], ["toscano", "Toscano"], ["toro", "Toro"], ["tutistar", "Tutistar"], ["timaran", "Timaran"],
    ["umaña", "Umaña"], ["universidad", "Universidad"], ["urresta", "Urresta"], ["urbano", "Urbano"], ["urrego", "Urrego"],
    ["urigarro", "Urigarro"], ["vela", "Vela"], ["villota", "Villota"], ["villamil", "Villamil"],
    ["wanda", "Whanda"], ["vallejos", "Vallejos"], ["vallejo", "Vallejo"], ["vinueza", "Vinueza"], ["viteri", "Viteri"], ["vicuña", "Vicuña"], ["villacres", "Villacres"],
    ["yela", "Yela"], ["zarama", "Zarama"]
  ];


const mapaReemplazos = new Map(reemplazos.map(([k, v]) => [normalizarTexto(k), v]));
  const tokenRegex = /([\w\u00C0-\u017F]+)([.,;:]*)/g;

  function aplicarReemplazos(texto) {
    return texto.replace(tokenRegex, (match, palabra, signo) => {
      const clave = normalizarTexto(palabra);
      return (mapaReemplazos.get(clave) || palabra) + (signo || '');
    });
  }

  // ========================================
  // Tokenización canónica (palabra, puntuación, normalizada)
  // ========================================
  function tokenizeConNorm(text) {
    const tokens = [];
    let m;
    // reset regex lastIndex
    tokenRegex.lastIndex = 0;
    while ((m = tokenRegex.exec(text)) !== null) {
      const word = m[1];
      const punct = m[2] || '';
      tokens.push({ word, punct, norm: normalizarTexto(word, true) });
    }
    return tokens;
  }

  function tokensToString(tokens) {
    // Une tokens respetando puntuación sin dejar espacio antes de .,;:
    const parts = tokens.map(t => t.word + (t.punct || ''));
    return parts.join(' ').replace(/\s+([.,;:])/g, '$1').trim();
  }

  // ========================================
  // Búsqueda y comparaciones sobre tokens
  // ========================================
  function findAllSequenceIndices(haystack, needle) {
    const res = [];
    if (!needle.length || needle.length > haystack.length) return res;
    for (let i = 0; i <= haystack.length - needle.length; i++) {
      let ok = true;
      for (let j = 0; j < needle.length; j++) {
        if (haystack[i + j].norm !== needle[j].norm) {
          ok = false;
          break;
        }
      }
      if (ok) res.push(i);
    }
    return res;
  }

  function findMaxOverlapSuffixPrefix(a, b) {
    // máximo k tal que los últimos k tokens de a == primeros k tokens de b
    const maxK = Math.min(a.length, b.length);
    for (let k = maxK; k > 0; k--) {
      let ok = true;
      for (let i = 0; i < k; i++) {
        if (a[a.length - k + i].norm !== b[i].norm) {
          ok = false;
          break;
        }
      }
      if (ok) return k;
    }
    return 0;
  }

  function countSequenceOccurrences(haystack, needle) {
    return findAllSequenceIndices(haystack, needle).length;
  }

  // ========================================
  // Funciones de reconocimiento (idénticas a antes)
  // ========================================
  function iniciarReconocimiento() {
    if (grabando) return;
    if (!('webkitSpeechRecognition' in window)) {
      alert("Tu navegador no soporta reconocimiento de voz.");
      return;
    }
    recognition = new webkitSpeechRecognition();
    recognition.lang = 'es-CO';
    recognition.continuous = true;
    recognition.interimResults = true;

    const btn = document.getElementById('grabarBoton');
    btn.textContent = 'Detener Grabación (F2-F9-ENTER)';
    btn.classList.add('grabando');

    const ta = document.getElementById('titulo');
    ta.focus();
    ta.setSelectionRange(ta.value.length, ta.value.length);
    window.textoFinal = ta.value;

    recognition.onresult = evt => {
      let interim = '';
      for (let i = evt.resultIndex; i < evt.results.length; i++) {
        const r = evt.results[i];
        const raw = r[0].transcript.trim();
        const textoFinalR = aplicarReemplazos(raw);

        if (r.isFinal) {
          const actualNorm = normalizarTexto(window.textoFinal, true);
          const plusNorm = normalizarTexto(textoFinalR, true);
          if (!actualNorm.endsWith(plusNorm)) {
            window.textoFinal += (window.textoFinal ? ' ' : '') + textoFinalR;
          }
        } else {
          interim += raw + ' ';
        }
      }

      let valor = window.textoFinal;
      if (interim.trim()) valor += (valor ? ' ' : '') + interim.trim();

      ta.value = aplicarReemplazos(valor);
      ta.setSelectionRange(ta.value.length, ta.value.length);
    };

    recognition.onend = () => {
      if (grabando) recognition.start();
    };

    recognition.start();
    grabando = true;
  }

  function detenerReconocimiento() {
    grabando = false;
    if (recognition) recognition.stop();
    const btn = document.getElementById('grabarBoton');
    btn.textContent = 'Grabar (F2-F9)';
    btn.classList.remove('grabando');
  }

  // ========================================
  // Función mejorada: agregarAlTextarea
  // - genera varios candidatos de fusión y elige el mejor
  // ========================================
  function agregarAlTextarea() {
    const inputElem = document.getElementById("textoInput");
    const textareaElem = document.getElementById("titulo");

    let nuevoTexto = inputElem.value.trim();
    let textoExistente = textareaElem.value.trim();

    if (!nuevoTexto) return; // No añadir si está vacío

    // Normalizar para comparación (sin mayúsculas ni acentos)
    const normalizar = txt => txt.normalize("NFD")
                                  .replace(/[\u0300-\u036f]/g, "")
                                  .toLowerCase();

    const nuevoNorm = normalizar(nuevoTexto);
    const existenteNorm = normalizar(textoExistente);

    // Evitar insertar si ya está todo el texto
    if (existenteNorm.includes(nuevoNorm)) {
        return;
    }

    // Evitar duplicaciones parciales
    const partesExistente = textoExistente.split(/\s+/);
    const partesNuevo = nuevoTexto.split(/\s+/);

    let i = 0;
    while (i < partesNuevo.length && existenteNorm.endsWith(normalizar(partesNuevo.slice(0, i + 1).join(" ")))) {
        i++;
    }

    if (i > 0) {
        nuevoTexto = partesNuevo.slice(i).join(" ");
    }

    // Unir el texto
    const textoFinal = (textoExistente + " " + nuevoTexto).trim();
    textareaElem.value = textoFinal;

    // Mantener el valor del input en memoria y no borrarlo
    localStorage.setItem("textoInput", inputElem.value);
}


  // ========================================
  // Persistencia
  // ========================================
  function persistir(textoInputValor, tituloValor) {
    try {
      localStorage.setItem('textoInput', textoInputValor);
    } catch (e) {
      // si el storage falla, no rompemos la app
      console.warn('localStorage no disponible:', e);
    }
  }

  // ========================================
  // Eventos y carga inicial (idéntico comportamiento)
  // ========================================
  document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('grabarBoton');
  const form = document.getElementById('capituloForm');
  const tituloInput = document.getElementById('titulo');
  const paginaInput = document.getElementById('paginaFinal');
  const textoInput = document.getElementById('textoInput');

  // Restaurar solo textoInput (no tocamos #titulo)
  if (textoInput) {
    const inputGuardado = localStorage.getItem('textoInput');
    if (inputGuardado !== null) textoInput.value = inputGuardado;

    // Guardar cada vez que el usuario escriba en el input
    textoInput.addEventListener('input', () => {
      try { localStorage.setItem('textoInput', textoInput.value); } catch (e) {}
    });
  }

  // Inicializar window.textoFinal con lo que ya haya en el textarea (si existe)
  if (tituloInput) {
    window.textoFinal = tituloInput.value || '';
  } else {
    window.textoFinal = '';
  }

  // Botón de grabación (protegido)
  if (btn) {
    btn.addEventListener('click', () => {
      grabando ? detenerReconocimiento() : iniciarReconocimiento();
    });
  }

  // Atajos del teclado (global)
  window.addEventListener('keydown', e => {
    if (e.repeat) return;

    if (e.key === 'F2' || e.key === 'F9') {
      // prevenir comportamiento por defecto opcional
      e.preventDefault();
      grabando ? detenerReconocimiento() : iniciarReconocimiento();
    }

    if (e.key === '+') {
      if (textoInput && textoInput.value.trim()) {
        e.preventDefault();
        agregarAlTextarea();
      }
    }
  });

  // Mantener textoFinal sincronizado con textbox manualmente
  if (tituloInput) {
    tituloInput.addEventListener('input', () => {
      window.textoFinal = tituloInput.value;
    });

    tituloInput.addEventListener('keydown', e => {
      if (e.key === 'Enter' && grabando) {
        e.preventDefault();
        detenerReconocimiento();
        if (paginaInput) paginaInput.focus();
      }
    });
  }

  // Submit cuando presionan Enter en paginaInput
  if (paginaInput) {
    paginaInput.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (form && typeof form.requestSubmit === 'function') form.requestSubmit();
        else if (form) form.submit();
      }
    });
  }

  // NO guardar #titulo en beforeunload (tal como pediste)
});

})();