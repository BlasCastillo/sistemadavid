<?php
// Consultamos las categorías ACTIVAS (estado 1) para llenar el select
$categoriasActivas = CategoriasControlador::ctrMostrarCategorias(null, 1);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-plus-circle text-primary me-2"></i> Crear Subcategoría</h1>
        <a href="index.php?ruta=subcategorias" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver</a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow border-0 border-top border-primary border-3">
                <div class="card-body p-4">
                    <form id="formAgregarSubcategoria" autocomplete="off">
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Categoría Principal (Padre) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-list"></i></span>
                                <select class="form-select" name="idCategoriaPadre" required>
                                    <option value="" disabled selected>Seleccione la categoría...</option>
                                    <?php foreach($categoriasActivas as $cat): ?>
                                        <option value="<?php echo $cat->getId(); ?>"><?php echo $cat->getNombre(); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Nombre de la Subcategoría <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" name="nombreSubcategoria" placeholder="Ej: Quesos Blancos" required>
                            </div>
                        </div>

                        <hr class="mt-2 mb-4">
                        
                        <div class="d-flex justify-content-end">
                            <a href="index.php?ruta=subcategorias" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="fas fa-save me-2"></i> Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>