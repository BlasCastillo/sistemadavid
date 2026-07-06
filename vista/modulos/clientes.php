<?php
// Lógica de Filtro Activos/Inactivos
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$clientes = ClientesControlador::ctrMostrarClientes(null, $estadoFiltro);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-users text-primary me-2"></i> <?php echo $estadoFiltro == 1 ? "Directorio de Clientes" : "Clientes Inactivos"; ?></h1>
        
        <div>
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=clientes&estado=0" class="btn btn-outline-secondary fw-bold me-2"><i class="fas fa-eye-slash me-1"></i> Ver Inactivos</a>
            <?php else: ?>
                <a href="index.php?ruta=clientes" class="btn btn-outline-success fw-bold me-2"><i class="fas fa-eye me-1"></i> Ver Activos</a>
            <?php endif; ?>

            <a href="index.php?ruta=clientes-crear" class="btn btn-primary fw-bold shadow-sm">
                <i class="fas fa-user-plus me-1"></i> Nuevo Cliente
            </a>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Cédula / RIF</th>
                            <th>Nombre Completo / Empresa</th>
                            <th>Teléfono</th>
                            <th>Correo Electrónico</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cli): ?>
                            <tr>
                                <td><?php echo $cli->getId(); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $cli->getDocumento(); ?></span></td>
                                <td class="fw-bold text-dark"><?php echo $cli->getNombre(); ?></td>
                                <td><?php echo $cli->getTelefono() ? $cli->getTelefono() : '<span class="text-muted small">N/A</span>'; ?></td>
                                <td><?php echo $cli->getEmail() ? $cli->getEmail() : '<span class="text-muted small">N/A</span>'; ?></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=clientes-editar&idCliente=<?php echo $cli->getId(); ?>" class="btn btn-sm btn-warning text-dark" title="Editar Datos"><i class="fas fa-edit"></i></a>
                                            <button class="btn btn-sm btn-danger btnEliminarCliente" idCliente="<?php echo $cli->getId(); ?>" nombreCliente="<?php echo $cli->getNombre(); ?>" title="Desactivar"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success btnActivarCliente" idCliente="<?php echo $cli->getId(); ?>" nombreCliente="<?php echo $cli->getNombre(); ?>" title="Reactivar"><i class="fas fa-undo"></i> Reactivar</button>
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