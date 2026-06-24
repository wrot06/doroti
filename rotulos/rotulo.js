// rotulo.js

document.addEventListener("DOMContentLoaded", function () {
    const acc = document.querySelectorAll(".accordion");
    acc.forEach((button) => {
        button.addEventListener("click", function () {
            const row = this.closest('tr');
            if (!row) return;
            const panel = row.nextElementSibling;
            if (!panel || !panel.classList.contains('panel')) return;
            
            if (panel.style.display === "none" || panel.style.display === "") {
                panel.style.display = "table-row";
                this.textContent = "^";
            } else {
                panel.style.display = "none";
                this.textContent = "v";
            }
        });
    });
});
