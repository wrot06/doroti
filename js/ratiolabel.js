document.addEventListener("DOMContentLoaded", function () {

    function actualizarRadios() {
        // quitar negrita a todos los labels
        document.querySelectorAll('.form-check-label').forEach(function(label) {
            label.classList.remove("radio-bold");
        });

        // poner negrita al label del seleccionado
        let seleccionado = document.querySelector('input[name="etiqueta"]:checked');
        if (seleccionado) {
            let label = document.querySelector('label[for="' + seleccionado.id + '"]');
            if (label) {
                label.classList.add("radio-bold");
            }
        }
    }

    // escuchar cambios en los radios
    document.querySelectorAll('input[name="etiqueta"]').forEach(function(radio) {
        radio.addEventListener("change", actualizarRadios);
    });

    // inicializar (por si ya hay uno marcado al cargar)
    actualizarRadios();
});
