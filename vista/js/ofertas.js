/* ==============================================================
   MÓDULO DE OFERTAS Y PROMOCIONES - LÓGICA Y AJAX
   ============================================================== */

$(document).ready(function() {

    /* ==============================================================
       1. BUSCADOR DE PRODUCTOS (Select2)
       ============================================================== */
    $('#buscadorProductoOferta').select2({
        placeholder: 'Escanee o escriba el nombre del producto...',
        minimumInputLength: 1,
        dropdownParent: $('#modalAgregarOferta'), // Importante para que funcione dentro de un modal
        ajax: {
            url: 'index.php',
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { buscarProductoSelect: params.term }; },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        // Cálculo del precio regular (Costo + Margen)
                        let costo = parseFloat(item.costo_usdt) || 0;
                        let margen = parseFloat(item.margen_ganancia) || 0;
                        let precioRegular = costo * (1 + (margen / 100));

                        return {
                            id: item.id,
                            text: item.codigo_barras + ' - ' + item.nombre,
                            precio_regular: precioRegular.toFixed(4)
                        }
                    })
                };
            },
            cache: true
        },
        language: {
            noResults: function() { return "Producto no encontrado..."; },
            searching: function() { return "Buscando en catálogo..."; },
            inputTooShort: function() { return "Escriba al menos 1 letra..."; }
        }
    });

    /* ==============================================================
       2. MATEMÁTICA EN TIEMPO REAL (Calcular Oferta)
       ============================================================== */
    // Capturamos el precio regular cuando se selecciona un producto
    $('#buscadorProductoOferta').on('select2:select', function (e) {
        let data = e.params.data;
        $('#precioRegularOferta').val(data.precio_regular);
        calcularPrecioFinal();
    });

    // Recalculamos al escribir el porcentaje de descuento
    $('#porcentajeOferta').on('input', function() {
        calcularPrecioFinal();
    });

    function calcularPrecioFinal() {
        let precioRegular = parseFloat($('#precioRegularOferta').val()) || 0;
        let descuento = parseFloat($('#porcentajeOferta').val()) || 0;
        
        if (precioRegular > 0 && descuento >= 0 && descuento <= 100) {
            let montoDescontado = precioRegular * (descuento / 100);
            let precioFinal = precioRegular - montoDescontado;
            
            // Asignamos el valor con 4 decimales
            $('#precioFinalOferta').val(precioFinal.toFixed(4));
        } else {
            $('#precioFinalOferta').val('');
        }
    }

    /* ==============================================================
       3. GUARDAR OFERTA EN LA BASE DE DATOS (AJAX)
       ============================================================== */
    $("#formCrearOferta").on("submit", function(e) {
        e.preventDefault();
        
        let btnSubmit = $(this).find('button[type="submit"]');
        let originalText = btnSubmit.html();
        
        btnSubmit.html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando...').prop("disabled", true);

        $.ajax({
            url: "index.php",
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(respuesta) {
                if(respuesta.status === "success") {
                    $("#modalAgregarOferta").modal("hide");
                    Swal.fire({
                        icon: 'success', title: '¡Éxito!', text: respuesta.mensaje, showConfirmButton: false, timer: 2000
                    }).then(function() {
                        window.location = "index.php?ruta=ofertas";
                    });
                } else if(respuesta.status === "warning") {
                    btnSubmit.html(originalText).prop("disabled", false);
                    Swal.fire("Choque de Fechas", respuesta.mensaje, "warning");
                } else {
                    btnSubmit.html(originalText).prop("disabled", false);
                    Swal.fire("Error", respuesta.mensaje, "error");
                }
            },
            error: function(xhr) {
                btnSubmit.html(originalText).prop("disabled", false);
                console.error(xhr.responseText);
                Swal.fire("Error Fatal", "Error de comunicación con el servidor.", "error");
            }
        });
    });

    /* ==============================================================
       4. ELIMINAR OFERTA (AJAX)
       ============================================================== */
    $(".tablaOfertas").on("click", ".btnEliminarOferta", function() {
        let idOferta = $(this).attr("idOferta");
        
        Swal.fire({
            title: '¿Anular esta oferta?',
            text: "El producto volverá a su precio regular inmediatamente. Esta acción no se puede revertir.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, anular oferta',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "index.php",
                    method: "POST",
                    data: { idOfertaEliminar: idOferta },
                    dataType: "json",
                    success: function(respuesta) {
                        if(respuesta.status === "success") {
                            Swal.fire({
                                icon: 'success', title: 'Eliminada', text: respuesta.mensaje, showConfirmButton: false, timer: 1500
                            }).then(function() {
                                window.location = "index.php?ruta=ofertas";
                            });
                        } else {
                            Swal.fire("Error", respuesta.mensaje, "error");
                        }
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
                    }
                });
            }
        });
    });

});