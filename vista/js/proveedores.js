/* ==========================================
1. CREAR PROVEEDOR (AJAX)
========================================== */
$("#formAgregarProveedor").on("submit", function(e) {
    e.preventDefault();
    $.ajax({
        url: "index.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(respuesta) {
            if (respuesta.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Guardado!', text: respuesta.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=proveedores"; });
            } else {
                Swal.fire('Error', respuesta.mensaje, 'error');
            }
        }
    });
});

/* ==========================================
2. EDITAR PROVEEDOR (AJAX)
========================================== */
$("#formEditarProveedor").on("submit", function(e) {
    e.preventDefault();
    $.ajax({
        url: "index.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(respuesta) {
            if (respuesta.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Actualizado!', text: respuesta.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=proveedores"; });
            } else {
                Swal.fire('Error', respuesta.mensaje, 'error');
            }
        }
    });
});

/* ==========================================
3. DESACTIVAR PROVEEDOR (AJAX)
========================================== */
$(".btnEliminarProveedor").on("click", function() {
    let id = $(this).attr("idProveedor");
    let nombre = $(this).attr("nombreProveedor");

    Swal.fire({
        title: '¿Desactivar proveedor?',
        text: "La empresa '" + nombre + "' pasará a la lista de inactivos.",
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
                data: { idProveedorEliminar: id },
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
4. REACTIVAR PROVEEDOR (AJAX)
========================================== */
$(".btnActivarProveedor").on("click", function() {
    let id = $(this).attr("idProveedor");
    let nombre = $(this).attr("nombreProveedor");

    Swal.fire({
        title: '¿Reactivar proveedor?',
        text: "La empresa '" + nombre + "' volverá a estar disponible para compras.",
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
                data: { idProveedorActivar: id },
                dataType: "json",
                success: function(respuesta) {
                    if (respuesta.status === "success") {
                        window.location = "index.php?ruta=proveedores";
                    } else {
                        Swal.fire('Error', respuesta.mensaje, 'error');
                    }
                }
            });
        }
    });
});