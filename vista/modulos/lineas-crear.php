<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-plus-circle text-primary me-2"></i> Crear Línea</h1>
        <a href="index.php?ruta=lineas" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Volver</a>
    </div>
    <div class="row"><div class="col-md-6"><div class="card-reditus p-4 shadow border-0 border-top border-primary border-3"><div class="card-body p-4">
        <form id="formAgregarLinea" autocomplete="off">
            <div class="mb-4">
                <label class="form-label fw-semibold small">Nombre de la Línea <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-tag"></i></span>
                    <input type="text" class="form-control" name="nombreLinea" placeholder="Ej: Derivados Lácteos" required>
                </div>
            </div>
            <div class="d-flex justify-content-end">
                <a href="index.php?ruta=lineas" class="btn btn-outline-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-dodger fw-bold px-4"><i class="fas fa-save me-2"></i> Guardar</button>
            </div>
        </form>
    </div></div></div></div>
</div>
