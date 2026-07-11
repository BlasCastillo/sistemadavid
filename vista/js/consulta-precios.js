/* ====================================================================
   MÓDULO DE CONSULTA DE PRECIOS - LÓGICA DE ESCÁNER Y RENDERIZADO
==================================================================== */

$(document).ready(function() {
    
    let timerRetorno; // Temporizador para volver al carrusel
    let inputFisico = $("#inputLectorFisico");
    let html5QrcodeScanner = null; // Instancia global para la cámara móvil

    // 1. MANTENER EL FOCO EN EL INPUT OCULTO (Para Pistola Láser)
    // Forzamos el foco siempre que el usuario haga click fuera de un modal
    $(document).on("click", function(e) {
        if (!$(e.target).closest('.modal').length) {
            inputFisico.focus();
        }
    });

    // 2. ESCUCHAR LA PISTOLA LÁSER (Detecta la tecla Enter '13')
    inputFisico.on("keypress", function(e) {
        if (e.which === 13) { 
            e.preventDefault();
            let codigoLeido = $(this).val().trim();
            
            if (codigoLeido !== "") {
                procesarCodigoBarras(codigoLeido);
            }
            
            // Limpiamos el input para el siguiente disparo
            $(this).val(""); 
        }
    });

    // 3. INICIALIZAR LA CÁMARA MÓVIL (html5-qrcode) AL ABRIR EL MODAL
    $('#modalScannerCamara').on('shown.bs.modal', function () {
        // Instanciamos la librería en el contenedor definido en la vista
        html5QrcodeScanner = new Html5Qrcode("lectorCamara");
        
        const config = { fps: 10, qrbox: { width: 250, height: 150 } };
        
        // Iniciamos pidiendo la cámara trasera por defecto ("environment")
        html5QrcodeScanner.start(
            { facingMode: "environment" }, 
            config,
            (decodedText, decodedResult) => {
                // ÉXITO: Detenemos cámara, cerramos modal y procesamos el código
                html5QrcodeScanner.stop().then(() => {
                    $('#modalScannerCamara').modal('hide');
                    procesarCodigoBarras(decodedText);
                });
            },
            (errorMessage) => {
                // IGNORAR: Errores constantes mientras la cámara intenta enfocar
            }
        ).catch((err) => {
            Swal.fire("Error de Cámara", "No se pudo acceder a la cámara del dispositivo. Verifique los permisos del navegador.", "error");
        });
    });

    // APAGAR LA CÁMARA SI EL USUARIO CIERRA EL MODAL MANUALMENTE
    $('#modalScannerCamara').on('hidden.bs.modal', function () {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().catch(err => console.log("Error al detener la cámara:", err));
        }
        // Devolvemos el foco al lector físico
        inputFisico.focus();
    });

    /* ==========================================
       4. PROCESAR CÓDIGO BARRAS (AJAX ESTANDARIZADO)
    ========================================== */
    function procesarCodigoBarras(codigo) {
        
        // Detenemos el temporizador de retorno si estaba contando
        clearTimeout(timerRetorno);

        // Efecto visual de carga
        Swal.fire({
            title: 'Consultando precio...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: "index.php",
            method: "POST",
            data: { codigoBarrasConsulta: codigo },
            dataType: "json",
            success: function(res) {
                Swal.close();

                if (res.status === "success") {
                    // Renderizamos los datos en pantalla
                    mostrarResultadoEnPantalla(res.data);
                } else {
                    // El producto no existe o está inactivo
                    Swal.fire({ icon: 'error', title: 'Aviso', text: res.mensaje, timer: 2500, showConfirmButton: false });
                    inputFisico.focus();
                }
            },
            // BLOQUE DE ERROR ALINEADO CON EL ESTÁNDAR
            error: function(xhr, status, error) {
                Swal.close();
                console.error("Respuesta cruda del servidor:", xhr.responseText);
                Swal.fire('Error Interno', 'Revisa la consola. El servidor falló al procesar los datos.', 'error');
                inputFisico.focus();
            }
        });
    }

    /* ==========================================
       5. RENDERIZAR EL RESULTADO EN LA VISTA
    ========================================== */
    function mostrarResultadoEnPantalla(data) {
        
        // Ocultar el carrusel de espera y mostrar el bloque de resultados
        $("#estadoEspera").addClass("d-none");
        $("#estadoResultado").removeClass("d-none");

        // Inyectar datos básicos
        $("#resImagen").attr("src", (data.imagen !== "" && data.imagen !== null) ? data.imagen : "vista/img/productos/default.png");
        $("#resCodigo").text("CÓDIGO: " + data.codigo);
        $("#resNombre").text(data.nombre);

        // Función auxiliar para formatear la moneda (Coma para decimales, Punto para miles)
        const formatDinero = (num) => parseFloat(num).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // EVALUAMOS SI HAY OFERTA ACTIVA
        if (data.tiene_oferta === 1) {
            
            // Ocultar bloque normal, mostrar bloque oferta
            $("#bloquePrecioRegular").addClass("d-none");
            $("#bloquePrecioOferta").removeClass("d-none");

            $("#resBadgeDescuento").text("¡OFERTA -" + data.porcentaje_descuento + "%!");
            $("#resPrecioTachadoUsdt").text("Antes: $ " + formatDinero(data.precio_regular_usdt));
            $("#resOfertaUsdt").text("$ " + formatDinero(data.precio_oferta_usdt));
            $("#resOfertaBs").text("Bs " + formatDinero(data.precio_oferta_bs));
            
        } else {
            
            // Mostrar bloque normal, ocultar bloque oferta
            $("#bloquePrecioOferta").addClass("d-none");
            $("#bloquePrecioRegular").removeClass("d-none");

            $("#resPrecioUsdt").text("$ " + formatDinero(data.precio_regular_usdt));
            $("#resPrecioBs").text("Bs " + formatDinero(data.precio_regular_bs));
            
        }

        // Devolvemos el foco a la pistola inmediatamente
        inputFisico.focus();

        // PROGRAMAR RETORNO AUTOMÁTICO AL CARRUSEL DESPUÉS DE 10 SEGUNDOS
        timerRetorno = setTimeout(function() {
            $("#estadoResultado").addClass("d-none");
            $("#estadoEspera").removeClass("d-none");
            
            // Limpiamos la imagen por si acaso (evita flashes de la imagen anterior en la siguiente consulta)
            $("#resImagen").attr("src", ""); 
        }, 10000); 
    }
});