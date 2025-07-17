// =======================
// 1. Cambiar botón al enfocar
// =======================
$(document).on("focus", ".editable", function () {
    const $fila = $(this).closest("tr");
    const $boton = $fila.find("button");

    // Quitar botón editar de otros
    $(".editar").removeClass("editar btn-dark").addClass("eliminar btn-success").text("Eliminar");

    // Este se vuelve "Editar"
    $boton.removeClass("eliminar btn-success").addClass("editar btn-dark").text("Editar");

    // Marcar visualmente esta celda
    $(".editable").removeClass("editable-focus");
    $(this).addClass("editable-focus");
});

// =======================
// 2. Revertir botón y fondo al perder foco
// =======================
$(document).on("blur", ".editable", function () {
    const $fila = $(this).closest("tr");
    const $celda = $(this);

    setTimeout(() => {
        if (!$fila.find(":focus").length) {
            const $boton = $fila.find("button");
            $boton.removeClass("editar btn-dark").addClass("eliminar btn-success").text("Eliminar");
            $celda.removeClass("editable-focus");
        }
    }, 150);
});

// =======================
// 3. Hover visual en celda editable
// =======================
$(document).on("mouseenter", ".editable", function () {
    $(this).addClass("editable-hover");
});
$(document).on("mouseleave", ".editable", function () {
    $(this).removeClass("editable-hover");
});

// =======================
// 4. Cerrar modal: restaurar botones y enfocar textarea
// =======================
$('#editarCapituloModal').on('hidden.bs.modal', function () {
    $(".editar").each(function () {
        $(this)
            .removeClass("editar btn-info")
            .addClass("eliminar btn-success")
            .text("Eliminar");
    });

    $(".editable").removeClass("editable-focus");

    setTimeout(() => {
        $("#titulo").focus();
    }, 300);
});

// =======================
// 5. Abrir modal para editar
// =======================
$(document).on("click", ".editar", function () {
    const $fila = $(this).closest("tr");

    const id = $fila.data("id");
    const tituloCompleto = $fila.find(".editable").text().trim();

    const partes = tituloCompleto.split(":");
    const serie = partes.length > 1 ? partes[0].trim() : "";
    const titulo = partes.length > 1 ? partes.slice(1).join(":").trim() : tituloCompleto;

    $("#editId").val(id);
    $("#editTitulo").val(titulo);

    const $select = $("#editSerie").empty();

    $("input[name='etiqueta']").each(function () {
        const etiqueta = $(this).val();
        const selected = (etiqueta === serie) ? "selected" : "";
        $select.append(`<option value="${etiqueta}" ${selected}>${etiqueta}</option>`);
    });

    $("#editarCapituloModal").modal("show");
});

// =======================
// 6. Guardar cambios desde modal
// =======================
$("#guardarCambios").on("click", function () {
    const id = $("#editId").val();
    const nuevoTitulo = $("#editTitulo").val().trim();
    const nuevaSerie = $("#editSerie").val();

    if (!nuevoTitulo || !nuevaSerie) {
        alert("Todos los campos son obligatorios.");
        return;
    }

    const tituloCompleto = `${nuevaSerie}: ${nuevoTitulo}`;

    $.ajax({
        url: 'rene/editar_capitulo.php',
        method: 'POST',
        data: {
            id: id,
            titulo: tituloCompleto,
            caja: window.caja,
            carpeta: window.carpeta
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                const $fila = $(`#capitulosTable tbody tr[data-id="${id}"]`);
                $fila.find(".editable").text(tituloCompleto);

                $("#editarCapituloModal").modal("hide");
            } else {
                alert(response.message || "Error al guardar cambios.");
            }
        },
        error: function (xhr) {
            console.error("Error:", xhr.responseText);
            alert("Error en la solicitud.");
        }
    });
});


// Permitir guardar con ENTER dentro del modal
$("#editarCapituloModal").on("keydown", function (e) {
    if (e.key === "Enter") {
        e.preventDefault(); // Evita que se envíe algún formulario accidental
        $("#guardarCambios").click(); // Dispara el mismo botón
    }
});

