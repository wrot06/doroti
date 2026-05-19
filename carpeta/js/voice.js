(function () {
    let grabando = false; let recognition = null; window.textoFinal = ''; let lastFinal = '';
    const LIMIT_CHARS = 300; // Límite de caracteres

    // ------- Utilidades -------
    function normalizarTexto(texto, quitarPuntuacion = false) {
        let t = texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
        if (quitarPuntuacion) t = t.replace(/[.,;:¡!¿?]/g, "");
        return t.trim();
    }

    const tokenRegex = /([\w\u00C0-\u017F]+)([.,;:]*)/g;
    const mapaReemplazos = (Array.isArray(window.REEMPLAZOS) ? window.REEMPLAZOS : []).reduce((m, [k, v]) => { m.set(normalizarTexto(k), v); return m; }, new Map());
    const regexReemplazos = Array.isArray(window.REGEX_REEMPLAZOS) ? window.REGEX_REEMPLAZOS : [];

    function aplicarReemplazos(texto) {
        return texto.replace(tokenRegex, (match, palabra, signo) => {
            const clave = normalizarTexto(palabra);
            if (mapaReemplazos.has(clave)) return mapaReemplazos.get(clave) + (signo || '');
            for (const [re, val] of regexReemplazos) if (re.test(clave)) return val + (signo || '');
            return palabra + (signo || '');
        });
    }

    // ------- Reconocimiento -------
    function iniciarReconocimiento() {
        if (grabando || !('webkitSpeechRecognition' in window)) return;

        recognition = new webkitSpeechRecognition();
        recognition.lang = 'es-CO';
        recognition.continuous = true;
        recognition.interimResults = true;
        grabando = true;

        const ta = document.getElementById('titulo');
        const btn = document.getElementById('grabarBoton');

        if (btn) btn.classList.add('grabando');
        if (ta) {
            window.textoFinal = ta.value.trim();
            ta.focus();
            ta.setSelectionRange(ta.value.length, ta.value.length);
        }

        recognition.onresult = evt => {
            let interim = '';
            for (let i = evt.resultIndex; i < evt.results.length; i++) {
                const r = evt.results[i];
                const textoProcesado = aplicarReemplazos(r[0].transcript.trim());

                if (r.isFinal) {
                    const plusNorm = normalizarTexto(textoProcesado, true);
                    const textoNormCompleto = normalizarTexto(window.textoFinal, true);
                    if (plusNorm && !textoNormCompleto.includes(plusNorm)) {
                        window.textoFinal += (window.textoFinal ? " " : "") + textoProcesado;
                    }
                    lastFinal = plusNorm;
                } else interim += textoProcesado + ' ';
            }

            if (ta) {
                let valor = window.textoFinal;
                if (interim) valor += (valor ? " " : "") + interim.trim();
                let textoAplicado = aplicarReemplazos(valor);

                // Validar longitud
                if (textoAplicado.length > LIMIT_CHARS) {
                    textoAplicado = textoAplicado.substring(0, LIMIT_CHARS);
                    // Si estamos grabando, quizás queramos detener o avisar, 
                    // pero por ahora solo truncamos el visual y el final
                }

                ta.value = textoAplicado;
                ta.setSelectionRange(ta.value.length, ta.value.length);
                actualizarContador(ta);
            }
        };

        recognition.onend = () => {
            grabando = false;
            recognition = null;
            if (btn) btn.classList.remove('grabando');
        };

        try { recognition.start(); } catch (e) { }
    }

    function detenerReconocimiento() {
        if (recognition) {
            try { recognition.abort(); } catch (e) { }
            recognition = null;
        }
        grabando = false;
        const btn = document.getElementById('grabarBoton');
        if (btn) btn.classList.remove('grabando');
    }

    // ------- Activación solo mientras se mantiene F2 o F9 -------
    let teclaActiva = false;

    window.addEventListener('keydown', e => {
        if (e.repeat) return;
        if ((e.key === 'F2' || e.key === 'F9') && !teclaActiva) {
            e.preventDefault();
            teclaActiva = true;
            iniciarReconocimiento();
        }
    });

    window.addEventListener('keyup', e => {
        if (e.key === 'F2' || e.key === 'F9') {
            e.preventDefault();
            teclaActiva = false;
            detenerReconocimiento();
        }
    });

    // ------- NUEVO: Click botón toggle -------
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('grabarBoton');
        if (btn) {
            btn.addEventListener('click', () => {
                if (grabando) detenerReconocimiento();
                else iniciarReconocimiento();
            });
        }
    });


    // ------- Sincronizar textoFinal si se escribe a mano -------
    document.addEventListener('DOMContentLoaded', () => {
        const tituloInput = document.getElementById('titulo');
        const counter = document.getElementById('charCounter');

        if (tituloInput) {
            // Inicializar contador
            actualizarContador(tituloInput);

            tituloInput.addEventListener('input', () => {
                // Truncar si se pasa
                if (tituloInput.value.length > LIMIT_CHARS) {
                    tituloInput.value = tituloInput.value.substring(0, LIMIT_CHARS);
                }

                window.textoFinal = tituloInput.value.trim() || '';
                lastFinal = normalizarTexto(window.textoFinal, true);
                actualizarContador(tituloInput);
            });
        }
    });

    // Listener para limpiar el contador cuando se agrega un capítulo
    document.addEventListener('capitulo-agregado', () => {
        const tituloInput = document.getElementById('titulo');
        if (tituloInput) {
            actualizarContador(tituloInput);
        }
    });

    function actualizarContador(el) {
        const counter = document.getElementById('charCounter');
        if (counter && el) {
            counter.textContent = `${el.value.length} / ${LIMIT_CHARS}`;
            if (el.value.length >= LIMIT_CHARS) {
                counter.classList.add('text-danger');
            } else {
                counter.classList.remove('text-danger');
            }
        }
    }


})();
