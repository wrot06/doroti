function centrarTextarea() {
    const textarea = document.getElementById("titulo");
    if (textarea) {
        textarea.scrollIntoView({ behavior: "smooth", block: "center" });
        textarea.focus();
    }
}

document.addEventListener("DOMContentLoaded", function () {
    centrarTextarea(); // Al iniciar la página
});

// Este evento puede ser disparado desde capituloForm.js después de agregar un capítulo
document.addEventListener("capitulo-agregado", function () {
    centrarTextarea(); // Cada vez que se agregue un capítulo
});
