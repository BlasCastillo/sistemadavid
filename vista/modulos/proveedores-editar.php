<?php
if (isset($_GET["idProveedor"])) {
    $proveedorActual = ProveedoresControlador::ctrMostrarProveedores($_GET["idProveedor"]);
    if (!$proveedorActual) { echo '<script>window.location = "index.php?ruta=proveedores";</script>'; exit; }
} else { echo '<script>window.location = "index.php?ruta=proveedores";</script>'; exit; }
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-edit text-warning me-2"></i> Editar Proveedor</h1>
        <a href="index.php?ruta=proveedores" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver al directorio</a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card-reditus p-4 shadow border-0 border-top border-warning border-3">
                <div class="card-body p-4">
                    <form id="formEditarProveedor" autocomplete="off">
                        
                        <input type="hidden" name="idProveedorEditar" value="<?php echo $proveedorActual->getId(); ?>">
                        
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label class="form-label fw-semibold small">RIF / Documento <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" name="editarDocProveedor" value="<?php echo $proveedorActual->getDocumento(); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-8 mb-4">
                                <label class="form-label fw-semibold small">Razón Social <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-building"></i></span>
                                    <input type="text" class="form-control" name="editarRazonSocial" value="<?php echo htmlspecialchars($proveedorActual->getRazonSocial()); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold small">Teléfono de Contacto</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-phone"></i></span>
                                    <input type="text" class="form-control" name="editarTelefonoProveedor" value="<?php echo $proveedorActual->getTelefono(); ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold small">Correo Electrónico</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="editarEmailProveedor" value="<?php echo $proveedorActual->getEmail(); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Dirección Física</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-map-marker-alt"></i></span>
                                <textarea class="form-control" name="editarDireccionProveedor" rows="2"><?php echo htmlspecialchars($proveedorActual->getDireccion()); ?></textarea>
                            </div>
                        </div>

                        <hr class="mt-2 mb-4">
                        
                        <div class="d-flex justify-content-end">
                            <a href="index.php?ruta=proveedores" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-warning text-dark fw-bold px-4"><i class="fas fa-sync-alt me-2"></i> Actualizar Datos</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
