/* ==============================================================
   MÓDULO DE COMPRAS - LÓGICA Y MATEMÁTICAS
   ============================================================== */

$(document).ready(function() {
    // Inicializar Select2 en los formularios de compras si existen
    if ($('.select2-dinamico').length > 0) {
        $('.select2-dinamico').select2({
            placeholder: "Seleccione una opción",
            allowClear: true
        });
    }
});

/* ==============================================================
   1. EL MOTOR MATEMÁTICO (Cálculo de Tasa y Brecha)
   ============================================================== */

// A. Buscar Productos para el Select2
$('#buscadorProductosCompra').select2({
    placeholder: 'Escanee o escriba el nombre...',
    minimumInputLength: 1, // Buscar desde la primera letra
    ajax: {
        url: 'index.php',
        type: 'POST',
        dataType: 'json',
        delay: 250,
        data: function (params) {
            return {
                buscarProductoSelect: params.term
            };
        },
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
    // MAGIA DE UX: Cambiamos los textos y agregamos el botón
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
    escapeMarkup: function (markup) {
        return markup; // Le decimos a Select2 que permita dibujar nuestro botón HTML
    }
});

// B. Mostrar el último costo referencial al seleccionar un producto
$('#buscadorProductosCompra').on('select2:select', function (e) {
    let data = e.params.data;
    if(data.costo_usdt > 0) {
        Swal.fire({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            icon: 'info', title: `Último costo real: $${parseFloat(data.costo_usdt).toFixed(4)} USDT`
        });
    }
});

// C. AGREGAR AL CARRITO TEMPORAL
$("#btnAgregarItemCompra").on("click", function() {
    
    let idProducto = $("#buscadorProductosCompra").val();
    let cantidad = $("#cantidadItemCompra").val();
    let costoNominal = $("#costoNominalItemCompra").val();
    
    let moneda = $("#monedaCompra").val();
    let tasaBcv = parseFloat($("#tasaBcvCompra").val());
    let brecha = parseFloat($("#brechaCompra").val());

    // Validaciones básicas
    if(!idProducto || cantidad <= 0 || costoNominal <= 0) {
        Swal.fire("Atención", "Debe seleccionar un producto, ingresar cantidad y costo válido.", "warning");
        return;
    }

    // EL NÚCLEO FINANCIERO: Cálculo del Costo Real en USDT
    let costoRealUsdt = 0;
    
    if (moneda === "Bs") {
        // En Bs: Se divide entre BCV y se le suma el % de la brecha
        let costoBaseUsd = costoNominal / tasaBcv;
        costoRealUsdt = costoBaseUsd * (1 + (brecha / 100));
    } 
    else if (moneda === "USD_Fisico") {
        // Dólar Físico: Se asume costo nominal y se le suma el % de la brecha
        costoRealUsdt = costoNominal * (1 + (brecha / 100));
    } 
    else if (moneda === "USDT") {
        // USDT: Relación 1 a 1, sin brecha
        costoRealUsdt = costoNominal;
    }

    // Enviar a la base de datos temporal
    $.ajax({
        url: "index.php",
        method: "POST",
        data: {
            idProductoCompra: idProducto,
            cantidadCompra: cantidad,
            costoNominalCompra: costoNominal,
            costoRealUsdtCompra: costoRealUsdt
        },
        dataType: "json",
        success: function(respuesta) {
            if(respuesta.status === "success") {
                // Limpiar campos para el siguiente producto
                $("#buscadorProductosCompra").val(null).trigger('change');
                $("#cantidadItemCompra").val("");
                $("#costoNominalItemCompra").val("");
                
                cargarCarritoTemporal(); // Refrescar la tabla
            } else {
                Swal.fire("Error", respuesta.mensaje, "error");
            }
        }
    });
});

/* ==============================================================
   2. RENDERIZAR LA TABLA TEMPORAL Y TOTALES
   ============================================================== */
function cargarCarritoTemporal() {
    $.ajax({
        url: "index.php",
        method: "POST",
        data: { cargarTemporalesCompra: "ok" }, // Necesitaremos un pequeño interceptor para esto
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
                            <button type="button" class="btn btn-sm btn-outline-danger btnEliminarItemTemporal" idItem="${item.id}">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            $("#listaItemsTemporal").html(filas);
            
            // Actualizar Totales en pantalla y en los inputs ocultos del formulario
            $("#granTotalNominalTexto").text(granTotalNominal.toFixed(2));
            $("#granTotalUsdtTexto").text(granTotalUsdt.toFixed(4));
            
            $("#totalNominalCompraForm").val(granTotalNominal);
            $("#totalUsdtCompraForm").val(granTotalUsdt);
        }
    });
}

// Cargar el carrito automáticamente al abrir la pantalla
cargarCarritoTemporal();

/* ==============================================================
   3. ELIMINAR ÍTEM DE LA TABLA TEMPORAL
   ============================================================== */
// Usamos delegación de eventos (.on) porque los botones se crean dinámicamente
$(".tablaComprasTemporales").on("click", ".btnEliminarItemTemporal", function() {
    let idTemporal = $(this).attr("idItem");

    $.ajax({
        url: "index.php",
        method: "POST",
        data: { idTemporalEliminar: idTemporal },
        dataType: "json",
        success: function(respuesta) {
            if(respuesta.status === "success") {
                // Borra el ítem y recalcula los totales fluidamente
                cargarCarritoTemporal(); 
            } else {
                Swal.fire("Error", respuesta.mensaje, "error");
            }
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            Swal.fire("Error Interno", "No se pudo eliminar el ítem temporal.", "error");
        }
    });
});

/* ==============================================================
   4. PROCESAR COMPRA (CIERRE DE FACTURA)
   ============================================================== */
$("#formProcesarCompra").on("submit", function(e) {
    e.preventDefault();

    Swal.fire({
        title: '¿Procesar Factura de Compra?',
        text: "Esta acción sumará el stock, actualizará los costos y no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, procesar compra'
    }).then((result) => {
        if (result.isConfirmed) {
            
            let formData = new FormData(this);
            formData.append("procesarCompraFinal", "true");

            $.ajax({
                url: "index.php",
                method: "POST",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function(respuesta) {
                    if (respuesta.status === "success") {
                        Swal.fire({ icon: 'success', title: '¡Compra Procesada!', text: respuesta.mensaje, showConfirmButton: false, timer: 2000 })
                        .then(function() { window.location = "index.php?ruta=compras"; });
                    } else { 
                        Swal.fire('Error', respuesta.mensaje, 'error'); 
                    }
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    Swal.fire("Error Crítico", "Fallo al procesar la transacción. Revise la consola.", "error");
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

    $.ajax({
        url: "index.php",
        method: "POST",
        data: { idCompraDetalle: idCompra },
        dataType: "json",
        success: function(respuesta) {
            
            // Construimos una tabla HTML para inyectarla en SweetAlert
            let tabla = `
                <table class="table table-sm table-bordered table-striped text-start mt-3">
                    <thead class="table-dark">
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-end">Costo U. (USDT)</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            let totalUsdt = 0;

            respuesta.forEach(function(item) {
                let subtotal = parseFloat(item.cantidad) * parseFloat(item.costo_real_usdt);
                totalUsdt += subtotal;

                tabla += `
                    <tr>
                        <td><small class="d-block text-muted">${item.codigo_barras}</small> ${item.producto_nombre}</td>
                        <td class="text-center fw-bold">${item.cantidad}</td>
                        <td class="text-end">$${parseFloat(item.costo_real_usdt).toFixed(4)}</td>
                        <td class="text-end fw-bold text-success">$${subtotal.toFixed(4)}</td>
                    </tr>
                `;
            });

            tabla += `
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold text-dark">TOTAL FACTURA (USDT):</td>
                            <td class="text-end fw-bold text-success fs-5">$${totalUsdt.toFixed(4)}</td>
                        </tr>
                    </tfoot>
                </table>
            `;

            // Lanzamos el SweetAlert con formato ancho
            Swal.fire({
                title: '<i class="fas fa-file-invoice-dollar text-success me-2"></i> <strong>Detalle de Compra #'+idCompra+'</strong>',
                html: tabla,
                width: 750, // Lo hacemos más ancho para que la tabla se vea bien
                showCloseButton: true,
                showConfirmButton: false // Quitamos el botón OK para que parezca una ventana de sistema
            });
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            Swal.fire("Error", "No se pudo cargar el detalle de la factura. Revise la consola.", "error");
        }
    });
});