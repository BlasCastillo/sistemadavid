<?php
// Seguridad
if ($_SESSION["rol_id"] != 1) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}

// Validamos que venga un ID por la URL
if (isset($_GET["idRol"])) {
    $rolActual = RolesControlador::ctrMostrarRoles($_GET["idRol"]);
    if (!$rolActual) {
        echo '<script>window.location = "index.php?ruta=roles";</script>';
        exit;
    }
} else {
    echo '<script>window.location = "index.php?ruta=roles";</script>';
    exit;
}

// MAGIA PHP: Decodificamos el JSON de la BD a un Array real para encender los interruptores
$permisos = json_decode($rolActual->getPermisos() ?? '[]', true);
if (!is_array($permisos)) { $permisos = []; }
?>

<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-cogs text-warning me-2"></i> Configurar Rol</h1>
        <a href="index.php?ruta=roles" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver a la lista
        </a>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow border-0 border-top border-warning border-3 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-dark">Nombre del Rol</h6>
                </div>
                <div class="card-body">
                    <form id="formEditarRol" autocomplete="off">
                        <input type="hidden" name="idRolEditar" value="<?php echo $rolActual->getId(); ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Identificador del Cargo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-id-badge"></i></span>
                                <input type="text" class="form-control" name="nombreRolEditar" value="<?php echo $rolActual->getNombre(); ?>" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning text-dark fw-bold w-100"><i class="fas fa-sync-alt me-2"></i> Actualizar Nombre</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card shadow border-0 border-top border-info border-3 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-info">Permisos Dinámicos del Sistema</h6>
                    <span class="badge bg-secondary">Módulo JSON</span>
                </div>
                <div class="card-body">
                    
                    <form id="formPermisosRol">
                        <input type="hidden" name="rolIdPermisos" value="<?php echo $rolActual->getId(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 border-end">
                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-desktop me-2"></i>Accesos al Menú</h6>
                                
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="permisosActivos[]" value="ver_dashboard" id="chk_dashboard" <?php echo in_array("ver_dashboard", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="chk_dashboard">Dashboard (Gráficos y Datos)</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="permisosActivos[]" value="ver_configuracion" id="chk_config" <?php echo in_array("ver_configuracion", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="chk_config">Configuración (Tasas y Roles)</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="permisosActivos[]" value="ver_archivo" id="chk_archivo" <?php echo in_array("ver_archivo", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="chk_archivo">Archivo Maestro (Inventario)</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="permisosActivos[]" value="ver_compras" id="chk_compras" <?php echo in_array("ver_compras", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="chk_compras">Módulo de Compras</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="permisosActivos[]" value="ver_clientes" id="chk_clientes" <?php echo in_array("ver_clientes", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="chk_clientes">Directorio de Clientes</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="permisosActivos[]" value="ver_reportes" id="chk_reportes" <?php echo in_array("ver_reportes", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="chk_reportes">Reportes Financieros</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="permisosActivos[]" value="ver_gastos" id="chk_gastos" <?php echo in_array("ver_gastos", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="chk_gastos">Gastos Operativos (Egresos)</label>
                                </div>    
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="permisosActivos[]" value="ver_procesos" id="chk_procesos" <?php echo in_array("ver_procesos", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="chk_procesos">Procesos de Ventas / Caja</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="fw-bold text-danger border-bottom pb-2 mb-3"><i class="fas fa-shield-alt me-2"></i>Operaciones Sensibles</h6>
                                
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input border-danger" type="checkbox" name="permisosActivos[]" value="forzar_tasas" id="chk_tasas" <?php echo in_array("forzar_tasas", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-danger" for="chk_tasas">Forzar Tasa de Cambio Manual</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input border-danger" type="checkbox" name="permisosActivos[]" value="crear_usuarios" id="chk_usuarios" <?php echo in_array("crear_usuarios", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-danger" for="chk_usuarios">Crear y Editar Usuarios</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input border-danger" type="checkbox" name="permisosActivos[]" value="auditar_cierres" id="chk_auditar" <?php echo in_array("auditar_cierres", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-danger" for="chk_auditar">Auditar Cierres Ciegos</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input border-danger" type="checkbox" name="permisosActivos[]" value="aprobar_descuentos" id="chk_desc" <?php echo in_array("aprobar_descuentos", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-danger" for="chk_desc">Aprobar Descuentos Especiales</label>
                                </div>
                                
                                <div class="form-check form-switch mt-4 pt-3 border-top">
                                    <input class="form-check-input bg-dark border-dark" type="checkbox" name="permisosActivos[]" value="all" id="chk_all" <?php echo in_array("all", $permisos) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold text-dark" for="chk_all">Acceso Total (Modo Dios)</label>
                                </div>
                            </div>
                        </div>

                        <hr class="mt-4 mb-4">
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-info text-white fw-bold px-4"><i class="fas fa-save me-2"></i> Guardar Permisos</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>