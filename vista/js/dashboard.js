$(document).ready(function() {

    /* ==========================================
    1. EL GATILLO SILENCIOSO
    ========================================== */
    // Se ejecuta 1 segundo después de cargar el dashboard para no ralentizar la interfaz visual
    setTimeout(function() {
        $.ajax({
            url: "index.php",
            method: "POST",
            data: { tipoSincronizacion: "silencioso" },
            dataType: "json",
            success: function(respuesta) {
                // Si el backend dice "success", significa que pasaron 60 min y hubo un cambio
                if (respuesta.status === "success") {
                    console.log("EasyPOS - Gatillo: Tasas actualizadas en segundo plano.");
                    // Refrescamos silenciosamente la página para mostrar los nuevos números
                    window.location.reload(); 
                } else {
                    // Si dice "skip" o "info", no hacemos nada.
                    console.log("EasyPOS - Gatillo: " + respuesta.mensaje);
                }
            }
        });
    }, 1000);


    /* ==========================================
    2. EL BOTÓN DE REVISIÓN FORZADA
    ========================================== */
    $("#btnForzarSincronizacion").on("click", function() {
        
        let btn = $(this);
        let icono = btn.find("i");
        let textoOriginal = btn.html();
        
        // Efecto visual de carga
        icono.addClass("fa-spin");
        btn.html('<i class="fas fa-sync-alt fa-spin me-1"></i> Revisando...');
        btn.prop("disabled", true);

        $.ajax({
            url: "index.php",
            method: "POST",
            data: { tipoSincronizacion: "forzado" },
            dataType: "json",
            success: function(respuesta) {
                
                // Restauramos el botón
                btn.html(textoOriginal);
                btn.prop("disabled", false);

                if (respuesta.status === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Tasas Actualizadas!',
                        text: respuesta.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        window.location.reload();
                    });
                } else if (respuesta.status === "info") {
                    Swal.fire({
                        icon: 'info',
                        title: 'Todo al día',
                        text: respuesta.mensaje,
                        confirmButtonColor: '#3085d6'
                    });
                }
            },
            error: function() {
                btn.html(textoOriginal);
                btn.prop("disabled", false);
                Swal.fire('Error', 'No se pudo contactar con los servidores de cambio.', 'error');
            }
        });
    });

});