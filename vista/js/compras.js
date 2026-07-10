/* ==============================================================
   MÓDULO DE COMPRAS - LÓGICA Y MATEMÁTICAS
   ============================================================== */

$(document).ready(function() {
    if ($('.select2-dinamico').length > 0) {
        $('.select2-dinamico').select2({ placeholder: "Seleccione una opción", allowClear: true });
    }
});

/* ==============================================================
   1. BUSCADOR DE PRODUCTOS (Select2)
   ============================================================== */
$('#buscadorProductosCompra').select2({
    placeholder: 'Escanee o escriba el nombre...',
    minimumInputLength: 1,
    ajax: {
        url: 'index.php',
        type: 'POST',
        dataType: 'json',
        delay: 250,
        data: function (params) { return { buscarProductoSelect: params.term }; },
        processResults: function (data) {
            return {
                results: $.map(data, function (item) {
                    return {
                        id: item.id,
                        text: item.codigo_barras + ' - ' + item.nombre,
                        costo_usdt: item.costo_usdt
                    }
                })
            };
        },
        cache: true
    },
    language: {
        noResults: function() {
            return `<div class="text-center p-2">
                        <span class="d-block mb-2 text-muted"><i class="fas fa-search-minus me-1"></i> Producto no encontrado</span>
                        <a href="index.php?ruta=productos-crear" class="btn btn-sm btn-primary w-100 shadow-sm" target="_blank">
                            <i class="fas fa-plus-circle me-1"></i> Crear Nuevo Producto
                        </a>
                    </div>`;
        },
        searching: function() { return "Buscando en catálogo..."; },
        inputTooShort: function(args) { return "Escriba al menos 1 letra o escanee el código..."; }
    },
    escapeMarkup: function (markup) { return markup; }
});

$('#buscadorProductosCompra').on('select2:select', function (e) {
    let data = e.params.data;
    if(data.costo_usdt > 0) {
        Swal.fire({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            icon: 'info', title: `Último costo real: $${parseFloat(data.costo_usdt).toFixed(4)} USDT`
        });
    }
});

/* ==============================================================
   2. AGREGAR AL CARRITO TEMPORAL (FRONTEND SEGURO)
   ============================================================== */
$("#btnAgregarItemCompra").on("click", function() {
    
    console.log("🟢 1. Se detectó el clic en el botón."); // Auditoría

    let idProducto = $("#buscadorProductosCompra").val();
    let cantidad = $("#cantidadItemCompra").val();
    let costoNominal = $("#costoNominalItemCompra").val();
    let moneda = $("#monedaCompra").val(); 

    // Validaciones básicas visuales
    if(!idProducto || cantidad <= 0 || costoNominal <= 0) {
        Swal.fire("Atención", "Debe seleccionar un producto, ingresar cantidad y costo válido.", "warning");
        return;
    }

    console.log("🚀 2. Validaciones superadas, enviando a PHP...");

    $.ajax({
        url: "index.php",
        method: "POST",
        data: {
            idProductoCompraSegura: idProducto, 
            cantidadCompra: cantidad,
            costoNominalCompra: costoNominal,
            monedaCompra: moneda
        },
        dataType: "json",
        success: function(respuesta) {
            console.log("✅ 3. Respuesta de PHP recibida:", respuesta);
            
            if(respuesta.status === "success") {
                $("#buscadorProductosCompra").val(null).trigger('change');
                $("#cantidadItemCompra").val("");
                $("#costoNominalItemCompra").val("");
                
                cargarCarritoTemporal();
            } else {
                Swal.fire("Error", respuesta.mensaje, "error");
            }
        },
        error: function(xhr, status, error) {
            console.error("❌ ERROR FATAL EN PHP:", xhr.responseText);
            Swal.fire("Fallo del Servidor", "Revisa la consola (F12) para ver el error exacto.", "error");
        }
    });
});

/* ==============================================================
   3. RENDERIZAR LA TABLA TEMPORAL Y TOTALES
   ============================================================== */
function cargarCarritoTemporal() {
    $.ajax({
        url: "index.php",
        method: "POST",
        data: { cargarTemporalesCompra: "ok" },
        dataType: "json",
        success: function(respuesta) {
            let filas = "";
            let granTotalNominal = 0;
            let granTotalUsdt = 0;

            respuesta.forEach(function(item) {
                let subtotalNominal = parseFloat(item.cantidad) * parseFloat(item.costo_nominal);
                let subtotalUsdt = parseFloat(item.cantidad) * parseFloat(item.costo_real_usdt);
                
                granTotalNominal += subtotalNominal;
                granTotalUsdt += subtotalUsdt;

                filas += `
                    <tr>
                        <td><span class="badge bg-secondary">${item.codigo_barras}</span></td>
                        <td class="fw-bold text-dark">${item.producto_nombre}</td>
                        <td class="text-center fw-bold">${item.cantidad}</td>
                        <td class="text-end text-muted">${parseFloat(item.costo_nominal).toFixed(2)}</td>
                        <td class="text-end fw-bold text-success">$${parseFloat(item.costo_real_usdt).toFixed(4)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger btnEliminarItemTemporal" idItem="${item.id}"><i class="fas fa-times"></i></button>
                        </td>
                    </tr>
                `;
            });

            $("#listaItemsTemporal").html(filas);
            $("#granTotalNominalTexto").text(granTotalNominal.toFixed(2));
            $("#granTotalUsdtTexto").text(granTotalUsdt.toFixed(4));
            
            $("#totalNominalCompraForm").val(granTotalNominal);
            $("#totalUsdtCompraForm").val(granTotalUsdt);
        }
    });
}

cargarCarritoTemporal();

/* ==============================================================
   4. ELIMINAR ÍTEM Y PROCESAR COMPRA (CIERRE DE FACTURA)
   ============================================================== */
$(".tablaComprasTemporales").on("click", ".btnEliminarItemTemporal", function() {
    let idTemporal = $(this).attr("idItem");
    $.ajax({
        url: "index.php", method: "POST", data: { idTemporalEliminar: idTemporal }, dataType: "json",
        success: function(respuesta) {
            if(respuesta.status === "success") { cargarCarritoTemporal(); } 
            else { Swal.fire("Error", respuesta.mensaje, "error"); }
        }
    });
});

$("#formProcesarCompra").on("submit", function(e) {
    e.preventDefault();
    Swal.fire({
        title: '¿Procesar Factura de Compra?', text: "Esta acción sumará el stock, actualizará los costos y no se puede deshacer.",
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#198754', cancelButtonColor: '#d33', confirmButtonText: 'Sí, procesar compra'
    }).then((result) => {
        if (result.isConfirmed) {
            let formData = new FormData(this);
            formData.append("procesarCompraFinal", "true");

            $.ajax({
                url: "index.php", method: "POST", data: formData, cache: false, contentType: false, processData: false, dataType: "json",
                success: function(respuesta) {
                    if (respuesta.status === "success") {
                        Swal.fire({ icon: 'success', title: '¡Compra Procesada!', text: respuesta.mensaje, showConfirmButton: false, timer: 2000 })
                        .then(function() { window.location = "index.php?ruta=compras"; });
                    } else { Swal.fire('Error', respuesta.mensaje, 'error'); }
                }
            });
        }
    });
});

/* ==============================================================
   5. VER DETALLE DE COMPRA (OJO)
   ============================================================== */
$(".btnImprimirCompra").on("click", function() {
    let idCompra = $(this).attr("idCompra");
    let fechaCompra = $(this).attr("fechaCompra"); 

    $.ajax({
        url: "index.php", method: "POST", data: { idCompraDetalle: idCompra }, dataType: "json",
        success: function(respuesta) {
            let tabla = `<table class="table table-sm table-bordered table-striped text-start mt-3"><thead class="table-dark"><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-end">Costo U. (USDT)</th><th class="text-end">Subtotal</th></tr></thead><tbody>`;
            let totalUsdt = 0;
            respuesta.forEach(function(item) {
                let subtotal = parseFloat(item.cantidad) * parseFloat(item.costo_real_usdt);
                totalUsdt += subtotal;
                tabla += `<tr><td><small class="d-block text-muted">${item.codigo_barras}</small> ${item.producto_nombre}</td><td class="text-center fw-bold">${item.cantidad}</td><td class="text-end">$${parseFloat(item.costo_real_usdt).toFixed(4)}</td><td class="text-end fw-bold text-success">$${subtotal.toFixed(4)}</td></tr>`;
            });
            tabla += `</tbody><tfoot><tr><td colspan="3" class="text-end fw-bold text-dark">TOTAL FACTURA (USDT):</td><td class="text-end fw-bold text-success fs-5">$${totalUsdt.toFixed(4)}</td></tr></tfoot></table>`;
            
            Swal.fire({ title: '<div class="d-flex justify-content-between align-items-center"><span class="text-dark"><i class="fas fa-file-invoice-dollar text-success me-2"></i> Compra #'+idCompra+'</span> <span class="fs-6 text-muted"><i class="far fa-calendar-alt me-1"></i> '+fechaCompra+'</span></div>', html: tabla, width: 750, showCloseButton: true, showConfirmButton: false });
        }
    });
});

/* ==============================================================
   6. CONTROL DE CONDICIÓN DE PAGO (CONTADO / CRÉDITO)
   ============================================================== */
$("#condicionPagoCompra").on("change", function() {
    let condicion = $(this).val();
    let inputDias = $("#diasCreditoCompra");

    if (condicion === "Credito") {
        inputDias.prop("readonly", false);
        inputDias.removeClass("bg-light").addClass("bg-white");
        inputDias.val("15"); // Sugerimos 15 días por defecto
        inputDias.focus();
    } else {
        inputDias.prop("readonly", true);
        inputDias.removeClass("bg-white").addClass("bg-light");
        inputDias.val("0");
    }
});