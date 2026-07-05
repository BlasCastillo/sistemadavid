<?php
// Seguridad
if ($_SESSION["rol_id"] != 1) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}

// Validamos el ID
if (isset($_GET["idUsuario"])) {
    $usuarioActual = UsuariosControlador::ctrMostrarUsuarios($_GET["idUsuario"]);
    if (!$usuarioActual) {
        echo '<script>window.location = "index.php?ruta=usuarios";</script>';
        exit;
    }
} else {
    echo '<script>window.location = "index.php?ruta=usuarios";</script>';
    exit;
}
?>

<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-key text-warning me-2"></i> Actualizar Credenciales</h1>
        <a href="index.php?ruta=usuarios" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver a la lista
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow mb-4 border-0 border-top border-warning border-3">
                <div class="card-header bg-white py-3 text-center">
                    <h6 class="m-0 fw-bold text-dark">Nueva contraseña para: <span class="text-primary">@<?php echo $usuarioActual->getUsuario(); ?></span></h6>
                </div>
                <div class="card-body p-4">
                    
                    <form id="formEditarClave" autocomplete="off">
                        
                        <input type="hidden" name="editarClaveId" value="<?php echo $usuarioActual->getId(); ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Escriba la nueva contraseña <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="nuevaClaveSegura" name="nuevaClaveSegura" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Confirme la nueva contraseña <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-check-double"></i></span>
                                <input type="password" class="form-control" id="confirmarClave" required>
                            </div>
                            <small class="text-danger d-none mt-1 fw-bold" id="errorClave">Las contraseñas no coinciden.</small>
                        </div>

                        <hr class="mt-4 mb-4">

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning text-dark fw-bold btn-lg"><i class="fas fa-save me-2"></i> Guardar Contraseña</button>
                            <a href="index.php?ruta=usuarios" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>