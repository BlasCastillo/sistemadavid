<?php
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$Lineas = LineasControlador::ctrMostrarLineas(null, $estadoFiltro);
?>

<div class="container-fluid px-0">
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-tags me-2 text-primary"></i> <?php echo $estadoFiltro == 1 ? "Líneas de Productos" : "Líneas Inactivas"; ?></h4>
            <small class="text-muted d-block mt-1">Las líneas representan la división comercial más amplia del catálogo.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=lineas&estado=0" class="btn btn-outline-secondary"><i class="fas fa-eye-slash me-1"></i> Ver Inactivas</a>
            <?php else: ?>
                <a href="index.php?ruta=lineas" class="btn btn-outline-secondary"><i class="fas fa-eye me-1"></i> Ver Activas</a>
            <?php endif; ?>
            <a href="index.php?ruta=lineas-crear" class="btn btn-dodger"><i class="fas fa-plus me-1"></i> Nueva Línea</a>
        </div>
    </div>

    <div class="card-reditus p-4 border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de la Línea</th>
                            <th>Fecha de Registro</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($Lineas as $cat): ?>
                            <tr>
                                <td class="text-muted"><?php echo $cat->getId(); ?></td>
                                <td class="fw-semibold"><?php echo $cat->getNombre(); ?></td>
                                <td class="text-muted"><?php echo date('d/m/Y', strtotime($cat->getCreadoEn())); ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=lineas-editar&idLinea=<?php echo $cat->getId(); ?>" class="btn-action text-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                            <button class="btn-action text-danger btnEliminarLinea" idLinea="<?php echo $cat->getId(); ?>" nombreLinea="<?php echo $cat->getNombre(); ?>" title="Desactivar"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                            <button class="btn-action text-success btnActivarLinea" idLinea="<?php echo $cat->getId(); ?>" nombreLinea="<?php echo $cat->getNombre(); ?>" title="Reactivar"><i class="fas fa-undo"></i></button>
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
