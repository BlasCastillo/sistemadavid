<?php
if (isset($_GET["idLinea"])) {
    $LineaActual = LineasControlador::ctrMostrarLineas($_GET["idLinea"]);
    if (!$LineaActual) { echo '<script>window.location = "index.php?ruta=Lineas";</script>'; exit; }
} else { echo '<script>window.location = "index.php?ruta=Lineas";</script>'; exit; }
?>
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-edit text-warning me-2"></i> Editar Categoría</h1>
        <a href="index.php?ruta=Lineas" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Volver</a>
    </div>
    <div class="row"><div class="col-md-6"><div class="card-reditus p-4 shadow border-0 border-top border-warning border-3"><div class="card-body p-4">
        <form id="formEditarLinea" autocomplete="off">
            <input type="hidden" name="idLineaEditar" value="<?php echo $LineaActual->getId(); ?>">
            <div class="mb-4">
                <label class="form-label fw-semibold small">Nombre de la Categoría <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-tag"></i></span>
                    <input type="text" class="form-control" name="nombreLineaEditar" value="<?php echo $LineaActual->getNombre(); ?>" required>
                </div>
            </div>
            <div class="d-flex justify-content-end">
                <a href="index.php?ruta=Lineas" class="btn btn-outline-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-warning text-dark fw-bold px-4"><i class="fas fa-sync-alt me-2"></i> Actualizar</button>
            </div>
        </form>
    </div></div></div></div>
</div>
