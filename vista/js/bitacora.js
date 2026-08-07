$(document).ready(function () {

    // 🛡️ ESCUDO DE URL: Si no estamos en la bitácora, el script se apaga.
    const parametrosUrl = new URLSearchParams(window.location.search);
    const rutaActual = parametrosUrl.get('ruta');

    if (rutaActual !== 'bitacora') {
        return;
    }

    // Inicializamos Select2 si lo estás usando (opcional, para que los selectores se vean geniales)
    if ($('.select2').length > 0) {
        $('.select2').select2({ theme: 'bootstrap-5' });
    }

    // Variables globales para la paginación nativa
    let bitacoraData = [];
    let currentPage = 1;
    const rowsPerPage = 15;

    // 1. Carga inicial automática (Sin filtros)
    if ($("#cuerpoBitacora").length > 0) {
        cargarBitacora(null, null, null, null);
    }

    // 2. Evento del botón Filtrar
    $("#btnFiltrarBitacora").on("click", function () {
        let inicio = $("#filtroFechaInicio").val();
        let fin = $("#filtroFechaFin").val();
        let usuario = $("#filtroUsuario").val();
        let modulo = $("#filtroModulo").val();

        if (inicio && fin && inicio > fin) {
            Swal.fire("Atención", "La fecha de inicio no puede ser mayor a la fecha final.", "warning");
            return;
        }

        cargarBitacora(inicio, fin, usuario, modulo);
    });

    // 3. Evento del botón Limpiar
    $("#btnLimpiarFiltros").on("click", function () {
        $("#filtroFechaInicio").val("");
        $("#filtroFechaFin").val("");
        $("#filtroUsuario").val("").trigger('change'); // trigger change resetea el visual de Select2
        $("#filtroModulo").val("").trigger('change');

        cargarBitacora(null, null, null, null);
    });

    // ==========================================
    // FUNCIÓN PRINCIPAL: TRAER DATOS POR AJAX
    // ==========================================
    function cargarBitacora(fechaInicio, fechaFin, usuarioFiltro, moduloFiltro) {

        $("#cuerpoBitacora").html('<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> Analizando registros...</td></tr>');

        $.ajax({
            url: "index.php",
            method: "POST",
            data: {
                cargarBitacoraAjax: true,
                fechaInicio: fechaInicio,
                fechaFin: fechaFin,
                usuarioFiltro: usuarioFiltro,
                moduloFiltro: moduloFiltro
            },
            dataType: "json",
            success: function (respuesta) {
                bitacoraData = respuesta;
                currentPage = 1; // Reseteamos a la página 1

                if (bitacoraData.length === 0) {
                    $("#cuerpoBitacora").html('<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-search me-2"></i> No se encontraron movimientos con los filtros seleccionados.</td></tr>');
                    $("#infoPaginacion").text("Mostrando 0 registros");
                    $("#paginacionBitacora").empty();
                } else {
                    renderizarTabla();
                    renderizarPaginacion();
                }
            },
            error: function () {
                $("#cuerpoBitacora").html('<tr><td colspan="6" class="text-center py-4 text-danger">Error de comunicación con el servidor de auditoría.</td></tr>');
            }
        });
    }

    // ==========================================
    // RENDERIZAR TABLA Y PAGINACIÓN
    // ==========================================
    function renderizarTabla() {
        let html = "";
        let inicio = (currentPage - 1) * rowsPerPage;
        let fin = inicio + rowsPerPage;
        let datosPagina = bitacoraData.slice(inicio, fin);

        datosPagina.forEach(function (item) {

            // Colores semánticos
            let colorModulo = "text-dark";
            if (item.modulo.includes("Seguridad")) colorModulo = "text-danger fw-bold";
            if (item.modulo.includes("Ventas")) colorModulo = "text-success fw-bold";
            if (item.modulo.includes("Compras")) colorModulo = "text-primary fw-bold";
            if (item.modulo.includes("Tesorería")) colorModulo = "text-warning fw-bold";

            html += `<tr>
                        <td class="small">${item.fecha}</td>
                        <td><span class="badge bg-secondary">@${item.username}</span></td>
                        <td class="${colorModulo}">${item.modulo}</td>
                        <td class="small fw-bold">${item.accion}</td>
                        <td class="small">${item.detalles}</td>
                        <td class="small text-muted"><i class="fas fa-network-wired me-1"></i> ${item.ip_usuario}</td>
                     </tr>`;
        });

        $("#cuerpoBitacora").html(html);

        let total = bitacoraData.length;
        let finalMostrado = (fin > total) ? total : fin;
        $("#infoPaginacion").text(`Mostrando ${inicio + 1} a ${finalMostrado} de ${total} registros`);
    }

    function renderizarPaginacion() {
        let totalPages = Math.ceil(bitacoraData.length / rowsPerPage);
        let pagHtml = "";

        let disabledPrev = (currentPage === 1) ? "disabled" : "";
        pagHtml += `<li class="page-item ${disabledPrev}"><a class="page-link cursor-pointer text-dark" data-page="${currentPage - 1}">Anterior</a></li>`;

        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            let active = (i === currentPage) ? "active bg-primary text-white" : "text-dark";
            pagHtml += `<li class="page-item"><a class="page-link cursor-pointer ${active}" data-page="${i}">${i}</a></li>`;
        }

        let disabledNext = (currentPage === totalPages) ? "disabled" : "";
        pagHtml += `<li class="page-item ${disabledNext}"><a class="page-link cursor-pointer text-dark" data-page="${currentPage + 1}">Siguiente</a></li>`;

        $("#paginacionBitacora").html(pagHtml);
    }

    // Delegación de eventos para la paginación dinámica
    $(document).on("click", "#paginacionBitacora .page-link", function () {
        let parent = $(this).parent();
        if (parent.hasClass("disabled") || $(this).hasClass("active")) {
            return;
        }

        currentPage = parseInt($(this).attr("data-page"));
        renderizarTabla();
        renderizarPaginacion();
    });

});