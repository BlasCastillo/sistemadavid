/* ==============================================================
   MÓDULO DE CUENTAS POR PAGAR (CxP) - LÓGICA Y AJAX
   ============================================================== */

$(document).ready(function() {

    /* ==============================================================
       1. IR A LA VISTA DEDICADA DE ABONO (NUEVO FLUJO)
       ============================================================== */
    $(".tablaCuentasPorPagar").on("click", ".btnAbonarCxP", function() {
        let idCuenta = $(this).attr("idCuenta");
        // Redirigimos a la vista separada enviando el ID por la URL (GET)
        window.location = "index.php?ruta=cuentas-por-pagar-abonar&id=" + idCuenta;
    });

    /* ==============================================================
       2. GUARDAR ABONO EN LA BASE DE DATOS
       ============================================================== */
    $("#formAbonarCxP").on("submit", function(e) {
        e.preventDefault();
        
        let btnSubmit = $(this).find('button[type="submit"]');
        let btnOriginalText = btnSubmit.html();
        
        // Bloqueamos el botón para evitar doble envío (Antishock)
        btnSubmit.html('<span class="spinner-border spinner-border-sm me-2"></span> Procesando Pago...');
        btnSubmit.prop("disabled", true);

        $.ajax({
            url: "index.php",
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(respuesta) {
                if(respuesta.status === "success") {
                    $("#modalAbonarCxP").modal("hide");
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Abono Registrado!',
                        text: respuesta.mensaje,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(function() {
                        // Recargamos la vista para actualizar los saldos visualmente
                        window.location = "index.php?ruta=cuentas-por-pagar";
                    });
                } else {
                    // Si el backend lo rechaza, liberamos el botón
                    btnSubmit.html(btnOriginalText);
                    btnSubmit.prop("disabled", false);
                    Swal.fire("Error", respuesta.mensaje, "error");
                }
            },
            error: function(xhr) {
                btnSubmit.html(btnOriginalText);
                btnSubmit.prop("disabled", false);
                console.error(xhr.responseText);
                Swal.fire("Error Fatal", "Error de comunicación con el servidor. Revisa la consola.", "error");
            }
        });
    });

    /* ==============================================================
       3. VER HISTORIAL DE PAGOS DE LA CUENTA
       ============================================================== */
    $(".tablaCuentasPorPagar").on("click", ".btnVerPagosCxP", function() {
        let idCuenta = $(this).attr("idCuenta");
        let factura = $(this).attr("factura");
        
        // Efecto de carga previo
        Swal.fire({
            title: 'Cargando historial...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: "index.php",
            method: "POST",
            data: { idCuentaPorPagar: idCuenta },
            dataType: "json",
            success: function(respuesta) {
                Swal.close(); // Cerramos el loading
                
                // Si la base de datos nos dice que aún no hay pagos
                if(respuesta.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin abonos',
                        text: 'Esta factura aún no tiene pagos registrados.'
                    });
                    return;
                }

                // Construimos la tabla dinámica del historial
                let tabla = `<table class="table table-sm table-bordered table-striped text-start mt-3">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fecha / Cajero</th>
                                        <th>Método / Ref.</th>
                                        <th class="text-end">Monto (Bs)</th>
                                        <th class="text-end text-success">Monto (USDT)</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                                
                let totalAbonado = 0;

                // Llenamos las filas
                respuesta.forEach(function(pago) {
                    totalAbonado += parseFloat(pago.monto_pagado_usdt);
                    
                    // Formateamos la fecha (DD/MM/YYYY HH:MM)
                    let fecha = new Date(pago.fecha_pago);
                    let fechaFormat = fecha.toLocaleDateString() + ' ' + fecha.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

                    let referencia = pago.referencia !== "N/A" ? `<br><small class="text-muted">Ref: ${pago.referencia}</small>` : "";

                    tabla += `<tr>
                                <td>
                                    <small class="fw-bold">${fechaFormat}</small><br>
                                    <span class="badge bg-secondary" style="font-size:0.6rem"><i class="fas fa-user"></i> ${pago.cajero}</span>
                                </td>
                                <td><span class="text-uppercase fw-semibold text-primary">${pago.metodo_pago}</span>${referencia}</td>
                                <td class="text-end align-middle">Bs ${parseFloat(pago.monto_pagado_bs).toFixed(2)}</td>
                                <td class="text-end align-middle fw-bold text-success">$ ${parseFloat(pago.monto_pagado_usdt).toFixed(4)}</td>
                              </tr>`;
                });

                // Añadimos el pie de tabla con el total
                tabla += `</tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold text-dark">TOTAL ABONADO (USDT):</td>
                                    <td class="text-end fw-bold text-success fs-5">$ ${totalAbonado.toFixed(4)}</td>
                                </tr>
                            </tfoot>
                          </table>`;

                // Disparamos el SweetAlert con la tabla inyectada
                Swal.fire({
                    title: `<div class="text-start"><i class="fas fa-history text-primary me-2"></i>Historial de Pagos<br><small class="text-muted fs-6">Factura: ${factura}</small></div>`,
                    html: tabla,
                    width: 750,
                    showCloseButton: true,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                Swal.fire("Error", "No se pudo cargar el historial.", "error");
            }
        });
    });

    /* ==============================================================
       4. DINAMISMO DE MONEDA: Ajustar validación según tasa
       ============================================================== */
    $('select[name="monedaAbono"]').on('change', function() {
        let moneda = $(this).val();
        let inputMonto = $('#montoAbono');
        let simbolo = $('#simboloMonedaAbono'); // Para cambiar el $ a Bs visualmente
        
        // Leemos los valores que ahora sí están en el HTML
        let saldoUsd = parseFloat($('#saldoRestanteUsdt').val());
        let tasa = parseFloat($('#tasaBCV').val());

        if (moneda === 'Bs') {
            // El máximo permitido es SaldoUSD * Tasa
            let maxBs = (saldoUsd * tasa).toFixed(2);
            inputMonto.attr('max', maxBs);
            inputMonto.attr('step', '0.01');
            inputMonto.val(maxBs); 
            simbolo.text('Bs');
        } else {
            // Si es USD o USDT, el máximo es el saldo en USD
            inputMonto.attr('max', saldoUsd.toFixed(4));
            inputMonto.attr('step', '0.0001');
            inputMonto.val(saldoUsd.toFixed(4));
            simbolo.text('$');
        }
    });
});
