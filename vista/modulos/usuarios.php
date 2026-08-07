<?php
if ($_SESSION["rol_id"] != 1) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}

$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$usuarios = UsuariosControlador::ctrMostrarUsuarios(null, $estadoFiltro);
$roles = RolesControlador::ctrMostrarRoles();
?>

<div class="container-fluid px-0">
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-users me-2 text-primary"></i> <?php echo $estadoFiltro == 1 ? "Gestión de Usuarios" : "Usuarios Inactivos"; ?></h4>
            <small class="text-muted d-block mt-1">Administra los usuarios, credenciales de acceso y roles del sistema.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=usuarios&estado=0" class="btn btn-outline-secondary">
                    <i class="fas fa-user-slash me-1"></i> Ver Inactivos
                </a>
            <?php else: ?>
                <a href="index.php?ruta=usuarios" class="btn btn-outline-secondary">
                    <i class="fas fa-user-check me-1"></i> Ver Activos
                </a>
            <?php endif; ?>
            <a href="index.php?ruta=usuarios-crear" class="btn btn-dodger">
                <i class="fas fa-user-plus me-1"></i> Nuevo Usuario
            </a>
        </div>
    </div>

    <div class="card-reditus p-4 border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Fecha Registro</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usr): ?>
                            <tr>
                                <td class="text-muted"><?php echo $usr->getId(); ?></td>
                                <td class="fw-semibold"><?php echo $usr->getNombreCompleto(); ?></td>
                                <td><span class="badge badge-info">@<?php echo $usr->getUsuario(); ?></span></td>
                                <td>
                                    <?php
                                    $objetoRol = RolesControlador::ctrMostrarRoles($usr->getRolId());
                                    echo $objetoRol ? '<span class="badge badge-warning">' . $objetoRol->getNombre() . '</span>' : '<span class="text-muted">Sin Rol</span>';
                                    ?>
                                </td>
                                <td class="text-muted"><?php echo date('d/m/Y', strtotime($usr->getCreadoEn())); ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=usuarios-editar&idUsuario=<?php echo $usr->getId(); ?>" class="btn-action text-info" title="Editar Datos"><i class="fas fa-user-edit"></i></a>
                                            <a href="index.php?ruta=usuarios-clave&idUsuario=<?php echo $usr->getId(); ?>" class="btn-action text-warning" title="Cambiar Contraseña"><i class="fas fa-key"></i></a>
                                            <?php if($usr->getId() != $_SESSION["id_usuario"]): ?>
                                                <button class="btn-action text-danger btnEliminarUsuario" idUsuario="<?php echo $usr->getId(); ?>" nombreUsuario="<?php echo $usr->getNombreCompleto(); ?>" title="Desactivar"><i class="fas fa-user-slash"></i></button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="btn-action text-success btnActivarUsuario" idUsuario="<?php echo $usr->getId(); ?>" nombreUsuario="<?php echo $usr->getNombreCompleto(); ?>" title="Reactivar"><i class="fas fa-undo"></i></button>
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
