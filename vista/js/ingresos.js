/* ====================================================================
   MÓDULO DE INGRESOS EXTRAORDINARIOS
==================================================================== */
$(document).ready(function() {
    
    // Inicializar la tabla
    if ($(".tablaIngresos").length > 0) {
        $(".tablaIngresos").DataTable({
            "language": {
                "sProcessing":     "Procesando...",
                "sLengthMenu":     "Mostrar _MENU_ registros",
                "sZeroRecords":    "No se encontraron resultados",
                "sEmptyTable":     "Ningún dato disponible en esta tabla",
                "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
                "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0",
                "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                "sSearch":         "Buscar:",
                "oPaginate": {
                    "sFirst":    "Primero",
                    "sLast":     "Último",
                    "sNext":     "Siguiente",
                    "sPrevious": "Anterior"
                }
            }
        });
    }

    // =======================================================
    // BOTÓN: ANULAR INGRESO
    // =======================================================
    $(document).on("click", ".btnAnularIngreso", function() {
        
        let idIngreso = $(this).attr("idIngreso");

        Swal.fire({
            title: '¿Está seguro de anular este ingreso?',
            text: "El registro pasará a estado inactivo y no se sumará en el Cierre Z de la tienda.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, anular ingreso',
            cancelButtonText: 'Cancelar'
        }).then(function(result){
            if(result.value){
                // Si confirma, recargamos la página pasando la orden por la URL
                window.location = "index.php?ruta=ingresos&idIngresoAnular=" + idIngreso;
            }
        });
    });
});