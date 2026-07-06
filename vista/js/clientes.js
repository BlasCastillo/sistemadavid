/* ==========================================
1. CREAR CLIENTE (AJAX)
========================================== */
$("#formAgregarCliente").on("submit", function(e) {
    e.preventDefault();
    $.ajax({
        url: "index.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(respuesta) {
            if (respuesta.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Guardado!', text: respuesta.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=clientes"; });
            } else {
                Swal.fire('Error', respuesta.mensaje, 'error');
            }
        }
    });
});

/* ==========================================
2. EDITAR CLIENTE (AJAX)
========================================== */
$("#formEditarCliente").on("submit", function(e) {
    e.preventDefault();
    $.ajax({
        url: "index.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(respuesta) {
            if (respuesta.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Actualizado!', text: respuesta.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=clientes"; });
            } else {
                Swal.fire('Error', respuesta.mensaje, 'error');
            }
        }
    });
});

/* ==========================================
3. DESACTIVAR CLIENTE (AJAX)
========================================== */
$(".btnEliminarCliente").on("click", function() {
    let id = $(this).attr("idCliente");
    let nombre = $(this).attr("nombreCliente");

    Swal.fire({
        title: '¿Desactivar cliente?',
        text: "El cliente '" + nombre + "' pasará a la lista de inactivos.",
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
                data: { idClienteEliminar: id },
                dataType: "json",
                success: function(respuesta) {
                    if (respuesta.status === "success") {
                        window.location.reload();
                    } else {
                        Swal.fire('Error', respuesta.mensaje, 'error');
                    }
                }
            });
        }
    });
});

/* ==========================================
4. REACTIVAR CLIENTE (AJAX)
========================================== */
$(".btnActivarCliente").on("click", function() {
    let id = $(this).attr("idCliente");
    let nombre = $(this).attr("nombreCliente");

    Swal.fire({
        title: '¿Reactivar cliente?',
        text: "El cliente '" + nombre + "' volverá a estar disponible para facturación.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, reactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "index.php",
                method: "POST",
                data: { idClienteActivar: id },
                dataType: "json",
                success: function(respuesta) {
                    if (respuesta.status === "success") {
                        window.location = "index.php?ruta=clientes";
                    } else {
                        Swal.fire('Error', respuesta.mensaje, 'error');
                    }
                }
            });
        }
    });
});