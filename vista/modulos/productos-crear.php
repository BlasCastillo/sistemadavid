<?php
// Solo cargamos las Líneas. Las categorías y subcategorías se cargarán por AJAX.
$lineasActivas = LineasControlador::ctrMostrarLineas(null, 1);
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-box-open text-primary me-2"></i> Registrar Nuevo Producto</h1>
        <a href="index.php?ruta=productos" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver al Catálogo</a>
    </div>

    <div class="row">
        <div class="col-xl-9 col-lg-10">
            <div class="card-reditus p-4 shadow border-0 border-top border-primary border-3">
                <div class="card-body p-4">
                    <form id="formAgregarProducto" enctype="multipart/form-data" autocomplete="off">
                        
                        <div class="row mb-4 bg-light p-3 rounded">
                            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2"><i class="fas fa-sitemap me-2"></i>Clasificación del Producto</h6>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold small">Línea <span class="text-danger">*</span></label>
                                <select class="form-select select2-dinamico" name="idLineaProducto" id="idLineaProducto" required style="width: 100%;">
                                    <option value="">Seleccione Línea...</option>
                                    <?php foreach($lineasActivas as $linea): ?>
                                        <option value="<?php echo $linea->getId(); ?>"><?php echo $linea->getNombre(); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold small">Categoría <span class="text-danger">*</span></label>
                                <select class="form-select select2-dinamico" name="idCategoriaProducto" id="idCategoriaProducto" required disabled style="width: 100%;">
                                    <option value="" disabled selected>Esperando Línea...</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold small">Subcategoría <span class="text-danger">*</span></label>
                                <select class="form-select select2-dinamico" name="idSubcategoriaProducto" id="idSubcategoriaProducto" required disabled style="width: 100%;">
                                    <option value="" disabled selected>Esperando Categoría...</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-semibold small">Nombre del Producto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-tag"></i></span>
                                    <input type="text" class="form-control" name="nombreProducto" placeholder="Ej: Harina de Maíz Precocida 1Kg" required>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold small">Fotografía (Opcional)</label>
                                <input type="file" class="form-control" name="fotoProducto" accept="image/jpeg, image/png" capture="environment">
                                <div class="form-text small text-muted">Formatos: JPG, PNG. Peso máx: 2MB.</div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold small">Costo Nominal Referencial ($) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">$</span>
                                        <input type="number" step="0.0001" min="0" class="form-control" name="costoInicialProducto" value="0.0000" required>
                                    </div>
                                <div class="form-text small text-muted">Deje en 0.00 si ingresará por Compras.</div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold small">Margen de Ganancia (%) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">%</span>
                                    <input type="number" step="0.01" min="0" class="form-control" name="margenProducto" value="20.00" required>
                                </div>
                            </div>
                        </div>

                        <hr class="mt-2 mb-4">
                        
                        <div class="d-flex justify-content-end">
                            <a href="index.php?ruta=productos" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-dodger fw-bold px-4"><i class="fas fa-save me-2"></i> Guardar Producto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
