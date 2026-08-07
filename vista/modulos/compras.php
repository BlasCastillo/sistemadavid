<?php
// Traemos todo el historial de compras finalizadas
$historialCompras = ComprasControlador::ctrMostrarHistorial();
?>

<div class="container-fluid px-0">
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-shopping-cart me-2 text-success"></i> Historial de Compras</h4>
            <small class="text-muted d-block mt-1">Registro de todas las entradas de mercancía y facturas de proveedores.</small>
        </div>
        <a href="index.php?ruta=compras-crear" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> Registrar Nueva Compra
        </a>
    </div>

    <div class="card-reditus p-4 border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Factura / Fecha</th>
                            <th>Proveedor</th>
                            <th class="text-center">Moneda Pago</th>
                            <th class="text-end">Tasa BCV</th>
                            <th class="text-end">Total Pagado (Nominal)</th>
                            <th class="text-end">Costo Real (USDT)</th>
                            <th class="text-center">ESTADO</th> <th>Cajero/Usuario</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historialCompras as $compra): ?>
                            <tr>
                                <td class="text-muted"><?php echo $compra->id; ?></td>
                                <td>
                                    <div class="fw-semibold text-primary"><?php echo $compra->numero_factura; ?></div>
                                    <div class="small text-muted"><?php echo date("d/m/Y", strtotime($compra->fecha_compra)); ?></div>
                                </td>
                                <td class="fw-semibold"><?php echo $compra->proveedor_nombre; ?></td>
                                <td class="text-center">
                                    <?php if($compra->moneda == "Bs"): ?>
                                        <span class="badge badge-info">Bolívares (Bs)</span>
                                    <?php elseif($compra->moneda == "USD_Fisico"): ?>
                                        <span class="badge badge-warning">Dólar Físico</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">USDT / Digital</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-end"><?php echo number_format($compra->tasa_bcv, 2); ?> Bs</td>
                                <td class="text-end fw-semibold">
                                    <?php echo ($compra->moneda == "Bs" ? "Bs " : "$ ") . number_format($compra->total_nominal, 2); ?>
                                </td>
                                
                                <td class="text-end fw-bold text-success">
                                    $ <?php echo number_format($compra->total_usdt, 4); ?>
                                </td>
                                
                                <td class="text-center align-middle">
                                    <?php if($compra->estado_pago == "Pagado"): ?>
                                        <span class="badge bg-success shadow-sm px-3 py-2">
                                            <i class="fas fa-check-circle me-1"></i> Contado
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark shadow-sm px-3 py-2">
                                            <i class="fas fa-clock me-1"></i> Crédito (<?php echo $compra->dias_credito; ?> días)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td><span class="small text-muted"><i class="fas fa-user me-1"></i><?php echo $compra->usuario_nombre; ?></span></td>
                                <td class="text-center">
                                    <button class="btn-action text-info btnImprimirCompra" idCompra="<?php echo $compra->id; ?>" fechaCompra="<?php echo date('d/m/Y', strtotime($compra->fecha_compra)); ?>" title="Ver Detalle"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
