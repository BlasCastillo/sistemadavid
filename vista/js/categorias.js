// 1. CREAR CATEGORÍA
$("#formAgregarCategoria").on("submit", function(e) {
    e.preventDefault();
    $.ajax({
        url: "index.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(res) {
            if (res.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=categorias"; });
            } else { Swal.fire('Error', res.mensaje, 'error'); }
        }
    });
});

// 2. EDITAR CATEGORÍA
$("#formEditarCategoria").on("submit", function(e) {
    e.preventDefault();
    $.ajax({
        url: "index.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(res) {
            if (res.status === "success") {
                Swal.fire({ icon: 'success', title: '¡Actualizado!', text: res.mensaje, showConfirmButton: false, timer: 1500 })
                .then(function() { window.location = "index.php?ruta=categorias"; });
            } else { Swal.fire('Error', res.mensaje, 'error'); }
        }
    });
});

// 3. DESACTIVAR CATEGORÍA
$(".btnEliminarCategoria").on("click", function() {
    let id = $(this).attr("idCategoria");
    let nombre = $(this).attr("nombreCategoria");
    Swal.fire({
        title: '¿Desactivar categoría?',
        text: "La categoría '" + nombre + "' ya no aparecerá al registrar productos nuevos.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, desactivar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "index.php",
                method: "POST",
                data: { idCategoriaEliminar: id },
                dataType: "json",
                success: function(res) {
                    if (res.status === "success") { window.location.reload(); }
                    else { Swal.fire('Error', res.mensaje, 'error'); }
                }
            });
        }
    });
});

// 4. REACTIVAR CATEGORÍA
$(".btnActivarCategoria").on("click", function() {
    let id = $(this).attr("idCategoria");
    let nombre = $(this).attr("nombreCategoria");
    Swal.fire({
        title: '¿Reactivar categoría?',
        text: "La categoría '" + nombre + "' volverá a estar disponible.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Sí, reactivar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "index.php",
                method: "POST",
                data: { idCategoriaActivar: id },
                dataType: "json",
                success: function(res) {
                    if (res.status === "success") { window.location = "index.php?ruta=categorias"; }
                    else { Swal.fire('Error', res.mensaje, 'error'); }
                }
            });
        }
    });
});