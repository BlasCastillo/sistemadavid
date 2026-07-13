/* ====================================================================
   MÓDULO DE HISTORIAL DE VENTAS Y AUDITORÍA
==================================================================== */

$(document).ready(function() {
    
    // BASTIÓN DE SEGURIDAD: Si no estoy en la tabla de historial, aborto el script
    if ($("#tablaHistorialVentas").length === 0) return;

    console.log("🚩 [0] Archivo ventas-historial.js cargado.");

    // 1. Inicializar DataTable
    $('#tablaHistorialVentas').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        "order": [[0, "desc"]] // Ordenar para que la factura más reciente salga de primera
    });

    // 2. Acción: REIMPRIMIR TICKET
    $("#tablaHistorialVentas").on("click", ".btnReimprimirTicket", function() {
        let idVenta = $(this).attr("idVenta");
        console.log("🚩 [ACCIÓN] Reimprimiendo ticket de venta ID:", idVenta);
        
        // Reutilizamos el motor PDF de la raíz sin recargar la página actual
        window.open("ticket-factura.php?idVenta=" + idVenta, "_blank");
    });

    // 3. ANULAR FACTURA / NOTA DE CRÉDITO (Autorización de Doble Factor + Debug)
    $("#tablaHistorialVentas").on("click", ".btnAnularVenta", async function() {
        
        console.log("🚩 [CLIC] Botón de anulación activado.");

        let idVenta = $(this).attr("idVenta");
        let numFactura = $(this).attr("numFactura");

        const { value: formValues } = await Swal.fire({
            title: 'Autorización de Supervisor',
            html:
                '<div class="mb-3 text-start"><label class="form-label small fw-bold">Usuario Autorizado</label>' +
                '<input id="swal-usuario" class="swal2-input m-0" placeholder="Ej: admin_pedro" autocomplete="off"></div>' +
                '<div class="mb-3 text-start"><label class="form-label small fw-bold">Clave / PIN</label>' +
                '<input id="swal-pin" class="swal2-input m-0" type="password" placeholder="****" autocomplete="new-password"></div>',
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="fas fa-key"></i> Validar Permisos',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const usuario = document.getElementById('swal-usuario').value;
                const pin = document.getElementById('swal-pin').value;
                if (!usuario || !pin) {
                    Swal.showValidationMessage('Debe ingresar credenciales completas');
                    return false;
                }
                console.log("🚩 [CREDENCIALES] Enviando usuario:", usuario);
                return { usuario: usuario, pin: pin };
            }
        });

        if (formValues) {
            Swal.fire({ title: 'Auditando Permisos...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            $.ajax({
                url: "index.php",
                method: "POST",
                data: { supUsuario: formValues.usuario, supPin: formValues.pin }, 
                dataType: "json",
                success: function(res) {
                    console.log("🚩 [RESPUESTA SERVIDOR]", res);
                    
                    if(res.status === "success") {
                        // Cambio de paradigma: Redirección al módulo de devoluciones
                        Swal.fire({
                            title: '¡Permiso Concedido!',
                            text: 'Autorizado por: ' + res.nombre_supervisor + '. Abriendo panel de auditoría y devoluciones...',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            // Redireccionamos a la nueva pantalla pasando el ID de la venta por la URL
                            window.location = "index.php?ruta=ventas-devolucion&idVenta=" + idVenta;
                        });
                        
                    } else {
                        Swal.fire('Operación Rechazada', res.mensaje, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("🚨 [ERROR AJAX FATAL] Código HTTP:", xhr.status);
                    console.error("🚨 [TEXTO DEL ERROR]:", error);
                    console.error("🚨 [RESPUESTA CRUDA PHP]:", xhr.responseText); // Esta línea es oro puro
                    
                    Swal.fire('Error', 'Fallo de comunicación. Revisa la consola (F12).', 'error');
                }
            });
        }
    });
});