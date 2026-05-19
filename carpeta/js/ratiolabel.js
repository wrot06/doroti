document.addEventListener("DOMContentLoaded", function () { 

    function actualizarRadios() {
        // Restablecer estilos de todos los labels
        document.querySelectorAll('.form-check-label').forEach(function(label) {
            label.classList.remove("radio-bold");
            label.style.color = ""; 
            label.style.textDecoration = "";            
            label.style.fontWeight = ""; 
            label.style.fontSize = ""; 
            label.style.textTransform = "";
        });

        // Aplicar estilos al label del radio seleccionado
        let seleccionado = document.querySelector('input[name="etiqueta"]:checked');
        if (seleccionado) {
            let label = document.querySelector('label[for="' + seleccionado.id + '"]');
            if (label) {
                label.classList.add("radio-bold");
                label.style.color = "#a4151cff";
                label.style.textDecoration = "underline";
                label.style.fontWeight = "bold";
            }
        }
    }

    // Escuchar cambios en los radios
    document.querySelectorAll('input[name="etiqueta"]').forEach(function(radio) {
        radio.addEventListener("change", actualizarRadios);
    });

    // Inicializar al cargar
    actualizarRadios();
});
