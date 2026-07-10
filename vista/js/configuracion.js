/* ==============================================================
   MÓDULO DE CONFIGURACIÓN Y SUPER ADMIN (AJAX)
   ============================================================== */

$(document).ready(function() {

    // 1. DESBLOQUEAR EL NÚCLEO (PIN)
    $("#formDesbloquearCore").on("submit", function(e) {
        e.preventDefault();
        
        let btnSubmit = $(this).find('button[type="submit"]');
        let btnOriginalText = btnSubmit.html();
        
        // Efecto de carga
        btnSubmit.html('<span class="spinner-border spinner-border-sm me-2"></span> Verificando...');
        btnSubmit.prop("disabled", true);

        $.ajax({
            url: "index.php",
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                if(res.status === "success") {
                    // Recargamos la página fluidamente para mostrar el formulario de configuración
                    window.location = "index.php?ruta=auditoria-core"; 
                } else {
                    btnSubmit.html(btnOriginalText);
                    btnSubmit.prop("disabled", false);
                    Swal.fire("Acceso Denegado", res.mensaje, "error");
                }
            },
            error: function(xhr) {
                btnSubmit.html(btnOriginalText);
                btnSubmit.prop("disabled", false);
                console.error("Error del servidor:", xhr.responseText);
                Swal.fire("Error Fatal", "Fallo de comunicación con el backend. Revise la consola.", "error");
            }
        });
    });

    // 2. ACTUALIZAR LOS DATOS DE IDENTIDAD CORPORATIVA
    $("#formActualizarConfiguracion").on("submit", function(e) {
        e.preventDefault();
        
        let btnSubmit = $(this).find('button[type="submit"]');
        let btnOriginalText = btnSubmit.html();
        
        // Efecto de carga
        btnSubmit.html('<span class="spinner-border spinner-border-sm me-2"></span> Guardando...');
        btnSubmit.prop("disabled", true);

        $.ajax({
            url: "index.php",
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                btnSubmit.html(btnOriginalText);
                btnSubmit.prop("disabled", false);

                if(res.status === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: res.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    Swal.fire("Error", res.mensaje, "error");
                }
            },
            error: function(xhr) {
                btnSubmit.html(btnOriginalText);
                btnSubmit.prop("disabled", false);
                console.error("Error del servidor:", xhr.responseText);
                Swal.fire("Error Interno", "No se pudo actualizar la configuración.", "error");
            }
        });
    });

});