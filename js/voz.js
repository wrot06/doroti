(function () {
  let grabando = false;
  let recognition;

  const LIMITE = 56;

  const reemplazos = [
    ["albanely", "Albanely"],
    ["alirio", "Alirio"], ["arles", "Arles"], ["alier", "Alier"], ["arcos", "Arcos"], 
    ["ardila", "Ardila"], ["argotti", "Argoty"], ["anuario", "Anuario"], ["aponte", "Aponte"],
    ["barreiro", "Barreiro"], ["bastidas", "Bastidas"],
    ["belalcázar", "Belalcázar"], ["bravo", "Bravo"], ["brisueno", "Risueño"], ["burbano", "Burbano"], 
    ["calpa", "Calpa"], ["cuadros", "Cuadros"], ["calvache", "Calvache"], ["calvacci", "Calvachy"], ["coyez", "Goyes"], ["coyes", "Goyes"],
    ["calvachi", "Calvachy"], ["calvachí", "Calvachy"], ["cedeño", "Cedeño"], ["chicaiza", "Chicaiza"],
    ["collés", "Goyes"], ["canal", "Canal"], ["coral", "Coral"], ["correa", "Correa"], ["cortés", "Cortés"], ["cadena", "Cadena"],
    ["delgado", "Delgado"],  
    ["derecho", "Derecho"], ["dolores", "Dolores"], 
    ["edilma", "Edilma"], ["escudero", "Escudero"], ["eulises", "Eulises"],
    ["especialización", "Especialización"], ["erazo", "Erazo"], 
    ["estupiñán", "Estupiñán"], 
    ["flor", "Flor"], ["flores", "Flores"], ["forero", "Forero"],   ["familia", "Familia"],
    ["galán", "Galán"], ["giraldo", "Giraldo"], ["goiles", "Goyes"], ["goyes", "Goyes"],
    ["goyés", "Goyes"], ["goyez", "Goyes"], ["goyis", "Goyes"], ["goiz", "Goyes"], ["guerra", "Guerra"], ["guido", "Guido"], ["greeicy", "Greeicy"],
    ["gloria", "Gloria"],   
    ["hoyos", "Hoyos"], 
    ["insuasti", "Insuasty"], ["izquierdo", "Izquierdo"],
    ["jojoa", "Jojoa"], 
    ["llano", "Llano"], ["lafon", "Lafon"], ["lagos", "Lagos"], ["leyton", "Leyton"],
    ["legis", "LEGIS"], ["legarda", "Legarda"], ["libardo", "Libardo"], ["liborio", "Liborio"], ["livorio", "Livorio"],
    ["luz", "Luz"], ["luna", "Luna"],
    ["mafla", "Mafla"], ["madroñero", "Madroñero"], ["maya", "Maya"],
    ["maturana", "Maturana"], ["marco", "Marco"], ["materón", "Materón"], ["mesa", "Mesa"], ["miriam", "Myriam"], 
    ["morasurco", "Morasurco"], ["monsalve", "Monsalve"], ["monsalvo", "Monsalvo"],["morillo", "Morillo"], ["montanchez", "Montanchez"], ["montúfar", "Montúfar"], 
    ["meza", "Mesa"],
    ["munera", "Munera"], ["maigual", "Maigual"], ["moncayo", "Moncayo"], ["montilla", "Montilla"],
    ["nariño", "Nariño"], 
    ["navia", "Navia"], ["niño", "Niño"],
    ["ocaña", "Ocaña"], ["oliva", "Oliva"], ["osejo", "Osejo"], ["ocara", "OCARA"], ["omaira", "Omaira"],
    ["obando", "Obando"], ["oquendo", "Oquendo"],
    ["palacios", "Palacios"],
    ["paredes", "Paredes"], ["pasos", "Pasos"], ["peñafiel", "Peñafiel"], ["pinilla", "Pinilla"], ["poveda", "Poveda"],
    ["ramos", "Ramos"], ["reina", "Reina"],
    ["risueño", "Risueño"], ["revelo", "Revelo"], ["riascos", "Riascos"], ["ríos", "Ríos"], ["rosa", "Rosa"], ["rojas", "Rojas"], ["rugeles", "Rugeles"],
    ["ruales", "Rúales"],
    ["sabogal", "Sabogal"],
    ["sevillano", "Sevillano"], ["solarte", "Solarte"], ["sotelo", "Sotelo"], 
    ["tajumbina", "Tajumbina"], ["tazcón", "Tascón"], ["toscano", "Toscano"], ["toro", "Toro"], ["tutistar", "Tutistar"], ["timaran", "Timaran"],
    ["umaña", "Umaña"], ["universidad", "Universidad"], ["urresta", "Urresta"], ["urbano", "Urbano"], ["urrego", "Urrego"],
    ["urigarro", "Urigarro"],
    ["vela", "Vela"], ["villota", "Villota"], 
    ["Wanda", "Whanda"],
    ["vallejos", "Vallejos"], ["vallejo", "Vallejo"], ["vinueza", "Vinueza"], ["viteri", "Viteri"], ["vicuña", "Vicuña"],
    ["yela", "Yela"],
    ["zarama", "Zarama"]
  ];

  const mapaReemplazos = new Map(
    reemplazos.map(([clave, valor]) => [
      clave.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase(),
      valor
    ])
  );

  function aplicarReemplazos(texto) {
    return texto.split(/\s+/).map(palabra => {
      const match = palabra.match(/^([\wáéíóúüñÁÉÍÓÚÜÑ]+)([.,;:]*)$/);
      const base = match ? match[1] : palabra;
      const puntuacion = match ? match[2] : '';

      const clave = base.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
      const reemplazo = mapaReemplazos.get(clave);

      return reemplazo ? reemplazo + puntuacion : base + puntuacion;
    }).join(' ');
  }

  function colocarCursorAlFinal(textarea) {
    textarea.focus();
    const len = textarea.value.length;
    textarea.setSelectionRange(len, len);
  }

  window.iniciarVoz = function (textareaId, botonId) {
    const textarea = document.getElementById(textareaId);
    const boton = document.getElementById(botonId);

    if (!textarea || !boton) {
      console.warn("No se encontró textarea o botón.");
      return;
    }

    function iniciarReconocimiento() {
      if (!('webkitSpeechRecognition' in window)) {
        alert("Tu navegador no soporta reconocimiento de voz.");
        return;
      }

      const espacioDisponible = LIMITE - textarea.value.length;
      if (espacioDisponible <= 0) {
        alert("Campo lleno. No se puede agregar más texto.");
        return;
      }

      recognition = new webkitSpeechRecognition();
      recognition.lang = 'es-CO';
      recognition.continuous = true;
      recognition.interimResults = false;

      recognition.onresult = function (evt) {
        const fragmentos = [];

        for (let i = evt.resultIndex; i < evt.results.length; i++) {
          if (evt.results[i].isFinal) {
            const transcrito = evt.results[i][0].transcript.trim();
            const corregido = aplicarReemplazos(transcrito);
            fragmentos.push(corregido);
          }
        }

        if (fragmentos.length) {
          const textoActual = textarea.value.trim();
          let textoFinal = (textoActual ? textoActual + " " : "") + fragmentos.join(" ");
          if (textoFinal.length > LIMITE) {
            textoFinal = textoFinal.substring(0, LIMITE);
          }
          textarea.value = textoFinal;
          colocarCursorAlFinal(textarea);
        }
      };

      recognition.onend = () => {
        if (grabando) recognition.start();
      };

      recognition.onerror = (e) => {
        console.error("Error en reconocimiento:", e);
        detenerReconocimiento();
      };

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

    boton.addEventListener('click', () => {
      grabando ? detenerReconocimiento() : iniciarReconocimiento();
    });

    document.addEventListener('keydown', (e) => {
      if ((e.key === 'F2' || e.key === 'F9') && !grabando && boton.offsetParent !== null) {
        iniciarReconocimiento();
      }
    });

    document.addEventListener('keyup', (e) => {
      if ((e.key === 'F2' || e.key === 'F9') && grabando && boton.offsetParent !== null) {
        detenerReconocimiento();
      }
    });
  };
})();
