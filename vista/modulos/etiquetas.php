<?php
// Validación estricta de sesión
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}
?>

<div class="container-fluid px-0">
    
    <!-- CABECERA DEL MÓDULO -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold"><i class="fas fa-tags text-primary me-2"></i> Generador de Etiquetas</h1>
            <small class="text-muted d-block mt-1">Escanee productos en el piso de ventas para actualizar sus habladores de precio.</small>
        </div>
        <!-- Botones de Cámara Omnicanal -->
        <button class="btn btn-dodger btn-lg rounded-pill shadow-sm d-md-none fw-bold" data-bs-toggle="modal" data-bs-target="#modalScannerCamara">
            <i class="fas fa-camera me-2"></i> Escanear con Cámara
        </button>
        <button class="btn btn-outline-primary shadow-sm d-none d-md-inline-block fw-bold" data-bs-toggle="modal" data-bs-target="#modalScannerCamara">
            <i class="fas fa-camera me-2"></i> Activar Lector Web
        </button>
    </div>

    <div class="row">
        <!-- ==============================================================
             COLUMNA IZQUIERDA: CONFIGURACIÓN E INPUTS
             ============================================================== -->
        <div class="col-lg-4 mb-4">
            <div class="card-reditus p-4 shadow border-0 border-top border-primary border-3 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-cogs me-2"></i>Captura y Formato</h6>
                </div>
                <div class="card-body bg-light">
                    
                    <!-- Lector Físico Visible (Pistola USB/Bluetooth) -->
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-dark">Escáner Físico (Pistola)</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-white border-primary text-primary"><i class="fas fa-barcode"></i></span>
                            <input type="text" id="inputLectorEtiquetas" class="form-control border-primary fw-bold" placeholder="Escanee el código..." autofocus autocomplete="off">
                        </div>
                        <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle"></i> Mantenga el cursor aquí al usar la pistola.</small>
                    </div>

                    <!-- Buscador Manual Fallback -->
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-dark">Búsqueda Manual (Teclado)</label>
                        <select class="form-select select2-dinamico" id="buscadorManualEtiquetas" style="width: 100%;">
                            <option value="" disabled selected>Escriba el nombre del producto...</option>
                        </select>
                    </div>

                    <hr class="my-4">

                    <!-- Configuración del PDF -->
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-print me-2"></i>Configuración de Salida</h6>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-dark">Formato de Etiqueta <span class="text-danger">*</span></label>
                        <select class="form-select border-secondary shadow-sm fw-semibold" id="formatoImpresion" name="formatoImpresion">
                            <option value="zebra_pequena">Rollo Térmico (5.7 x 4.0 cm)</option>
                            <option value="zebra_grande">Rollo Térmico (8.9 x 5.9 cm)</option>
                            <option value="a4_grid">Hoja Convencional (A4 - Cuadrícula)</option>
                        </select>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="button" class="btn btn-danger btn-lg fw-bold shadow-sm" id="btnGenerarPDFEtiquetas">
                            <i class="fas fa-file-pdf me-2"></i> Generar PDF
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- ==============================================================
             COLUMNA DERECHA: LA LISTA DE IMPRESIÓN (CARRITO DOM)
             ============================================================== -->
        <div class="col-lg-8 mb-4">
            <div class="card-reditus p-4 shadow border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-list-ol me-2"></i>Lista de Habladores a Imprimir</h6>
                    <span class="badge bg-primary rounded-pill" id="contadorEtiquetasTotales">0 Etiquetas</span>
                </div>
                <div class="card-body p-0">
                    
                    <div class="table-responsive" style="min-height: 400px; max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" id="tablaEtiquetas">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 15%;">Código</th>
                                    <th style="width: 45%;">Producto</th>
                                    <th class="text-end" style="width: 20%;">Precio Mostrar</th>
                                    <th class="text-center" style="width: 15%;">Cantidad</th>
                                    <th class="text-center" style="width: 5%;"><i class="fas fa-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody id="listaEtiquetasTemporal">
                                <!-- Filas inyectadas por JS -->
                                <tr id="filaVaciaEtiquetas">
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-barcode fa-3x mb-3 opacity-50 d-block"></i>
                                        La lista está vacía. Escanee productos para comenzar.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
                <div id="lectorCamaraEtiquetas" class="w-100" style="min-height: 350px;"></div>
                
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
