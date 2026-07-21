<?php
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}

$esAdmin = ($_SESSION["rol_id"] == 1);
$permisos = $_SESSION["permisos"] ?? [];
if (!$esAdmin && !in_array("all", $permisos) && !in_array("ver_reportes", $permisos)) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}

// Capturamos filtros dinámicos de días
$diasStock = isset($_GET["diasStock"]) ? intval($_GET["diasStock"]) : 30;
$diasAntiguedad = isset($_GET["diasAntiguedad"]) ? intval($_GET["diasAntiguedad"]) : 60;

$stockMuerto = ReportesControlador::ctrStockMuerto($diasStock);
$antiguedad = ReportesControlador::ctrAntiguedadInventario($diasAntiguedad);
?>

<div class="container-fluid py-4">
    
    <!-- ENCABEZADO Y EXPORTACIÓN DINÁMICA -->
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="fas fa-chart-pie me-2 text-primary"></i> Analítica de Ventas y Logística
            </h4>
            <small class="text-muted">Control gerencial de ingresos y salud del inventario en almacén.</small>
        </div>
        
        <div class="d-flex gap-2">
            <button type="button" id="btnExportarPDF" class="btn btn-outline-danger btn-sm fw-bold shadow-sm">
                <i class="fas fa-file-pdf me-1"></i> PDF Corporativo
            </button>
            <button type="button" id="btnExportarExcel" class="btn btn-outline-success btn-sm fw-bold shadow-sm">
                <i class="fas fa-file-excel me-1"></i> Exportar Excel
            </button>
        </div>
    </div>

    <!-- FILTRO POR RANGO DE FECHAS (VENTAS) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-white rounded-3 border border-light">
            <div class="row align-items-end g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Fecha Inicial</label>
                    <input type="date" class="form-control" id="filtroFechaInicio" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Fecha Final</label>
                    <input type="date" class="form-control" id="filtroFechaFin" value="<?php echo date('Y-m-t'); ?>">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100 fw-bold shadow-sm" id="btnFiltrarReporte">
                        <i class="fas fa-search me-1"></i> Filtrar Ingresos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE FLUJO DE VENTAS -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-cash-register me-2 text-primary"></i> Flujo de Ingresos en el Periodo</h6>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="tablaReporteVentas">
                    <thead class="table-light text-muted text-uppercase fs-7">
                        <tr>
                            <th>Fecha</th>
                            <th class="text-center">Facturas</th>
                            <th class="text-end">Total en Bs</th>
                            <th class="text-end">Total Referencia (USD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECCIÓN LOGÍSTICA: KPIS CON RANGOS DINÁMICOS -->
    <div class="row g-4 mb-4">
        
        <!-- Stock Muerto / No Venta -->
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-boxes text-secondary me-2"></i> Stock Estancado (Sin Venta)</h6>
                    <select class="form-select form-select-sm w-auto fw-bold text-dark bg-light" id="selectDiasStock" onchange="cambiarFiltroLogistico()">
                        <option value="15" <?php echo $diasStock == 15 ? 'selected' : ''; ?>>Más de 15 días</option>
                        <option value="30" <?php echo $diasStock == 30 ? 'selected' : ''; ?>>Más de 30 días</option>
                        <option value="45" <?php echo $diasStock == 45 ? 'selected' : ''; ?>>Más de 45 días</option>
                        <option value="60" <?php echo $diasStock == 60 ? 'selected' : ''; ?>>Más de 60 días</option>
                        <option value="90" <?php echo $diasStock == 90 ? 'selected' : ''; ?>>Más de 90 días</option>
                    </select>
                </div>
                <div class="card-body p-0 border-top border-light">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted fs-7 sticky-top">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-end">Capital Inmovilizado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($stockMuerto) > 0): ?>
                                    <?php foreach ($stockMuerto as $prod): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo $prod["nombre"]; ?></div>
                                                <small class="text-muted font-monospace"><?php echo $prod["codigo_barras"]; ?></small>
                                                <?php if(!empty($prod["ultima_venta_real"])): ?>
                                                    <div class="small text-secondary mt-1" style="font-size: 0.75rem;"><i class="fas fa-clock me-1"></i>Últ. Venta: <?php echo date('d/m/Y', strtotime($prod["ultima_venta_real"])); ?></div>
                                                <?php else: ?>
                                                    <div class="small text-danger mt-1" style="font-size: 0.75rem;"><i class="fas fa-times-circle me-1"></i>Nunca se ha vendido</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill">
                                                    <?php echo $prod["stock"]; ?> Und
                                                </span>
                                            </td>
                                            <td class="text-end align-middle">
                                                <div class="fw-bold text-danger">$<?php echo number_format($prod["capital_inmovilizado"], 2, ',', '.'); ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-muted py-5"><i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>Excelente flujo.<br>Sin inventario estancado.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Antigüedad de Inventario -->
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-warehouse text-secondary me-2"></i> Antigüedad en Almacén (Compra)</h6>
                    <select class="form-select form-select-sm w-auto fw-bold text-dark bg-light" id="selectDiasAntiguedad" onchange="cambiarFiltroLogistico()">
                        <option value="15" <?php echo $diasAntiguedad == 15 ? 'selected' : ''; ?>>Más de 15 días</option>
                        <option value="30" <?php echo $diasAntiguedad == 30 ? 'selected' : ''; ?>>Más de 30 días</option>
                        <option value="45" <?php echo $diasAntiguedad == 45 ? 'selected' : ''; ?>>Más de 45 días</option>
                        <option value="60" <?php echo $diasAntiguedad == 60 ? 'selected' : ''; ?>>Más de 60 días</option>
                        <option value="90" <?php echo $diasAntiguedad == 90 ? 'selected' : ''; ?>>Más de 90 días</option>
                    </select>
                </div>
                <div class="card-body p-0 border-top border-light">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted fs-7 sticky-top">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-end">Capital Inmovilizado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($antiguedad) > 0): ?>
                                    <?php foreach ($antiguedad as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo $item["nombre"]; ?></div>
                                                <small class="text-muted font-monospace"><?php echo $item["codigo_barras"]; ?></small>
                                                <div class="small text-secondary mt-1" style="font-size: 0.75rem;"><i class="fas fa-truck-loading me-1"></i>Comprado: <?php echo date('d/m/Y', strtotime($item["fecha_ultima_compra"])); ?></div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 rounded-pill">
                                                    <?php echo $item["stock"]; ?> Und
                                                </span>
                                            </td>
                                            <td class="text-end align-middle">
                                                <div class="fw-bold text-danger">$<?php echo number_format($item["capital_inmovilizado"], 2, ',', '.'); ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-muted py-5"><i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>Excelente.<br>Rotación de compras saludable.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- SECCIÓN DE EXPORTACIÓN MAESTRA DE CATÁLOGOS Y GESTIÓN DE CRÉDITO -->
    <div class="card border-0 shadow-sm mt-2 mb-4">
        <div class="card-header bg-white py-3 border-0 border-bottom border-light">
            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-database me-2 text-success"></i> Exportación Maestra y Gestión Financiera</h6>
            <small class="text-muted">Descarga de catálogos y control de créditos activos en formato Excel para auditorías.</small>
        </div>
        <div class="card-body bg-light rounded-bottom">
            <div class="d-flex flex-wrap gap-3 justify-content-center">
                <a href="exportar_catalogos.php?modulo=productos" class="btn btn-outline-success fw-bold shadow-sm px-4">
                    <i class="fas fa-file-excel me-2"></i> Inventario de Productos
                </a>
                <a href="exportar_catalogos.php?modulo=clientes" class="btn btn-outline-success fw-bold shadow-sm px-4">
                    <i class="fas fa-file-excel me-2"></i> Directorio de Clientes
                </a>
                <a href="exportar_catalogos.php?modulo=proveedores" class="btn btn-outline-success fw-bold shadow-sm px-4">
                    <i class="fas fa-file-excel me-2"></i> Directorio de Proveedores
                </a>
                <a href="exportar_catalogos.php?modulo=cxp" class="btn btn-outline-danger fw-bold shadow-sm px-4">
                    <i class="fas fa-file-excel me-2"></i> Cuentas por Pagar (CxP)
                </a>
                <!-- BOTÓN DE GESTIÓN DE CRÉDITO A CLIENTES INCORPORADO -->
                <a href="exportar_catalogos.php?modulo=cxc" class="btn btn-outline-primary fw-bold shadow-sm px-4">
                    <i class="fas fa-file-excel me-2"></i> Gestión de Crédito (CxC Clientes)
                </a>
            </div>
        </div>
    </div>

</div>

<script>
function cambiarFiltroLogistico() {
    let diasStock = $("#selectDiasStock").val();
    let diasAntiguedad = $("#selectDiasAntiguedad").val();
    window.location = "index.php?ruta=reporte-ventas&diasStock=" + diasStock + "&diasAntiguedad=" + diasAntiguedad;
}
</script>