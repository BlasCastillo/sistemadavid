<?php
// Validación estricta de sesión
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}

// Traemos los clientes activos de la BD
require_once "controlador/ClientesControlador.php";
$clientes = ClientesControlador::ctrMostrarClientes(null, 1);
if(!is_array($clientes)) { $clientes = []; }
?>

<div class="container-fluid py-4 h-100">
    
    <!-- CABECERA DEL MÓDULO POS -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold"><i class="fas fa-cash-register text-success me-2"></i> Punto de Venta (POS)</h1>
            <small class="text-muted d-block mt-1">Escanee productos y gestione las facturas de los clientes.</small>
        </div>
        <button class="btn btn-success btn-lg rounded-pill shadow-sm d-md-none fw-bold" data-bs-toggle="modal" data-bs-target="#modalScannerVentas">
            <i class="fas fa-camera me-2"></i> Escanear
        </button>
        <button class="btn btn-outline-success shadow-sm d-none d-md-inline-block fw-bold" data-bs-toggle="modal" data-bs-target="#modalScannerVentas">
            <i class="fas fa-camera me-2"></i> Activar Lector Web
        </button>
    </div>

    <div class="row">
        <!-- COLUMNA IZQUIERDA -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow border-0 border-top border-success border-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-success"><i class="fas fa-barcode me-2"></i>Captura de Productos</h6>
                </div>
                <div class="card-body bg-light">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-dark">Escáner Físico (Pistola)</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-white border-success text-success"><i class="fas fa-barcode"></i></span>
                            <input type="text" id="inputLectorVentas" class="form-control border-success fw-bold" placeholder="Escanee el código..." autofocus autocomplete="off">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold small text-dark">Búsqueda Manual (Teclado)</label>
                        <select class="form-select select2-dinamico" id="buscadorManualVentas" style="width: 100%;">
                            <option value="" disabled selected>Escriba el nombre del producto...</option>
                        </select>
                    </div>

                </div>
            </div>

            <!-- PANEL DE FACTURAS SUSPENDIDAS -->
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white py-3">
                    <h6 class="m-0 fw-bold"><i class="fas fa-pause-circle me-2 text-warning"></i> Facturas en Espera</h6>
                </div>
                <input type="text" class="form-control form-control-sm bg-secondary text-white border-0 shadow-none" id="buscadorSuspendidas" placeholder="Buscar por Cédula/DNI...">
                <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;" id="panelFacturasSuspendidas">
                    <!-- Facturas suspendidas -->
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow border-0 h-100 flex-column d-flex">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-shopping-basket me-2 text-primary"></i> Carrito Actual</h6>
                    
                    <!-- SELECT 2 HÍBRIDO (CLIENTES) -->
                    <div style="width: 300px;">
                        <select class="form-select" id="identificadorClientePOS">
                            <option value="" selected disabled>Buscar o ingresar C.I...</option>
                            <?php foreach($clientes as $cli): ?>
                                <option value="<?php echo $cli->getDocumento(); ?>">
                                    <?php echo $cli->getDocumento() . " - " . $cli->getNombre(); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" style="font-size: 0.7rem;">Si es nuevo, escriba la C.I y presione Enter.</small>
                    </div>
                </div>
                
                <div class="card-body p-0 flex-grow-1" style="min-height: 350px;">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" id="tablaVentasCrear">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 15%;">Código</th>
                                    <th style="width: 45%;">Producto</th>
                                    <th class="text-center" style="width: 15%;">Cant.</th>
                                    <th class="text-end" style="width: 20%;">Precio U.</th>
                                    <th class="text-center" style="width: 5%;"><i class="fas fa-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody id="listaVentasTemporal"></tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-light p-4 border-top">
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3 mb-md-0 text-center text-md-start">
                            <h6 class="text-muted mb-1 fw-bold text-uppercase">Total Factura</h6>
                            <h2 class="text-success mb-0 fw-bold" id="totalVentaUsdtVisual">$0.00</h2>
                        </div>
                        <div class="col-md-6">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-warning fw-bold text-dark px-4 py-2" id="btnSuspenderFactura" disabled>
                                    <i class="fas fa-pause me-2"></i> Suspender
                                </button>
                                <button type="button" class="btn btn-success fw-bold px-4 py-2 btn-lg shadow-sm" id="btnProcederPago" disabled>
                                    Cobrar Factura <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Scanner Omitido por brevedad, mantén el que tenías -->

<!-- ==============================================================
     MODAL PARA LECTOR DE CÁMARA (Librería html5-qrcode)
     ============================================================== -->
<div class="modal fade" id="modalScannerVentas" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-camera-retro me-2 text-info"></i> Lector de Código de Barras</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0 text-center bg-black position-relative">
                <div id="lectorCamaraVentas" class="w-100" style="min-height: 350px;"></div>
                <div class="position-absolute top-50 start-50 translate-middle pointer-events-none" style="width: 250px; height: 150px; border: 2px solid rgba(23, 162, 184, 0.5); border-radius: 10px; box-shadow: 0 0 0 4000px rgba(0,0,0,0.4);">
                    <div class="position-absolute top-50 start-0 w-100 border-top border-danger shadow" style="transform: translateY(-50%); opacity: 0.8;"></div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 justify-content-center py-3">
                <button type="button" class="btn btn-secondary fw-bold px-4 rounded-pill" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i> Cancelar Lectura
                </button>
            </div>
        </div>
    </div>
</div>