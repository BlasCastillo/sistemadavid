<?php
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}

require_once "modelo/Cierres.php";

// Capturamos las variables de sesión de forma segura
$idCajeroSesion = isset($_SESSION["id"]) ? $_SESSION["id"] : (isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0);
$nombreCajero = isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : (isset($_SESSION["usuario"]) ? $_SESSION["usuario"] : "Cajero en Turno");

$datosTurno = Cierres::mdlObtenerMontosEsperados($idCajeroSesion);
$apertura = date("d/m/Y h:i A", strtotime($datosTurno["fecha_apertura"]));
$esperados = $datosTurno["esperados"];
?>

<div class="container py-4" style="max-width: 900px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold"><i class="fas fa-lock text-warning me-2"></i> Cierre de Turno (Reporte X)</h1>
            <small class="text-muted d-block mt-1">Cajero: <span class="fw-bold text-dark"><?php echo $nombreCajero; ?></span> | Turno iniciado: <?php echo $apertura; ?></small>
        </div>
        <a href="index.php?ruta=ventas" class="btn btn-outline-secondary shadow-sm fw-bold">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Pasamos los montos esperados a JSON para que JS pueda validarlos al guardar -->
    <input type="hidden" id="jsonEsperados" value='<?php echo json_encode($esperados); ?>'>

    <form id="formCierreTurno">
        <div class="row">
            
            <!-- COLUMNA IZQUIERDA: ARQUEO FÍSICO (Lo que el cajero debe digitar) -->
            <div class="col-md-6 mb-4">
                <div class="card shadow border-0 border-top border-warning border-3 h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-warning"><i class="fas fa-hand-holding-usd me-2"></i> 1. Dinero Físico (En Gaveta)</h6>
                        <small class="text-muted">Cuente y digite exactamente lo que tiene.</small>
                    </div>
                    <div class="card-body bg-light">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Efectivo Dólares ($)</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-white text-success fw-bold"><i class="fas fa-dollar-sign"></i></span>
                                <input type="number" step="0.01" min="0" class="form-control fw-bold fs-4 text-end text-success inputDeclarado" id="declarado_efectivo_usd" required placeholder="0.00">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Efectivo Bolívares (Bs)</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-white text-primary fw-bold">Bs</span>
                                <input type="number" step="0.01" min="0" class="form-control fw-bold fs-4 text-end text-primary inputDeclarado" id="declarado_efectivo_bs" required placeholder="0.00">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Lote Punto de Venta (Bs)</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-white text-secondary fw-bold"><i class="fas fa-receipt"></i></span>
                                <input type="number" step="0.01" min="0" class="form-control fw-bold fs-4 text-end text-secondary inputDeclarado" id="declarado_punto_bs" required placeholder="0.00">
                            </div>
                            <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle"></i> Imprima el reporte de cierre del terminal e ingrese el total.</small>
                        </div>

                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: DIGITAL (Lo que el sistema auto-rellena) -->
            <div class="col-md-6 mb-4">
                <div class="card shadow border-0 border-top border-info border-3 h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-info"><i class="fas fa-laptop-code me-2"></i> 2. Pagos Electrónicos</h6>
                        <small class="text-muted">Calculados automáticamente por el sistema.</small>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">Zelle / Binance ($)</label>
                            <input type="text" class="form-control bg-light fw-bold text-end text-muted" readonly value="<?php echo number_format($esperados['zelle_usd'], 2); ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">Pago Móvil (Bs)</label>
                            <input type="text" class="form-control bg-light fw-bold text-end text-muted" readonly value="<?php echo number_format($esperados['pago_movil_bs'], 2); ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">Transferencias (Bs)</label>
                            <input type="text" class="form-control bg-light fw-bold text-end text-muted" readonly value="<?php echo number_format($esperados['transferencia_bs'], 2); ?>">
                        </div>

                        <hr>
                        <div class="alert alert-info border-0 shadow-sm mt-4">
                            <i class="fas fa-info-circle me-2"></i> Los pagos electrónicos ya fueron verificados al momento de facturar. Solo concéntrese en el efectivo y los recibos del punto.
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <!-- ACCIÓN FINAL -->
        <div class="card shadow border-0 mt-2">
            <div class="card-body p-4 text-center bg-light">
                <h5 class="fw-bold mb-3">¿Está seguro de enviar su Arqueo de Caja?</h5>
                <button type="submit" class="btn btn-warning btn-lg fw-bold px-5 shadow" id="btnProcesarArqueo">
                    <i class="fas fa-check-circle me-2"></i> Procesar Reporte y Cuadrar Caja
                </button>
            </div>
        </div>
    </form>

</div>