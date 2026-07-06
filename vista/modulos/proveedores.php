<?php
// Lógica de Filtro Activos/Inactivos
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$proveedores = ProveedoresControlador::ctrMostrarProveedores(null, $estadoFiltro);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-building text-primary me-2"></i> <?php echo $estadoFiltro == 1 ? "Directorio de Proveedores" : "Proveedores Inactivos"; ?></h1>
        
        <div>
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=proveedores&estado=0" class="btn btn-outline-secondary fw-bold me-2"><i class="fas fa-eye-slash me-1"></i> Ver Inactivos</a>
            <?php else: ?>
                <a href="index.php?ruta=proveedores" class="btn btn-outline-success fw-bold me-2"><i class="fas fa-eye me-1"></i> Ver Activos</a>
            <?php endif; ?>

            <a href="index.php?ruta=proveedores-crear" class="btn btn-primary fw-bold shadow-sm">
                <i class="fas fa-plus me-1"></i> Nuevo Proveedor
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
                            <th>RIF / Documento</th>
                            <th>Razón Social</th>
                            <th>Teléfono</th>
                            <th>Correo Electrónico</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores as $prov): ?>
                            <tr>
                                <td><?php echo $prov->getId(); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $prov->getDocumento(); ?></span></td>
                                <td class="fw-bold text-dark"><?php echo $prov->getRazonSocial(); ?></td>
                                <td><?php echo $prov->getTelefono() ? $prov->getTelefono() : '<span class="text-muted small">N/A</span>'; ?></td>
                                <td><?php echo $prov->getEmail() ? $prov->getEmail() : '<span class="text-muted small">N/A</span>'; ?></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=proveedores-editar&idProveedor=<?php echo $prov->getId(); ?>" class="btn btn-sm btn-warning text-dark" title="Editar Datos"><i class="fas fa-edit"></i></a>
                                            <button class="btn btn-sm btn-danger btnEliminarProveedor" idProveedor="<?php echo $prov->getId(); ?>" nombreProveedor="<?php echo $prov->getRazonSocial(); ?>" title="Desactivar"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success btnActivarProveedor" idProveedor="<?php echo $prov->getId(); ?>" nombreProveedor="<?php echo $prov->getRazonSocial(); ?>" title="Reactivar"><i class="fas fa-undo"></i> Reactivar</button>
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