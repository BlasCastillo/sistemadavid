<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-user-plus text-primary me-2"></i> Registrar Cliente</h1>
        <a href="index.php?ruta=clientes" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver al directorio</a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow border-0 border-top border-primary border-3">
                <div class="card-body p-4">
                    <form id="formAgregarCliente" autocomplete="off">
                        
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label class="form-label fw-semibold small">Cédula / RIF <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" name="nuevoDocCliente" placeholder="Ej: V-12345678" required>
                                </div>
                            </div>
                            <div class="col-md-8 mb-4">
                                <label class="form-label fw-semibold small">Nombre Completo / Empresa <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="nuevoNombreCliente" placeholder="Ej: Juan Pérez" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold small">Teléfono de Contacto</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-phone"></i></span>
                                    <input type="text" class="form-control" name="nuevoTelefonoCliente" placeholder="Ej: 0414-1234567">
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold small">Correo Electrónico</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="nuevoEmailCliente" placeholder="Ej: juan@email.com">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Dirección Física</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-map-marker-alt"></i></span>
                                <textarea class="form-control" name="nuevaDireccionCliente" rows="2" placeholder="Dirección completa..."></textarea>
                            </div>
                        </div>

                        <hr class="mt-2 mb-4">
                        
                        <div class="d-flex justify-content-end">
                            <a href="index.php?ruta=clientes" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="fas fa-save me-2"></i> Guardar Cliente</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>