/* ==========================================
FUNCIONALIDAD: VER/OCULTAR CONTRASEÑA
========================================== */
$("#btnVerClave").on("click", function() {
    let inputClave = $("#ingClave");
    let icono = $(this).find("i");

    if (inputClave.attr("type") === "password") {
        inputClave.attr("type", "text");
        icono.removeClass("fa-eye").addClass("fa-eye-slash text-primary");
    } else {
        inputClave.attr("type", "password");
        icono.removeClass("fa-eye-slash text-primary").addClass("fa-eye");
    }
});

/* ==========================================
PETICIÓN AJAX: INICIO DE SESIÓN
========================================== */
$("#formLogin").on("submit", function(e) {
    
    // Evitamos que la página se recargue (comportamiento por defecto del form)
    e.preventDefault();

    // Capturamos los valores
    let usuario = $("#ingUsuario").val();
    let clave = $("#ingClave").val();
    
    // Cambiamos el texto del botón para dar feedback de carga
    let btnSubmit = $("#btnIngresar");
    let btnOriginalText = btnSubmit.html();
    btnSubmit.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Validando...');
    btnSubmit.prop("disabled", true);

    // Ejecutamos AJAX con jQuery
    $.ajax({
        url: "index.php", // La petición va al router principal
        method: "POST",
        data: {
            ingUsuario: usuario,
            ingClave: clave
        },
        dataType: "json", // Esperamos una respuesta estricta en JSON
        success: function(respuesta) {
            
            // Restauramos el botón
            btnSubmit.html(btnOriginalText);
            btnSubmit.prop("disabled", false);

            if (respuesta.status === "success") {
                
                // Alerta de éxito con SweetAlert2
                Swal.fire({
                    icon: 'success',
                    title: '¡Acceso Concedido!',
                    text: respuesta.mensaje,
                    showConfirmButton: false,
                    timer: 1500
                }).then(function() {
                    // Redirigimos al dashboard recargando la página,
                    // ahora PHP detectará la sesión y cargará la plantilla completa.
                    window.location = "index.php?ruta=dashboard";
                });

            } else {
                // Alerta de error (Credenciales inválidas)
                Swal.fire({
                    icon: 'error',
                    title: 'Error de acceso',
                    text: respuesta.mensaje,
                    confirmButtonText: 'Reintentar'
                });
                
                // Limpiamos la contraseña por seguridad
                $("#ingClave").val("");
            }
        },
        error: function(xhr, status, error) {
            // Manejo de errores de servidor (ej. error 500 en PHP)
            btnSubmit.html(btnOriginalText);
            btnSubmit.prop("disabled", false);
            
            console.error("Error AJAX:", xhr.responseText);
            
            Swal.fire({
                icon: 'error',
                title: 'Error crítico del servidor',
                text: 'Revisa la consola de tu navegador para más detalles.',
            });
        }
    });
});