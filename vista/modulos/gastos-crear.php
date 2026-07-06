<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-plus-circle text-primary me-2"></i> Registrar Egreso</h1>
        <a href="index.php?ruta=gastos" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver</a>
    </div>

    <div class="row"><div class="col-md-8"><div class="card shadow border-0 border-top border-primary border-3"><div class="card-body p-4">
        <form id="formAgregarGasto" autocomplete="off">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-semibold small">Fecha del Gasto <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="fechaGasto" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-semibold small">Clasificación <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipoGasto" required>
                        <option value="Fijo">Gasto Fijo (Alquiler, servicios, nómina)</option>
                        <option value="Variable">Gasto Variable (Mantenimiento, imprevistos)</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 mb-4">
                    <label class="form-label fw-semibold small">Concepto / Descripción <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="conceptoGasto" placeholder="Ej: Pago de servicio de Internet corporativo" required>
                </div>
                <div class="col-md-4 mb-4">
                    <label class="form-label fw-semibold small">Monto Total ($) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0.01" class="form-control" name="montoGasto" placeholder="0.00" required>
                    </div>
                </div>
            </div>
            <hr class="mt-2 mb-4">
            <div class="d-flex justify-content-end">
                <a href="index.php?ruta=gastos" class="btn btn-outline-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary fw-bold px-4"><i class="fas fa-save me-2"></i> Guardar Egreso</button>
            </div>
        </form>
    </div></div></div></div>
</div>