<?php
// Validación de sesión
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}

// Validación de la URL
if(!isset($_GET["idVenta"])) {
    echo '<script>window.location = "index.php?ruta=ventas";</script>';
    exit;
}

// 1. Extraemos la "Radiografía" completa de la venta
$idVenta = intval($_GET["idVenta"]);
$datosVenta = VentasControlador::ctrMostrarVentaCompleta($idVenta);

// Si alguien manipula la URL y pone un ID que no existe
if(!$datosVenta || !$datosVenta["cabecera"]) {
    echo '<div class="alert alert-danger m-4">Error: Factura no encontrada.</div>';
    exit;
}

$cabecera = $datosVenta["cabecera"];
$detalles = $datosVenta["detalles"];
$pagos = $datosVenta["pagos"];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800 fw-bold">
            <i class="fas fa-exchange-alt text-danger me-2"></i> Gestión de Devolución
        </h1>
        <a href="index.php?ruta=ventas" class="btn btn-secondary shadow-sm fw-bold">
            <i class="fas fa-arrow-left me-1"></i> Volver al Historial
        </a>
    </div>

    <div class="row">
        <!-- COLUMNA IZQUIERDA: RESUMEN DE LA FACTURA ORIGINAL -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow border-0 border-top border-dark border-3 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-file-invoice me-1"></i> Factura Original: F-<?php echo $cabecera->numero_factura; ?></h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item px-0"><b>Cliente:</b> <?php echo $cabecera->cliente_nombre; ?></li>
                        <li class="list-group-item px-0"><b>Documento:</b> <?php echo $cabecera->cliente_doc; ?></li>
                        <li class="list-group-item px-0"><b>Cajero:</b> <span class="text-primary fw-bold"><?php echo $cabecera->cajero_nombre; ?></span></li>
                        <li class="list-group-item px-0"><b>Fecha:</b> <?php echo date("d/m/Y h:i A", strtotime($cabecera->fecha_venta)); ?></li>
                        <li class="list-group-item px-0"><b>Tasa BCV Aplicada:</b> Bs. <?php echo number_format($cabecera->tasa_bcv, 4, ',', '.'); ?></li>
                    </ul>

                    <div class="mt-3 p-3 bg-light rounded text-center border">
                        <div class="small text-muted text-uppercase fw-bold mb-1">Total Cobrado</div>
                        <h4 class="text-success fw-bold m-0">$<?php echo number_format($cabecera->total_usdt, 2, ',', '.'); ?></h4>
                        <div class="text-muted small">Bs. <?php echo number_format($cabecera->total_bs, 2, ',', '.'); ?></div>
                    </div>

                    <h6 class="mt-4 fw-bold small text-muted text-uppercase">Métodos de Pago Usados</h6>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-bordered text-center small">
                            <thead class="table-light">
                                <tr><th>Método</th><th>Monto</th><th>Ref.</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($pagos as $p): ?>
                                <tr>
                                    <td><?php echo $p->metodo_pago; ?></td>
                                    <td class="text-end fw-bold"><?php echo $p->moneda == "USD" ? "$" : "Bs."; ?><?php echo number_format($p->monto_pagado, 2, ',', '.'); ?></td>
                                    <td><?php echo $p->referencia; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: SELECCIÓN DE PRODUCTOS A DEVOLVER -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow border-0 border-top border-danger border-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-danger"><i class="fas fa-box-open me-1"></i> Selección de Productos a Reversar</h6>
                    <button class="btn btn-sm btn-outline-primary" id="btnSeleccionarTodo">Seleccionar Todo</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaDevolucion">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th style="width: 50px;">Dev.</th>
                                    <th class="text-start">Producto</th>
                                    <th>Precio ($)</th>
                                    <th style="width: 140px;">Cant. Devuelta</th>
                                    <th class="text-end pe-3">Subtotal ($)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($detalles as $item): ?>
                                <tr class="fila-producto">
                                    <td class="text-center">
                                        <input class="form-check-input check-devolver" type="checkbox" 
                                               precio="<?php echo $item->precio_unitario_usdt; ?>" 
                                               max-cant="<?php echo $item->cantidad; ?>" 
                                               idProducto="<?php echo $item->producto_id; ?>"
                                               idDetalle="<?php echo $item->id; ?>">
                                    </td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark"><?php echo $item->nombre; ?></div>
                                        <small class="text-muted">Cod: <?php echo $item->codigo_barras; ?> | Cant. Orig: <?php echo $item->cantidad; ?></small>
                                    </td>
                                    <td class="text-center fw-bold text-success">$<span class="precio-unitario"><?php echo number_format($item->precio_unitario_usdt, 2, '.', ''); ?></span></td>
                                    <td class="text-center">
                                        <input type="number" class="form-control form-control-sm text-center input-cant-devolver" value="1" min="1" max="<?php echo $item->cantidad; ?>" disabled>
                                    </td>
                                    <td class="text-end fw-bold pe-3 text-danger">$<span class="subtotal-devolver">0.00</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold text-uppercase pt-3">Monto Total a Reembolsar:</td>
                                    <td class="text-end fw-bold fs-5 text-danger pt-3 pe-3">$<span id="granTotalDevolucion">0.00</span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <div class="card-footer bg-white p-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-hand-holding-usd me-1"></i> Método de Reembolso</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <select class="form-select form-select-lg border-primary shadow-sm fw-bold" id="metodoReembolso">
                                <option value="credito_usd">📝 Nota de Crédito (Saldo a favor en $)</option>
                                <option value="efectivo_bs">💵 Devolución de Dinero (Efectivo Bs)</option>
                                <option value="efectivo_usd">💵 Devolución de Dinero (Efectivo USD)</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-grid">
                            <button class="btn btn-danger btn-lg shadow-sm fw-bold" id="btnProcesarDevolucion" disabled>
                                <i class="fas fa-check-circle me-1"></i> Procesar Nota de Crédito
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>