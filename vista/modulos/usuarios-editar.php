<?php
// Seguridad
if ($_SESSION["rol_id"] != 1) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}

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

$roles = RolesControlador::ctrMostrarRoles();
?>

<div class="container-fluid px-0">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-user-edit text-warning me-2"></i> Editar Usuario</h1>
        <a href="index.php?ruta=usuarios" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver a la lista
        </a>
    </div>

    <div class="card-reditus p-4 shadow mb-4 border-0 border-top border-warning border-3">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-dark">Modificando datos de: <span class="text-primary">@<?php echo $usuarioActual->getUsuario(); ?></span></h6>
        </div>
        <div class="card-body p-4">
            
            <form id="formEditarUsuario" autocomplete="off">
                <input type="hidden" name="editarIdUsuario" value="<?php echo $usuarioActual->getId(); ?>">
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <label class="form-label fw-semibold small">Nombre Completo <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-font"></i></span>
                            <input type="text" class="form-control" name="editarNombre" value="<?php echo $usuarioActual->getNombreCompleto(); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <label class="form-label fw-semibold small">Nombre de Usuario <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user-shield"></i></span>
                            <input type="text" class="form-control" name="editarUsuario" value="<?php echo $usuarioActual->getUsuario(); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <label class="form-label fw-semibold small">Rol del Sistema <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user-tag"></i></span>
                            <select class="form-select" name="editarRol" required>
                                <?php foreach($roles as $rol): ?>
                                    <option value="<?php echo $rol->getId(); ?>" <?php echo ($rol->getId() == $usuarioActual->getRolId()) ? 'selected' : ''; ?>>
                                        <?php echo $rol->getNombre(); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label fw-semibold small text-danger">PIN de Autorización (Opcional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-danger text-danger"><i class="fas fa-lock"></i></span>
                            <input type="password" maxlength="4" class="form-control border-danger" name="editarPin" placeholder="Ej: 1234">
                        </div>
                        <small class="text-muted d-block mt-1">Déjelo en blanco si desea conservar el PIN actual del usuario.</small>
                    </div>
                </div>

                <hr class="mt-4 mb-4">

                <div class="d-flex justify-content-end">
                    <a href="index.php?ruta=usuarios" class="btn btn-outline-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4"><i class="fas fa-sync-alt me-2"></i> Actualizar Cambios</button>
                </div>

            </form>
        </div>
    </div>
</div>
