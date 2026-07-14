<?php
// Validación estricta de sesión
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}

// 1. Validamos que exista una cédula en la URL
if(!isset($_GET["cedula"]) || empty($_GET["cedula"])) {
    echo '<script>
        Swal.fire("Error", "Debe identificar al cliente antes de proceder al pago.", "error").then(function(){
            window.location = "index.php?ruta=ventas-crear";
        });
    </script>';
    exit;
}

$cedulaRecibida = trim($_GET["cedula"]);

// 2. Traemos las Tasas de Cambio Frescas
$stmt = Conexion::conectar()->prepare("SELECT tasa_bcv FROM tasas_cambio ORDER BY id DESC LIMIT 1");
$stmt->execute();
$tasaActual = $stmt->fetch(PDO::FETCH_OBJ);
$tasaBcvHoy = $tasaActual ? floatval($tasaActual->tasa_bcv) : 1;

// 3. Verificamos si el cliente existe en la Base de Datos
$stmtCliente = Conexion::conectar()->prepare("SELECT * FROM clientes WHERE documento = :documento");
$stmtCliente->bindParam(":documento", $cedulaRecibida, PDO::PARAM_STR);
$stmtCliente->execute();
$clienteDB = $stmtCliente->fetch(PDO::FETCH_OBJ);

$esClienteNuevo = !$clienteDB;
$nombreCliente = $esClienteNuevo ? "" : $clienteDB->nombre;
$telefonoCliente = $esClienteNuevo ? "" : $clienteDB->telefono;
$emailCliente = $esClienteNuevo ? "" : $clienteDB->email; // NUEVA LÍNEA
$direccionCliente = $esClienteNuevo ? "" : $clienteDB->direccion;
$atributoSoloLectura = $esClienteNuevo ? "" : "readonly";
$claseFondo = $esClienteNuevo ? "bg-white" : "bg-light";

// 4. Traemos el carrito activo del cajero para totalizar
require_once "modelo/Ventas.php";
$carrito = Ventas::leerTemporales($_SESSION["id_usuario"]);

if(count($carrito) == 0) {
    echo '<script>window.location = "index.php?ruta=ventas-crear";</script>';
    exit;
}

$totalUsdt = 0;
foreach($carrito as $item) {
    $totalUsdt += ($item->cantidad * $item->precio_venta_usdt) - $item->descuento_aplicado;
}
$totalBs = $totalUsdt * $tasaBcvHoy;
?>

<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800 fw-bold"><i class="fas fa-wallet text-success me-2"></i> Liquidación y Pagos</h1>
        <a href="index.php?ruta=ventas-crear" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver a la Caja
        </a>
    </div>

    <div class="row">
        <!-- ==============================================================
             COLUMNA IZQUIERDA: DATOS DEL CLIENTE
             ============================================================== -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow border-0 border-top border-primary border-3 mb-4 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-user-tag me-2"></i>Datos de Facturación</h6>
                    <?php if($esClienteNuevo): ?>
                        <span class="badge bg-danger pulse-animation">¡Cliente Nuevo!</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    
                    <form id="formClienteExpress">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">C.I / RIF Documento</label>
                            <input type="text" class="form-control bg-light fw-bold text-primary" id="docClienteFinal" value="<?php echo $cedulaRecibida; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nombre / Razón Social <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo $claseFondo; ?>" id="nomClienteFinal" value="<?php echo $nombreCliente; ?>" <?php echo $atributoSoloLectura; ?> required placeholder="Ej: Juan Pérez">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Teléfono Móvil</label>
                            <input type="text" class="form-control <?php echo $claseFondo; ?>" id="telClienteFinal" value="<?php echo $telefonoCliente; ?>" <?php echo $atributoSoloLectura; ?> placeholder="Opcional">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Correo Electrónico</label>
                            <input type="email" class="form-control <?php echo $claseFondo; ?>" id="emaClienteFinal" value="<?php echo $emailCliente; ?>" <?php echo $atributoSoloLectura; ?> placeholder="Opcional">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Dirección Fiscal</label>
                            <textarea class="form-control <?php echo $claseFondo; ?>" id="dirClienteFinal" rows="2" <?php echo $atributoSoloLectura; ?> placeholder="Opcional"><?php echo $direccionCliente; ?></textarea>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!-- ==============================================================
             COLUMNA DERECHA: MULTIPAGO Y DESCUENTOS
             ============================================================== -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow border-0 h-100">
                
                <!-- ZONA DE TOTALES -->
                <div class="card-body bg-dark text-white rounded-top p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase text-muted fw-bold mb-1">Monto a Pagar</h6>
                        <h2 class="mb-0 fw-bold text-warning" id="granTotalPagarVisual">$<?php echo number_format($totalUsdt, 2); ?></h2>
                        <small>Equivalente a Bs <?php echo number_format($totalBs, 2); ?> (Tasa: <?php echo number_format($tasaBcvHoy, 2); ?>)</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-warning fw-bold border-2 shadow-sm" id="btnAplicarDescuentoGlobal">
                            <i class="fas fa-tags me-1"></i> Aplicar Descuento
                        </button>
                    </div>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- CONTROLES PARA AGREGAR PAGO -->
                    <div class="row align-items-end mb-4 p-3 bg-white border rounded shadow-sm">
                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="form-label fw-bold small">Moneda</label>
                            <select class="form-select fw-bold text-primary" id="monedaMultipago">
                                <option value="USD">Dólares ($)</option>
                                <option value="BS">Bolívares (Bs)</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <label class="form-label fw-bold small">Método de Pago</label>
                            <select class="form-select" id="metodoMultipago">
                                <option value="Efectivo">Efectivo</option>
                                <option value="Zelle">Zelle / Binance</option>
                                <option value="Punto de Venta" class="opt-bs d-none">Punto de Venta</option>
                                <option value="Pago Movil" class="opt-bs d-none">Pago Móvil</option>
                                <option value="Saldo a Favor">Saldo a Favor</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="form-label fw-bold small">Monto</label>
                            <input type="number" step="0.01" min="0.01" class="form-control border-primary" id="montoMultipago" placeholder="0.00">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="button" class="btn btn-primary fw-bold" id="btnAgregarPago"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>

                    <!-- TABLA DE MULTIPAGOS -->
                    <div class="table-responsive bg-white rounded border mb-4" style="min-height: 150px;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Método</th>
                                    <th class="text-center">Moneda</th>
                                    <th class="text-end">Monto Declarado</th>
                                    <th class="text-end text-success">Equivalencia ($)</th>
                                    <th class="text-center"><i class="fas fa-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody id="listaMultipagos">
                                <!-- Filas JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- INDICADORES FINALES -->
                    <div class="row text-center mt-3 border-top pt-3">
                        <div class="col-6 border-end">
                            <h6 class="text-muted fw-bold text-uppercase mb-1">Monto Faltante</h6>
                            <h3 class="fw-bold text-danger mb-0" id="montoFaltanteVisual">$<?php echo number_format($totalUsdt, 2); ?></h3>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted fw-bold text-uppercase mb-1">Vuelto a Entregar</h6>
                            <h3 class="fw-bold text-success mb-0" id="montoVueltoVisual">$0.00</h3>
                        </div>
                    </div>

                </div>

                <div class="card-footer bg-white p-4 border-top">
                    <div class="d-grid">
                        <button type="button" class="btn btn-success btn-lg fw-bold shadow" id="btnProcesarVentaDefinitiva" disabled>
                            <i class="fas fa-check-circle me-2"></i> Emitir Factura y Cobrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Variables ocultas para cálculos en JS -->
<input type="hidden" id="tasaBcvGlobal" value="<?php echo $tasaBcvHoy; ?>">
<input type="hidden" id="totalUsdtGlobal" value="<?php echo $totalUsdt; ?>">