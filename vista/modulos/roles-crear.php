<?php
// Seguridad: Solo el Gerente General (Rol 1) puede crear roles
if ($_SESSION["rol_id"] != 1) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}
?>

<div class="container-fluid px-0">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-plus-circle text-primary me-2"></i> Crear Nuevo Rol</h1>
        <a href="index.php?ruta=roles" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver a la lista
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card-reditus p-4 shadow mb-4 border-0 border-top border-primary border-3">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-dark">Detalles del Rol Operativo</h6>
                </div>
                <div class="card-body p-4">
                    
                    <form id="formAgregarRol" autocomplete="off">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Nombre del Rol <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-id-badge"></i></span>
                                <input type="text" class="form-control" name="nombreRol" placeholder="Ej: Supervisor de Caja" required>
                            </div>
                        </div>

                        <div class="alert alert-info border-0 shadow-sm mt-3 mb-4 small">
                            <i class="fas fa-info-circle me-1"></i> <strong>Nota de Seguridad:</strong> 
                            Al guardar, este rol nacerá sin acceso a ningún módulo. El sistema lo redirigirá a la lista principal para que proceda a configurarle sus permisos específicos.
                        </div>

                        <hr class="mt-2 mb-4">

                        <div class="d-flex justify-content-end">
                            <a href="index.php?ruta=roles" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-dodger fw-bold px-4"><i class="fas fa-save me-2"></i> Guardar Rol</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
