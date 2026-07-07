<?php
if (isset($_GET["idProducto"])) {
    $productoActual = ProductosControlador::ctrMostrarProductos($_GET["idProducto"]);
    if (!$productoActual) { echo '<script>window.location = "index.php?ruta=productos";</script>'; exit; }
} else { echo '<script>window.location = "index.php?ruta=productos";</script>'; exit; }

$lineasActivas = LineasControlador::ctrMostrarLineas(null, 1);

// Lógica inteligente: Filtramos las categorías y subcategorías que le pertenecen a este producto
// para dejarlas pre-cargadas sin necesidad de usar AJAX al abrir la pantalla.
$categoriasDeLinea = [];
foreach(CategoriasControlador::ctrMostrarCategorias(null, 1) as $cat) {
    if($cat->getLineaId() == $productoActual->getLineaId()) {
        $categoriasDeLinea[] = $cat;
    }
}

$subcatsDeCategoria = [];
foreach(SubcategoriasControlador::ctrMostrarSubcategorias(null, 1) as $sub) {
    if($sub->getCategoriaId() == $productoActual->getCategoriaId()) {
        $subcatsDeCategoria[] = $sub;
    }
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-edit text-warning me-2"></i> Editar Producto</h1>
        <a href="index.php?ruta=productos" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver al Catálogo</a>
    </div>

    <div class="row">
        <div class="col-xl-9 col-lg-10">
            <div class="card shadow border-0 border-top border-warning border-3">
                <div class="card-body p-4">
                    <form id="formEditarProducto" enctype="multipart/form-data" autocomplete="off">
                        
                        <input type="hidden" name="idProductoEditar" value="<?php echo $productoActual->getId(); ?>">
                        <input type="hidden" name="imagenActualProducto" value="<?php echo $productoActual->getImagen(); ?>">
                        <input type="hidden" name="codigoBarrasActual" value="<?php echo $productoActual->getCodigoBarras(); ?>">

                        <div class="row mb-4 bg-light p-3 rounded">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h6 class="text-warning text-dark fw-bold mb-0"><i class="fas fa-sitemap me-2"></i>Clasificación del Producto</h6>
                                <span class="badge bg-secondary"><i class="fas fa-barcode me-1"></i> <?php echo $productoActual->getCodigoBarras(); ?></span>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold small">Línea <span class="text-danger">*</span></label>
                                <select class="form-select select2-dinamico" name="idLineaProductoEditar" id="idLineaProducto" required style="width: 100%;">
                                    <option value="" disabled>Seleccione Línea...</option>
                                    <?php foreach($lineasActivas as $linea): ?>
                                        <option value="<?php echo $linea->getId(); ?>" <?php echo ($linea->getId() == $productoActual->getLineaId()) ? 'selected' : ''; ?>>
                                            <?php echo $linea->getNombre(); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold small">Categoría <span class="text-danger">*</span></label>
                                <select class="form-select select2-dinamico" name="idCategoriaProductoEditar" id="idCategoriaProducto" required style="width: 100%;">
                                    <?php foreach($categoriasDeLinea as $cat): ?>
                                        <option value="<?php echo $cat->getId(); ?>" <?php echo ($cat->getId() == $productoActual->getCategoriaId()) ? 'selected' : ''; ?>>
                                            <?php echo $cat->getNombre(); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold small">Subcategoría <span class="text-danger">*</span></label>
                                <select class="form-select select2-dinamico" name="idSubcategoriaProductoEditar" id="idSubcategoriaProducto" required style="width: 100%;">
                                    <?php foreach($subcatsDeCategoria as $sub): ?>
                                        <option value="<?php echo $sub->getId(); ?>" <?php echo ($sub->getId() == $productoActual->getSubcategoriaId()) ? 'selected' : ''; ?>>
                                            <?php echo $sub->getNombre(); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-7 mb-3">
                                <label class="form-label fw-semibold small">Nombre del Producto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-tag"></i></span>
                                    <input type="text" class="form-control" name="nombreProductoEditar" value="<?php echo htmlspecialchars($productoActual->getNombre()); ?>" required>
                                </div>
                            </div>

                            <div class="col-md-5 mb-3">
                                <label class="form-label fw-semibold small">Reemplazar Fotografía</label>
                                <div class="d-flex align-items-center">
                                    <?php if($productoActual->getImagen()): ?>
                                        <img src="<?php echo $productoActual->getImagen(); ?>" class="img-thumbnail me-2" style="width: 45px; height: 45px; object-fit: cover;">
                                    <?php else: ?>
                                        <img src="vista/img/productos/default.png" class="img-thumbnail me-2" style="width: 45px; height: 45px; object-fit: cover;">
                                    <?php endif; ?>
                                    <input type="file" class="form-control" name="fotoProductoEditar" accept="image/jpeg, image/png">
                                </div>
                                <div class="form-text small text-muted ms-5 mt-1">Déjalo vacío para conservar la foto actual.</div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold small">Margen de Ganancia (%) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">%</span>
                                    <input type="number" step="0.01" min="0" class="form-control" name="margenProductoEditar" value="<?php echo $productoActual->getMargenGanancia(); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-8 mb-3 d-flex align-items-end">
                                <div class="alert alert-info py-2 px-3 mb-0 w-100 border-0 shadow-sm d-flex align-items-center">
                                    <i class="fas fa-info-circle fs-4 me-3"></i>
                                    <small>El <strong>Costo Base</strong> de este producto es manejado automáticamente por el módulo de <strong>Compras</strong> para mantener la integridad del inventario.</small>
                                </div>
                            </div>
                        </div>

                        <hr class="mt-2 mb-4">
                        
                        <div class="d-flex justify-content-end">
                            <a href="index.php?ruta=productos" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-warning text-dark fw-bold px-4"><i class="fas fa-sync-alt me-2"></i> Actualizar Producto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>