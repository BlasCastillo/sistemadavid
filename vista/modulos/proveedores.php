<?php
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$proveedores = ProveedoresControlador::ctrMostrarProveedores(null, $estadoFiltro);
?>

<div class="container-fluid py-4">
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-building me-2 text-primary"></i> <?php echo $estadoFiltro == 1 ? "Directorio de Proveedores" : "Proveedores Inactivos"; ?></h4>
            <small class="text-muted d-block mt-1">Gestiona el directorio de empresas y personas que abastecen el negocio.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=proveedores&estado=0" class="btn btn-outline-secondary"><i class="fas fa-eye-slash me-1"></i> Ver Inactivos</a>
            <?php else: ?>
                <a href="index.php?ruta=proveedores" class="btn btn-outline-secondary"><i class="fas fa-eye me-1"></i> Ver Activos</a>
            <?php endif; ?>
            <a href="index.php?ruta=proveedores-crear" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Nuevo Proveedor</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>RIF / Documento</th>
                            <th>Razón Social</th>
                            <th>Teléfono</th>
                            <th>Correo Electrónico</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores as $prov): ?>
                            <tr>
                                <td class="text-muted"><?php echo $prov->getId(); ?></td>
                                <td><span class="badge badge-info"><?php echo $prov->getDocumento(); ?></span></td>
                                <td class="fw-semibold"><?php echo $prov->getRazonSocial(); ?></td>
                                <td><?php echo $prov->getTelefono() ? $prov->getTelefono() : '<span class="text-muted">N/A</span>'; ?></td>
                                <td><?php echo $prov->getEmail() ? $prov->getEmail() : '<span class="text-muted">N/A</span>'; ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=proveedores-editar&idProveedor=<?php echo $prov->getId(); ?>" class="btn-action text-warning" title="Editar Datos"><i class="fas fa-edit"></i></a>
                                            <button class="btn-action text-danger btnEliminarProveedor" idProveedor="<?php echo $prov->getId(); ?>" nombreProveedor="<?php echo $prov->getRazonSocial(); ?>" title="Desactivar"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                            <button class="btn-action text-success btnActivarProveedor" idProveedor="<?php echo $prov->getId(); ?>" nombreProveedor="<?php echo $prov->getRazonSocial(); ?>" title="Reactivar"><i class="fas fa-undo"></i></button>
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