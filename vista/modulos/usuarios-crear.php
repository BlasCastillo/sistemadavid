<?php
// Seguridad: Solo el Gerente General (Rol 1) puede crear usuarios
if ($_SESSION["rol_id"] != 1) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}

// Traemos los roles para llenar el select dinámicamente
$roles = RolesControlador::ctrMostrarRoles();
?>

<div class="container-fluid px-0">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-user-plus text-primary me-2"></i> Registrar Nuevo Usuario</h1>
        <a href="index.php?ruta=usuarios" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver a la lista
        </a>
    </div>

    <div class="card-reditus p-4 shadow mb-4 border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">Complete los datos del personal</h6>
        </div>
        <div class="card-body p-4">
            
            <form id="formAgregarUsuario" autocomplete="off">
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-semibold small">Nombre Completo <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-font"></i></span>
                            <input type="text" class="form-control" name="nuevoNombre" placeholder="Ej: Juan Pérez" required>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-semibold small">Nombre de Usuario (Para acceder) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user-shield"></i></span>
                            <input type="text" class="form-control" name="nuevoUsuario" placeholder="Ej: juan_perez" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-semibold small">Contraseña de Ingreso <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" name="nuevaClave" placeholder="Mínimo letras y números" required>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-semibold small">Rol del Sistema <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user-tag"></i></span>
                            <select class="form-select" name="nuevoRol" required>
                                <option value="">Seleccione un rol...</option>
                                <?php foreach($roles as $rol): ?>
                                    <option value="<?php echo $rol->getId(); ?>"><?php echo $rol->getNombre(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-semibold small text-danger">PIN de Autorización (Opcional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-danger text-danger"><i class="fas fa-lock"></i></span>
                            <input type="password" maxlength="4" class="form-control border-danger" name="nuevoPin" placeholder="Ej: 1234 (4 dígitos numéricos)">
                        </div>
                        <small class="text-muted d-block mt-1">Este PIN servirá para que Gerentes/Encargados aprueben mermas o descuentos rápidamente en el módulo de caja.</small>
                    </div>
                </div>

                <hr class="mt-4 mb-4">

                <div class="d-flex justify-content-end">
                    <a href="index.php?ruta=usuarios" class="btn btn-outline-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-dodger fw-bold px-4"><i class="fas fa-save me-2"></i> Guardar Usuario</button>
                </div>

            </form>
        </div>
    </div>
</div>
