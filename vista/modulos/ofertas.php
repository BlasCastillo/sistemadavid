<?php
// Protección de ruta
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    return;
}
?>

<div class="container-fluid py-4">
    <!-- CABECERA -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold"><i class="fas fa-tags text-danger me-2"></i> Ofertas y Promociones</h1>
            <p class="text-muted mb-0">Gestione descuentos, programe promociones y administre el carrusel de precios.</p>
        </div>
        <button class="btn btn-danger fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarOferta">
            <i class="fas fa-plus-circle me-1"></i> Programar Nueva Oferta
        </button>
    </div>

    <!-- TABLA DE DATOS -->
    <div class="card shadow border-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover table-striped tablaOfertas align-middle text-start w-100">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 5%">#</th>
                            <th>Producto</th>
                            <th class="text-center">Vigencia</th>
                            <th class="text-end">Precio Regular</th>
                            <th class="text-center">Descuento</th>
                            <th class="text-end text-danger">Precio Oferta</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width: 10%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                       
                        $ofertas = OfertasControlador::ctrMostrarOfertas();
                        
                        foreach ($ofertas as $key => $oferta) {
                            
                            // Matemáticas para mostrar el precio regular original (Propiedades públicas)
                            $precioRegular = $oferta->costo_usdt * (1 + ($oferta->margen_ganancia / 100));

                            // Badges de Estado
                            if ($oferta->estado_dinamico == 1) {
                                $estadoHtml = '<span class="badge bg-success shadow-sm"><i class="fas fa-play-circle me-1"></i>Activa</span>';
                            } else if ($oferta->estado_dinamico == 2) {
                                $estadoHtml = '<span class="badge bg-warning text-dark shadow-sm"><i class="fas fa-clock me-1"></i>Programada</span>';
                            } else {
                                $estadoHtml = '<span class="badge bg-secondary shadow-sm"><i class="fas fa-times-circle me-1"></i>Vencida</span>';
                            }

                            // AQUI ESTÁ LA CORRECCIÓN: Uso de los Getters para propiedades privadas
                            $fechaInicio = date("d/m/Y", strtotime($oferta->getFechaInicio()));
                            $fechaFin = date("d/m/Y", strtotime($oferta->getFechaFin()));

                            echo '<tr>
                                    <td>'.($key+1).'</td>
                                    <td>
                                        <small class="d-block text-muted">'.$oferta->codigo_barras.'</small>
                                        <span class="fw-bold text-dark">'.$oferta->producto_nombre.'</span>
                                    </td>
                                    <td class="text-center small fw-semibold text-muted">
                                        '.$fechaInicio.' <i class="fas fa-arrow-right mx-1 text-secondary"></i> '.$fechaFin.'
                                    </td>
                                    <td class="text-end text-muted text-decoration-line-through">
                                        $ '.number_format($precioRegular, 4, ',', '.').'
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger fs-6">- '.$oferta->getPorcentajeDescuento().'%</span>
                                    </td>
                                    <td class="text-end fw-bold text-danger fs-5">
                                        $ '.number_format($oferta->getPrecioOfertaUsdt(), 4, ',', '.').'
                                    </td>
                                    <td class="text-center">'.$estadoHtml.'</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-danger btnEliminarOferta" idOferta="'.$oferta->getId().'" title="Anular Oferta">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==============================================================
     MODAL PROGRAMAR OFERTA
     ============================================================== -->
<div class="modal fade" id="modalAgregarOferta" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            
            <form id="formCrearOferta" autocomplete="off">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i> Configurar Promoción</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">1. Seleccione el Producto <span class="text-danger">*</span></label>
                        <select class="form-select w-100" id="buscadorProductoOferta" name="idProductoOferta" required>
                            <!-- AJAX Select2 lo llena -->
                        </select>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Precio Regular Actual (USDT)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-dollar-sign"></i></span>
                                <input type="text" class="form-control bg-white fw-bold text-secondary" id="precioRegularOferta" readonly placeholder="0.0000">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-danger">Porcentaje de Descuento (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.01" max="99.99" class="form-control border-danger fw-bold" id="porcentajeOferta" name="porcentajeOferta" placeholder="Ej: 15.50" required>
                                <span class="input-group-text bg-danger text-white"><i class="fas fa-percent"></i></span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-success">Precio Final de Oferta (USDT)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white"><i class="fas fa-tag"></i></span>
                                <input type="number" step="0.0001" class="form-control bg-white fw-bold text-success" id="precioFinalOferta" name="precioFinalOferta" readonly required>
                            </div>
                        </div>
                    </div>

                    <div class="row bg-white p-3 border rounded">
                        <label class="form-label fw-bold text-dark mb-3">2. Rango de Vigencia <span class="text-danger">*</span></label>
                        
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label small fw-semibold text-muted">Fecha de Inicio</label>
                            <input type="date" class="form-control" name="fechaInicioOferta" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Fecha de Culminación</label>
                            <input type="date" class="form-control" name="fechaFinOferta" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                </div>

                <div class="modal-footer d-flex justify-content-between bg-white">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm">
                        <i class="fas fa-save me-2"></i> Guardar Promoción
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>