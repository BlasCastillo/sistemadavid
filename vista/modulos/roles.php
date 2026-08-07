<?php
if ($_SESSION["rol_id"] != 1) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}

$roles = RolesControlador::ctrMostrarRoles();
?>

<div class="container-fluid px-0">
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-user-tag me-2 text-primary"></i> Gestión de Roles y Permisos</h4>
            <small class="text-muted d-block mt-1">Define los roles de acceso y sus permisos dentro del sistema.</small>
        </div>
        <a href="index.php?ruta=roles-crear" class="btn btn-dodger">
            <i class="fas fa-plus me-1"></i> Nuevo Rol
        </a>
    </div>

    <div class="card-reditus p-4 border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre del Rol</th>
                            <th>Fecha de Creación</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $rol): ?>
                            <tr>
                                <td class="text-muted"><?php echo $rol->getId(); ?></td>
                                <td class="fw-semibold"><?php echo $rol->getNombre(); ?></td>
                                <td class="text-muted"><?php echo date('d/m/Y', strtotime($rol->getCreadoEn())); ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="index.php?ruta=roles-editar&idRol=<?php echo $rol->getId(); ?>" class="btn-action text-warning" title="Configurar Rol y Permisos"><i class="fas fa-cogs"></i></a>
                                        <?php if($rol->getId() != 1 && $rol->getId() != 2): ?>
                                            <button class="btn-action text-danger btnEliminarRol" idRol="<?php echo $rol->getId(); ?>" title="Eliminar Rol"><i class="fas fa-trash"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
