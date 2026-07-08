<?php
// Lógica de Filtro Activos/Inactivos
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$clientes = ClientesControlador::ctrMostrarClientes(null, $estadoFiltro);
?>

<div class="container-fluid py-4">
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-users me-2 text-primary"></i> <?php echo $estadoFiltro == 1 ? "Directorio de Clientes" : "Clientes Inactivos"; ?></h4>
            <small class="text-muted d-block mt-1">Gestión del directorio de clientes registrados en el sistema.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=clientes&estado=0" class="btn btn-outline-secondary">
                    <i class="fas fa-eye-slash me-1"></i> Ver Inactivos
                </a>
            <?php else: ?>
                <a href="index.php?ruta=clientes" class="btn btn-outline-secondary">
                    <i class="fas fa-eye me-1"></i> Ver Activos
                </a>
            <?php endif; ?>
            <a href="index.php?ruta=clientes-crear" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i> Nuevo Cliente
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cédula / RIF</th>
                            <th>Nombre Completo / Empresa</th>
                            <th>Teléfono</th>
                            <th>Correo Electrónico</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cli): ?>
                            <tr>
                                <td class="text-muted"><?php echo $cli->getId(); ?></td>
                                <td><span class="badge badge-info"><?php echo $cli->getDocumento(); ?></span></td>
                                <td class="fw-semibold"><?php echo $cli->getNombre(); ?></td>
                                <td><?php echo $cli->getTelefono() ? $cli->getTelefono() : '<span class="text-muted">N/A</span>'; ?></td>
                                <td><?php echo $cli->getEmail() ? $cli->getEmail() : '<span class="text-muted">N/A</span>'; ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=clientes-editar&idCliente=<?php echo $cli->getId(); ?>" class="btn-action text-warning" title="Editar Datos"><i class="fas fa-edit"></i></a>
                                            <button class="btn-action text-danger btnEliminarCliente" idCliente="<?php echo $cli->getId(); ?>" nombreCliente="<?php echo $cli->getNombre(); ?>" title="Desactivar"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                            <button class="btn-action text-success btnActivarCliente" idCliente="<?php echo $cli->getId(); ?>" nombreCliente="<?php echo $cli->getNombre(); ?>" title="Reactivar"><i class="fas fa-undo"></i></button>
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