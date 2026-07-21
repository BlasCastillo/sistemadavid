$(document).ready(function() {
    
    // Si no estamos en la vista de conciliaciones, abortamos
    if ($("#tablaConciliaciones").length === 0) return;

    let tablaConciliaciones;

    // Función para cargar la tabla según el estado
    function cargarTablaConciliaciones(estadoFiltro) {
        
        // Destruimos la instancia anterior si existe para evitar duplicados
        if ($.fn.DataTable.isDataTable('#tablaConciliaciones')) {
            $('#tablaConciliaciones').DataTable().destroy();
        }

        tablaConciliaciones = $('#tablaConciliaciones').DataTable({
            "ajax": {
                "url": "index.php",
                "type": "POST",
                "data": { estadoConciliacion: estadoFiltro },
                "dataSrc": "data"
            },
            "order": [[3, "desc"]], // Ordenar por fecha descendente por defecto
            "columns": [
                { 
                    "data": "numero_factura",
                    "className": "text-center fw-bold",
                    "render": function(data, type, row) {
                        return '<span class="badge bg-secondary fs-6">F-' + data + '</span>';
                    }
                },
                { 
                    "data": "metodo_pago",
                    "render": function(data, type, row) {
                        let color = "bg-primary";
                        if(data.includes("Zelle")) color = "bg-info text-dark";
                        if(data.includes("Pago Móvil")) color = "bg-warning text-dark";
                        if(data.includes("Punto")) color = "bg-danger";
                        return '<span class="badge ' + color + '"><i class="fas fa-credit-card me-1"></i> ' + data + '</span>';
                    }
                },
                { "data": "referencia", "className": "fw-bold font-monospace text-muted" },
                { "data": "fecha_pago" },
                { 
                    "data": "monto_pagado",
                    "className": "text-end fw-bold text-success fs-6",
                    "render": function(data, type, row) {
                        let simbolo = (row.moneda === "USD") ? "$" : "Bs. ";
                        return simbolo + parseFloat(data).toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                },
                { 
                    "data": "pago_id",
                    "className": "text-center",
                    "render": function(data, type, row) {
                        if (estadoFiltro === "Pendiente") {
                            return `<button class="btn btn-success btn-sm fw-bold shadow-sm btnConciliar" idPago="${data}">
                                        <i class="fas fa-check-circle me-1"></i> Conciliar
                                    </button>`;
                        } else {
                            return `<span class="badge bg-success bg-opacity-10 text-success border border-success">
                                        <i class="fas fa-check-double me-1"></i> Verificado por ${row.auditor}
                                    </span>`;
                        }
                    }
                }
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            }
        });
    }

    // Inicializamos con el valor por defecto (Pendiente)
    cargarTablaConciliaciones("Pendiente");

    // Escuchamos los cambios en los radio buttons (Pestañas)
    $(".filtro-conciliacion").on("change", function() {
        let estadoSeleccionado = $(this).val();
        cargarTablaConciliaciones(estadoSeleccionado);
    });

    /* ==============================================================
       APROBAR CONCILIACIÓN
       ============================================================== */
    $('#tablaConciliaciones tbody').on("click", ".btnConciliar", function() {
        let idPago = $(this).attr("idPago");

        Swal.fire({
            title: '¿Confirmar Ingreso?',
            text: "Al confirmar, declararás que este dinero ya está reflejado en la cuenta bancaria de la empresa.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754', // Success green
            cancelButtonColor: '#6c757d', // Secondary gray
            confirmButtonText: 'Sí, dinero en cuenta'
        }).then((result) => {
            if (result.isConfirmed) {
                
                Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

                $.ajax({
                    url: "index.php",
                    method: "POST",
                    data: { idPagoConciliar: idPago },
                    dataType: "json",
                    success: function(respuesta) {
                        if (respuesta.status === "success") {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Conciliado!',
                                text: respuesta.mensaje,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(function() {
                                // Recargamos la página completa para actualizar las tarjetas numéricas de arriba
                                window.location.reload(); 
                            });
                        } else {
                            Swal.fire('Error', respuesta.mensaje, 'error');
                        }
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error Critico', 'Revise la consola. Fallo de conexión.', 'error');
                    }
                });
            }
        });
    });
});