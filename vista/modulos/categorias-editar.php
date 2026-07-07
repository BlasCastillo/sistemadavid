<?php
if (isset($_GET["idCategoria"])) {
    $categoriaActual = CategoriasControlador::ctrMostrarCategorias($_GET["idCategoria"]);
    if (!$categoriaActual) { echo '<script>window.location = "index.php?ruta=categorias";</script>'; exit; }
} else { echo '<script>window.location = "index.php?ruta=categorias";</script>'; exit; }

$lineasActivas = LineasControlador::ctrMostrarLineas(null, 1);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-edit text-warning me-2"></i> Editar Categoría</h1>
        <a href="index.php?ruta=categorias" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver</a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow border-0 border-top border-warning border-3">
                <div class="card-body p-4">
                    <form id="formEditarCategoria" autocomplete="off">
                        
                        <input type="hidden" name="idCategoriaEditar" value="<?php echo $categoriaActual->getId(); ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Línea Principal (Padre) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-sitemap"></i></span>
                                <select class="form-select" name="idLineaPadreEditar" required>
                                    <option value="" disabled>Seleccione la línea...</option>
                                    <?php foreach($lineasActivas as $linea): ?>
                                        <option value="<?php echo $linea->getId(); ?>" <?php echo ($linea->getId() == $categoriaActual->getLineaId()) ? 'selected' : ''; ?>>
                                            <?php echo $linea->getNombre(); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Nombre de la Categoría <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-tags"></i></span>
                                <input type="text" class="form-control" name="nombreCategoriaEditar" value="<?php echo $categoriaActual->getNombre(); ?>" required>
                            </div>
                        </div>

                        <hr class="mt-2 mb-4">
                        
                        <div class="d-flex justify-content-end">
                            <a href="index.php?ruta=categorias" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-warning text-dark fw-bold px-4"><i class="fas fa-sync-alt me-2"></i> Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>