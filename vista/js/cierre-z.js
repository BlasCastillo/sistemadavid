/* ====================================================================
   MÓDULO DE CIERRE DE TIENDA (REPORTE Z)
==================================================================== */
$(document).ready(function() {
    
    // BANDERA: Solo ejecutar en la vista del Cierre Z
    if (window.location.href.indexOf("ruta=cierre-z") === -1) {
        return;
    }

    // =======================================================
    // 1. FUNCIÓN RADAR
    // =======================================================
    function escanearRadar() {
        let datos = new FormData();
        datos.append("accionCierreZ", "radar");

        $.ajax({
            url: "index.php", method: "POST", data: datos, cache: false, contentType: false, processData: false, dataType: "json",
            success: function(respuesta){
                if(respuesta.length > 0) {
                    let htmlAlert = `<div class="alert alert-warning text-start shadow-sm border-warning">
                            <h5 class="fw-bold text-dark"><i class="fas fa-exclamation-triangle text-danger me-2"></i> ¡Cierres Pendientes Detectados!</h5>
                            <p class="text-dark small mb-3">El sistema no permite emitir el Reporte Z maestro porque existen cajeros con ventas activas que no han realizado su Reporte X (Cierre de Caja).</p>
                            <ul class="list-group mb-0">`;
                            
                    respuesta.forEach(cajero => {
                        htmlAlert += `<li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                <div>
                                    <span class="d-block"><i class="fas fa-user-circle text-primary me-1"></i> <b>@${cajero.nombre_cajero}</b></span>
                                    <span class="badge bg-danger rounded-pill mt-1">${cajero.cantidad_facturas} facturas sin cerrar</span>
                                </div>
                                <button class="btn btn-sm btn-outline-danger shadow-sm btnForzarCierreX" idCajero="${cajero.usuario_id}" nombreCajero="${cajero.nombre_cajero}">
                                    <i class="fas fa-power-off"></i> Forzar Cierre
                                </button>
                            </li>`;
                    });

                    htmlAlert += `</ul></div>`;
                    $("#radarCierresResultados").html(htmlAlert);
                    $("#btnEjecutarZ").prop("disabled", true); 

                } else {
                    let htmlLuzVerde = `<div class="alert alert-success shadow-sm mb-0">
                            <h4 class="fw-bold mb-1"><i class="fas fa-check-circle me-2"></i> ¡Todo Despejado!</h4>
                            <p class="mb-0">Todos los cajeros han cerrado caja. La tienda está lista para la consolidación total.</p>
                        </div>`;
                    $("#radarCierresResultados").html(htmlLuzVerde);
                    $("#btnEjecutarZ").prop("disabled", false).removeClass("btn-danger").addClass("btn-success"); 
                }
            },
            error: function(xhr){
                $("#radarCierresResultados").html('<div class="alert alert-danger">Error de comunicación con el Radar.</div>');
            }
        });
    }

    escanearRadar();

    // =======================================================
    // 2. BOTÓN: FORZAR CIERRE X DE CAJERO (CORTE REMOTO)
    // =======================================================
    $(document).on("click", ".btnForzarCierreX", function() {
        let idCajero = $(this).attr("idCajero");
        let nombreCajero = $(this).attr("nombreCajero");

        Swal.fire({
            title: '¿Forzar Cierre de Caja?',
            text: `Se ejecutará el Reporte X de @${nombreCajero} declarando $0.00 en físico.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, Forzar Cierre',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let datos = new FormData();
                datos.append("accionCierreZ", "forzar_cierre_x");
                datos.append("idCajeroForzado", idCajero);

                $.ajax({
                    url: "index.php", method: "POST", data: datos, cache: false, contentType: false, processData: false, dataType: "json",
                    success: function(respuesta){
                        if(respuesta.status === "ok") {
                            Swal.fire({
                                icon: "success",
                                title: "Cierre Forzado",
                                text: `La caja de @${nombreCajero} ha sido cerrada.`
                            }).then(() => {
                                // ¡LA SOLUCIÓN AL PRIMER BUG! Forzamos la recarga al cerrar la alerta.
                                window.location.reload(); 
                            });
                        } else {
                            Swal.fire("Error", "No se pudo forzar el cierre.", "error");
                        }
                    }
                });
            }
        });
    });

    // =======================================================
    // 3. BOTÓN MAESTRO: EJECUTAR CIERRE Z (CON PIN)
    // =======================================================
    $(document).on("click", "#btnEjecutarZ", function() {
        
        Swal.fire({
            title: '¿Emitir Reporte Z de Tienda?',
            html: `Esta acción consolidará todos los Reportes X, gastos e ingresos de hoy.<br><br>
                   <b class="text-danger">Requiere credenciales gerenciales para autorizar.</b>
                   <div class="mt-3">
                       <input id="swal-user-z" class="form-control mb-2 text-center" placeholder="Usuario del Gerente" autocomplete="off">
                       <input id="swal-pin-z" type="password" class="form-control text-center" placeholder="PIN / Contraseña">
                   </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-lock"></i> Autorizar y Cerrar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const user = document.getElementById('swal-user-z').value;
                const pin = document.getElementById('swal-pin-z').value;
                if (!user || !pin) {
                    Swal.showValidationMessage('Debe ingresar el Usuario y la Contraseña/PIN.');
                    return false;
                }
                return { user: user, pin: pin };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                
                let btn = $("#btnEjecutarZ");
                let originalHtml = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i> Consolidando...');
                btn.prop("disabled", true);

                let datos = new FormData();
                datos.append("accionCierreZ", "ejecutar_z");
                datos.append("userGerente", result.value.user);
                datos.append("pinGerente", result.value.pin);

                $.ajax({
                    url: "index.php", method: "POST", data: datos, cache: false, contentType: false, processData: false, dataType: "json",
                    success: function(respuesta){
                        if(respuesta.status === "ok") {
                            Swal.fire({
                                icon: 'success', title: '¡Cierre Z Exitoso!', text: 'El día operativo ha sido cerrado.', confirmButtonText: 'Finalizar'
                            }).then(function() {
                                window.location = "index.php?ruta=dashboard"; 
                            });
                        } else if(respuesta.status === "credenciales_invalidas") {
                            Swal.fire("Acceso Denegado", "Usuario o PIN gerencial incorrectos.", "error");
                            btn.html(originalHtml).prop("disabled", false);
                        } else if(respuesta.status === "sin_cajas") {
                            Swal.fire("Aviso", "No hay Reportes X finalizados para consolidar hoy.", "info");
                            btn.html(originalHtml).prop("disabled", false);
                        } else {
                            Swal.fire("Error", "Problema de base de datos al generar el Cierre Z.", "error");
                            btn.html(originalHtml).prop("disabled", false);
                        }
                    },
                    error: function(xhr) {
                        console.error("Error devuelto por PHP: ", xhr.responseText);
                        Swal.fire("Error del Servidor", "Ocurrió una falla en el backend. Revisa la consola.", "error");
                        btn.html(originalHtml).prop("disabled", false);
                    }
                });
            }
        });
    });

});