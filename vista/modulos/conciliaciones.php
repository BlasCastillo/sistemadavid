<?php
// Validar Sesión
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}

// Validar Permisos (RBAC) - Solo administradores o personas con el permiso específico
$esAdmin = ($_SESSION["rol_id"] == 1);
$permisos = $_SESSION["permisos"] ?? [];
if (!$esAdmin && !in_array("all", $permisos) && !in_array("conciliar_pagos", $permisos)) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}

// Extraer Totales Consolidados (El dinero real en el banco)
$totales = ConciliacionesControlador::ctrTotalesConsolidados();
?>

<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800 fw-bold">
            <i class="fas fa-university text-primary me-2"></i> Conciliaciones Bancarias
        </h1>
        <button class="btn btn-primary shadow-sm fw-bold" onclick="window.location.reload();">
            <i class="fas fa-sync-alt me-1"></i> Actualizar Tablero
        </button>
    </div>

    <!-- TARJETAS DE CONSOLIDACIÓN (Dinero Verificado) -->
    <div class="row mb-4 g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10 h-100 border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-success small fw-bold text-uppercase mb-1">Total Consolidado en Banco (Bs)</div>
                            <h3 class="fw-bold text-success mb-0">Bs. <?php echo number_format($totales["BS"], 2, ',', '.'); ?></h3>
                        </div>
                        <i class="fas fa-money-bill-wave text-success opacity-50 fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-primary bg-opacity-10 h-100 border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-primary small fw-bold text-uppercase mb-1">Total Consolidado en Banco (USD)</div>
                            <h3 class="fw-bold text-primary mb-0">$<?php echo number_format($totales["USD"], 2, ',', '.'); ?></h3>
                        </div>
                        <i class="fas fa-dollar-sign text-primary opacity-50 fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE AUDITORÍA -->
    <div class="card shadow border-0 border-top border-dark border-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-list-check me-1"></i> Auditoría de Pagos Electrónicos</h6>
            
            <!-- Selector de Estado -->
            <div class="btn-group shadow-sm" role="group">
                <input type="radio" class="btn-check filtro-conciliacion" name="filtroEstado" id="filtroPendientes" value="Pendiente" checked>
                <label class="btn btn-outline-danger fw-bold" for="filtroPendientes">Esperando Verificación</label>

                <input type="radio" class="btn-check filtro-conciliacion" name="filtroEstado" id="filtroConciliados" value="Conciliado">
                <label class="btn btn-outline-success fw-bold" for="filtroConciliados">Ya Conciliados</label>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle w-100" id="tablaConciliaciones">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center">Factura</th>
                            <th>Método</th>
                            <th>Referencia</th>
                            <th>Fecha Transacción</th>
                            <th class="text-end">Monto</th>
                            <th class="text-center">Acciones / Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Llenado por AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>