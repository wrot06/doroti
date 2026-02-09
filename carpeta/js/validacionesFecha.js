window.configurarFechas = function(fechaInicialId, fechaFinalId) {
    const fechaInicial = document.getElementById(fechaInicialId);
    const fechaFinal = document.getElementById(fechaFinalId);

    const hoy = new Date().toISOString().split('T')[0];
    let fechaFinalAutorellena = false;

    // Al cargar: limitar máximos
    fechaInicial.max = hoy;
    fechaFinal.max = hoy;

    fechaInicial.addEventListener('change', () => {
        const fi = fechaInicial.value;

        if (!fi) return;

        // Verificación de fecha válida
        if (fi > hoy) {
            alert('La fecha inicial no puede ser mayor que hoy.');
            fechaInicial.value = '';
            return;
        }

        // Establece límites en fecha final
        fechaFinal.min = fi;
        fechaFinal.max = hoy;

        // Solo la primera vez: autocompletar fecha final
        if (!fechaFinalAutorellena) {
            fechaFinal.value = fi;
            fechaFinalAutorellena = true;
        }

        // Si la fecha final ya existe pero está fuera de rango, limpiar
        if (fechaFinal.value && (fechaFinal.value < fi || fechaFinal.value > hoy)) {
            fechaFinal.value = '';
        }
    });

    fechaFinal.addEventListener('change', () => {
        const fi = fechaInicial.value;
        const ff = fechaFinal.value;

        if (!ff || !fi) return;

        if (ff < fi) {
            alert('La fecha final no puede ser menor que la fecha inicial.');
            fechaFinal.value = '';
        } else if (ff > hoy) {
            alert('La fecha final no puede ser mayor que hoy.');
            fechaFinal.value = '';
        }
    });
};
