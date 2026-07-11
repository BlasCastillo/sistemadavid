<?php
// Validación estricta de sesión
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}
?>

<div class="container-fluid py-4 h-100 d-flex flex-column" style="min-height: 80vh;">
    
    <!-- CABECERA DEL MÓDULO -->
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="module-title">
            <h4 class="fw-bold mb-0 text-dark">
                <i class="fas fa-barcode me-2 text-primary"></i> Verificador de Precios
            </h4>
            <small class="text-muted d-block mt-1">Escanee un producto para consultar su precio y disponibilidad al instante.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <!-- Botón para pantallas pequeñas (Tablets/Móviles) -->
            <button class="btn btn-primary btn-lg rounded-pill shadow-sm d-md-none fw-bold" data-bs-toggle="modal" data-bs-target="#modalScannerCamara">
                <i class="fas fa-camera me-2"></i> Escanear con Cámara
            </button>
            <!-- Botón para pantallas grandes (PC/Laptops) -->
            <button class="btn btn-outline-primary shadow-sm d-none d-md-inline-block fw-bold" data-bs-toggle="modal" data-bs-target="#modalScannerCamara">
                <i class="fas fa-camera me-2"></i> Activar Cámara Web
            </button>
        </div>
    </div>

    <!-- INPUT OCULTO PARA PISTOLA LÁSER (Hardware) -->
    <!-- Se mantiene fuera del rango visual pero captura todo el texto -->
    <input type="text" id="inputLectorFisico" class="form-control" style="position: absolute; left: -9999px;" autofocus autocomplete="off">

    <!-- CONTENEDOR PRINCIPAL DE RESULTADOS -->
    <div class="card shadow border-0 flex-grow-1 align-items-center justify-content-center bg-white p-4 text-center rounded-3" id="contenedorPrincipalLector">
        
        <!-- ==============================================================
             ESTADO 1: PANTALLA DE ESPERA (CARRUSEL DE OFERTAS ACTIVAS)
             ============================================================== -->
        <div id="estadoEspera" class="w-100" style="max-width: 800px;">
            <h3 class="text-muted mb-4"><i class="fas fa-hand-holding-barcode fa-2x mb-3 d-block text-secondary opacity-75"></i> Pase el producto por el escáner</h3>
            
            <?php
            // Llamamos al controlador para extraer las promociones del día
            $ofertasActivas = ConsultaPreciosControlador::ctrMostrarCarrusel();
            
            if(count($ofertasActivas) > 0):
            ?>
            <div id="carruselOfertas" class="carousel slide shadow-sm rounded-3 border overflow-hidden" data-bs-ride="carousel">
                <div class="carousel-inner bg-light">
                    <?php foreach($ofertasActivas as $key => $oferta): 
                        $activo = ($key == 0) ? "active" : "";
                        
                        // Uso estricto de Getters según el modelo ConsultaPrecios
                        $costoUsdt = $oferta->getCostoUsdt();
                        $margen = $oferta->getMargenGanancia();
                        $precioReg = $costoUsdt * (1 + ($margen / 100));
                        
                        $img = ($oferta->getImagen() != "") ? $oferta->getImagen() : "vista/img/productos/default.png";
                    ?>
                    <div class="carousel-item <?php echo $activo; ?> p-5" data-bs-interval="4000">
                        <div class="row align-items-center">
                            <div class="col-sm-5 text-center mb-4 mb-sm-0">
                                <img src="<?php echo $img; ?>" class="img-fluid rounded shadow-sm bg-white p-2 border" style="max-height: 250px; width: 100%; object-fit: contain;">
                            </div>
                            <div class="col-sm-7 text-start">
                                <span class="badge bg-danger fs-5 mb-2 shadow-sm text-uppercase">¡Oferta -<?php echo $oferta->getPorcentajeDescuento(); ?>%!</span>
                                <h3 class="fw-bold text-dark mb-2"><?php echo $oferta->getNombre(); ?></h3>
                                <h5 class="text-muted text-decoration-line-through mb-1">Antes: $ <?php echo number_format($precioReg, 2, ',', '.'); ?></h5>
                                <h1 class="display-3 fw-bold text-success mb-0">$ <?php echo number_format($oferta->getPrecioOfertaUsdt(), 2, ',', '.'); ?></h1>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Botones de Navegación del Carrusel -->
                <button class="carousel-control-prev" type="button" data-bs-target="#carruselOfertas" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon bg-dark rounded-circle p-3 shadow" aria-hidden="true"></span>
                    <span class="visually-hidden">Anterior</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carruselOfertas" data-bs-slide="next">
                    <span class="carousel-control-next-icon bg-dark rounded-circle p-3 shadow" aria-hidden="true"></span>
                    <span class="visually-hidden">Siguiente</span>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- ==============================================================
             ESTADO 2: LECTURA EXITOSA (Oculto por defecto, inyectado vía JS)
             ============================================================== -->
        <div id="estadoResultado" class="w-100 d-none animate__animated animate__fadeIn" style="max-width: 850px;">
            <div class="row bg-light rounded-4 shadow-lg p-4 border align-items-center">
                
                <div class="col-md-5 text-center mb-4 mb-md-0">
                    <div class="bg-white p-3 rounded-3 shadow-sm border">
                        <img id="resImagen" src="" class="img-fluid rounded" style="max-height: 320px; width: 100%; object-fit: contain;">
                    </div>
                </div>
                
                <div class="col-md-7 text-start px-md-4">
                    <span class="badge bg-dark fs-6 mb-2 py-2 px-3 shadow-sm font-monospace" id="resCodigo"></span>
                    <h2 class="fw-bold text-dark mb-4 lh-base" id="resNombre"></h2>
                    
                    <!-- Tarjeta de Precio Regular -->
                    <div id="bloquePrecioRegular" class="bg-white p-4 rounded-3 border shadow-sm">
                        <p class="text-muted mb-1 fs-6 text-uppercase fw-bold letter-spacing-1">Precio de Venta</p>
                        <h1 class="display-3 fw-bold text-primary mb-0" id="resPrecioUsdt"></h1>
                        <hr class="my-2 text-muted">
                        <h3 class="text-secondary fw-bold mb-0" id="resPrecioBs"></h3>
                    </div>

                    <!-- Tarjeta de Precio Oferta (Intercambiable por JS) -->
                    <div id="bloquePrecioOferta" class="d-none mt-3 p-4 bg-white border border-danger border-2 rounded-3 shadow-sm position-relative overflow-hidden">
                        <div class="position-absolute top-0 end-0 bg-danger text-white px-3 py-1 fw-bold rounded-bottom-start shadow-sm">Promoción</div>
                        <span class="badge bg-danger fs-5 mb-3 shadow-sm" id="resBadgeDescuento"></span>
                        
                        <p class="text-muted text-decoration-line-through mb-1 fs-5" id="resPrecioTachadoUsdt"></p>
                        <h1 class="display-3 fw-bold text-danger mb-0" id="resOfertaUsdt"></h1>
                        <hr class="my-2 text-danger opacity-25">
                        <h3 class="text-secondary fw-bold mb-0" id="resOfertaBs"></h3>
                    </div>
                    
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ==============================================================
     MODAL PARA LECTOR DE CÁMARA (Librería html5-qrcode)
     ============================================================== -->
<div class="modal fade" id="modalScannerCamara" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-camera-retro me-2 text-info"></i> Lector de Código de Barras</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            
            <div class="modal-body p-0 text-center bg-black position-relative">
                <!-- Zona de renderizado de la cámara web/móvil -->
                <div id="lectorCamara" class="w-100" style="min-height: 350px;"></div>
                
                <!-- Guía visual superpuesta -->
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