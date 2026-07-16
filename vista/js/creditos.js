$(document).ready(function() {
    
    // LLAVE DE PASO (Aislamiento total del módulo)
    if (window.location.href.indexOf("ruta=creditos") === -1) {
        return;
    }

    console.log("🚩 [0] Archivo creditos.js cargado y activo.");

    // INICIALIZACIÓN BLINDADA DEL DATATABLE
    if ($.fn.DataTable.isDataTable('.tablaCreditos')) {
        $('.tablaCreditos').DataTable().destroy();
    }
    
    $(".tablaCreditos").DataTable({
        "language": { 
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json" 
        }
    });

    /* ==============================================================
       1. ABRIR MODAL Y CARGAR DEUDA RESTANTE
       ============================================================== */
    $(".tablaCreditos").on("click", ".btnAbonarCredito", function() {
        let idVenta = $(this).attr("idVenta");
        let factura = $(this).attr("factura");
        let cliente = $(this).attr("cliente");
        let restante = parseFloat($(this).attr("restante"));
        let tasa = parseFloat($(this).attr("tasa")); 

        $("#idVentaAbono").val(idVenta);
        $("#tasaBcvAbono").val(tasa);
        $("#maximoAbonoUsdt").val(restante); 

        $("#lblClienteAbono").text(cliente);
        $("#lblFacturaAbono").text(factura);
        $("#lblRestanteAbono").text(restante.toFixed(2));

        $("#monedaAbono").val("USD").trigger("change");
        $("#metodoAbono").val("Efectivo");
        $("#montoAbono").val(restante.toFixed(2)); 
        $("#referenciaAbono").val("");

        $("#modalAbonarCredito").modal("show");
    });

    /* ==============================================================
       2. DINAMISMO ENTRE MONEDAS (USD / BS)
       ============================================================== */
    $("#monedaAbono").on("change", function() {
        let moneda = $(this).val();
        let tasa = parseFloat($("#tasaBcvAbono").val());
        let maximoUsdt = parseFloat($("#maximoAbonoUsdt").val());

        if (moneda === "BS") {
            $("#metodoAbono .opt-bs").removeClass("d-none");
            $("#metodoAbono").val("Pago Movil");
            $("#simboloMonedaAbono").text("Bs");
            $("#montoAbono").val((maximoUsdt * tasa).toFixed(2));
            $("#equivalenciaAbono").removeClass("d-none").text(`Equivale a: $${maximoUsdt.toFixed(2)} USD`);
        } else {
            $("#metodoAbono .opt-bs").addClass("d-none");
            $("#metodoAbono").val("Efectivo");
            $("#simboloMonedaAbono").text("$");
            $("#montoAbono").val(maximoUsdt.toFixed(2));
            $("#equivalenciaAbono").addClass("d-none");
        }
    });

    $("#montoAbono").on("keyup change", function() {
        let monto = parseFloat($(this).val()) || 0;
        let moneda = $("#monedaAbono").val();
        let tasa = parseFloat($("#tasaBcvAbono").val());
        
        if(moneda === "BS") {
            let equivalente = monto / tasa;
            $("#equivalenciaAbono").text(`Equivale a: $${equivalente.toFixed(2)} USD`);
        }
    });

    /* ==============================================================
       3. PROCESAR EL ABONO Y ENVIAR AL BACKEND
       ============================================================== */
    $("#formAbonarCredito").on("submit", function(e) {
        e.preventDefault();

        let maximoUsdt = parseFloat($("#maximoAbonoUsdt").val());
        let monto = parseFloat($("#montoAbono").val());
        let moneda = $("#monedaAbono").val();
        let tasa = parseFloat($("#tasaBcvAbono").val());

        let equivalenteUsdt = (moneda === "BS") ? (monto / tasa) : monto;

        if (equivalenteUsdt > (maximoUsdt + 0.05)) {
            Swal.fire("Monto Excedido", `No puede abonar más dinero que la deuda restante. El límite es $${maximoUsdt.toFixed(2)}.`, "warning");
            return;
        }

        $("#btnGuardarAbono").prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-2"></i> Procesando Abono...');

        let formData = new FormData(this);

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
                        icon: 'success', title: '¡Abono Exitoso!', text: res.mensaje, showConfirmButton: false, timer: 2000
                    }).then(function() {
                        // NUEVO: Abrimos el ticket PDF en una nueva pestaña y luego recargamos la vista
                        window.open("ticket-abono.php?idPago=" + res.id_pago, "_blank");
                        window.location = "index.php?ruta=creditos";
                    });
                } else {
                    Swal.fire("Error Crítico", res.mensaje, "error");
                    $("#btnGuardarAbono").prop("disabled", false).html('<i class="fas fa-check-circle me-2"></i> Procesar Cuota');
                }
            },
            error: function(xhr) {
                console.error("🚨 [ERROR ABONO]:", xhr.responseText);
                Swal.fire("Error", "Ocurrió un problema de red o de servidor. Revise la consola (F12).", "error");
                $("#btnGuardarAbono").prop("disabled", false).html('<i class="fas fa-check-circle me-2"></i> Procesar Cuota');
            }
        });
    });

    /* ==============================================================
       4. NUEVO: CARGAR Y MOSTRAR EL HISTORIAL DE PAGOS DE LA FACTURA
       ============================================================== */
    $(".tablaCreditos").on("click", ".btnVerHistorial", function() {
        let idVenta = $(this).attr("idVenta");
        let factura = $(this).attr("factura");
        let cliente = $(this).attr("cliente");

        $("#lblClienteHistorial").text(cliente);
        $("#lblFacturaHistorial").text(factura);
        $("#cuerpoHistorialPagos").html('<tr><td colspan="6" class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br>Buscando pagos...</td></tr>');
        
        $("#modalHistorialPagos").modal("show");

        let datos = new FormData();
        datos.append("idVentaHistorial", idVenta);

        $.ajax({
            url: "index.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(respuesta) {
                
                if(respuesta.length === 0) {
                    $("#cuerpoHistorialPagos").html('<tr><td colspan="6" class="text-center py-4 text-muted">No se han registrado abonos para esta factura.</td></tr>');
                    return;
                }

                let html = "";
                respuesta.forEach(function(pago) {
                    
                    let badgeClass = (pago.moneda === 'BS') ? 'bg-info text-dark' : 'bg-success';
                    let simbolo = (pago.moneda === 'BS') ? 'Bs ' : '$ ';
                    let ref = (pago.referencia == "" || pago.referencia == null) ? "N/A" : pago.referencia;
                    
                    html += `
                        <tr>
                            <td class="align-middle">${pago.fecha_pago}</td>
                            <td class="align-middle fw-bold text-dark">${pago.metodo_pago}</td>
                            <td class="align-middle text-center"><span class="badge ${badgeClass}">${pago.moneda}</span></td>
                            <td class="align-middle text-muted small">${ref}</td>
                            <td class="align-middle text-end fw-bold">${simbolo}${parseFloat(pago.monto_pagado).toFixed(2)}</td>
                            <td class="align-middle text-center">
                                <a href="ticket-abono.php?idPago=${pago.id}" target="_blank" class="btn btn-sm btn-outline-danger" title="Imprimir Recibo">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });

                $("#cuerpoHistorialPagos").html(html);
            }
        });
    });

});