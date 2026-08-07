<?php
if (isset($_GET["idSubcategoria"])) {
    $subcategoriaActual = SubcategoriasControlador::ctrMostrarSubcategorias($_GET["idSubcategoria"]);
    if (!$subcategoriaActual) { echo '<script>window.location = "index.php?ruta=subcategorias";</script>'; exit; }
} else { echo '<script>window.location = "index.php?ruta=subcategorias";</script>'; exit; }

$categoriasActivas = CategoriasControlador::ctrMostrarCategorias(null, 1);
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-edit text-warning me-2"></i> Editar Subcategoría</h1>
        <a href="index.php?ruta=subcategorias" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver</a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card-reditus p-4 shadow border-0 border-top border-warning border-3">
                <div class="card-body p-4">
                    <form id="formEditarSubcategoria" autocomplete="off">
                        
                        <input type="hidden" name="idSubcategoriaEditar" value="<?php echo $subcategoriaActual->getId(); ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Categoría Principal (Padre) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-list"></i></span>
                                <select class="form-select" name="idCategoriaPadreEditar" required>
                                    <option value="" disabled>Seleccione la categoría...</option>
                                    <?php foreach($categoriasActivas as $cat): ?>
                                        <option value="<?php echo $cat->getId(); ?>" <?php echo ($cat->getId() == $subcategoriaActual->getCategoriaId()) ? 'selected' : ''; ?>>
                                            <?php echo $cat->getNombre(); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Nombre de la Subcategoría <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" name="nombreSubcategoriaEditar" value="<?php echo $subcategoriaActual->getNombre(); ?>" required>
                            </div>
                        </div>

                        <hr class="mt-2 mb-4">
                        
                        <div class="d-flex justify-content-end">
                            <a href="index.php?ruta=subcategorias" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-warning text-dark fw-bold px-4"><i class="fas fa-sync-alt me-2"></i> Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
