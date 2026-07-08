<?php
// Traemos todo el historial de compras finalizadas
$historialCompras = ComprasControlador::ctrMostrarHistorial();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-shopping-cart text-success me-2"></i> Historial de Compras</h1>
        <a href="index.php?ruta=compras-crear" class="btn btn-success fw-bold shadow-sm"><i class="fas fa-plus me-1"></i> Registrar Nueva Compra</a>
    </div>

    <div class="card shadow border-0 border-top border-success border-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Factura / Fecha</th>
                            <th>Proveedor</th>
                            <th>Moneda Pago</th>
                            <th>Tasa BCV</th>
                            <th>Total Pagado (Nominal)</th>
                            <th class="text-success">Costo Real (USDT)</th>
                            <th>Cajero/Usuario</th>
                            <th class="text-center" style="width: 100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historialCompras as $compra): ?>
                            <tr>
                                <td><?php echo $compra->id; ?></td>
                                <td>
                                    <div class="fw-bold fs-6 text-primary"><?php echo $compra->numero_factura; ?></div>
                                    <div class="small text-muted"><?php echo date("d/m/Y", strtotime($compra->fecha_compra)); ?></div>
                                </td>
                                <td class="fw-bold"><?php echo $compra->proveedor_nombre; ?></td>
                                <td>
                                    <?php if($compra->moneda == "Bs"): ?>
                                        <span class="badge bg-primary">Bolívares (Bs)</span>
                                    <?php elseif($compra->moneda == "USD_Fisico"): ?>
                                        <span class="badge bg-secondary text-dark">Dólar Físico</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">USDT / Digital</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($compra->tasa_bcv, 2); ?> Bs</td>
                                <td class="fw-bold text-dark">
                                    <?php echo ($compra->moneda == "Bs" ? "Bs " : "$ ") . number_format($compra->total_nominal, 2); ?>
                                </td>
                                <td class="fw-bold text-success fs-6">
                                    $ <?php echo number_format($compra->total_usdt, 4); ?>
                                </td>
                                <td><span class="small text-muted"><i class="fas fa-user me-1"></i><?php echo $compra->usuario_nombre; ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-info text-white btnImprimirCompra" idCompra="<?php echo $compra->id; ?>" title="Ver Detalle"><i class="fas fa-eye"></i></button>
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