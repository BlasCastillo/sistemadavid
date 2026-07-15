/* ====================================================================
   MÓDULO DE VENTAS - LIQUIDACIÓN Y MULTIPAGO (Con Billetera)
==================================================================== */
$(document).ready(function() {
    
    // Validar que estamos en la vista correcta comprobando que exista el input global
    if ($("#tasaBcvGlobal").length === 0) return; 

    console.log("🚩 [0] Archivo ventas-pago.js cargado.");

    let tasaBcv = parseFloat($("#tasaBcvGlobal").val());
    let totalFacturaUsdt = parseFloat($("#totalUsdtGlobal").val());
    let matrizPagos = []; 
    
    // Memoria global fuerte para que no se pierda el código de la nota
    let codigoNotaCreditoActiva = "N/A"; 
    // Bandera para saber si mostramos la opción en el select
    let clienteTieneBilletera = false;
    // NUEVO: Memoria fantasma para auditar alteraciones HTML
    let saldoBilleteraReal = 0; 

    /* ==============================================================
       1. AUDITORÍA INICIAL (BÚSQUEDA SILENCIOSA DE BILLETERA)
       ============================================================== */
    let docClienteInicial = $("#docClienteFinal").val().trim();
    
    // Por defecto, ocultamos la opción para evitar errores visuales
    $("#metodoMultipago option[value='Saldo a Favor']").hide();

    if (docClienteInicial !== "") {
        $.ajax({
            url: "index.php",
            method: "POST",
            data: { buscarBilleteraAjax: "ok", documentoCliente: docClienteInicial },
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    clienteTieneBilletera = true;
                    // Si la moneda seleccionada es Dólares, mostramos la opción
                    if ($("#monedaMultipago").val() !== "BS") {
                        $("#metodoMultipago option[value='Saldo a Favor']").show();
                    }
                    console.log("🚩 [BILLETERA] Cliente con saldo activo encontrado:", res.monto_usd);
                }
            }
        });
    }

    /* ==============================================================
       2. DINAMISMO DEL FORMULARIO DE PAGOS
       ============================================================== */
    
    // Al cambiar la MONEDA
    $("#monedaMultipago").on("change", function() {
        let moneda = $(this).val();
        let pagado = 0;
        matrizPagos.forEach(p => pagado += p.equivalencia_usdt);
        let faltanteUsdt = totalFacturaUsdt - pagado;
        
        // Bloqueo de seguridad: No se puede usar Billetera en Bolívares
        if (moneda === "BS") {
            $("#metodoMultipago .opt-bs").removeClass("d-none");
            $("#metodoMultipago").val("Pago Movil");
            $("#metodoMultipago option[value='Saldo a Favor']").hide(); 
            
            $("#montoMultipago").prop("readonly", false);
            if (faltanteUsdt > 0) $("#montoMultipago").val((faltanteUsdt * tasaBcv).toFixed(2));
        } else {
            $("#metodoMultipago .opt-bs").addClass("d-none"); 
            $("#metodoMultipago").val("Efectivo");
            
            // Solo mostramos la billetera si el cliente pasó la auditoría silenciosa inicial
            if (clienteTieneBilletera) {
                $("#metodoMultipago option[value='Saldo a Favor']").show(); 
            } else {
                $("#metodoMultipago option[value='Saldo a Favor']").hide(); 
            }
            
            $("#montoMultipago").prop("readonly", false);
            if (faltanteUsdt > 0) $("#montoMultipago").val(faltanteUsdt.toFixed(2));
        }
    });

    // Al cambiar el MÉTODO DE PAGO (Auditoría de Consumo Total)
    $("#metodoMultipago").on("change", function() {
        let metodo = $(this).val();
        let docCliente = $("#docClienteFinal").val().trim(); 

        if (metodo === "Saldo a Favor") {
            
            if(docCliente === "") {
                Swal.fire("Error", "Debe identificar al cliente con su Cédula/RIF para poder buscar su Billetera.", "error");
                $(this).val("Efectivo"); 
                return;
            }

            $("#montoMultipago").prop("readonly", true).val("Buscando...");
            $("#btnAgregarPago").prop("disabled", true);

            $.ajax({
                url: "index.php",
                method: "POST",
                data: { buscarBilleteraAjax: "ok", documentoCliente: docCliente },
                dataType: "json",
                success: function(res) {
                    if(res.status === "success") {
                        
                        let saldoNota = parseFloat(res.monto_usd);
                        
                        // Calculamos cuánto le falta por pagar en este momento
                        let pagado = 0;
                        matrizPagos.forEach(p => pagado += p.equivalencia_usdt);
                        let faltanteUsdt = totalFacturaUsdt - pagado;

                        // REGLA DE NEGOCIO: CONSUMO TOTAL OBLIGATORIO (Margen de 0.05 por redondeos)
                        if (faltanteUsdt < (saldoNota - 0.05)) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Consumo Insuficiente',
                                text: `La Nota de Crédito disponible es por $${saldoNota.toFixed(2)}, pero a esta factura solo le faltan $${faltanteUsdt.toFixed(2)} por pagar. Debe agregar más productos al carrito para consumirla en su totalidad.`
                            });
                            
                            $("#metodoMultipago").val("Efectivo");
                            $("#montoMultipago").prop("readonly", false).val(faltanteUsdt > 0 ? faltanteUsdt.toFixed(2) : "");
                            $("#btnAgregarPago").prop("disabled", false);
                            codigoNotaCreditoActiva = "N/A";
                            saldoBilleteraReal = 0; // Reset
                            return; // Abortamos aquí mismo
                        }

                        // Si pasa la validación, inyectamos el saldo y guardamos las memorias fuertes
                        codigoNotaCreditoActiva = res.codigo_nota;
                        saldoBilleteraReal = saldoNota; // Guardamos el valor real en secreto

                        Swal.fire({
                            icon: 'info',
                            title: 'Billetera Encontrada y Aplicada',
                            text: 'Se utilizará la Nota: ' + res.codigo_nota + ' ($' + res.monto_usd + ')',
                            toast: true, position: 'top-end', showConfirmButton: false, timer: 4000
                        });
                        
                        $("#montoMultipago").val(res.monto_usd);
                        $("#btnAgregarPago").prop("disabled", false);

                    } else {
                        Swal.fire("Billetera Vacía", "Este cliente no tiene Notas de Crédito disponibles generadas el día de hoy.", "warning");
                        $("#metodoMultipago").val("Efectivo");
                        $("#montoMultipago").prop("readonly", false).val("");
                        $("#btnAgregarPago").prop("disabled", false);
                        codigoNotaCreditoActiva = "N/A";
                        saldoBilleteraReal = 0; // Reset
                    }
                },
                error: function(xhr) {
                    console.error("🚨 [ERROR BILLETERA]:", xhr.responseText);
                    $("#metodoMultipago").val("Efectivo");
                    $("#montoMultipago").prop("readonly", false).val("");
                    $("#btnAgregarPago").prop("disabled", false);
                    codigoNotaCreditoActiva = "N/A";
                    saldoBilleteraReal = 0; // Reset
                }
            });

        } else {
            $("#montoMultipago").prop("readonly", false);
            codigoNotaCreditoActiva = "N/A";
        }
    });

    /* ==============================================================
       3. AGREGAR PAGOS PARCIALES A LA CUADRÍCULA
       ============================================================== */
    $("#btnAgregarPago").on("click", async function() {
        let moneda = $("#monedaMultipago").val();
        let metodo = $("#metodoMultipago").val();
        let monto = parseFloat($("#montoMultipago").val());
        let referencia = "N/A";

        if (isNaN(monto) || monto <= 0) {
            Swal.fire("Monto Inválido", "Ingrese un monto mayor a cero.", "warning");
            return;
        }

        // NUEVAS VALIDACIONES BILLETERA
        if (metodo === "Saldo a Favor") {
            
            // 1. Escudo Anti-Duplicados (Bug 005)
            let billeteraYaAplicada = matrizPagos.some(p => p.metodo === "Saldo a Favor");
            if (billeteraYaAplicada) {
                Swal.fire("Acción Denegada", "Ya aplicó la Nota de Crédito en esta factura. No puede agregarla dos veces.", "error");
                return;
            }

            // 2. Candado del Saldo Real (Anti-HTML Tampering)
            // Margen de 0.01 por seguridad en redondeos decimales estrictos
            if (monto > (saldoBilleteraReal + 0.01)) { 
                Swal.fire("Monto Alterado Detectado", "El sistema detectó una discrepancia con el saldo real de la billetera. Operación bloqueada.", "error");
                $("#montoMultipago").val(saldoBilleteraReal); // Regresamos el input a la normalidad
                return;
            }

            referencia = codigoNotaCreditoActiva;
        }

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
            
            if (refInput) { referencia = refInput; } else { return; } 
        }

        let equivalenteUsdt = moneda === "BS" ? (monto / tasaBcv) : monto;

        matrizPagos.push({
            id: Date.now(), 
            moneda: moneda,
            metodo: metodo,
            monto_declarado: monto,
            equivalencia_usdt: equivalenteUsdt,
            referencia: referencia
        });

        $("#montoMultipago").val("").prop("readonly", false);
        $("#metodoMultipago").val("Efectivo"); 
        codigoNotaCreditoActiva = "N/A"; 
        renderizarPagos();
    });

    /* ==============================================================
       4. RENDERIZADO Y CÁLCULO DE TOTALES (FALTANTE Y VUELTO)
       ============================================================== */
    function renderizarPagos() {
        let html = "";
        let totalPagadoUsdt = 0;

        matrizPagos.forEach(function(pago) {
            totalPagadoUsdt += pago.equivalencia_usdt;
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

        // --- PARCHE VISUAL (Amortiguador de Céntimos) ---
        // Si el vuelto a entregar al cliente es menor a $0.02 (basura matemática), forzamos a 0.00
        if (vuelto > 0 && vuelto <= 0.02) {
            vuelto = 0;
            faltante = 0;
        }
        // ------------------------------------------------

        $("#montoFaltanteVisual").text("$" + faltante.toFixed(2));
        $("#montoVueltoVisual").text("$" + vuelto.toFixed(2));

        if($("#spanFaltanteBs").length === 0) $("#montoFaltanteVisual").after(`<small class="d-block text-danger mt-1" id="spanFaltanteBs"></small>`);
        if($("#spanVueltoBs").length === 0) $("#montoVueltoVisual").after(`<small class="d-block text-success mt-1" id="spanVueltoBs"></small>`);

        $("#spanFaltanteBs").text(`(Bs ${(faltante * tasaBcv).toFixed(2)})`);
        $("#spanVueltoBs").text(`(Bs ${(vuelto * tasaBcv).toFixed(2)})`);

        // Tolerancia de 0.05 USD para activar el botón de procesar 
        if (faltante <= 0.05 && matrizPagos.length > 0) {
            $("#btnProcesarVentaDefinitiva").prop("disabled", false);
        } else {
            $("#btnProcesarVentaDefinitiva").prop("disabled", true);
        }
    }

    /* ==============================================================
       5. PROCESAR VENTA DEFINITIVA AL SERVIDOR
       ============================================================== */
    $("#btnProcesarVentaDefinitiva").on("click", function() {
        
        let docCliente = $("#docClienteFinal").val().trim();
        let nomCliente = $("#nomClienteFinal").val().trim();
        
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
                formData.append("emaClienteFinal", $("#emaClienteFinal").val());
                formData.append("dirClienteFinal", $("#dirClienteFinal").val());
                formData.append("listaPagosFinal", datosPagosJSON);

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
                                window.open("ticket-factura.php?idVenta=" + res.id_venta, "_blank");
                                window.location = "index.php?ruta=ventas-crear"; 
                            });
                        } else if (res.mensaje.includes("menor al Saldo a Favor") || res.mensaje === "error_fraude_billetera") {
                            // Atrapamos los rechazos del backend si intentan burlar el JS e imprimimos el Payload en consola para auditar
                            if (res.debug) console.warn("🚩 [AUDITORÍA DE SALDO]:", res.debug);
                            Swal.fire("Transacción Rechazada", "La Nota de Crédito es inválida o el monto de la compra no alcanza para consumirla completa.", "error");
                            $("#btnProcesarVentaDefinitiva").prop("disabled", false).html('<i class="fas fa-check-circle me-2"></i> Emitir Factura y Cobrar');
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
       6. SISTEMA DE DESCUENTOS POR PIN
       ============================================================== */
    $("#btnAplicarDescuentoGlobal").on("click", function() {
        Swal.fire({
            title: 'Descuento Especial',
            text: "El motor de validación de PIN del supervisor se implementará en la siguiente fase.",
            icon: 'info'
        });
    });
});