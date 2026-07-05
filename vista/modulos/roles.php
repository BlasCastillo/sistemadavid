<?php
// Seguridad: Solo el Gerente General (Rol 1) puede gestionar roles
if ($_SESSION["rol_id"] != 1) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}

// Obtenemos la lista de roles directamente del controlador
$roles = RolesControlador::ctrMostrarRoles();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-user-tag text-primary me-2"></i> Gestión de Roles y Permisos</h1>
        
        <a href="index.php?ruta=roles-crear" class="btn btn-primary fw-bold shadow-sm">
            <i class="fas fa-plus me-1"></i> Nuevo Rol
        </a>
    </div>

    <div class="card shadow mb-4 border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Nombre del Rol</th>
                            <th>Fecha de Creación</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $rol): ?>
                            <tr>
                                <td><?php echo $rol->getId(); ?></td>
                                <td class="fw-bold text-dark"><?php echo $rol->getNombre(); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($rol->getCreadoEn())); ?></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        
                                        <a href="index.php?ruta=roles-editar&idRol=<?php echo $rol->getId(); ?>" class="btn btn-sm btn-warning text-dark" title="Configurar Rol y Permisos">
                                            <i class="fas fa-cogs"></i> Configurar
                                        </a>
                                        
                                        <?php if($rol->getId() != 1 && $rol->getId() != 2): ?>
                                            <button class="btn btn-sm btn-danger btnEliminarRol" idRol="<?php echo $rol->getId(); ?>" title="Eliminar Rol">
                                                <i class="fas fa-trash"></i>
                                            </button>
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