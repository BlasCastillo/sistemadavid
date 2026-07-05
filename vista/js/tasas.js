/* ==========================================
GUARDAR TASAS MANUALMENTE (AJAX)
========================================== */
$("#formTasasManual").on("submit", function(e) {
    e.preventDefault();

    let btnSubmit = $("#btnGuardarTasa");
    let btnOriginalText = btnSubmit.html();
    
    // Alerta de confirmación antes de afectar los precios de toda la tienda
    Swal.fire({
        title: '¿Está seguro?',
        text: "Al actualizar las tasas manualmente, se recalculará inmediatamente el costo de reposición de todos los productos en el inventario.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, forzar tasas',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        
        if (result.isConfirmed) {
            
            btnSubmit.html('<span class="spinner-border spinner-border-sm"></span> Guardando...');
            btnSubmit.prop("disabled", true);

            // Obtenemos los datos del formulario
            let datosFormulario = $(this).serialize();

            $.ajax({
                url: "index.php", // Va al Front Controller
                method: "POST",
                data: datosFormulario,
                dataType: "json",
                success: function(respuesta) {
                    
                    btnSubmit.html(btnOriginalText);
                    btnSubmit.prop("disabled", false);

                    if (respuesta.status === "success") {
                        Swal.fire({
                            icon: 'success',
                            title: 'Actualizado',
                            text: respuesta.mensaje,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function() {
                            // Recargamos para ver los nuevos datos
                            window.location = "index.php?ruta=tasas-cambio";
                        });
                    } else {
                        Swal.fire('Error', respuesta.mensaje, 'error');
                    }
                },
                error: function(xhr) {
                    btnSubmit.html(btnOriginalText);
                    btnSubmit.prop("disabled", false);
                    console.error(xhr.responseText);
                    Swal.fire('Error', 'Ocurrió un error crítico en el servidor.', 'error');
                }
            });
        }
    });
});