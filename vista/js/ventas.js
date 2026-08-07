/* ====================================================================
   MÓDULO DE VENTAS (CAJA POS) - DEBUGGING Y CARRITO
==================================================================== */

$(document).ready(function () {

    if ($("#listaVentasTemporal").length === 0) return;

    console.log("🚩 [0] Archivo ventas.js cargado correctamente.");



    // Configuración HÍBRIDA para Clientes (Permite seleccionar o escribir uno nuevo)
    $('#identificadorClientePOS').select2({
        tags: true, // ¡ESTO ES CLAVE! Permite texto libre si no encuentra coincidencia
        placeholder: 'Buscar o ingresar DNI/C.I...',
        allowClear: true
    });

    // Inicializar Select2 para búsqueda de productos manual
    if ($('.select2-dinamico').length > 0) {
        $('.select2-dinamico').select2();
    }

    let inputFisico = $("#inputLectorVentas");
    let html5QrcodeScanner = null; // Para la cámara móvil

    // Mantener el foco en el escáner láser por defecto (con excepciones)
    $(document).on("click", function (e) {
        if (!$(e.target).closest('.modal').length &&
            !$(e.target).closest('.select2-container').length &&
            !$(e.target).closest('.swal2-container').length && // NUEVO: Excepción para SweetAlert (PIN de descuento)
            e.target.id !== 'identificadorClientePOS' &&
            e.target.id !== 'buscadorSuspendidas' &&          // Excepción para el buscador
            !$(e.target).hasClass('input-cantidad-venta')) {  // Excepción para los inputs de cantidad
            inputFisico.focus();
        }
    });

    cargarCarritoVentas();
    cargarFacturasSuspendidas();

    /* ==============================================================
       1. BÚSQUEDA Y CAPTURA DE PRODUCTOS
       ============================================================== */

    // 1.A - Búsqueda Manual
    $('#buscadorManualVentas').select2({
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
                        return { id: item.codigo_barras, text: item.codigo_barras + ' - ' + item.nombre }
                    })
                };
            }
        }
    });

    $('#buscadorManualVentas').on('select2:select', function (e) {
        let codigo = e.params.data.id;
        console.log("🚩 [MANUAL] Código seleccionado:", codigo);
        agregarProductoCarrito(codigo, 1);
        $(this).val(null).trigger('change');
    });

    // 1.B - Escáner Físico
    inputFisico.on("keypress", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            let codigo = $(this).val().trim();
            console.log("🚩 [PISTOLA] Enter detectado. Código:", codigo);
            if (codigo !== "") { agregarProductoCarrito(codigo, 1); }
            $(this).val("");
        }
    });

    // 1.C - Cámara Móvil (Omnicanalidad)
    $('#modalScannerVentas').on('shown.bs.modal', function () {
        html5QrcodeScanner = new Html5Qrcode("lectorCamaraVentas");
        html5QrcodeScanner.start(
            { facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 150 } },
            (decodedText) => {
                html5QrcodeScanner.stop().then(() => {
                    $('#modalScannerVentas').modal('hide');
                    console.log("🚩 [CÁMARA] Código detectado:", decodedText);
                    agregarProductoCarrito(decodedText, 1);
                });
            },
            (errorMessage) => { /* Ignorar errores de enfoque */ }
        ).catch((err) => { Swal.fire("Error", "Cámara no disponible.", "error"); });
    });

    $('#modalScannerVentas').on('hidden.bs.modal', function () {
        if (html5QrcodeScanner) { html5QrcodeScanner.stop().catch(err => console.log(err)); }
        inputFisico.focus();
    });

    /* ==============================================================
       2. GESTIÓN DEL CARRITO (CON BANDERAS DE DEBUG)
       ============================================================== */

    function agregarProductoCarrito(codigo, cantidad) {
        console.log(`🚩 [AJAX 1] Enviando a PHP -> Codigo: ${codigo}, Cantidad: ${cantidad}`);

        $.ajax({
            url: "index.php",
            method: "POST",
            data: { codigoProductoVenta: codigo, cantidadVenta: cantidad },
            dataType: "json",
            success: function (res) {
                console.log("🚩 [RESPUESTA PHP 1] Agregar Producto:", res);
                if (res.status === "success") {
                    cargarCarritoVentas();
                } else {
                    Swal.fire({ icon: 'warning', title: 'Atención', text: res.mensaje, timer: 3000 });
                }
            },
            error: function (xhr, status, error) {
                console.error("🚨 [ERROR FATAL AJAX 1] agregarProductoCarrito");
                console.error("Status:", status);
                console.error("Respuesta Cruda PHP:", xhr.responseText);
                Swal.fire("Error 500", "Mira la consola F12 (Pestaña Console). PHP arrojó un error crítico.", "error");
            }
        });
    }

    function cargarCarritoVentas() {
        console.log("🚩 [AJAX 2] Solicitando carrito activo...");

        $.ajax({
            url: "index.php",
            method: "POST",
            data: { cargarTemporalesVenta: "ok" },
            dataType: "json",
            success: function (respuesta) {
                console.log("🚩 [RESPUESTA PHP 2] Carrito Actual:", respuesta);

                let filas = "";
                let totalUsdt = 0;

                if (respuesta.length === 0) {
                    filas = `<tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-shopping-basket fa-2x mb-2 d-block"></i> El carrito está vacío</td></tr>`;
                } else {
                    respuesta.forEach(function (item) {

                        // MODIFICADO PARA DESCUENTOS PARCIALES
                        let subtotalBruto = parseFloat(item.cantidad) * parseFloat(item.precio_venta_usdt);
                        let descVisual = parseFloat(item.descuento_aplicado || 0);
                        let subtotalNeto = subtotalBruto - descVisual;
                        totalUsdt += subtotalNeto;

                        let badgeDescuento = descVisual > 0 ? `<span class="badge bg-warning text-dark mt-1 d-block" style="font-size: 0.65rem;">- $${descVisual.toFixed(2)}</span>` : "";

                        filas += `
                            <tr>
                                <td><span class="badge bg-secondary font-monospace">${item.codigo_barras}</span></td>
                                <td class="fw-bold">${item.producto_nombre} ${badgeDescuento}</td>
                                <td class="text-center">
                                    <input type="number" class="form-control text-center input-cantidad-venta fw-bold text-primary shadow-sm" idItem="${item.id}" value="${item.cantidad}" min="1" style="width: 80px; margin: 0 auto;">
                                </td>
                                <td class="text-end text-success fw-bold">$${subtotalNeto.toFixed(2)}</td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-info btnDescuentoItem" idItem="${item.id}" precioBase="${item.precio_venta_usdt}" cant="${item.cantidad}" title="Aplicar Descuento a este producto"><i class="fas fa-tags"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btnQuitarItemVenta" idItem="${item.id}"><i class="fas fa-times"></i></button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }

                $("#listaVentasTemporal").html(filas);
                $("#totalVentaUsdtVisual").text("$" + totalUsdt.toFixed(2));

                if (totalUsdt > 0) {
                    $("#btnProcederPago").prop("disabled", false);
                    $("#btnSuspenderFactura").prop("disabled", false);
                } else {
                    $("#btnProcederPago").prop("disabled", true);
                    $("#btnSuspenderFactura").prop("disabled", true);
                }
            },
            error: function (xhr, status, error) {
                console.error("🚨 [ERROR FATAL AJAX 2] cargarCarritoVentas");
                console.error("Respuesta Cruda PHP:", xhr.responseText);
            }
        });
    }

    // =========================================================================
    // EVENTOS DEL CARRITO (ESTRICTAMENTE FUERA DE LA FUNCIÓN cargarCarritoVentas)
    // =========================================================================

    // 2.A Quitar producto del carrito (Botón Rojo)
    $("#tablaVentasCrear").on("click", ".btnQuitarItemVenta", function () {
        let idItem = $(this).attr("idItem");
        console.log("🚩 [ACCIÓN] Eliminando item ID:", idItem);
        $.ajax({
            url: "index.php", method: "POST", data: { idTemporalVentaEliminar: idItem }, dataType: "json",
            success: function (res) {
                if (res.status === "success") { cargarCarritoVentas(); }
            }
        });
    });

    // 2.B Actualizar cantidad desde el input numérico
    $("#tablaVentasCrear").on("change", ".input-cantidad-venta", function () {
        let idItem = $(this).attr("idItem");
        let nuevaCant = $(this).val();

        console.log(`🚩 [ACCIÓN] Modificando cantidad. Item ID: ${idItem}, Nueva Cant: ${nuevaCant}`);

        if (nuevaCant < 1) {
            nuevaCant = 1;
            $(this).val(1);
        }

        $.ajax({
            url: "index.php", method: "POST", data: { idItemActualizar: idItem, nuevaCantidad: nuevaCant }, dataType: "json",
            success: function (res) {
                if (res.status === "success") {
                    cargarCarritoVentas(); // Recarga para actualizar los totales
                } else {
                    Swal.fire("Atención", res.mensaje, "warning");
                    cargarCarritoVentas(); // Devuelve el input a su valor original por falta de stock
                }
            },
            error: function (xhr) {
                console.error("🚨 [ERROR FATAL] Actualizando Cantidad:", xhr.responseText);
            }
        });
    });

    // =========================================================================
    // 2.C LÓGICA DE DESCUENTO PARCIAL POR PRODUCTO (NUEVO)
    // =========================================================================
    $("#tablaVentasCrear").on("click", ".btnDescuentoItem", async function () {
        let idItem = $(this).attr("idItem");
        let cant = parseFloat($(this).attr("cant"));
        let precioBase = parseFloat($(this).attr("precioBase"));
        let maxDescuento = precioBase * cant;

        console.log(`🚩 [ACCIÓN] Solicitando descuento para Item ID: ${idItem}`);

        // 1. Pedir Credenciales del Supervisor
        const { value: formValues } = await Swal.fire({
            title: 'Autorización Gerencial',
            html:
                '<input id="swal-usr" class="swal2-input" placeholder="Usuario Supervisor">' +
                '<input id="swal-pin" type="password" class="swal2-input" placeholder="PIN de Seguridad">',
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Validar',
            confirmButtonColor: '#198754',
            preConfirm: () => {
                return { usuario: document.getElementById('swal-usr').value, pin: document.getElementById('swal-pin').value }
            }
        });

        if (formValues) {
            $.ajax({
                url: "index.php", method: "POST", dataType: "json",
                data: { supUsuario: formValues.usuario, supPin: formValues.pin },
                success: async function (resAuth) {
                    if (resAuth.status === "success") {

                        // 2. Si el PIN es correcto, pedir el Monto del Descuento
                        const { value: montoDescuento } = await Swal.fire({
                            title: `Descuento Aprobado por ${resAuth.nombre_supervisor}`,
                            text: `Ingrese el descuento en Dólares ($). (Max: $${maxDescuento.toFixed(2)})`,
                            input: 'number',
                            inputAttributes: { min: 0, step: 0.01, max: maxDescuento },
                            showCancelButton: true,
                            confirmButtonText: 'Aplicar Rebaja'
                        });

                        if (montoDescuento) {
                            if (parseFloat(montoDescuento) > maxDescuento) {
                                Swal.fire("Error", "El descuento no puede ser mayor al valor total del producto.", "error"); return;
                            }
                            // 3. Aplicar en la Base de Datos Temporal
                            $.ajax({
                                url: "index.php", method: "POST", dataType: "json",
                                data: { idItemDescuento: idItem, montoDescuento: montoDescuento },
                                success: function (resDesc) {
                                    if (resDesc.status === "success") {
                                        Swal.fire({ icon: 'success', title: 'Descuento Aplicado', timer: 1500, showConfirmButton: false });
                                        cargarCarritoVentas();
                                    }
                                }
                            });
                        }
                    } else {
                        Swal.fire("Acceso Denegado", resAuth.mensaje, "error");
                    }
                }
            });
        }
    });

    /* ==============================================================
       3. SUSPENSIÓN Y RECUPERACIÓN DE FACTURAS
       ============================================================== */

    // Suspender
    $("#btnSuspenderFactura").on("click", function () {
        let cedula = $("#identificadorClientePOS").val();

        // Verificamos si es null (cuando escriben pero no le dan a la opción de crear la etiqueta) o vacío
        if (!cedula || cedula.trim() === "") {
            Swal.fire("Cédula Requerida", "Debe ingresar o seleccionar el DNI/Cédula del cliente para poder suspender su factura.", "warning");
            $("#identificadorClientePOS").select2('open'); // Abrimos el select para que lo vea
            return;
        }

        console.log("🚩 [AJAX 4] Suspendiendo factura para cédula:", cedula);

        $.ajax({
            url: "index.php", method: "POST", data: { cedulaSuspender: cedula }, dataType: "json",
            success: function (res) {
                if (res.status === "success") {
                    Swal.fire({ icon: 'info', title: 'Factura Suspendida', text: res.mensaje, timer: 2000, showConfirmButton: false });
                    $("#identificadorClientePOS").val(null).trigger('change');
                    cargarCarritoVentas();
                    cargarFacturasSuspendidas();
                } else {
                    Swal.fire("Error", res.mensaje, "error");
                }
            }
        });
    });

    // Listar Suspendidas en el Panel
    function cargarFacturasSuspendidas() {
        console.log("🚩 [AJAX 3] Buscando facturas suspendidas...");
        $.ajax({
            url: "index.php", method: "POST", data: { listarSuspendidas: "ok" }, dataType: "json",
            success: function (respuesta) {
                console.log("🚩 [RESPUESTA PHP 3] Suspendidas:", respuesta);
                let html = "";
                if (respuesta.length === 0) {
                    html = `<div class="alert alert-light text-center border text-muted small py-3"><i class="fas fa-check-circle d-block mb-1 fs-5"></i> Sin facturas en espera</div>`;
                } else {
                    respuesta.forEach(function (fac) {
                        html += `
                            <div class="card border-warning mb-2 shadow-sm item-suspendida" data-cedula="${fac.identificador_cliente.toLowerCase()}">
                                <div class="card-body p-2 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="m-0 fw-bold text-dark"><i class="fas fa-user-clock me-1 text-warning"></i> ${fac.identificador_cliente}</h6>
                                        <small class="text-muted">${fac.items} Art. | <span class="text-success fw-bold">$${parseFloat(fac.total).toFixed(2)}</span></small>
                                    </div>
                                    <button class="btn btn-sm btn-warning fw-bold btnRecuperarFactura" cedulaFactura="${fac.identificador_cliente}" title="Recuperar a la caja"><i class="fas fa-upload"></i></button>
                                </div>
                            </div>
                        `;
                    });
                }
                $("#panelFacturasSuspendidas").html(html);
            },
            error: function (xhr) {
                console.error("🚨 [ERROR FATAL AJAX 3] Suspendidas:", xhr.responseText);
            }
        });
    }

    // Recuperar
    $("#panelFacturasSuspendidas").on("click", ".btnRecuperarFactura", function () {
        let cedula = $(this).attr("cedulaFactura");
        console.log("🚩 [ACCIÓN] Recuperando factura de:", cedula);

        Swal.fire({
            title: '¿Recuperar Factura?',
            text: "El carrito actual se limpiará para cargar los artículos de " + cedula,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, recuperar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "index.php", method: "POST", data: { cedulaRecuperar: cedula }, dataType: "json",
                    success: function (res) {
                        if (res.status === "success") {
                            // Aquí inyectamos el nuevo valor en el Select2 y forzamos el cambio
                            let newOption = new Option(cedula, cedula, true, true);
                            $('#identificadorClientePOS').append(newOption).trigger('change');

                            cargarCarritoVentas();
                            cargarFacturasSuspendidas();
                            Swal.fire({ icon: 'success', title: 'Recuperada', timer: 1500, showConfirmButton: false });
                        }
                    }
                });
            }
        });
    });

    /* ==============================================================
       4. PROCEDER AL PAGO (Redirección a vista 2)
       ============================================================== */
    $("#btnProcederPago").on("click", function () {
        let cedula = $("#identificadorClientePOS").val();

        if (!cedula || cedula.trim() === "") {
            Swal.fire("Cédula Requerida", "Debe ingresar o seleccionar el DNI/Cédula del cliente para poder facturar.", "warning");
            $("#identificadorClientePOS").select2('open');
            return;
        }

        console.log("🚩 [ACCIÓN] Pasando a pagos con cédula:", cedula);
        window.location = "index.php?ruta=ventas-pago&cedula=" + encodeURIComponent(cedula);
    });

    /* ==============================================================
       5. BUSCADOR EN TIEMPO REAL DE FACTURAS SUSPENDIDAS
       ============================================================== */
    $("#buscadorSuspendidas").on("keyup", function () {
        let valorFiltro = $(this).val().toLowerCase();

        $("#panelFacturasSuspendidas .item-suspendida").filter(function () {
            // Oculta las tarjetas que no coincidan con lo que se escribe
            $(this).toggle($(this).attr("data-cedula").indexOf(valorFiltro) > -1);
        });
    });

});