(function () {
  let grabando = false;
  let recognition;

  const reemplazos = [
    ["alirio", "Alirio"], ["arles", "Arles"], ["alier", "Alier"], ["arcos", "Arcos"], 
    ["ardila", "Ardila"], ["argotti", "Argoty"], ["anuario", "Anuario"], ["aponte", "Aponte"],
    ["barreiro", "Barreiro"], ["bastidas", "Bastidas"],
    ["belalcázar", "Belalcázar"], ["bravo", "Bravo"], ["brisueno", "Risueño"], ["burbano", "Burbano"], 
    ["calpa", "Calpa"], ["cuadros", "Cuadros"], ["calvache", "Calvache"], ["calvacci", "Calvachy"], 
    ["calvachi", "Calvachy"], ["calvachí", "Calvachy"], ["cedeño", "Cedeño"],
    ["collés", "Goyes"], ["canal", "Canal"], ["coral", "Coral"], ["correa", "Correa"], ["cortés", "Cortés"], ["cadena", "Cadena"],
    ["flores", "Flores"], ["coyez", "Goyes"], 
    ["derecho", "Derecho"], ["dolores", "Dolores"], ["edilma", "Edilma"], ["escudero", "Escudero"],
    ["especialización", "Especialización"], ["especialización", "Especialización"], ["erazo", "Erazo"], 
    ["estupiñán", "Estupiñán"], 
    ["galán", "Galán"], ["giraldo", "Giraldo"], ["goiles", "Goyes"], ["goyes", "Goyes"],
    ["goyés", "Goyes"], ["goyez", "Goyes"], ["goyis", "Goyes"], ["goiz", "Goyes"], ["guerra", "Guerra"], ["gloria", "Gloria"], ["familia", "Familia"],
    ["hoyos", "Hoyos"], ["insuasti", "Insuasty"], ["jojoa", "Jojoa"], 
    ["llano", "Llano"], ["lafon", "Lafon"], ["lagos", "Lagos"], ["leyton", "Leyton"],
    ["legis", "LEGIS"], ["legarda", "Legarda"], ["libardo", "Libardo"], ["liborio", "Liborio"], ["livorio", "Livorio"],
    ["luz", "Luz"], ["luna", "Luna"],
    ["mafla", "Mafla"], ["madroñero", "Madroñero"], 
    ["maturana", "Maturana"], ["marco", "Marco"], ["materón", "Materón"], ["mesa", "Mesa"], ["miriam", "Myriam"], 
    ["morasurco", "Morasurco"], ["monsalve", "Monsalve"], ["monsalvo", "Monsalvo"],["morillo", "Morillo"], ["montanchez", "Montanchez"],
    ["munera", "Munera"], ["maigual", "Maigual"], ["moncayo", "Moncayo"], ["nariño", "Nariño"], ["navia", "Navia"],
    ["ocaña", "Ocaña"], ["oliva", "Oliva"], ["osejo", "Osejo"], ["ocara", "OCARA"], 
    ["palacios", "Palacios"],
    ["paredes", "Paredes"], ["pasos", "Pasos"], ["peñafiel", "Peñafiel"], ["pinilla", "Pinilla"], ["poveda", "Poveda"],
    ["ramos", "Ramos"], ["reina", "Reina"],
    ["risueño", "Risueño"], ["revelo", "Revelo"], ["riascos", "Riascos"], ["ríos", "Ríos"], ["rosa", "Rosa"], ["rojas", "Rojas"], ["rugeles", "Rugeles"],
    ["sevillano", "Sevillano"], ["solarte", "Solarte"], ["sotelo", "Sotelo"], 
    ["tajumbina", "Tajumbina"], ["tazcón", "Tascón"], ["toscano", "Toscano"], ["tutistar", "Tutistar"],
    ["umaña", "Umaña"], ["universidad", "Universidad"], ["urresta", "Urresta"], ["urbano", "Urbano"], ["vela", "Vela"], ["villota", "Villota"],
    ["Wanda", "Whanda"],
    ["vinueza", "Vinueza"], ["viteri", "Viteri"], ["zarama", "Zarama"]
  ];

  const mapaReemplazos = new Map(
  reemplazos.map(([clave, valor]) => [
    clave.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase(),
    valor
  ])
);


function aplicarReemplazos(texto) {
  return texto.split(/\s+/).map(palabra => {
    const match = palabra.match(/^([\wáéíóúüñÁÉÍÓÚÜÑ]+)([.,;:]*)$/); // separa palabra y puntuación
    const base = match ? match[1] : palabra;
    const puntuacion = match ? match[2] : '';

    const clave = base
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase();

    const reemplazo = mapaReemplazos.get(clave);

    // DEBUG opcional
    console.log({ original: palabra, base, clave, reemplazo });

    return reemplazo ? reemplazo + puntuacion : base + puntuacion;
  }).join(' ');
}



  function iniciarReconocimiento() {
    if (!('webkitSpeechRecognition' in window)) {
      alert("Tu navegador no soporta reconocimiento de voz.");
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
          fragmentos.push(aplicarReemplazos(transcrito));
        }
      }

      if (fragmentos.length) {
        const ta = document.getElementById('titulo');
        const textoFinal = fragmentos.join(' ');
        ta.value += (ta.value ? ' ' : '') + textoFinal;
        ta.focus();
        ta.setSelectionRange(ta.value.length, ta.value.length);
      }
    };

    recognition.onend = () => {
      if (grabando) recognition.start();
    };

    recognition.start();
    grabando = true;
    document.getElementById('grabarBoton').classList.add('grabando');
  }

  function detenerReconocimiento() {
    if (recognition) recognition.stop();
    grabando = false;
    document.getElementById('grabarBoton').classList.remove('grabando');

    const ta = document.getElementById('titulo');
    ta.focus();
    ta.setSelectionRange(ta.value.length, ta.value.length);
  }

  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('grabarBoton');
    btn.addEventListener('click', () => {
      grabando ? detenerReconocimiento() : iniciarReconocimiento();
    });

      document.addEventListener('keydown', e => {
        if ((e.key === 'F2' || e.key === 'F9') && !grabando) {
          iniciarReconocimiento();
        }
      });

      document.addEventListener('keyup', e => {
        if ((e.key === 'F2' || e.key === 'F9') && grabando) {
          detenerReconocimiento();
        }
      });
      
  });
})();
