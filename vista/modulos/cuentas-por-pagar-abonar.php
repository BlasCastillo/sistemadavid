<?php
// 1. Validamos que venga un ID por la URL
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    echo '<script>window.location = "index.php?ruta=cuentas-por-pagar";</script>';
    return;
}

// 2. Buscamos la cuenta usando el controlador que acabamos de crear
$cuenta = CuentasPorPagarControlador::ctrMostrarCuentaPorId($_GET["id"]);

// 3. Seguridad: Si la cuenta no existe o ya está pagada, lo devolvemos al catálogo
if (!$cuenta || $cuenta->saldo_restante_usdt <= 0) {
    echo '<script>
        Swal.fire({
            icon: "info",
            title: "Cuenta inactiva",
            text: "Esta deuda ya fue liquidada o no existe.",
            confirmButtonText: "Entendido"
        }).then(function() {
            window.location = "index.php?ruta=cuentas-por-pagar";
        });
    </script>';
    return;
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold"><i class="fas fa-money-check-alt text-primary me-2"></i> Procesar Pago a Proveedor</h1>
            <p class="text-muted mb-0">Registra una amortización o liquida la totalidad de la deuda.</p>
        </div>
        <a href="index.php?ruta=cuentas-por-pagar" class="btn btn-outline-secondary shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver a CxP
        </a>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow border-0 border-top border-info border-3 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-info"><i class="fas fa-file-invoice me-2"></i>Detalles de la Factura</h6>
                </div>
                <div class="card-body bg-light">
                    
                    <div class="text-center mb-4 border-bottom pb-3">
                        <h6 class="text-uppercase text-muted mb-1 small fw-bold">Saldo Pendiente</h6>
                        <h2 class="text-danger fw-bold mb-0">$ <?php echo number_format($cuenta->saldo_restante_usdt, 4); ?></h2>
                    </div>

                    <ul class="list-group list-group-flush rounded shadow-sm border">
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-white">
                            <span class="small fw-semibold text-muted">Proveedor:</span>
                            <span class="fw-bold text-dark text-end"><?php echo $cuenta->proveedor_nombre; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-white">
                            <span class="small fw-semibold text-muted">N° Factura:</span>
                            <span class="fw-bold text-primary"><?php echo $cuenta->numero_factura; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-white">
                            <span class="small fw-semibold text-muted">Total Original:</span>
                            <span class="fw-bold text-dark">$ <?php echo number_format($cuenta->total_deuda_usdt, 4); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-white">
                            <span class="small fw-semibold text-muted">Vencimiento:</span>
                            <span class="fw-bold text-dark"><?php echo date("d/m/Y", strtotime($cuenta->fecha_vencimiento)); ?></span>
                        </li>
                    </ul>

                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card shadow border-0 border-top border-primary border-3 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-cash-register me-2"></i>Registro del Abono</h6>
                </div>
                <div class="card-body p-4">
                    
                    <form id="formAbonarCxP" autocomplete="off">
                        
                        <input type="hidden" name="idCuentaAbono" value="<?php echo $cuenta->id; ?>">

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold small">Monto a Pagar <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light fw-bold text-dark">$</span>
                                    <input type="number" step="0.0001" min="0.01" max="<?php echo $cuenta->saldo_restante_usdt; ?>" class="form-control fw-bold text-success" name="montoAbono" id="montoAbono" value="<?php echo round($cuenta->saldo_restante_usdt, 4); ?>" required>
                                </div>
                                <div class="form-text small text-muted"><i class="fas fa-info-circle me-1"></i> Por defecto sugiere liquidar todo el saldo.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold small">Moneda del Pago <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg fw-bold" name="monedaAbono" required>
                                    <option value="Bs" selected>Bolívares (Bs) - Se calculará a Tasa BCV</option>
                                    <option value="USDT">USDT / Binance Pay</option>
                                    <option value="USD_Fisico">Dólar Físico ($)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4 bg-light p-3 rounded border">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label fw-semibold small">Método de Pago <span class="text-danger">*</span></label>
                                <select class="form-select" name="metodoPagoAbono" required>
                                    <option value="" disabled selected>Seleccione método...</option>
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="Pago Movil">Pago Móvil</option>
                                    <option value="Transferencia Bancaria">Transferencia Bancaria</option>
                                    <option value="Zelle">Zelle</option>
                                    <option value="Binance">Binance Pay</option>
                                    <option value="Punto de Venta">Punto de Venta</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Número de Referencia</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-receipt text-muted"></i></span>
                                    <input type="text" class="form-control" name="referenciaAbono" placeholder="Ej: 09812443">
                                </div>
                                <div class="form-text small text-muted">Opcional si es efectivo.</div>
                            </div>
                        </div>

                        <hr class="mt-2 mb-4">
                        
                        <div class="d-flex justify-content-end">
                            <a href="index.php?ruta=cuentas-por-pagar" class="btn btn-outline-secondary me-2 px-4">Cancelar</a>
                            <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm">
                                <i class="fas fa-check-circle me-2"></i> Procesar Pago a Proveedor
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
