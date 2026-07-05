$(document).ready(function() {

    /* ==========================================
    1. REGISTRAR USUARIO (AJAX)
    ========================================== */
    $("#formAgregarUsuario").on("submit", function(e) {
        e.preventDefault();
        let datos = $(this).serialize();

        $.ajax({
            url: "index.php",
            method: "POST",
            data: datos,
            dataType: "json",
            success: function(respuesta) {
                if (respuesta.status === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Operación Exitosa!',
                        text: respuesta.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        window.location = "index.php?ruta=usuarios";
                    });
                } else {
                    Swal.fire('Error', respuesta.mensaje, 'error');
                }
            }
        });
    });

    
    /* ==========================================
2. PROCESAR ACTUALIZACIÓN DE DATOS (AJAX)
========================================== */
$("#formEditarUsuario").on("submit", function(e) {
    e.preventDefault();
    let datos = $(this).serialize();

    $.ajax({
        url: "index.php",
        method: "POST",
        data: datos,
        dataType: "json",
        success: function(respuesta) {
            if (respuesta.status === "success") {
                Swal.fire({
                    icon: 'success',
                    title: '¡Actualizado!',
                    text: respuesta.mensaje,
                    showConfirmButton: false,
                    timer: 1500
                }).then(function() {
                    // EL CAMBIO: Redireccionamos a la tabla
                    window.location = "index.php?ruta=usuarios";
                });
            } else {
                Swal.fire('Error', respuesta.mensaje, 'error');
            }
        }
    });
});

    /* ==========================================
    3. DESACTIVAR USUARIO (BORRADO LÓGICO AJAX)
    ========================================== */
    $(".btnEliminarUsuario").on("click", function() {
        let idUsuario = $(this).attr("idUsuario");
        let nombre = $(this).attr("nombreUsuario");

        Swal.fire({
            title: '¿Desea desactivar a este usuario?',
            text: "El usuario '" + nombre + "' perderá el acceso inmediato al sistema, pero su historial de operaciones quedará preservado.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "index.php",
                    method: "POST",
                    data: { idUsuarioEliminar: idUsuario },
                    dataType: "json",
                    success: function(respuesta) {
                        if (respuesta.status === "success") {
                            Swal.fire({
                                icon: 'success',
                                title: 'Desactivado',
                                text: respuesta.mensaje,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(function() {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', respuesta.mensaje, 'error');
                        }
                    }
                });
            }
        });
    });
    /* ==========================================
5. ACTUALIZAR CONTRASEÑA (AJAX)
========================================== */
$("#formEditarClave").on("submit", function(e) {
    e.preventDefault();
    
    let clave1 = $("#nuevaClaveSegura").val();
    let clave2 = $("#confirmarClave").val();

    // Validación de seguridad frontal (NIST)
    if (clave1 !== clave2) {
        $("#errorClave").removeClass("d-none"); // Mostramos la advertencia roja
        return; // Detenemos la ejecución
    } else {
        $("#errorClave").addClass("d-none");
    }

    let datos = $(this).serialize();

    $.ajax({
        url: "index.php",
        method: "POST",
        data: datos,
        dataType: "json",
        success: function(respuesta) {
            if (respuesta.status === "success") {
                Swal.fire({
                    icon: 'success',
                    title: '¡Contraseña Protegida!',
                    text: respuesta.mensaje,
                    showConfirmButton: false,
                    timer: 1500
                }).then(function() {
                    window.location = "index.php?ruta=usuarios";
                });
                 } else {
                   Swal.fire('Error', respuesta.mensaje, 'error');
                        }
            }
        });
    });

    /* ==========================================
    6. REACTIVAR USUARIO (AJAX)
    ========================================== */
    $(".btnActivarUsuario").on("click", function() {
        let idUsuario = $(this).attr("idUsuario");
        let nombre = $(this).attr("nombreUsuario");

        Swal.fire({
            title: '¿Reactivar usuario?',
            text: "El usuario '" + nombre + "' volverá a tener acceso al sistema.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754', // Color verde Success
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, reactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "index.php",
                    method: "POST",
                    data: { idUsuarioActivar: idUsuario },
                    dataType: "json",
                    success: function(respuesta) {
                        if (respuesta.status === "success") {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Reactivado!',
                                text: respuesta.mensaje,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(function() {
                                window.location = "index.php?ruta=usuarios";
                            });
                        } else {
                            Swal.fire('Error', respuesta.mensaje, 'error');
                        }
                    }
                });
            }
        });
    });
});