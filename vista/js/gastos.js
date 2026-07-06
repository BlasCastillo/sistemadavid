// 1. REGISTRAR GASTO
$("#formAgregarGasto").on("submit", function(e) {
    e.preventDefault();
    $.ajax({
        url: "index.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(res) {
            if (res.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=gastos"; });
            } else { Swal.fire('Error', res.mensaje, 'error'); }
        }
    });
});

// 2. EDITAR GASTO
$("#formEditarGasto").on("submit", function(e) {
    e.preventDefault();
    $.ajax({
        url: "index.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(res) {
            if (res.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Actualizado!', text: res.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=gastos"; });
            } else { Swal.fire('Error', res.mensaje, 'error'); }
        }
    });
});

// 3. ANULAR GASTO
$(".btnAnularGasto").on("click", function() {
    let id = $(this).attr("idGasto");
    let concepto = $(this).attr("conceptoGasto");
    Swal.fire({
        title: '¿Anular este gasto operativo?',
        text: "El registro '" + concepto + "' será descontado del cálculo financiero.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, anular'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "index.php",
                method: "POST",
                data: { idGastoAnular: id },
                dataType: "json",
                success: function(res) {
                    if (res.status === "success") { window.location.reload(); }
                    else { Swal.fire('Error', res.mensaje, 'error'); }
                }
            });
        }
    });
});

// 4. RESTAURAR GASTO
$(".btnReactivarGasto").on("click", function() {
    let id = $(this).attr("idGasto");
    let concepto = $(this).attr("conceptoGasto");
    Swal.fire({
        title: '¿Restaurar este gasto?',
        text: "El registro '" + concepto + "' volverá a computar como egreso activo.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Sí, restaurar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "index.php",
                method: "POST",
                data: { idGastoReactivar: id },
                dataType: "json",
                success: function(res) {
                    if (res.status === "success") { window.location = "index.php?ruta=gastos"; }
                    else { Swal.fire('Error', res.mensaje, 'error'); }
                }
            });
        }
    });
});