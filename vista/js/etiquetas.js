/* ====================================================================
   MÓDULO DE ETIQUETAS - DEBUGGING SELECT2
==================================================================== */

$(document).ready(function() {
    
    console.log("🚩 [0] Archivo etiquetas.js (Modo Debug) cargado.");

    // Inicializar Select2 general
    if ($('.select2-dinamico').length > 0) {
        console.log("🚩 [1] Inicializando select2-dinamico generales...");
        $('.select2-dinamico').select2({ placeholder: "Seleccione una opción", allowClear: true });
    }

    let inputFisico = $("#inputLectorEtiquetas");
    let html5QrcodeScanner = null;
    let tablaVaciaHtml = $("#filaVaciaEtiquetas").prop('outerHTML'); 

    // MANTENER FOCO
    $(document).on("click", function(e) {
        if (!$(e.target).closest('.modal').length && !$(e.target).closest('.select2-container').length && e.target.id !== 'formatoImpresion') {
            inputFisico.focus();
        }
    });

    /* ==============================================================
       2. BUSCADOR DE PRODUCTOS MANUAL (CON BANDERAS)
       ============================================================== */
    
    // Destruimos por si el inicializador global lo afectó
    if ($('#buscadorManualEtiquetas').hasClass("select2-hidden-accessible")) {
        console.log("🚩 [2] Destruyendo inicialización previa del buscador manual...");
        $('#buscadorManualEtiquetas').select2('destroy');
    }

    console.log("🚩 [3] Configurando Select2 AJAX para el buscador manual...");

    $('#buscadorManualEtiquetas').select2({
        placeholder: 'Escanee o escriba el nombre...',
        minimumInputLength: 1,
        ajax: {
            url: 'index.php',
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: function (params) { 
                console.log("🚩 [AJAX ENVIANDO] Buscando término:", params.term);
                return { buscarProductoSelect: params.term }; 
            },
            processResults: function (data) {
                console.log("🚩 [AJAX RESPUESTA] Datos recibidos de PHP:", data);
                return {
                    results: $.map(data, function (item) {
                        return {
                            id: item.codigo_barras, 
                            text: item.codigo_barras + ' - ' + item.nombre
                        }
                    })
                };
            },
            cache: true,
            // NUEVO: Captura de errores internos de Select2
            error: function(jqXHR, status, error) {
                console.error("🚨 [ERROR SELECT2 AJAX] Falló la petición.");
                console.error("Status:", status);
                console.error("Error:", error);
                console.error("Respuesta Cruda PHP:", jqXHR.responseText);
            }
        }
    });

    $('#buscadorManualEtiquetas').on('select2:select', function (e) {
        let codigoSeleccionado = e.params.data.id;
        console.log("🚩 [SELECCIÓN] Código clickeado:", codigoSeleccionado);
        procesarCodigoBarras(codigoSeleccionado);
        $(this).val(null).trigger('change'); 
    });

    /* ==============================================================
       RESTO DEL CÓDIGO (Escáner, Procesar, Eliminar y PDF)
       Mantenemos exactamente el tuyo para no alterar nada más.
       ============================================================== */
    
    inputFisico.on("keypress", function(e) {
        if (e.which === 13) { 
            e.preventDefault();
            let codigoLeido = $(this).val().trim();
            if (codigoLeido !== "") { procesarCodigoBarras(codigoLeido); }
            $(this).val(""); 
        }
    });

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
            (errorMessage) => { }
        ).catch((err) => { Swal.fire("Error", "No se pudo acceder a la cámara.", "error"); });
    });

    $('#modalScannerCamara').on('hidden.bs.modal', function () {
        if (html5QrcodeScanner) { html5QrcodeScanner.stop().catch(err => console.log(err)); }
        inputFisico.focus();
    });

    function procesarCodigoBarras(codigo) {
        let filaExistente = $(`#fila_etiqueta_${codigo}`);
        if (filaExistente.length > 0) {
            let inputCantidad = filaExistente.find('.input-cantidad-etiqueta');
            let nuevaCantidad = parseInt(inputCantidad.val()) + 1;
            inputCantidad.val(nuevaCantidad);
            actualizarContadorTotal();
            Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, icon: 'success', title: '+1 agregado a la lista' });
            return;
        }

        Swal.fire({ title: 'Buscando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: "index.php", method: "POST", data: { codigoBarrasConsulta: codigo }, dataType: "json",
            success: function(res) {
                Swal.close();
                if (res.status === "success") {
                    let p = res.data;
                    let precioFinalUsdt = (p.tiene_oferta === 1) ? p.precio_oferta_usdt : p.precio_regular_usdt;
                    let badgeOferta = (p.tiene_oferta === 1) ? `<span class="badge bg-danger ms-2"><i class="fas fa-tag"></i> OFERTA</span>` : '';

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
                    $("#filaVaciaEtiquetas").remove();
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

    $("#tablaEtiquetas").on("change", ".input-cantidad-etiqueta", function() {
        if($(this).val() < 1) $(this).val(1);
        actualizarContadorTotal();
    });

    $("#tablaEtiquetas").on("click", ".btnQuitarEtiqueta", function() {
        $(this).closest("tr").remove();
        if ($("#listaEtiquetasTemporal tr").length === 0) { $("#listaEtiquetasTemporal").html(tablaVaciaHtml); }
        actualizarContadorTotal();
    });

    function actualizarContadorTotal() {
        let total = 0;
        $(".input-cantidad-etiqueta").each(function() { total += parseInt($(this).val()); });
        $("#contadorEtiquetasTotales").text(`${total} Etiquetas`);
    }

    $("#btnGenerarPDFEtiquetas").on("click", function() {
        let arrayEtiquetas = [];
        $(".input-cantidad-etiqueta").each(function() {
            arrayEtiquetas.push({ codigo: $(this).attr("data-codigo"), cantidad: parseInt($(this).val()) });
        });

        if (arrayEtiquetas.length === 0) {
            Swal.fire("Lista Vacía", "Debe escanear al menos un producto para generar las etiquetas.", "warning");
            return;
        }

        let formato = $("#formatoImpresion").val();
        let datosJsonString = JSON.stringify(arrayEtiquetas);
        let urlGenerador = `etiquetas-pdf.php?formato=${formato}&datos=${encodeURIComponent(datosJsonString)}`;
        window.open(urlGenerador, '_blank');
        
        Swal.fire({
            title: '¡PDF Generado!', text: "¿Desea limpiar la lista actual para escanear nuevos pasillos?", icon: 'success', showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#6c757d', confirmButtonText: 'Sí, limpiar lista', cancelButtonText: 'No, mantener lista'
        }).then((result) => {
            if (result.isConfirmed) {
                $("#listaEtiquetasTemporal").html(tablaVaciaHtml);
                actualizarContadorTotal();
            }
            inputFisico.focus();
        });
    });
});