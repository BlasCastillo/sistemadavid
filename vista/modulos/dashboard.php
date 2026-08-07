<?php
$modoDios = ($_SESSION["rol_id"] == 1);
$permisos = $_SESSION["permisos"] ?? [];

// Si no es admin y no tiene permiso, lo rebotamos al POS
if (!$modoDios && !in_array("ver_dashboard", $permisos)) {
    echo '<script>window.location = "index.php?ruta=ventas-crear";</script>';
    exit;
}

// 1. Solicitamos la tasa activa
$tasaActual = Tasas::obtenerTasaActiva();

// 2. Solicitamos las nuevas métricas al Controlador
require_once "controlador/DashboardControlador.php";
$diario = DashboardControlador::ctrResumenDiario();
$global = DashboardControlador::ctrResumenGlobal();
$analitica = DashboardControlador::ctrTopProductos();
?>

<div class="container-fluid px-0">
    
    <!-- ENCABEZADO Y SINCRONIZACIÓN -->
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="module-title">
            <h4 class="fw-bold mb-0 text-dark">
                <i class="fas fa-chart-line me-2" style="color: var(--accent-color);"></i> Dashboard General
            </h4>
            <small class="text-muted">Resumen operativo, financiero y tasas al día.</small>
        </div>
        
        <div class="d-flex align-items-center bg-white p-2 px-3 rounded shadow-sm border border-light">
            <span class="text-muted small me-3">
                <i class="fas fa-clock text-info me-1"></i> Actualizado: 
                <strong class="text-dark">
                    <?php echo $tasaActual ? date('d/m/Y - h:i A', strtotime($tasaActual->creado_en)) : "Sin datos"; ?>
                </strong>
            </span>
            <button class="btn btn-sm btn-dodger" id="btnForzarSincronizacion">
                <i class="fas fa-sync-alt"></i> Sincronizar
            </button>
        </div>
    </div>
    
    <!-- BLOQUE A: TASAS DE CAMBIO (Estandarizado a Soft UI) -->
    <div class="row g-4 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card card-reditus p-3 h-100 border-bottom border-primary border-3">
                <div class="d-flex align-items-center">
                    <div class="icon-shape bg-soft-primary">
                        <i class="fas fa-university"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Tasa Oficial (BCV)</div>
                        <h4 class="mb-0 fw-bold text-dark">Bs. <?php echo $tasaActual ? number_format($tasaActual->tasa_bcv, 4, ',', '.') : '0,0000'; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-reditus p-3 h-100 border-bottom border-warning border-3">
                <div class="d-flex align-items-center">
                    <div class="icon-shape bg-soft-warning">
                        <i class="fab fa-bitcoin"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Tasa USDT (Binance)</div>
                        <h4 class="mb-0 fw-bold text-dark">Bs. <?php echo $tasaActual ? number_format($tasaActual->tasa_binance, 4, ',', '.') : '0,0000'; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-reditus p-3 h-100 border-bottom border-danger border-3">
                <div class="d-flex align-items-center">
                    <div class="icon-shape" style="background-color: #FEE2E2; color: #EF4444;">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Brecha Cambiaria</div>
                        <h4 class="mb-0 fw-bold text-dark"><?php echo $tasaActual ? $tasaActual->brecha_porcentaje : '0'; ?> %</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BLOQUE B: RESUMEN FINANCIERO (DOBLE MONEDA - SOFT UI APLICADO) -->
    <h6 class="fw-bold text-secondary text-uppercase mb-3 mt-5"><i class="fas fa-wallet me-2"></i> Resumen Financiero</h6>
    <div class="row g-4 mb-4">
        <!-- Ingresos Hoy -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-reditus p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Ingresos de Hoy</div>
                        <h3 class="fw-bolder mb-0 text-dark">Bs. <?php echo number_format($diario["ingresos_bs"], 2, ',', '.'); ?></h3>
                        <div class="small text-muted mt-1">Ref: $<?php echo number_format($diario["ingresos_usdt"], 2, ',', '.'); ?></div>
                    </div>
                    <div class="icon-shape bg-soft-success">
                        <i class="fas fa-cash-register"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Ticket Promedio -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-reditus p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Ticket Promedio</div>
                        <h3 class="fw-bolder mb-0 text-dark">Bs. <?php echo number_format($diario["ticket_promedio_bs"], 2, ',', '.'); ?></h3>
                        <div class="small text-muted mt-1">Ref: $<?php echo number_format($diario["ticket_promedio_usdt"], 2, ',', '.'); ?></div>
                    </div>
                    <div class="icon-shape bg-soft-info">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Cuentas Por Cobrar -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-reditus p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Por Cobrar</div>
                        <h3 class="fw-bolder mb-0 text-dark">Bs. <?php echo number_format($global["cxc_bs"], 2, ',', '.'); ?></h3>
                        <div class="small text-muted mt-1">Ref: $<?php echo number_format($global["cxc_usdt"], 2, ',', '.'); ?></div>
                    </div>
                    <div class="icon-shape bg-soft-warning">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Valor Inventario -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-reditus p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Inventario</div>
                        <?php 
                            $inventario_bs = $tasaActual ? ($global["inventario_usdt"] * $tasaActual->tasa_bcv) : 0;
                        ?>
                        <h3 class="fw-bolder mb-0 text-dark">Bs. <?php echo number_format($inventario_bs, 2, ',', '.'); ?></h3>
                        <div class="small text-muted mt-1">Ref: $<?php echo number_format($global["inventario_usdt"], 2, ',', '.'); ?></div>
                    </div>
                    <div class="icon-shape bg-soft-primary">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BLOQUE C Y D: OPERATIVIDAD Y ANALÍTICA -->
    <div class="row g-4 mt-2">
        
        <!-- Columna Izquierda: Gráfico y Métricas Rápidas -->
        <div class="col-lg-8">
            <div class="row g-3 mb-4">
                <div class="col-sm-3 col-6">
                    <div class="card card-reditus p-3 text-center h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Clientes Hoy</div>
                        <h4 class="fw-bolder text-dark m-0"><?php echo $diario["facturas_hoy"]; ?></h4>
                    </div>
                </div>
                <div class="col-sm-3 col-6">
                    <div class="card card-reditus p-3 text-center h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Cajeros Act.</div>
                        <h4 class="fw-bolder text-dark m-0"><?php echo $diario["cajeros_activos"]; ?></h4>
                    </div>
                </div>
                <div class="col-sm-3 col-6">
                    <div class="card card-reditus p-3 text-center h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Compras Mes</div>
                        <h4 class="fw-bolder text-dark m-0"><?php echo $global["compras_mes"]; ?></h4>
                    </div>
                </div>
                <div class="col-sm-3 col-6">
                    <div class="card card-reditus p-3 text-center h-100 <?php echo $global["alerta_stock"] > 0 ? 'border-danger border-2' : ''; ?>">
                        <div class="text-muted small fw-bold text-uppercase mb-1 <?php echo $global["alerta_stock"] > 0 ? 'text-danger' : ''; ?>">Alerta Stock</div>
                        <h4 class="fw-bolder m-0 <?php echo $global["alerta_stock"] > 0 ? 'text-danger' : 'text-dark'; ?>"><?php echo $global["alerta_stock"]; ?> <small class="fs-6 text-muted fw-normal">Prod.</small></h4>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Ventas -->
            <div class="card card-reditus h-100">
                <div class="card-header bg-transparent py-3 border-0 border-bottom">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-chart-bar me-2 text-primary"></i> Flujo de Ingresos (Últimos 7 días)</h6>
                </div>
                <div class="card-body pt-3">
                    <div style="height: 300px; width: 100%;">
                        <canvas id="graficoVentas7Dias"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Ranking de Productos (Estandarizado) -->
        <div class="col-lg-4">
            <div class="card card-reditus h-100">
                <div class="card-header bg-transparent py-3 border-0 border-bottom">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-trophy text-warning me-2"></i> Top 5: Más Vendidos (Mes)</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (count($analitica["top5_vendidos"]) > 0): ?>
                            <?php foreach ($analitica["top5_vendidos"] as $index => $prod): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-light">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-light text-dark border rounded-circle me-3" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;"><?php echo $index + 1; ?></span>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark"><?php echo $prod["nombre"]; ?></h6>
                                            <small class="text-muted text-monospace"><?php echo $prod["codigo_barras"]; ?></small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold text-success fs-5"><?php echo $prod["total_vendido"]; ?></span>
                                        <div class="fs-7 text-muted fw-normal" style="font-size: 0.7rem;">Unds</div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted py-4">No hay ventas registradas este mes.</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="card-footer bg-light p-3 border-top rounded-bottom-4">
                    <div class="text-muted small fw-bold text-uppercase mb-1"><i class="fas fa-truck-loading me-1"></i> Más comprado (Mes):</div>
                    <h6 class="fw-bold text-primary mb-0 d-flex justify-content-between align-items-center">
                        <span class="text-truncate me-2"><?php echo $analitica["mas_comprado"]["nombre"]; ?></span>
                        <span class="badge-reditus"><?php echo $analitica["mas_comprado"]["total_comprado"]; ?> Und</span>
                    </h6>
                </div>
            </div>
        </div>

    </div>
</div>
