(function(){
  // VARIABLES GLOBALES
  let grabando = false;
  let recognition;

  // MAPA DE REEMPLAZOS
  const reemplazos = [
  ["alirio", "Alirio"], ["arles", "Arles"], ["argotti", "Argoty"], ["barreiro", "Barreiro"], ["bastidas", "Bastidas"],
  ["belalcázar", "Belalcázar"], ["bravo", "Bravo"], ["brisueno", "Risueño"], ["burbano", "Burbano"], ["calpa", "Calpa"], ["cuadros", "Cuadros"],
  ["calvache", "Calvache"], ["calvacci", "Calvachy"], ["calvachi", "Calvachy"], ["calvachí", "Calvachy"], ["collés", "Goyes"],
  ["canal", "Canal"], ["coral", "Coral"], ["correa", "Correa"], ["cortés", "Cortés"], ["cadena", "Cadena"], ["flores", "Flores"],
  ["coyez", "Goyes"], ["derecho", "Derecho"], ["dolores", "Dolores"], ["edilma", "Edilma"], ["especialización", "Especialización"],
  ["erazo", "Erazo"], ["giraldo", "Giraldo"], ["goiles", "Goyes"], ["goyes", "Goyes"], ["goyés", "Goyes"], ["goyez", "Goyes"],
  ["goyis", "Goyes"], ["goiz", "Goyes"], ["guerra", "Guerra"], ["gloria", "Gloria"], ["hoyos", "Hoyos"], ["insuasti", "Insuasty"], ["jojoa", "Jojoa"], ["lagos", "Lagos"],
  ["leyton", "Leyton"], ["legis", "LEGIS"], ["legarda", "Legarda"], ["libardo", "Libardo"], ["madroñero", "Madroñero"],
  ["marco", "Marco"], ["materón", "Materón"], ["miriam", "Myriam"], ["morasurco", "Morasurco"], ["munera", "Munera"],
  ["maigual", "Maigual"], ["moncayo", "Moncayo"], ["nariño", "Nariño"], ["navia", "Navia"], ["ocaña", "Ocaña"],
  ["oliva", "Oliva"], ["osejo", "Osejo"], ["ocara", "OCARA"], ["palacios", "Palacios"], ["paredes", "Paredes"],
  ["pasos", "Pasos"], ["pinilla", "Pinilla"], ["para", "para"], ["ramos", "Ramos"], ["reina", "Reina"], ["risueño", "Risueño"],
  ["revelo", "Revelo"], ["riascos", "Riascos"], ["rosa", "Rosa"], ["rojas", "Rojas"], ["solarte", "Solarte"],
  ["sotelo", "Sotelo"], ["tajumbina", "Tajumbina"], ["tazcón", "Tascón"], ["tutistar", "Tutistar"], ["universidad", "Universidad"],
  ["urresta", "Urresta"], ["urbano", "Urbano"], ["vela", "Vela"], ["villota", "Villota"], ["vinueza", "Vinueza"],
  ["viteri", "Viteri"], ["zarama", "Zarama"]
];

  function aplicarReemplazos(texto) {
    reemplazos.forEach(([orig, rep]) => {
      const re = new RegExp(`\\b${orig}\\b`, "gi");
      texto = texto.replace(re, rep);
    });
    return texto;
  }

  function iniciarReconocimiento() {
    if (!('webkitSpeechRecognition' in window)) {
      alert("Tu navegador no lo soporta.");
      return;
    }
    recognition = new webkitSpeechRecognition();
    recognition.lang = 'es-CO';
    recognition.continuous = true;
    recognition.interimResults = false;

    recognition.onresult = function(evt) {
      let fragmento = [];
      for (let i = evt.resultIndex; i < evt.results.length; i++){
        if (evt.results[i].isFinal) {
          fragmento.push( aplicarReemplazos(evt.results[i][0].transcript.trim()) );
        }
      }
      if (fragmento.length) {
        const ta = document.getElementById('titulo');
        let textoFinal = fragmento.join(' ');
        // opcional: límite de caracteres
        ta.value += (ta.value ? ' ' : '') + textoFinal;
        ta.focus();
        ta.setSelectionRange(ta.value.length, ta.value.length);
      }
    };

    recognition.onend = () => { if (grabando) recognition.start(); };
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

  // EVENTOS
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('grabarBoton');
    btn.addEventListener('click', () => {
      grabando ? detenerReconocimiento() : iniciarReconocimiento();
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'F2' && !grabando) iniciarReconocimiento();
    });
    document.addEventListener('keyup', e => {
      if (e.key === 'F2' && grabando) detenerReconocimiento();
    });
  });

})();
