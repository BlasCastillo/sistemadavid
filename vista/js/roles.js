/* ==========================================
1. CREAR ROL (AJAX)
========================================== */
$("#formAgregarRol").on("submit", function(e) {
    e.preventDefault();
    let datos = $(this).serialize();

    $.ajax({
        url: "index.php",
        method: "POST",
        data: datos,
        dataType: "json",
        success: function(respuesta) {
            if (respuesta.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Guardado!', text: respuesta.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=roles"; });
            } else {
                Swal.fire('Error', respuesta.mensaje, 'error');
            }
        }
    });
});

/* ==========================================
2. ACTUALIZAR NOMBRE DEL ROL (AJAX)
========================================== */
$("#formEditarRol").on("submit", function(e) {
    e.preventDefault();
    let datos = $(this).serialize();

    $.ajax({
        url: "index.php",
        method: "POST",
        data: datos,
        dataType: "json",
        success: function(respuesta) {
            if (respuesta.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Nombre Actualizado!', text: respuesta.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=roles"; });
            } else {
                Swal.fire('Error', respuesta.mensaje, 'error');
            }
        }
    });
});

/* ==========================================
3. GUARDAR PERMISOS DINÁMICOS (AJAX)
========================================== */
$("#formPermisosRol").on("submit", function(e) {
    e.preventDefault();
    let datos = $(this).serialize();

    $.ajax({
        url: "index.php",
        method: "POST",
        data: datos,
        dataType: "json",
        success: function(respuesta) {
            if (respuesta.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Permisos Actualizados!', text: respuesta.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=roles"; });
            } else {
                Swal.fire('Error', respuesta.mensaje, 'error');
            }
        }
    });
});

/* ==========================================
4. ELIMINAR ROL (AJAX - Se ejecuta desde la tabla)
========================================== */
$(".btnEliminarRol").on("click", function() {
    let idRol = $(this).attr("idRol");

    Swal.fire({
        title: '¿Está seguro de borrar este Rol?',
        text: "Si hay usuarios usando este rol, la acción será bloqueada por seguridad.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, borrar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "index.php",
                method: "POST",
                data: { idRolEliminar: idRol },
                dataType: "json",
                success: function(respuesta) {
                    if (respuesta.status === "success") {
                        Swal.fire({ icon: 'success', title: '¡Eliminado!', text: respuesta.mensaje, showConfirmButton: false, timer: 1500 })
                        .then(function() { window.location.reload(); }); // Aquí SÍ usamos reload porque estamos en la tabla
                    } else {
                        Swal.fire('Error', respuesta.mensaje, 'error');
                    }
                }
            });
        }
    });
});