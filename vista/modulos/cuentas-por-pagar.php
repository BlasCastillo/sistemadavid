        <?php
// Traemos todas las cuentas por pagar registradas
$cuentas = CuentasPorPagarControlador::ctrMostrarCuentas();
?>

<div class="container-fluid py-4">
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-hand-holding-usd me-2 text-danger"></i> Cuentas por Pagar (CxP)</h4>
            <small class="text-muted d-block mt-1">Gestión de deudas a proveedores y control de pagos.</small>
        </div>
        <a href="index.php?ruta=compras-crear" class="btn btn-primary shadow-sm">
            <i class="fas fa-truck-loading me-1"></i> Nueva Compra
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0 tablaCuentasPorPagar">
                    <thead>
                        <tr>
                            <th># CxP</th>
                            <th>Proveedor</th>
                            <th>Factura de Origen</th>
                            <th>Vencimiento</th>
                            <th class="text-end">Total Deuda (USDT)</th>
                            <th class="text-end">Saldo Pendiente</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuentas as $cuenta): ?>
                            <tr>
                                <td class="text-muted fw-bold"><?php echo $cuenta->id; ?></td>
                                <td class="fw-semibold text-dark"><?php echo $cuenta->proveedor_nombre; ?></td>
                                
                                <td>
                                    <div class="text-primary fw-bold"><?php echo $cuenta->numero_factura; ?></div>
                                    <div class="small text-muted border-top border-light mt-1 pt-1">
                                        Generada: <?php echo date("d/m/Y", strtotime($cuenta->fecha_compra)); ?>
                                    </div>
                                </td>

                                <td>
                                    <?php 
                                        $fecha_vence = strtotime($cuenta->fecha_vencimiento);
                                        $hoy = strtotime(date("Y-m-d"));
                                        
                                        if($cuenta->estado == 'Pagada') {
                                            echo '<span class="text-success fw-bold"><i class="fas fa-check"></i> Liquidada</span>';
                                        } else if ($fecha_vence < $hoy) {
                                            echo '<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle"></i> Vencida (' . date("d/m/Y", $fecha_vence) . ')</span>';
                                        } else {
                                            echo '<span class="text-warning text-dark fw-bold"><i class="far fa-clock"></i> ' . date("d/m/Y", $fecha_vence) . '</span>';
                                        }
                                    ?>
                                </td>

                                <td class="text-end fw-semibold text-muted">
                                    $ <?php echo number_format($cuenta->total_deuda_usdt, 4); ?>
                                </td>
                                
                                <td class="text-end fw-bold <?php echo ($cuenta->saldo_restante_usdt > 0) ? 'text-danger' : 'text-success'; ?> fs-6">
                                    $ <?php echo number_format($cuenta->saldo_restante_usdt, 4); ?>
                                </td>

                                <td class="text-center align-middle">
                                    <?php if($cuenta->estado == "Pagada"): ?>
                                        <span class="badge bg-success shadow-sm px-3 py-2">
                                            Pagada
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger shadow-sm px-3 py-2">
                                            Pendiente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <div class="btn-group shadow-sm">
                                        <button class="btn btn-light btn-sm text-info btnVerPagosCxP border" idCuenta="<?php echo $cuenta->id; ?>" factura="<?php echo $cuenta->numero_factura; ?>" title="Ver Historial de Abonos">
                                            <i class="fas fa-list"></i>
                                        </button>
                                        
                                        <?php if($cuenta->saldo_restante_usdt > 0): ?>
                                            <button class="btn btn-primary btn-sm btnAbonarCxP" idCuenta="<?php echo $cuenta->id; ?>" title="Registrar Abono">
                                                <i class="fas fa-money-bill-wave"></i> Pagar
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled title="Deuda Liquidada">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>