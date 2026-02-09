// js/textotareainsert.js (versión mejorada)
(function () {
  'use strict';

  const DEBUG = false; // pon true si quieres logs de depuración

  // Normaliza: quita tildes, pone minúsculas y (opcional) elimina puntuación
  function _normalizarBase(txt) {
    return String(txt || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }
  function normalizar(txt, options = { removePunctuation: false }) {
    let t = _normalizarBase(txt).toLowerCase();
    if (options.removePunctuation) {
      // elimina signos/puntuación comunes y colapsa espacios
      t = t.replace(/[\u2000-\u206F!\"#$%&'()*+,\-./:;<=>?@\[\]^_`{|}~¡¿]/g, ' ')
           .replace(/\s+/g, ' ')
           .trim();
    }
    return t;
  }

  // Inserta el contenido de #textoInput en #titulo sin duplicados y evitando superposiciones
  window.agregarAlTextarea = function () {
    const inputElem = document.getElementById('textoInput');
    const textareaElem = document.getElementById('titulo');
    if (!inputElem || !textareaElem) {
      if (DEBUG) console.warn('agregarAlTextarea: elementos no encontrados');
      return;
    }

    let nuevo = (inputElem.value || '');
    if (!nuevo) {
      if (DEBUG) console.debug('agregarAlTextarea: texto nuevo vacío, nada que hacer');
      return;
    }

    let existente = (textareaElem.value || '').trim();

    // Comparaciones en forma "sin puntuación" para ser tolerantes
    const nuevoNorm = normalizar(nuevo, { removePunctuation: true });
    const existNorm = normalizar(existente, { removePunctuation: true });

    // Si el texto nuevo ya está contenido (completamente) en el existente → salir
    if (existNorm && nuevoNorm && existNorm.includes(nuevoNorm)) {
      if (DEBUG) console.debug('agregarAlTextarea: ya contenido, skip');
      return;
    }

    // Tokenizar en palabras (filtrar tokens vacíos)
    const a = existente ? existente.split(/\s+/).filter(Boolean) : [];
    const b = nuevo ? nuevo.split(/\s+/).filter(Boolean) : [];

    // Buscar máximo solapamiento entre tail(a) y head(b)
    let overlap = 0;
    const maxCheck = Math.min(a.length, b.length);
    for (let k = maxCheck; k > 0; k--) {
      const tailA = normalizar(a.slice(-k).join(' '), { removePunctuation: true });
      const headB = normalizar(b.slice(0, k).join(' '), { removePunctuation: true });
      if (tailA === headB) { overlap = k; break; }
    }

    if (overlap > 0) {
      // quitar las palabras que se solapan del nuevo
      nuevo = b.slice(overlap).join(' ');
    }

    const final = (existente + (existente && nuevo ? ' ' : '') + nuevo);
    textareaElem.value = final;

    // SINCRONIZAR con el buffer que usa el reconocimiento de voz
    // (esto evita que, al reanudar, el motor borre lo insertado)
    try {
      window.textoFinal = final;
    } catch (e) { /* seguro */ }

    // Notificar a otros módulos (voice.js puede escuchar esto para actualizar checkpoints)
    try {
      const ev = new CustomEvent('texto-insertado', { detail: { final } });
      document.dispatchEvent(ev);
    } catch (e) { /* ignore */ }

    // Persistir el input auxiliar (no el textarea) por si el usuario recarga
    try { localStorage.setItem('textoInput', inputElem.value); } catch (e) {}

    if (DEBUG) console.debug('agregarAlTextarea -> final:', final, ' overlap:', overlap);
  };

  // Restaurar #textoInput del localStorage y persistir cambios
  document.addEventListener('DOMContentLoaded', () => {
    const inputElem = document.getElementById('textoInput');
    if (!inputElem) return;

    try {
      const guardado = localStorage.getItem('textoInput');
      if (guardado !== null) inputElem.value = guardado;
    } catch (e) { /* localStorage no disponible */ }

    inputElem.addEventListener('input', () => {
      try { localStorage.setItem('textoInput', inputElem.value); } catch (e) {}
    });
  });
})();
