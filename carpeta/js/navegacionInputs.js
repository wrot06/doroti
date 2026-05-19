// js/navegacionInputs.js

function activarNavegacionConEnter(textareaId, siguienteInputId) {
    const textarea = document.getElementById(textareaId);
    const siguienteInput = document.getElementById(siguienteInputId);

    if (!textarea || !siguienteInput) {
        console.warn("IDs inválidos:", textareaId, siguienteInputId);
        return;
    }

    textarea.addEventListener("keydown", function (event) {
        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            const valor = textarea.value.trim();
            if (valor !== "") {
                siguienteInput.focus();
            }
        }
    });
}
