<?php
if (isset($_GET["idCliente"])) {
    $clienteActual = ClientesControlador::ctrMostrarClientes($_GET["idCliente"]);
    if (!$clienteActual) { echo '<script>window.location = "index.php?ruta=clientes";</script>'; exit; }
} else { echo '<script>window.location = "index.php?ruta=clientes";</script>'; exit; }
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-user-edit text-warning me-2"></i> Editar Cliente</h1>
        <a href="index.php?ruta=clientes" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver al directorio</a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card-reditus p-4 shadow border-0 border-top border-warning border-3">
                <div class="card-body p-4">
                    <form id="formEditarCliente" autocomplete="off">
                        
                        <input type="hidden" name="idClienteEditar" value="<?php echo $clienteActual->getId(); ?>">
                        
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label class="form-label fw-semibold small">Cédula / RIF <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" name="editarDocCliente" value="<?php echo $clienteActual->getDocumento(); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-8 mb-4">
                                <label class="form-label fw-semibold small">Nombre Completo / Empresa <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="editarNombreCliente" value="<?php echo htmlspecialchars($clienteActual->getNombre()); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold small">Teléfono de Contacto</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-phone"></i></span>
                                    <input type="text" class="form-control" name="editarTelefonoCliente" value="<?php echo $clienteActual->getTelefono(); ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold small">Correo Electrónico</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="editarEmailCliente" value="<?php echo $clienteActual->getEmail(); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Dirección Física</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-map-marker-alt"></i></span>
                                <textarea class="form-control" name="editarDireccionCliente" rows="2"><?php echo htmlspecialchars($clienteActual->getDireccion()); ?></textarea>
                            </div>
                        </div>

                        <hr class="mt-2 mb-4">
                        
                        <div class="d-flex justify-content-end">
                            <a href="index.php?ruta=clientes" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-warning text-dark fw-bold px-4"><i class="fas fa-sync-alt me-2"></i> Actualizar Datos</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
