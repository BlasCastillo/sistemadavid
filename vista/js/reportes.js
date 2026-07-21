$(document).ready(function() {

    if ($("#tablaReporteVentas").length === 0) return;

    let tablaVentasReporte;

    function cargarReporteVentas(fechaInicial, fechaFinal) {
        
        if ($.fn.DataTable.isDataTable('#tablaReporteVentas')) {
            $('#tablaReporteVentas').DataTable().destroy();
        }

        tablaVentasReporte = $('#tablaReporteVentas').DataTable({
            "ajax": {
                "url": "index.php",
                "type": "POST",
                "data": { 
                    fechaInicial: fechaInicial, 
                    fechaFinal: fechaFinal 
                },
                "dataSrc": function(json) {
                    if (json.status === "error") {
                        Swal.fire("Acceso Denegado", json.mensaje, "error");
                        return []; 
                    }
                    return json.data || [];
                },
                "error": function(xhr, error, thrown) {
                    console.error("Error en petición AJAX:", xhr.responseText);
                    Swal.fire("Error Crítico", "Fallo de comunicación con el servidor. Revise la consola.", "error");
                }
            },
            "order": [[0, "asc"]],
            // DISTRIBUCIÓN OPTIMIZADA Y DISEÑO PLANO
            "columns": [
                { 
                    "data": "fecha",
                    "width": "20%",
                    "className": "align-middle",
                    "render": function(data) {
                        let partes = data.split("-");
                        return '<div class="fw-bold text-dark"><i class="far fa-calendar-alt text-muted me-2"></i>' + partes[2] + "/" + partes[1] + "/" + partes[0] + '</div>';
                    }
                },
                { 
                    "data": "cantidad_facturas",
                    "width": "15%", // Espacio reducido
                    "className": "text-center align-middle",
                    "render": function(data) {
                        // Diseño sobrio, sin parecer un botón de acción
                        return '<span class="fw-bold text-secondary fs-6">' + data + '</span>';
                    }
                },
                { 
                    "data": "total_bs",
                    "width": "32%", // Más espacio para montos altos
                    "className": "text-end align-middle",
                    "render": function(data) {
                        let monto = parseFloat(data).toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        return '<div class="fw-bold text-success"><small class="text-muted me-1">Bs.</small>' + monto + '</div>';
                    }
                },
                { 
                    "data": "total_usdt",
                    "width": "33%", // Más espacio para montos altos
                    "className": "text-end align-middle",
                    "render": function(data) {
                        let monto = parseFloat(data).toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        return '<div class="fw-bold text-primary"><small class="text-muted me-1">$</small>' + monto + '</div>';
                    }
                }
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            }
        });
    }

    let inicioInicial = $("#filtroFechaInicio").val();
    let finInicial = $("#filtroFechaFin").val();
    cargarReporteVentas(inicioInicial, finInicial);

    $("#btnFiltrarReporte").on("click", function() {
        let fInicio = $("#filtroFechaInicio").val();
        let fFin = $("#filtroFechaFin").val();

        if (fInicio === "" || fFin === "") {
            Swal.fire("Atención", "Debe seleccionar ambas fechas para realizar el filtro.", "warning");
            return;
        }
        if (fInicio > fFin) {
            Swal.fire("Error Logístico", "La fecha inicial no puede ser mayor que la fecha final.", "error");
            return;
        }

        cargarReporteVentas(fInicio, fFin);
    });

    /* ==============================================================
       EXPORTACIÓN DINÁMICA (CORREGIDA A LA RAÍZ)
       ============================================================== */
    function abrirExportacion(rutaBase) {
        let fInicio = $("#filtroFechaInicio").val();
        let fFin = $("#filtroFechaFin").val();
        let dStock = $("#selectDiasStock").val();
        let dAnt = $("#selectDiasAntiguedad").val();

        let urlCompleta = `${rutaBase}?inicio=${fInicio}&fin=${fFin}&diasStock=${dStock}&diasAntiguedad=${dAnt}`;
        window.open(urlCompleta, '_blank');
    }

    $("#btnExportarPDF").on("click", function() {
        // CORREGIDO: Apunta al archivo en la raíz del proyecto
        abrirExportacion("reporte_analitica_pdf.php");
    });

    $("#btnExportarExcel").on("click", function() {
        // CORREGIDO: Apunta al archivo en la raíz del proyecto
        abrirExportacion("reporte_analitica_excel.php");
    });
});