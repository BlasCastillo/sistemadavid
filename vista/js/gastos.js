/* ==========================================
   1. REGISTRAR GASTO
========================================== */
$(document).on("submit", "#formAgregarGasto", function(e) {
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

/* ==========================================
   2. EDITAR GASTO
========================================== */
$(document).on("submit", "#formEditarGasto", function(e) {
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

/* ==========================================
   3. ANULAR GASTO (CON BANDERAS DE DEBUG)
========================================== */
$(document).on("click", ".btnAnularGasto", function() {
    let id = $(this).attr("idGasto");
    let concepto = $(this).attr("conceptoGasto");
    
    console.log("🚩 BANDERA 1: Clic en Anular detectado.");
    console.log("🚩 BANDERA 2: ID capturado ->", id);
    console.log("🚩 BANDERA 3: Concepto capturado ->", concepto);

    Swal.fire({
        title: '¿Anular este gasto operativo?',
        text: "El registro '" + concepto + "' será descontado del cálculo financiero.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, anular'
    }).then((result) => {
        if (result.isConfirmed) {
            
            console.log("🚩 BANDERA 4: Usuario confirmó Swal. Enviando AJAX...");
            
            $.ajax({
                url: "index.php",
                method: "POST",
                data: { idGastoAnular: id },
                dataType: "json",
                success: function(res) {
                    console.log("🚩 BANDERA 5 (ÉXITO): Respuesta del servidor ->", res);
                    if (res.status === "success") { 
                        window.location.reload(); 
                    } else { 
                        Swal.fire('Error', res.mensaje, 'error'); 
                    }
                },
                error: function(xhr, status, error) {
                    // AQUÍ ESTÁ EL DETECTOR DE FALLAS SILENCIOSAS
                    console.error("🚨 ERROR AJAX DETECTADO");
                    console.error("Estado:", status);
                    console.error("Error:", error);
                    console.error("Respuesta cruda de PHP (responseText):", xhr.responseText);
                    
                    Swal.fire('Error Fatal Backend', 'Revisa la consola (F12) para ver por qué PHP está fallando.', 'error');
                }
            });
        }
    });
});

/* ==========================================
   4. RESTAURAR GASTO (CON BANDERAS DE DEBUG)
========================================== */
$(document).on("click", ".btnReactivarGasto", function() {
    let id = $(this).attr("idGasto");
    let concepto = $(this).attr("conceptoGasto");
    
    console.log("🚩 BANDERA 1: Clic en Restaurar detectado. ID:", id);

    Swal.fire({
        title: '¿Restaurar este gasto?',
        text: "El registro '" + concepto + "' volverá a computar como egreso activo.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Sí, restaurar'
    }).then((result) => {
        if (result.isConfirmed) {
            console.log("🚩 BANDERA 2: Enviando AJAX de restauración...");
            
            $.ajax({
                url: "index.php",
                method: "POST",
                data: { idGastoReactivar: id },
                dataType: "json",
                success: function(res) {
                    console.log("🚩 BANDERA 3 (ÉXITO): Respuesta del servidor ->", res);
                    if (res.status === "success") { 
                        window.location.reload(); 
                    } else { 
                        Swal.fire('Error', res.mensaje, 'error'); 
                    }
                },
                error: function(xhr, status, error) {
                    console.error("🚨 ERROR AJAX DETECTADO");
                    console.error("Respuesta cruda de PHP:", xhr.responseText);
                    Swal.fire('Error Fatal Backend', 'Revisa la consola.', 'error');
                }
            });
        }
    });
});