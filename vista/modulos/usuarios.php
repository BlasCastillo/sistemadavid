<?php
if ($_SESSION["rol_id"] != 1) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}

// Lógica de Filtro: Si en la URL dice &estado=0, mostramos inactivos. Si no, mostramos activos (1)
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;

// Solicitamos los datos con el filtro aplicado
$usuarios = UsuariosControlador::ctrMostrarUsuarios(null, $estadoFiltro);
$roles = RolesControlador::ctrMostrarRoles();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">
            <i class="fas fa-users text-primary me-2"></i> 
            <?php echo $estadoFiltro == 1 ? "Gestión de Usuarios" : "Usuarios Inactivos"; ?>
        </h1>
        
        <div>
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=usuarios&estado=0" class="btn btn-outline-secondary fw-bold shadow-sm me-2">
                    <i class="fas fa-user-slash me-1"></i> Ver Inactivos
                </a>
            <?php else: ?>
                <a href="index.php?ruta=usuarios" class="btn btn-outline-success fw-bold shadow-sm me-2">
                    <i class="fas fa-user-check me-1"></i> Ver Activos
                </a>
            <?php endif; ?>

            <a href="index.php?ruta=usuarios-crear" class="btn btn-primary fw-bold shadow-sm">
                <i class="fas fa-user-plus me-1"></i> Nuevo Usuario
            </a>
        </div>
    </div>

    <div class="card shadow mb-4 border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <tbody>
                        <?php foreach ($usuarios as $usr): ?>
                            <tr>
                                <td><?php echo $usr->getId(); ?></td>
                                <td class="fw-bold text-dark"><?php echo $usr->getNombreCompleto(); ?></td>
                                <td><span class="badge bg-secondary">@<?php echo $usr->getUsuario(); ?></span></td>
                                <td>
                                    <?php 
                                    $objetoRol = RolesControlador::ctrMostrarRoles($usr->getRolId());
                                    echo $objetoRol ? $objetoRol->getNombre() : 'Sin Rol';
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($usr->getCreadoEn())); ?></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=usuarios-editar&idUsuario=<?php echo $usr->getId(); ?>" class="btn btn-sm btn-info text-white" title="Editar Datos">
                                                <i class="fas fa-user-edit"></i>
                                            </a>
                                            <a href="index.php?ruta=usuarios-clave&idUsuario=<?php echo $usr->getId(); ?>" class="btn btn-sm btn-warning text-dark" title="Cambiar Contraseña">
                                                <i class="fas fa-key"></i>
                                            </a>
                                            <?php if($usr->getId() != $_SESSION["id_usuario"]): ?>
                                                <button class="btn btn-sm btn-danger btnEliminarUsuario" idUsuario="<?php echo $usr->getId(); ?>" nombreUsuario="<?php echo $usr->getNombreCompleto(); ?>" title="Desactivar">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            <?php endif; ?>
                                        
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success btnActivarUsuario" idUsuario="<?php echo $usr->getId(); ?>" nombreUsuario="<?php echo $usr->getNombreCompleto(); ?>" title="Reactivar Usuario">
                                                <i class="fas fa-undo"></i> Reactivar
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