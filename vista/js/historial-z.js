/* ====================================================================
   MÓDULO: HISTORIAL DE CIERRES Z
==================================================================== */
$(document).ready(function() {
    
    // 1. Inicializar la tabla de datos
    if ($(".tablaHistorialZ").length > 0) {
        $(".tablaHistorialZ").DataTable({
            "language": {
                "sProcessing":     "Procesando...",
                "sLengthMenu":     "Mostrar _MENU_ registros",
                "sZeroRecords":    "No se encontraron resultados",
                "sEmptyTable":     "Ningún reporte Z disponible en esta tabla",
                "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
                "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0",
                "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                "sInfoPostFix":    "",
                "sSearch":         "Buscar:",
                "sUrl":            "",
                "sInfoThousands":  ",",
                "sLoadingRecords": "Cargando...",
                "oPaginate": {
                    "sFirst":    "Primero",
                    "sLast":     "Último",
                    "sNext":     "Siguiente",
                    "sPrevious": "Anterior"
                },
                "oAria": {
                    "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                }
            },
            "order": [[ 0, "desc" ]] // Ordenamos para que el último Z generado aparezca arriba
        });
    }

    // 2. Botón para imprimir el Ticket Z
    $(document).on("click", ".btnImprimirZ", function() {
        let idCierreZ = $(this).attr("idCierreZ");
        
        // Abrimos el ticket en una pestaña nueva pasando el ID por la URL
        window.open("ticket-cierre-z.php?idCierreZ=" + idCierreZ, "_blank");
    });

});