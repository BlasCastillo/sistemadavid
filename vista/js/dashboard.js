$(document).ready(function() {

    /* ==========================================
    1. EL GATILLO SILENCIOSO
    ========================================== */
    setTimeout(function() {
        $.ajax({
            url: "index.php",
            method: "POST",
            data: { tipoSincronizacion: "silencioso" },
            dataType: "json",
            success: function(respuesta) {
                if (respuesta.status === "success") {
                    console.log("EasyPOS - Gatillo: Tasas actualizadas en segundo plano.");
                    window.location.reload(); 
                } else {
                    console.log("EasyPOS - Gatillo: " + respuesta.mensaje);
                }
            }
        });
    }, 1000);

    /* ==========================================
    2. EL BOTÓN DE REVISIÓN FORZADA
    ========================================== */
    $("#btnForzarSincronizacion").on("click", function() {
        let btn = $(this);
        let icono = btn.find("i");
        let textoOriginal = btn.html();
        
        icono.addClass("fa-spin");
        btn.html('<i class="fas fa-sync-alt fa-spin me-1"></i> Revisando...');
        btn.prop("disabled", true);

        $.ajax({
            url: "index.php",
            method: "POST",
            data: { tipoSincronizacion: "forzado" },
            dataType: "json",
            success: function(respuesta) {
                btn.html(textoOriginal);
                btn.prop("disabled", false);

                if (respuesta.status === "success") {
                    Swal.fire({ icon: 'success', title: '¡Tasas Actualizadas!', text: respuesta.mensaje, showConfirmButton: false, timer: 1500 })
                    .then(function() { window.location.reload(); });
                } else if (respuesta.status === "info") {
                    Swal.fire({ icon: 'info', title: 'Todo al día', text: respuesta.mensaje, confirmButtonColor: '#3085d6' });
                }
            },
            error: function() {
                btn.html(textoOriginal);
                btn.prop("disabled", false);
                Swal.fire('Error', 'No se pudo contactar con los servidores de cambio.', 'error');
            }
        });
    });

    /* ==========================================
    3. RENDERIZAR GRÁFICO DE VENTAS (CHART.JS)
    ========================================== */
    if ($("#graficoVentas7Dias").length > 0) {
        $.ajax({
            url: "index.php",
            method: "POST",
            data: { cargarGraficoVentas: "ok" },
            dataType: "json",
            success: function(respuesta) {
                let fechas = [];
                let totales = [];

                // Formateamos la data para el gráfico
                respuesta.forEach(function(item) {
                    let partes = item.fecha.split("-");
                    fechas.push(partes[2] + "/" + partes[1]); // Formato DD/MM
                    totales.push(parseFloat(item.total_bs));
                });

                let ctx = document.getElementById('graficoVentas7Dias').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: fechas,
                        datasets: [{
                            label: 'Ingresos (Bs)',
                            data: totales,
                            backgroundColor: 'rgba(13, 110, 253, 0.7)', // Azul Bootstrap
                            borderColor: 'rgba(13, 110, 253, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: function(value) { return 'Bs ' + value; } }
                            }
                        }
                    }
                });
            }
        });
    }
});