/* ====================================================================
   MÓDULO DE AUDITORÍA DE CIERRES (REPORTES X) - LÓGICA GRANULAR
==================================================================== */
$(document).ready(function() {
    
    if (window.location.href.indexOf("ruta=auditoria-cierres") === -1) return;

    if ($('.tabla-auditoria').length > 0) {
        $('.tabla-auditoria').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json" },
            "order": [[ 0, "desc" ]] 
        });
    }

    // 1. ABRIR EL MODAL Y PINTAR LÍNEAS
    $(document).on("click", ".btnVerDetalleCierre", function(){
        
        let idCierre = $(this).attr("idCierre");
        let cajeroNombre = $(this).closest("tr").find("td:eq(1)").text().trim();
        let estadoCierre = $(this).closest("tr").find("td:eq(4)").text().trim(); // Saber si ya fue Ajustado
        
        $("#detCierreID").text(idCierre.padStart(6, '0'));
        $("#modalDetalleCierre").modal("show");
        $("#cuerpoDetalleCierre").html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-warning"></i></div>');

        let datos = new FormData();
        datos.append("idCierreDetalle", idCierre);

        $.ajax({
            url: "index.php", method: "POST", data: datos, cache: false, contentType: false, processData: false, dataType: "json",
            success: function(respuesta){
                
                let esperados = JSON.parse(respuesta.montos_esperados);
                let declarados = JSON.parse(respuesta.montos_declarados);
                let diferencias = JSON.parse(respuesta.diferencias);

                let html = `<ul class="list-group shadow-sm">`;

                // Función Constructora de Filas con Botones Integrados
                const pintarFila = (titulo, dec, esp, dif, sim, monedaBD) => {
                    let colorDif = (dif == 0) ? 'text-success' : (dif > 0 ? 'text-primary' : 'text-danger');
                    let txtDif = (dif == 0) ? '<i class="fas fa-check"></i> Cuadrado' : (dif > 0 ? 'Sobra ' + sim + Math.abs(dif).toFixed(2) : 'Falta ' + sim + Math.abs(dif).toFixed(2));
                    
                    let filaHtml = `
                            <li class="list-group-item">
                                <b class="text-dark">${titulo}</b><br>
                                <div class="d-flex justify-content-between align-items-center small mt-2">
                                    <span class="text-muted">Sistema:<br><b class="text-dark">${sim}${parseFloat(esp).toFixed(2)}</b></span>
                                    <span class="text-muted text-center">Físico:<br><b class="text-dark">${sim}${parseFloat(dec).toFixed(2)}</b></span>
                                    <span class="fw-bold ${colorDif} text-end">Dif:<br>${txtDif}</span>
                                </div>`;

                    // Si hay descuadre Y la auditoría aún no está cerrada ("Ajustado")
                    if(Math.abs(dif) > 0.05 && respuesta.estado !== "Ajustado" && respuesta.estado !== "Cuadrado") {
                        filaHtml += `<div class="mt-2 pt-2 border-top text-end">`;
                        
                        if (dif < 0) { // FALTANTE
                            filaHtml += `<button class="btn btn-outline-danger btn-sm py-0 px-2 me-2 btnAjusteLinea" data-tipo="gasto" data-monto="${Math.abs(dif)}" data-moneda="${monedaBD}" data-concepto="Faltante en ${titulo} | Arqueo #${idCierre.padStart(6, '0')} | Cajero: ${cajeroNombre}"><i class="fas fa-level-down-alt"></i> Tienda asume</button>`;
                            filaHtml += `<button class="btn btn-outline-success btn-sm py-0 px-2 btnAjusteLinea" data-tipo="ingreso" data-monto="${Math.abs(dif)}" data-moneda="${monedaBD}" data-concepto="Cobro Cajero por faltante en ${titulo} | Arqueo #${idCierre.padStart(6, '0')} | Cajero: ${cajeroNombre}"><i class="fas fa-hand-holding-usd"></i> Cajero paga</button>`;
                        } else { // SOBRANTE
                            filaHtml += `<button class="btn btn-outline-primary btn-sm py-0 px-2 btnAjusteLinea" data-tipo="ingreso" data-monto="${Math.abs(dif)}" data-moneda="${monedaBD}" data-concepto="Sobrante en ${titulo} | Arqueo #${idCierre.padStart(6, '0')} | Cajero: ${cajeroNombre}"><i class="fas fa-level-up-alt"></i> Ingresar Sobrante</button>`;
                        }
                        
                        filaHtml += `</div>`;
                    }
                    filaHtml += `</li>`;
                    return filaHtml;
                };

                // Pintamos las líneas pasando la Moneda (USD o BS)
                html += pintarFila("Efectivo Dólares ($)", declarados.efectivo_usd, esperados.efectivo_usd, diferencias.efectivo_usd, "$", "USD");
                html += pintarFila("Efectivo Bolívares (Bs)", declarados.efectivo_bs, esperados.efectivo_bs, diferencias.efectivo_bs, "Bs ", "BS");
                html += pintarFila("Punto de Venta (Bs)", declarados.punto_venta_bs, esperados.punto_venta_bs, diferencias.punto_venta_bs, "Bs ", "BS");
                
                // Mención a pagos electrónicos
                html += `<li class="list-group-item bg-light border-top-0 mt-2">
                            <b class="text-secondary" style="font-size: 13px;">Pagos Automáticos (Sistema)</b><br>
                            <div class="d-flex justify-content-between small text-muted mt-1">
                                <span>Zelle: $${parseFloat(esperados.zelle_usd).toFixed(2)}</span>
                                <span>PM: Bs ${parseFloat(esperados.pago_movil_bs).toFixed(2)}</span>
                            </div>
                         </li></ul>`;
                
                // Botón maestro para cerrar la auditoría de este turno
                if(respuesta.estado !== "Ajustado" && respuesta.estado !== "Cuadrado") {
                    html += `
                    <div class="mt-4 d-grid">
                        <button class="btn btn-success fw-bold shadow-sm btnFinalizarAuditoria" idCierre="${idCierre}">
                            <i class="fas fa-check-double"></i> Marcar Auditoría como Finalizada
                        </button>
                    </div>`;
                }

                $("#cuerpoDetalleCierre").html(html);
            }
        });
    });

    // 2. EVENTO: PROCESAR UNA SOLA LÍNEA DE AJUSTE
    $(document).on("click", ".btnAjusteLinea", function(){
        let btn = $(this);
        let tipo = btn.attr("data-tipo");
        let monto = btn.attr("data-monto");
        let moneda = btn.attr("data-moneda");
        let concepto = btn.attr("data-concepto");

        let tituloSwal = (tipo === 'gasto') ? 'Asumir Pérdida' : 'Registrar Ingreso al Sistema';
        
        // Creamos el formulario HTML inyectado dentro de SweetAlert
        let htmlForm = `<p class="mb-3">Monto a registrar: <b>${moneda} ${parseFloat(monto).toFixed(2)}</b></p>`;
        
        if (tipo === 'ingreso') {
            htmlForm += `
                <div class="text-start p-3 bg-light border rounded">
                    <label class="small fw-bold text-secondary">Método de Pago:</label>
                    <select id="swalMetodo" class="form-select form-select-sm mb-2 shadow-sm">
                        <option value="Efectivo">Efectivo</option>
                        <option value="Pago Movil">Pago Móvil</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Zelle">Zelle / Binance</option>
                    </select>
                    
                    <label class="small fw-bold text-secondary">Referencia (Requerido para pagos electrónicos):</label>
                    <input id="swalRef" class="form-control form-control-sm shadow-sm" placeholder="Ej: 03498522">
                </div>
            `;
        }

        Swal.fire({
            title: tituloSwal,
            html: htmlForm,
            icon: (tipo === 'gasto') ? 'warning' : 'info',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonColor: (tipo === 'gasto') ? '#d33' : '#198754',
            confirmButtonText: 'Procesar Ajuste',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                // Si es ingreso, atrapamos los valores y validamos
                if(tipo === 'ingreso') {
                    let met = document.getElementById('swalMetodo').value;
                    let ref = document.getElementById('swalRef').value.trim();
                    
                    if(met !== 'Efectivo' && ref === '') {
                        Swal.showValidationMessage('Debe ingresar la referencia para pagos electrónicos.');
                        return false;
                    }
                    return { metodo: met, referencia: ref };
                }
                return { metodo: '', referencia: '' };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let datos = new FormData();
                datos.append("accionAuditoria", "movimiento_linea");
                datos.append("tipo", tipo);
                datos.append("monto", monto);
                datos.append("moneda", moneda);
                datos.append("concepto", concepto);
                
                if (tipo === 'ingreso') {
                    datos.append("metodo_pago", result.value.metodo);
                    datos.append("referencia", result.value.referencia);
                }

                $.ajax({
                    url: "index.php", method: "POST", data: datos, cache: false, contentType: false, processData: false, dataType: "json",
                    success: function(respuesta){
                        if(respuesta.status === "ok") {
                            btn.parent().html(`<span class="badge bg-success"><i class="fas fa-check-double"></i> Procesado Correctamente</span>`);
                        }
                    }
                });
            }
        });
    });

    // 3. EVENTO: FINALIZAR TODA LA AUDITORÍA
    $(document).on("click", ".btnFinalizarAuditoria", function(){
        let idCierre = $(this).attr("idCierre");

        Swal.fire({
            title: '¿Finalizar Auditoría?',
            text: "Se marcará este arqueo como 'Ajustado' y quitará la alerta roja.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, finalizar'
        }).then((result) => {
            if (result.isConfirmed) {
                let datos = new FormData();
                datos.append("accionAuditoria", "finalizar");
                datos.append("idCierre", idCierre);

                $.ajax({
                    url: "index.php", method: "POST", data: datos, cache: false, contentType: false, processData: false, dataType: "json",
                    success: function(respuesta){
                        if(respuesta.status === "ok") {
                            window.location = "index.php?ruta=auditoria-cierres";
                        }
                    }
                });
            }
        });
    });

});