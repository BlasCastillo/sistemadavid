<?php
// Validación estricta de sesión
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}

// Solicitamos el historial al controlador (Aplica filtros RBAC automáticamente)
$historialVentas = VentasControlador::ctrMostrarHistorialVentas();
?>

<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800 fw-bold"><i class="fas fa-list-ul text-primary me-2"></i> Historial de Ventas</h1>
        <a href="index.php?ruta=ventas-crear" class="btn btn-success shadow-sm fw-bold">
            <i class="fas fa-cash-register me-1"></i> Ir a la Caja
        </a>
    </div>

    <div class="card shadow border-0 border-top border-primary border-3">
        <div class="card-body p-4">
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle w-100" id="tablaHistorialVentas">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 100px;">N° Factura</th>
                            <th>Fecha y Hora</th>
                            <th>Cliente</th>
                            <th>Cajero</th>
                            <th class="text-end">Total ($)</th>
                            <th class="text-end">Total (Bs)</th>
                            <th class="text-center" style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <?php foreach ($historialVentas as $venta): ?>
                        <tr>
                            <td class="text-center fw-bold">
                                <span class="badge bg-secondary fs-6">F-<?php echo $venta->numero_factura; ?></span>
                            </td>
                            <td><?php echo date("d/m/Y h:i A", strtotime($venta->fecha_venta)); ?></td>
                            <td class="fw-bold text-dark"><?php echo $venta->cliente_nombre; ?></td>
                            <td><i class="fas fa-user-circle text-muted me-1"></i> <?php echo $venta->cajero_nombre; ?></td>
                            <td class="text-end fw-bold text-success fs-6">$<?php echo number_format($venta->total_usdt, 2, ',', '.'); ?></td>
<td class="text-end text-muted">Bs <?php echo number_format($venta->total_bs, 2, ',', '.'); ?></td>
                            <td class="text-center">
                                <div class="btn-group shadow-sm">
                                    <button type="button" class="btn btn-sm btn-info text-white btnReimprimirTicket" idVenta="<?php echo $venta->id; ?>" title="Reimprimir Ticket">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btnAnularVenta" idVenta="<?php echo $venta->id; ?>" numFactura="F-<?php echo $venta->numero_factura; ?>" title="Anular / Nota de Crédito">
                                        <i class="fas fa-ban"></i>
                                    </button>
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