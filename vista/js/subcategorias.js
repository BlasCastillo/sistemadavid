/* ==========================================
1. CREAR SUBCATEGORÍA (AJAX)
========================================== */
$("#formAgregarSubcategoria").on("submit", function(e) {
    e.preventDefault();
    $.ajax({
        url: "index.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(res) {
            if (res.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=subcategorias"; });
            } else { Swal.fire('Error', res.mensaje, 'error'); }
        }
    });
});

/* ==========================================
2. EDITAR SUBCATEGORÍA (AJAX)
========================================== */
$("#formEditarSubcategoria").on("submit", function(e) {
    e.preventDefault();
    $.ajax({
        url: "index.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(res) {
            if (res.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Actualizado!', text: res.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=subcategorias"; });
            } else { Swal.fire('Error', res.mensaje, 'error'); }
        }
    });
});

/* ==========================================
3. DESACTIVAR SUBCATEGORÍA
========================================== */
$(".btnEliminarSubcategoria").on("click", function() {
    let id = $(this).attr("idSubcategoria");
    let nombre = $(this).attr("nombreSubcategoria");
    Swal.fire({
        title: '¿Desactivar subcategoría?',
        text: "La subcategoría '" + nombre + "' ya no estará disponible al registrar productos.",
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
                data: { idSubcategoriaEliminar: id },
                dataType: "json",
                success: function(res) {
                    if (res.status === "success") { window.location.reload(); }
                    else { Swal.fire('Error', res.mensaje, 'error'); }
                }
            });
        }
    });
});

/* ==========================================
4. REACTIVAR SUBCATEGORÍA
========================================== */
$(".btnActivarSubcategoria").on("click", function() {
    let id = $(this).attr("idSubcategoria");
    let nombre = $(this).attr("nombreSubcategoria");
    Swal.fire({
        title: '¿Reactivar subcategoría?',
        text: "La subcategoría '" + nombre + "' volverá a estar disponible.",
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
                data: { idSubcategoriaActivar: id },
                dataType: "json",
                success: function(res) {
                    if (res.status === "success") { window.location = "index.php?ruta=subcategorias"; }
                    else { Swal.fire('Error', res.mensaje, 'error'); }
                }
            });
        }
    });
});