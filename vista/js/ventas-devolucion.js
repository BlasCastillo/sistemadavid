/* ====================================================================
   MÓDULO DE GESTIÓN DE DEVOLUCIONES Y NOTAS DE CRÉDITO
==================================================================== */

$(document).ready(function() {
    
    // Si no estamos en la vista de devolución, abortamos
    if ($("#tablaDevolucion").length === 0) return;

    console.log("🚩 [0] Archivo ventas-devolucion.js cargado.");

    // BLOQUEO DE FILAS VACÍAS (Anti-fraude para artículos ya devueltos)
    $(".fila-producto").each(function() {
        let max = parseInt($(this).find(".input-cant-devolver").attr("max"));
        if (max <= 0) {
            // Deshabilitamos todo visual y funcionalmente
            $(this).find(".check-devolver").prop("disabled", true).removeClass("check-devolver");
            $(this).find(".input-cant-devolver").val(0).prop("disabled", true);
            $(this).addClass("bg-light text-muted opacity-50");
        }
    });

    /* ==============================================================
       A. MOTOR DE CÁLCULO EN TIEMPO REAL
       ============================================================== */
    function calcularTotalesDevolucion() {
        let granTotal = 0;
        let itemsSeleccionados = 0;

        // Recorremos cada fila de la tabla
        $(".fila-producto").each(function() {
            let checkbox = $(this).find(".check-devolver");
            let inputCant = $(this).find(".input-cant-devolver");
            let spanSubtotal = $(this).find(".subtotal-devolver");

            // Si el checkbox está marcado
            if (checkbox.is(":checked")) {
                itemsSeleccionados++;
                inputCant.prop("disabled", false); // Habilitamos la cantidad
                
                let cant = parseInt(inputCant.val());
                let max = parseInt(inputCant.attr("max")); // Cantidad original comprada
                let precio = parseFloat(checkbox.attr("precio"));

                // Validaciones de seguridad (Evitar que devuelvan más de lo que compraron o valores negativos)
                if (cant > max) { 
                    cant = max; 
                    inputCant.val(max); 
                    Swal.fire({
                        icon: 'warning',
                        title: 'Límite excedido',
                        text: 'El cliente solo compró ' + max + ' unidades de este producto.',
                        toast: true, position: 'top-end', showConfirmButton: false, timer: 3000
                    });
                }
                if (cant < 1 || isNaN(cant)) { cant = 1; inputCant.val(1); }

                // Matemática
                let subtotal = cant * precio;
                spanSubtotal.text(subtotal.toFixed(2));
                granTotal += subtotal;

            } else {
                // Si se desmarca, bloqueamos, reseteamos a 1 y subtotal a 0
                inputCant.prop("disabled", true);
                inputCant.val(1);
                spanSubtotal.text("0.00");
            }
        });

        // Imprimir Gran Total
        $("#granTotalDevolucion").text(granTotal.toFixed(2));

        // Habilitar o deshabilitar el botón de procesar
        if (itemsSeleccionados > 0 && granTotal > 0) {
            $("#btnProcesarDevolucion").prop("disabled", false);
        } else {
            $("#btnProcesarDevolucion").prop("disabled", true);
        }
    }

    /* ==============================================================
       B. ESCUCHADORES DE EVENTOS
       ============================================================== */
    // Cuando hacen clic en el checkbox
    $(".check-devolver").on("change", function() {
        calcularTotalesDevolucion();
    });

    // Cuando teclean o usan las flechas en el input de cantidad
    $(".input-cant-devolver").on("input change", function() {
        calcularTotalesDevolucion();
    });

    // Botón mágico: Seleccionar Todo
    $("#btnSeleccionarTodo").on("click", function() {
        // Verifica si todos están marcados (ignorando los que están bloqueados permanentemente)
        let checkboxesDisponibles = $(".check-devolver:not(:disabled)");
        let todosMarcados = checkboxesDisponibles.filter(":checked").length === checkboxesDisponibles.length;
        
        // Invierte el estado solo de los disponibles
        checkboxesDisponibles.prop("checked", !todosMarcados);
        
        // Cambia el texto del botón
        if(!todosMarcados) {
            $(this).text("Deseleccionar Todo").removeClass("btn-outline-primary").addClass("btn-primary");
        } else {
            $(this).text("Seleccionar Todo").removeClass("btn-primary").addClass("btn-outline-primary");
        }
        
        calcularTotalesDevolucion();
    });

    /* ==============================================================
       C. RECOLECCIÓN DE DATOS Y ENVÍO AL SERVIDOR
       ============================================================== */
    $("#btnProcesarDevolucion").on("click", function() {
        
        let idVenta = new URLSearchParams(window.location.search).get("idVenta");
        let metodoReembolso = $("#metodoReembolso").val();
        let granTotal = parseFloat($("#granTotalDevolucion").text());
        let productosReversar = [];

        // Empaquetar solo los productos seleccionados
        $(".check-devolver:checked").each(function() {
            let fila = $(this).closest(".fila-producto");
            productosReversar.push({
                id_detalle: $(this).attr("idDetalle"),
                id_producto: $(this).attr("idProducto"),
                cantidad_devuelta: parseInt(fila.find(".input-cant-devolver").val()),
                precio_unitario: parseFloat($(this).attr("precio")),
                subtotal: parseFloat(fila.find(".subtotal-devolver").text())
            });
        });

        console.log("🚩 [PAQUETE DEVOLUCIÓN] Factura:", idVenta, "| Método:", metodoReembolso, "| Total:", granTotal, "| Items:", productosReversar);

        Swal.fire({
            title: '¿Confirmar Devolución?',
            text: "Se reversará el inventario y se procesará un reembolso por $" + granTotal.toFixed(2),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, procesar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Procesando Auditoría...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                
                $.ajax({
                    url: "index.php",
                    method: "POST",
                    data: {
                        procesarDevolucionAjax: "ok",
                        idVentaOriginal: idVenta,
                        metodoReembolso: metodoReembolso,
                        totalReembolso: granTotal,
                        itemsReversar: JSON.stringify(productosReversar) // Enviamos el array como texto JSON
                    },
                    dataType: "json",
                    success: function(respuesta) {
                        console.log("🚩 [RESPUESTA DEVOLUCIÓN]", respuesta);
                        
                        if(respuesta.status === "success") {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Procesado Correctamente!',
                                text: respuesta.mensaje,
                                confirmButtonText: 'Imprimir e ir al Historial'
                            }).then(() => {
                                // NUEVO: Imprimimos la nota de crédito y redirigimos
                                window.open("ticket-nota-credito.php?codigo=" + respuesta.codigo_nota, "_blank");
                                window.location = "index.php?ruta=ventas";
                            });
                        } else {
                            Swal.fire('Error', respuesta.mensaje, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("🚨 [ERROR AJAX]", xhr.responseText);
                        Swal.fire('Error del Servidor', 'Revisa la consola F12.', 'error');
                    }
                });
            }
        });
    });
});