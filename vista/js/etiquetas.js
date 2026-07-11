/* ====================================================================
   MÓDULO DE ETIQUETAS - LÓGICA DE CAPTURA Y CARRITO EN MEMORIA (DOM)
==================================================================== */

$(document).ready(function() {
    
    // Inicializar Select2
    if ($('.select2-dinamico').length > 0) {
        $('.select2-dinamico').select2({ placeholder: "Seleccione una opción", allowClear: true });
    }

    let inputFisico = $("#inputLectorEtiquetas");
    let html5QrcodeScanner = null;
    let tablaVaciaHtml = $("#filaVaciaEtiquetas").prop('outerHTML'); // Guardamos el diseño original de la fila vacía

    // 1. MANTENER FOCO EN EL INPUT (Para Pistola Láser)
    $(document).on("click", function(e) {
        if (!$(e.target).closest('.modal').length && !$(e.target).closest('.select2-container').length && e.target.id !== 'formatoImpresion') {
            inputFisico.focus();
        }
    });

    /* ==============================================================
       2. BUSCADOR DE PRODUCTOS MANUAL (Select2 AJAX)
       ============================================================== */
    $('#buscadorManualEtiquetas').select2({
        placeholder: 'Escanee o escriba el nombre...',
        minimumInputLength: 1,
        ajax: {
            url: 'index.php',
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { buscarProductoSelect: params.term }; },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return {
                            id: item.codigo_barras, // IMPORTANTE: Usamos el código de barras, no el ID, para unificar con la pistola
                            text: item.codigo_barras + ' - ' + item.nombre
                        }
                    })
                };
            },
            cache: true
        }
    });

    // Evento al seleccionar manualmente un producto
    $('#buscadorManualEtiquetas').on('select2:select', function (e) {
        let codigoSeleccionado = e.params.data.id;
        procesarCodigoBarras(codigoSeleccionado);
        $(this).val(null).trigger('change'); // Limpiamos el select
    });

    /* ==============================================================
       3. ESCUCHA DE CÓDIGOS (Pistola Láser)
       ============================================================== */
    inputFisico.on("keypress", function(e) {
        if (e.which === 13) { 
            e.preventDefault();
            let codigoLeido = $(this).val().trim();
            if (codigoLeido !== "") { procesarCodigoBarras(codigoLeido); }
            $(this).val(""); 
        }
    });

    /* ==============================================================
       4. ESCUCHA DE CÓDIGOS (Cámara Móvil)
       ============================================================== */
    $('#modalScannerCamara').on('shown.bs.modal', function () {
        html5QrcodeScanner = new Html5Qrcode("lectorCamaraEtiquetas");
        html5QrcodeScanner.start(
            { facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 150 } },
            (decodedText) => {
                html5QrcodeScanner.stop().then(() => {
                    $('#modalScannerCamara').modal('hide');
                    procesarCodigoBarras(decodedText);
                });
            },
            (errorMessage) => { /* Ignorar errores de enfoque */ }
        ).catch((err) => { Swal.fire("Error", "No se pudo acceder a la cámara.", "error"); });
    });

    $('#modalScannerCamara').on('hidden.bs.modal', function () {
        if (html5QrcodeScanner) { html5QrcodeScanner.stop().catch(err => console.log(err)); }
        inputFisico.focus();
    });

    /* ==============================================================
       5. PROCESAR CÓDIGO BARRAS Y AGREGAR A LA LISTA (DOM)
       ============================================================== */
    function procesarCodigoBarras(codigo) {
        
        // 5.1. Verificar si el producto YA ESTÁ en la lista temporal
        let filaExistente = $(`#fila_etiqueta_${codigo}`);
        
        if (filaExistente.length > 0) {
            // Si ya existe, le sumamos 1 a la cantidad de etiquetas a imprimir
            let inputCantidad = filaExistente.find('.input-cantidad-etiqueta');
            let nuevaCantidad = parseInt(inputCantidad.val()) + 1;
            inputCantidad.val(nuevaCantidad);
            actualizarContadorTotal();
            
            Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, icon: 'success', title: '+1 agregado a la lista' });
            return;
        }

        // 5.2. Si no existe en la lista, lo buscamos en el servidor usando el controlador de ConsultaPrecios
        Swal.fire({ title: 'Buscando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: "index.php",
            method: "POST",
            data: { codigoBarrasConsulta: codigo }, // Reutilizamos el backend del Verificador de Precios
            dataType: "json",
            success: function(res) {
                Swal.close();

                if (res.status === "success") {
                    let p = res.data;
                    
                    // Lógica para decidir qué precio se imprime en la etiqueta
                    let precioFinalUsdt = (p.tiene_oferta === 1) ? p.precio_oferta_usdt : p.precio_regular_usdt;
                    let badgeOferta = (p.tiene_oferta === 1) ? `<span class="badge bg-danger ms-2"><i class="fas fa-tag"></i> OFERTA</span>` : '';

                    // Construimos la nueva fila HTML
                    let nuevaFila = `
                        <tr id="fila_etiqueta_${p.codigo}">
                            <td><span class="badge bg-secondary font-monospace fs-6">${p.codigo}</span></td>
                            <td class="fw-bold text-dark">${p.nombre} ${badgeOferta}</td>
                            <td class="text-end fw-bold text-success fs-5">$${parseFloat(precioFinalUsdt).toFixed(2)}</td>
                            <td class="text-center">
                                <input type="number" min="1" value="1" class="form-control text-center fw-bold input-cantidad-etiqueta" data-codigo="${p.codigo}" style="width: 80px; margin: 0 auto;">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger btnQuitarEtiqueta"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    `;

                    // Si la fila "vacía" está presente, la removemos
                    $("#filaVaciaEtiquetas").remove();
                    
                    // Inyectamos la fila en la tabla
                    $("#listaEtiquetasTemporal").prepend(nuevaFila);
                    actualizarContadorTotal();
                    
                    Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, icon: 'success', title: 'Producto listo para imprimir' });

                } else {
                    Swal.fire({ icon: 'error', title: 'Ups...', text: res.mensaje, timer: 2500, showConfirmButton: false });
                }
                inputFisico.focus();
            },
            error: function(xhr) {
                Swal.close();
                Swal.fire('Error', 'Fallo al consultar el servidor.', 'error');
                inputFisico.focus();
            }
        });
    }

    /* ==============================================================
       6. MANIPULACIÓN DEL CARRITO VISUAL (Quitar y Cambiar Cantidad)
       ============================================================== */
    
    // Escuchar cambios en los inputs de cantidad para actualizar el contador
    $("#tablaEtiquetas").on("change", ".input-cantidad-etiqueta", function() {
        if($(this).val() < 1) $(this).val(1);
        actualizarContadorTotal();
    });

    // Botón de eliminar fila
    $("#tablaEtiquetas").on("click", ".btnQuitarEtiqueta", function() {
        $(this).closest("tr").remove();
        
        // Si no quedan filas, restauramos el mensaje de "Lista vacía"
        if ($("#listaEtiquetasTemporal tr").length === 0) {
            $("#listaEtiquetasTemporal").html(tablaVaciaHtml);
        }
        actualizarContadorTotal();
    });

    function actualizarContadorTotal() {
        let total = 0;
        $(".input-cantidad-etiqueta").each(function() {
            total += parseInt($(this).val());
        });
        $("#contadorEtiquetasTotales").text(`${total} Etiquetas`);
    }

    /* ==============================================================
       7. ENVIAR A IMPRIMIR (GENERAR PDF)
       ============================================================== */
    $("#btnGenerarPDFEtiquetas").on("click", function() {
        
        let arrayEtiquetas = [];
        
        // Recorremos todos los inputs de cantidad para armar el paquete de datos
        $(".input-cantidad-etiqueta").each(function() {
            arrayEtiquetas.push({
                codigo: $(this).attr("data-codigo"),
                cantidad: parseInt($(this).val())
            });
        });

        if (arrayEtiquetas.length === 0) {
            Swal.fire("Lista Vacía", "Debe escanear al menos un producto para generar las etiquetas.", "warning");
            return;
        }

        let formato = $("#formatoImpresion").val();
        
        // Convertimos el arreglo JS a un string JSON seguro para viajar por URL o POST
        let datosJsonString = JSON.stringify(arrayEtiquetas);

        // Disparamos la apertura de una nueva pestaña enviando los datos al controlador de etiquetas
        // Usamos window.open para que el PDF se dibuje en una pestaña aparte sin recargar la actual
        let urlGenerador = `etiquetas-pdf.php?formato=${formato}&datos=${encodeURIComponent(datosJsonString)}`;

            window.open(urlGenerador, '_blank');
        
       
        
        Swal.fire({
            title: '¡PDF Generado!',
            text: "¿Desea limpiar la lista actual para escanear nuevos pasillos?",
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, limpiar lista',
            cancelButtonText: 'No, mantener lista'
        }).then((result) => {
            if (result.isConfirmed) {
                $("#listaEtiquetasTemporal").html(tablaVaciaHtml);
                actualizarContadorTotal();
            }
            inputFisico.focus();
        });
    });

});