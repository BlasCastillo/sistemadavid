<?php
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}
?>
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold"><i class="fas fa-hand-holding-usd text-primary me-2"></i> Gestión de Créditos</h1>
            <small class="text-muted d-block mt-1">Cuentas por cobrar y registro de abonos.</small>
        </div>
    </div>
    
    <div class="card-reditus p-4 shadow border-0 border-top border-primary border-3 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-list me-2"></i> Cuentas Pendientes</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover dt-responsive tablaCreditos" width="100%">
                    <thead class="table-light">
                        <tr>
                            <th style="width:10px">#</th>
                            <th>Factura</th>
                            <th>Fecha Venta</th>
                            <th>Cliente</th>
                            <th class="text-end">Total Factura ($)</th>
                            <th class="text-end">Abonado ($)</th>
                            <th class="text-end">Deuda Restante ($)</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        require_once "controlador/CreditosControlador.php";
                        $creditos = CreditosControlador::ctrMostrarCreditos();
                        
                        foreach ($creditos as $key => $value):
                            $restante = floatval($value->total_usdt) - floatval($value->total_abonado);
                            if($restante < 0.05) $restante = 0;
                        ?>
                        <tr>
                            <td><?php echo ($key + 1); ?></td>
                            <td><span class="badge bg-dark fs-6"><?php echo $value->numero_factura; ?></span></td>
                            <td><?php echo date("d/m/Y", strtotime($value->fecha_venta)); ?></td>
                            <td>
                                <span class="fw-bold"><?php echo $value->cliente_nombre; ?></span><br>
                                <small class="text-muted"><i class="fas fa-id-card me-1"></i> <?php echo $value->cliente_doc; ?></small>
                            </td>
                            <td class="text-end text-secondary fw-bold">$<?php echo number_format($value->total_usdt, 2); ?></td>
                            <td class="text-end text-success fw-bold">$<?php echo number_format($value->total_abonado, 2); ?></td>
                            <td class="text-end text-danger fw-bold fs-5">$<?php echo number_format($restante, 2); ?></td>
                            <td class="text-center">
                                <div class="btn-group shadow-sm">
                                    <!-- NUEVO: Botón para ver el historial de pagos -->
                                    <button class="btn btn-sm btn-info text-white fw-bold btnVerHistorial" 
                                            idVenta="<?php echo $value->id; ?>"
                                            factura="<?php echo $value->numero_factura; ?>"
                                            cliente="<?php echo $value->cliente_nombre; ?>">
                                        <i class="fas fa-list"></i> Pagos
                                    </button>
                                    
                                    <button class="btn btn-sm btn-dodger fw-bold btnAbonarCredito" 
                                            idVenta="<?php echo $value->id; ?>"
                                            factura="<?php echo $value->numero_factura; ?>"
                                            cliente="<?php echo $value->cliente_nombre; ?>"
                                            restante="<?php echo $restante; ?>"
                                            tasa="<?php echo $value->tasa_bcv; ?>">
                                        <i class="fas fa-plus-circle"></i> Abonar
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

<!-- ==============================================================
     MODAL PARA REGISTRAR ABONO
     ============================================================== -->
<div class="modal fade" id="modalAbonarCredito" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-hand-holding-usd me-2"></i> Registrar Abono a Cuenta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <div class="alert alert-light border border-primary shadow-sm mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Cliente:</span>
                        <span class="fw-bold text-dark" id="lblClienteAbono"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">N° Factura:</span>
                        <span class="fw-bold text-dark" id="lblFacturaAbono"></span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-danger fw-bold">Deuda Actual:</span>
                        <span class="text-danger fw-bold fs-3">$<span id="lblRestanteAbono"></span></span>
                    </div>
                </div>

                <form id="formAbonarCredito">
                    <input type="hidden" id="idVentaAbono" name="idVentaAbono">
                    <input type="hidden" id="tasaBcvAbono" name="tasaBcvAbono">
                    <input type="hidden" id="maximoAbonoUsdt">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Moneda</label>
                            <select class="form-select fw-bold text-primary" id="monedaAbono" name="monedaAbono" required>
                                <option value="USD">Dólares ($)</option>
                                <option value="BS">Bolívares (Bs)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Método de Pago</label>
                            <select class="form-select" id="metodoAbono" name="metodoAbono" required>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Zelle">Zelle / Binance</option>
                                <option value="Pago Movil" class="opt-bs d-none">Pago Móvil</option>
                                <option value="Punto de Venta" class="opt-bs d-none">Punto de Venta</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Monto a Entregar</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-white border-primary text-primary fw-bold" id="simboloMonedaAbono">$</span>
                            <input type="number" step="0.01" min="0.01" class="form-control border-primary fw-bold fs-4" id="montoAbono" name="montoAbono" required placeholder="0.00">
                        </div>
                        <small class="text-success fw-bold d-block mt-2 d-none" id="equivalenciaAbono"></small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Referencia de Transacción</label>
                        <input type="text" class="form-control" id="referenciaAbono" name="referenciaAbono" placeholder="Opcional. Ej: 12345678">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-dodger btn-lg fw-bold shadow-sm" id="btnGuardarAbono">
                            <i class="fas fa-check-circle me-2"></i> Procesar Cuota
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ==============================================================
     NUEVO: MODAL PARA VER HISTORIAL DE PAGOS
     ============================================================== -->
<div class="modal fade" id="modalHistorialPagos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-history me-2"></i> Historial de Abonos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">Cliente: <span id="lblClienteHistorial" class="text-primary"></span></h6>
                    <span class="badge bg-dark fs-6" id="lblFacturaHistorial"></span>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Método</th>
                                <th class="text-center">Moneda</th>
                                <th>Referencia</th>
                                <th class="text-end">Monto Abonado</th>
                                <th class="text-center">Ticket</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoHistorialPagos">
                            <!-- Inyectado por JS -->
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
