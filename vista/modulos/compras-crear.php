<?php
// 1. Traer proveedores y blindar la variable
$proveedores = ProveedoresControlador::ctrMostrarProveedores(null, 1);
if (!is_array($proveedores)) { $proveedores = []; }

// 2. Traer la última tasa registrada
$stmt = Conexion::conectar()->prepare("SELECT tasa_bcv, brecha_porcentaje FROM tasas_cambio ORDER BY id DESC LIMIT 1");
$stmt->execute();
$tasaActual = $stmt->fetch(PDO::FETCH_OBJ);

$tasaBcvHoy = $tasaActual ? $tasaActual->tasa_bcv : 1;
$brechaHoy = $tasaActual ? $tasaActual->brecha_porcentaje : 0;
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-cart-plus text-success me-2"></i> Registrar Compra</h1>
        <a href="index.php?ruta=compras" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver al Historial</a>
    </div>

    <form id="formProcesarCompra" autocomplete="off">
        <div class="row">
            
            <div class="col-lg-4 mb-4">
                <div class="card shadow border-0 border-top border-success border-3 h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-success"><i class="fas fa-file-invoice-dollar me-2"></i>Datos de la Factura</h6>
                    </div>
                    <div class="card-body bg-light">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Proveedor <span class="text-danger">*</span></label>
                            <select class="form-select select2-dinamico" name="idProveedorCompra" required>
                                <option value="" disabled selected>Seleccione Proveedor...</option>
                                <?php foreach($proveedores as $prov): ?>
                                    <option value="<?php echo $prov->getId(); ?>">
                                        <?php echo $prov->getDocumento() . " - " . $prov->getRazonSocial(); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">N° de Factura <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="numeroFacturaCompra" placeholder="Ej: F-001234" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Fecha de Compra <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fechaCompra" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <hr class="my-4">

                        

                        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-coins me-2"></i>Configuración Financiera</h6>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Condición <span class="text-danger">*</span></label>
                                <select class="form-select fw-bold text-dark" id="condicionPagoCompra" name="condicionPagoCompra" required>
                                    <option value="Contado" selected>Contado (Pagado)</option>
                                    <option value="Credito">A Crédito (Deuda)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Plazo (Días)</label>
                                <div class="input-group">
                                    <input type="number" min="0" class="form-control bg-light" id="diasCreditoCompra" name="diasCreditoCompra" value="0" readonly>
                                    <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Moneda de Pago / Facturación <span class="text-danger">*</span></label>
                            <select class="form-select fw-bold text-primary" id="monedaCompra" name="monedaCompra" required>
                                <option value="Bs" selected>Bolívares (Bs)</option>
                                <option value="USD_Fisico">Dólares Físicos ($)</option>
                                <option value="USDT">USDT / Digital</option>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Tasa BCV</label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs</span>
                                    <input type="text" class="form-control bg-white" id="tasaBcvCompra" name="tasaBcvCompra" value="<?php echo $tasaBcvHoy; ?>" readonly>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Brecha</label>
                                <div class="input-group">
                                    <span class="input-group-text">%</span>
                                    <input type="text" class="form-control bg-white" id="brechaCompra" name="brechaCompra" value="<?php echo $brechaHoy; ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Observaciones</label>
                            <textarea class="form-control" name="observacionesCompra" rows="2" placeholder="Notas adicionales..."></textarea>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-4">
                <div class="card shadow border-0 h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-dark"><i class="fas fa-boxes me-2"></i>Detalle de Productos</h6>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="row gx-2 mb-4 p-3 bg-light rounded border align-items-end" id="panelBuscadorProductos">
                            <div class="col-md-5 mb-2 mb-md-0">
                                <label class="form-label fw-semibold small">Buscar Producto</label>
                                <select class="form-select" id="buscadorProductosCompra" style="width: 100%;">
                                    <option value="" disabled selected>Escanee código o escriba el nombre...</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2 mb-md-0">
                                <label class="form-label fw-semibold small">Cantidad</label>
                                <input type="number" min="1" class="form-control" id="cantidadItemCompra" placeholder="0">
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="form-label fw-semibold small">Costo Factura (<span class="simboloMoneda">Bs</span>)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="costoNominalItemCompra" placeholder="0.00">
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="button" class="btn btn-primary fw-bold" id="btnAgregarItemCompra"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>

                        <div class="table-responsive mb-4" style="min-height: 250px;">
                            <table class="table table-hover align-middle tablaComprasTemporales">
                                <thead class="table-light">
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">Costo U. Nominal</th>
                                        <th class="text-end text-success">Costo U. Real (USDT)</th>
                                        <th class="text-center" style="width: 50px;">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody id="listaItemsTemporal">
                                </tbody>
                            </table>
                        </div>

                        <hr>

                        <div class="row align-items-center">
                            <div class="col-md-6 text-md-end text-center mb-3 mb-md-0">
                                <h5 class="mb-1 text-muted">Total Nominal: <span class="fw-bold text-dark" id="granTotalNominalTexto">0.00</span> <span class="simboloMoneda">Bs</span></h5>
                                <h4 class="mb-0 text-success">Total Real: $ <span class="fw-bold" id="granTotalUsdtTexto">0.0000</span></h4>
                                
                                <input type="hidden" name="totalNominalCompra" id="totalNominalCompraForm" value="0">
                                <input type="hidden" name="totalUsdtCompra" id="totalUsdtCompraForm" value="0">
                            </div>
                            <div class="col-md-6 d-grid">
                                <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm"><i class="fas fa-check-circle me-2"></i> Procesar y Liquidar Compra</button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>
</div>