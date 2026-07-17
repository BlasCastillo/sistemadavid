$(document).ready(function() {
    
    // LLAVE DE PASO: Solo se ejecuta en la vista de Cierre de Turno
    if (window.location.href.indexOf("ruta=cierre-turno") === -1) {
        return;
    }

    console.log("🚩 Módulo de Cierre de Turno Activo.");

    $("#formCierreTurno").on("submit", function(e) {
        e.preventDefault();

        // 1. Capturamos lo que el sistema esperaba (oculto en el HTML)
        let esperados = JSON.parse($("#jsonEsperados").val());

        // 2. Capturamos lo que el cajero digitó físicamente
        let declaradoUsd = parseFloat($("#declarado_efectivo_usd").val()) || 0;
        let declaradoBs = parseFloat($("#declarado_efectivo_bs").val()) || 0;
        let declaradoPunto = parseFloat($("#declarado_punto_bs").val()) || 0;

        // 2.5. CALCULAMOS LAS DIFERENCIAS (¡La pieza que faltaba!)
        let difUsd = declaradoUsd - parseFloat(esperados.efectivo_usd);
        let difBs = declaradoBs - parseFloat(esperados.efectivo_bs);
        let difPunto = declaradoPunto - parseFloat(esperados.punto_venta_bs);

        // 3. Función interna para enviar los datos al Backend (PHP)
        const enviarCierre = (userGerente = null, pinGerente = null, obs = "") => {
            
            $("#btnProcesarArqueo").prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-2"></i> Verificando...');

            let declaradosJson = {
                efectivo_usd: declaradoUsd,
                efectivo_bs: declaradoBs,
                punto_venta_bs: declaradoPunto,
                zelle_usd: parseFloat(esperados.zelle_usd),
                pago_movil_bs: parseFloat(esperados.pago_movil_bs),
                transferencia_bs: parseFloat(esperados.transferencia_bs)
            };

            let datos = new FormData();
            datos.append("procesarCierreTurno", "ok");
            datos.append("declarados", JSON.stringify(declaradosJson));
            datos.append("esperados", JSON.stringify(esperados));
            
            // Adjuntamos credenciales solo si existen
            if(userGerente !== null) datos.append("userGerente", userGerente);
            if(pinGerente !== null) datos.append("pinGerente", pinGerente);
            
            datos.append("observaciones", obs);

            $.ajax({
                url: "index.php",
                method: "POST",
                data: datos,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function(respuesta) {
                    if (respuesta.status === "success") {
                        Swal.fire({
                            icon: 'success', 
                            title: '¡Turno Cerrado!', 
                            text: respuesta.mensaje, 
                            showConfirmButton: false, 
                            timer: 2000
                        }).then(function() {
                            // Llamamos directamente al archivo en la raíz del proyecto
window.open("ticket-cierre.php?idCierre=" + respuesta.id_cierre, "_blank");
                            
                            // 2. Redirigimos la pestaña actual de vuelta a ventas
                            window.location = "index.php?ruta=ventas"; 
                        });
                    } else {
                        // Si el backend detecta trampa o error de credenciales, rechaza la operación
                        Swal.fire("Acceso Denegado", respuesta.mensaje, "error");
                        $("#btnProcesarArqueo").prop("disabled", false).html('<i class="fas fa-check-circle me-2"></i> Procesar Reporte y Cuadrar Caja');
                    }
                },
                error: function(xhr) {
                    console.error("Error del servidor:", xhr.responseText);
                    Swal.fire("Error Crítico", "El servidor rechazó la solicitud. Revise la consola.", "error");
                    $("#btnProcesarArqueo").prop("disabled", false).html('<i class="fas fa-check-circle me-2"></i> Procesar Reporte y Cuadrar Caja');
                }
            });
        };

        // 4. EL CHOQUE CON LA REALIDAD (Validación de Diferencias)
        if (Math.abs(difUsd) > 0.05 || Math.abs(difBs) > 0.05 || Math.abs(difPunto) > 0.05) {
            
            let msjError = `<div class="text-start alert alert-warning border border-warning"><ul class="mb-0">`;
            if (Math.abs(difUsd) > 0.05) msjError += `<li><b>Efectivo ($):</b> ${difUsd > 0 ? 'Sobrante detectado' : 'Faltante detectado'}</li>`;
            if (Math.abs(difBs) > 0.05) msjError += `<li><b>Efectivo (Bs):</b> ${difBs > 0 ? 'Sobrante detectado' : 'Faltante detectado'}</li>`;
            if (Math.abs(difPunto) > 0.05) msjError += `<li><b>Punto de Venta:</b> ${difPunto > 0 ? 'Sobrante detectado' : 'Faltante detectado'}</li>`;
            msjError += `</ul></div>`;

            // Modal Avanzado con 2 Inputs
            Swal.fire({
                title: '¡Discrepancia de Caja!',
                html: `Se detectaron diferencias físicas contra el sistema:<br><br>${msjError}
                       <b class="text-danger">Requiere credenciales gerenciales para autorizar.</b>
                       <div class="mt-3">
                           <input id="swal-user" class="form-control mb-2 text-center" placeholder="Usuario del Gerente" autocomplete="off">
                           <input id="swal-pin" type="password" class="form-control text-center" placeholder="PIN / Contraseña">
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-key"></i> Autorizar Descuadre',
                cancelButtonText: 'Volver a Contar',
                confirmButtonColor: '#e74a3b',
                preConfirm: () => {
                    const user = document.getElementById('swal-user').value;
                    const pin = document.getElementById('swal-pin').value;
                    if (!user || !pin) {
                        Swal.showValidationMessage('Debe ingresar el Usuario y la Contraseña/PIN.');
                        return false;
                    }
                    return { user: user, pin: pin };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarCierre(result.value.user, result.value.pin, "Cierre enviado con discrepancia autorizada.");
                }
            });

        } else {
            // CUADRE PERFECTO
            enviarCierre(null, null, "Arqueo de caja cuadrado perfectamente.");
        }
    });
});