/* ====================================================================
   MÓDULO DE VENTAS - LIQUIDACIÓN Y MULTIPAGO
==================================================================== */
$(document).ready(function() {
    
    // Validar que estamos en la vista correcta comprobando que exista el input global
    if ($("#tasaBcvGlobal").length === 0) return; 

    console.log("🚩 [0] Archivo ventas-pago.js cargado.");

    let tasaBcv = parseFloat($("#tasaBcvGlobal").val());
    let totalFacturaUsdt = parseFloat($("#totalUsdtGlobal").val());
    let matrizPagos = []; // Array que almacenará los pagos parciales

    /* ==============================================================
       1. DINAMISMO DEL FORMULARIO DE PAGOS
       ============================================================== */
    
    // Cambiar los métodos de pago disponibles y AUTO-LLENAR el monto
    $("#monedaMultipago").on("change", function() {
        let moneda = $(this).val();
        
        // Calculamos cuánto falta pagar
        let pagado = 0;
        matrizPagos.forEach(p => pagado += p.equivalencia_usdt);
        let faltanteUsdt = totalFacturaUsdt - pagado;
        
        if (moneda === "BS") {
            $("#metodoMultipago .opt-bs").removeClass("d-none");
            $("#metodoMultipago").val("Pago Movil");
            // Auto-llena el input con el monto exacto convertido a Bolívares
            if (faltanteUsdt > 0) $("#montoMultipago").val((faltanteUsdt * tasaBcv).toFixed(2));
        } else {
            $("#metodoMultipago .opt-bs").addClass("d-none"); 
            $("#metodoMultipago").val("Efectivo");
            // Auto-llena el input con el monto en Dólares
            if (faltanteUsdt > 0) $("#montoMultipago").val(faltanteUsdt.toFixed(2));
        }
    });

    /* ==============================================================
       2. AGREGAR PAGOS PARCIALES A LA CUADRÍCULA
       ============================================================== */
    
    $("#btnAgregarPago").on("click", async function() {
        let moneda = $("#monedaMultipago").val();
        let metodo = $("#metodoMultipago").val();
        let monto = parseFloat($("#montoMultipago").val());

        if (isNaN(monto) || monto <= 0) {
            Swal.fire("Monto Inválido", "Ingrese un monto mayor a cero.", "warning");
            return;
        }

        let referencia = "N/A";
        
        // Si es método digital, pedimos el número de referencia obligatorio
        if (["Zelle", "Pago Movil", "Punto de Venta"].includes(metodo)) {
            const { value: refInput } = await Swal.fire({
                title: 'Número de Referencia',
                text: `Ingrese el comprobante para el pago de ${monto} ${moneda}`,
                input: 'text',
                inputPlaceholder: 'Ej: 12345678',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Agregar Pago',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) return '¡Necesita ingresar el número de referencia!'
                }
            });
            
            if (refInput) { 
                referencia = refInput; 
            } else { 
                return; // Si cancela el modal, detenemos el proceso
            } 
        }

        // Matemática: Si es Bs, lo dividimos entre la tasa para pasarlo a USDT
        let equivalenteUsdt = moneda === "BS" ? (monto / tasaBcv) : monto;

        // Empaquetamos el pago en la matriz
        matrizPagos.push({
            id: Date.now(), // ID temporal único
            moneda: moneda,
            metodo: metodo,
            monto_declarado: monto,
            equivalencia_usdt: equivalenteUsdt,
            referencia: referencia
        });

        // Limpiamos el input y renderizamos
        $("#montoMultipago").val("");
        renderizarPagos();
    });

    /* ==============================================================
       3. RENDERIZADO Y CÁLCULO DE TOTALES (FALTANTE Y VUELTO)
       ============================================================== */
    
    function renderizarPagos() {
        let html = "";
        let totalPagadoUsdt = 0;

        matrizPagos.forEach(function(pago) {
            totalPagadoUsdt += pago.equivalencia_usdt;
            
            // Color de la insignia de moneda
            let badgeClass = pago.moneda === 'BS' ? 'bg-info text-dark' : 'bg-success';
            
            html += `
                <tr>
                    <td class="fw-bold text-dark">${pago.metodo} <small class="d-block text-muted fw-normal">Ref: ${pago.referencia}</small></td>
                    <td class="text-center"><span class="badge ${badgeClass}">${pago.moneda}</span></td>
                    <td class="text-end fw-semibold">${pago.moneda === 'BS' ? 'Bs' : '$'} ${pago.monto_declarado.toFixed(2)}</td>
                    <td class="text-end fw-bold text-success">$${pago.equivalencia_usdt.toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm text-danger btnQuitarPago" id_pago="${pago.id}"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
        });

        if (matrizPagos.length === 0) {
            html = `<tr><td colspan="5" class="text-center py-3 text-muted">Aún no se han registrado pagos.</td></tr>`;
        }

        $("#listaMultipagos").html(html);
        calcularFaltante(totalPagadoUsdt);
    }

    // Quitar un pago erróneo
    $("#listaMultipagos").on("click", ".btnQuitarPago", function() {
        let id = $(this).attr("id_pago");
        matrizPagos = matrizPagos.filter(pago => pago.id != id);
        renderizarPagos();
    });

    function calcularFaltante(totalPagado) {
        let faltante = totalFacturaUsdt - totalPagado;
        let vuelto = 0;

        if (faltante <= 0) {
            vuelto = Math.abs(faltante);
            faltante = 0;
        }

        $("#montoFaltanteVisual").text("$" + faltante.toFixed(2));
        $("#montoVueltoVisual").text("$" + vuelto.toFixed(2));

        // NUEVO: Mostrar el equivalente en Bolívares dinámicamente
        if($("#spanFaltanteBs").length === 0) $("#montoFaltanteVisual").after(`<small class="d-block text-danger mt-1" id="spanFaltanteBs"></small>`);
        if($("#spanVueltoBs").length === 0) $("#montoVueltoVisual").after(`<small class="d-block text-success mt-1" id="spanVueltoBs"></small>`);

        $("#spanFaltanteBs").text(`(Bs ${(faltante * tasaBcv).toFixed(2)})`);
        $("#spanVueltoBs").text(`(Bs ${(vuelto * tasaBcv).toFixed(2)})`);

        // Validación Crítica
        if (faltante === 0 && matrizPagos.length > 0) {
            $("#btnProcesarVentaDefinitiva").prop("disabled", false);
        } else {
            $("#btnProcesarVentaDefinitiva").prop("disabled", true);
        }
    }

    /* ==============================================================
       4. PROCESAR VENTA DEFINITIVA AL SERVIDOR
       ============================================================== */
    
    $("#btnProcesarVentaDefinitiva").on("click", function() {
        
        let docCliente = $("#docClienteFinal").val().trim();
        let nomCliente = $("#nomClienteFinal").val().trim();
        
        // Validación de QA para cliente express
        if (nomCliente === "") {
            Swal.fire("Datos Faltantes", "Debe registrar el nombre del cliente para poder facturar.", "warning");
            $("#nomClienteFinal").focus();
            return;
        }

        Swal.fire({
            title: '¿Emitir Factura Definitiva?',
            text: "Se descargará el stock y se registrará el dinero en la caja.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, Facturar y Cobrar',
            cancelButtonText: 'Revisar Pagos'
        }).then((result) => {
            if (result.isConfirmed) {
                
                // Mapeamos el arreglo a un JSON simplificado para el Backend
                let datosPagosJSON = JSON.stringify(matrizPagos.map(p => {
                    return {
                        metodo: p.metodo,
                        moneda: p.moneda,
                        monto: p.monto_declarado,
                        referencia: p.referencia
                    };
                }));

                let formData = new FormData();
                formData.append("procesarVentaFinal", "ok");
                formData.append("docClienteFinal", docCliente);
                formData.append("nomClienteFinal", nomCliente);
                formData.append("telClienteFinal", $("#telClienteFinal").val());
                formData.append("emaClienteFinal", $("#emaClienteFinal").val()); // NUEVO CAMPO
                formData.append("dirClienteFinal", $("#dirClienteFinal").val());
                formData.append("listaPagosFinal", datosPagosJSON);

                // Bloqueamos el botón para evitar doble clic
                $("#btnProcesarVentaDefinitiva").prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-2"></i> Procesando...');

                $.ajax({
                    url: "index.php",
                    method: "POST",
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    success: function(res) {
                        if (res.status === "success") {
                            Swal.fire({
                                icon: 'success', title: '¡Cobro Exitoso!', text: res.mensaje, showConfirmButton: false, timer: 2000
                            }).then(function() {
                                // CORRECCIÓN: Llamamos al archivo raíz directamente, como en etiquetas
                                window.open("ticket-factura.php?idVenta=" + res.id_venta, "_blank");
                                window.location = "index.php?ruta=ventas-crear"; 
                            });
                        } else {
                            Swal.fire("Error Crítico", res.mensaje, "error");
                            $("#btnProcesarVentaDefinitiva").prop("disabled", false).html('<i class="fas fa-check-circle me-2"></i> Emitir Factura y Cobrar');
                        }
                    },
                    error: function(xhr) {
                        console.error("🚨 [ERROR FATAL] Liquidación:", xhr.responseText);
                        Swal.fire("Error de Servidor", "Consulte la consola (F12)", "error");
                        $("#btnProcesarVentaDefinitiva").prop("disabled", false).html('<i class="fas fa-check-circle me-2"></i> Emitir Factura y Cobrar');
                    }
                });
            }
        });
    });

    /* ==============================================================
       5. SISTEMA DE DESCUENTOS POR PIN (Adelanto del Sprint 3)
       ============================================================== */
    $("#btnAplicarDescuentoGlobal").on("click", function() {
        Swal.fire({
            title: 'Descuento Especial',
            text: "El motor de validación de PIN del supervisor se implementará en la siguiente fase.",
            icon: 'info'
        });
    });
});