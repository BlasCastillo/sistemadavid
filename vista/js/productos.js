/* ==========================================
1. CREAR PRODUCTO (AJAX CON IMAGEN)
========================================== */
$("#formAgregarProducto").on("submit", function (e) {
    e.preventDefault();

    // FormData es obligatorio para poder capturar la fotografía (<input type="file">)
    let formData = new FormData(this);

    $.ajax({
        url: "index.php",
        method: "POST",
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (res) {
            if (res.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Producto Registrado!', text: res.mensaje, showConfirmButton: false, timer: 2000 })
                    .then(function () { window.location = "index.php?ruta=productos"; });
            } else {
                Swal.fire('Error', res.mensaje, 'error');
            }
        },
        // NUEVO BLOQUE: Detector de errores fatales del servidor
        error: function (xhr, status, error) {
            console.error("Respuesta cruda del servidor:", xhr.responseText);
            Swal.fire('Error Interno', 'Revisa la consola. El servidor falló al procesar los datos.', 'error');
        }
    });

});

/* ==========================================
2. EDITAR PRODUCTO (AJAX CON IMAGEN)
========================================== */
$("#formEditarProducto").on("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(this);

    $.ajax({
        url: "index.php",
        method: "POST",
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (res) {
            if (res.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Producto Registrado!', text: res.mensaje, showConfirmButton: false, timer: 2000 })
                    .then(function () { window.location = "index.php?ruta=productos"; });
            } else {
                Swal.fire('Error', res.mensaje, 'error');
            }
        },
        // NUEVO BLOQUE: Detector de errores fatales del servidor
        error: function (xhr, status, error) {
            console.error("Respuesta cruda del servidor:", xhr.responseText);
            Swal.fire('Error Interno', 'Revisa la consola. El servidor falló al procesar los datos.', 'error');
        }
    });
});

/* ==========================================
3. DESACTIVAR PRODUCTO
========================================== */
$(".btnEliminarProducto").on("click", function () {
    let id = $(this).attr("idProducto");
    let nombre = $(this).attr("nombreProducto");

    Swal.fire({
        title: '¿Desactivar producto?',
        text: "El producto '" + nombre + "' ya no aparecerá en el buscador de la caja registradora.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, desactivar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "index.php",
                method: "POST",
                data: { idProductoEliminar: id },
                dataType: "json",
                success: function (res) {
                    if (res.status === "success") { window.location.reload(); }
                    else { Swal.fire('Error', res.mensaje, 'error'); }
                }
            });
        }
    });
});

/* ==========================================
4. REACTIVAR PRODUCTO
========================================== */
$(".btnActivarProducto").on("click", function () {
    let id = $(this).attr("idProducto");
    let nombre = $(this).attr("nombreProducto");

    Swal.fire({
        title: '¿Reactivar producto?',
        text: "El producto '" + nombre + "' volverá a estar disponible para la venta.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, reactivar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "index.php",
                method: "POST",
                data: { idProductoActivar: id },
                dataType: "json",
                success: function (res) {
                    if (res.status === "success") { window.location = "index.php?ruta=productos"; }
                    else { Swal.fire('Error', res.mensaje, 'error'); }
                }
            });
        }
    });


})

/* ====================================================================
   5. SELECTS DINÁMICOS EN CASCADA (LÍNEA -> CATEGORÍA -> SUBCATEGORÍA)
==================================================================== */

// Inicializar la librería Select2 para búsqueda interna
$(document).ready(function () {
    $('.select2-dinamico').select2({
        placeholder: "Seleccione una opción",
        allowClear: true
    });
});

// A. Cuando cambia la LÍNEA, buscamos las Categorías
$("#idLineaProducto").on("change", function () {
    let idLinea = $(this).val();

    // Bloquear y limpiar los siguientes selects
    $("#idCategoriaProducto").html('<option value="" disabled selected>Cargando...</option>').prop('disabled', true);
    $("#idSubcategoriaProducto").html('<option value="" disabled selected>Esperando Categoría...</option>').prop('disabled', true);

    if (idLinea) {
        $.ajax({
            url: "index.php",
            method: "POST",
            data: { idLineaAjax: idLinea },
            dataType: "json",
            success: function (respuesta) {
                let opciones = '<option value="" disabled selected>Seleccione Categoría...</option>';
                respuesta.forEach(function (cat) {
                    opciones += `<option value="${cat.id}">${cat.nombre}</option>`;
                });

                $("#idCategoriaProducto").html(opciones).prop('disabled', false);
            }
        });
    }
});

// B. Cuando cambia la CATEGORÍA, buscamos las Subcategorías
$("#idCategoriaProducto").on("change", function () {
    let idCategoria = $(this).val();

    $("#idSubcategoriaProducto").html('<option value="" disabled selected>Cargando...</option>').prop('disabled', true);

    if (idCategoria) {
        $.ajax({
            url: "index.php",
            method: "POST",
            data: { idCategoriaAjax: idCategoria },
            dataType: "json",
            success: function (respuesta) {
                let opciones = '<option value="" disabled selected>Seleccione Subcategoría...</option>';
                respuesta.forEach(function (sub) {
                    opciones += `<option value="${sub.id}">${sub.nombre}</option>`;
                });

                $("#idSubcategoriaProducto").html(opciones).prop('disabled', false);
            }
        });
    }
});