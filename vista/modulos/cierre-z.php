<?php
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}
?>
<div class="content-wrapper">
    <section class="content-header mb-3">
        <div class="container-fluid px-0">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold"><i class="fas fa-store-slash text-danger me-2"></i> Cierre de Tienda (Reporte Z)</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end bg-transparent p-0 m-0">
                        <li class="breadcrumb-item"><a href="index.php?ruta=dashboard">Inicio</a></li>
                        <li class="breadcrumb-item active">Reporte Z</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="card-reditus p-4 shadow-sm border-top border-danger border-3">
            <div class="card-body">
                
                <!-- ZONA 1: EL RADAR DE CAJEROS -->
                <div class="mb-4">
                    <h5 class="fw-bold text-secondary mb-3"><i class="fas fa-satellite-dish"></i> Radar de Sesiones Activas</h5>
                    <div id="radarCierresResultados" class="text-center p-4 bg-light rounded border">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2 mb-0 text-muted">Escaneando facturas del día...</p>
                    </div>
                </div>

                <!-- ZONA 2: BOTÓN MAESTRO DE CIERRE -->
                <div class="text-center border-top pt-4">
                    <p class="text-muted small mb-3">El Cierre Z consolidará todos los Reportes X, gastos e ingresos de la jornada, y dejará la tienda en cero para el día de mañana.</p>
                    <button class="btn btn-danger btn-lg fw-bold shadow px-5" id="btnEjecutarZ" disabled>
                        <i class="fas fa-lock me-2"></i> Ejecutar Cierre Z de Tienda
                    </button>
                </div>

            </div>
        </div>
    </section>
</div>
