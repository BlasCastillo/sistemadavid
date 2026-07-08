<?php
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$categorias = CategoriasControlador::ctrMostrarCategorias(null, $estadoFiltro);
?>

<div class="container-fluid py-4">
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-list me-2 text-primary"></i> <?php echo $estadoFiltro == 1 ? "Categorías de Productos" : "Categorías Inactivas"; ?></h4>
            <small class="text-muted d-block mt-1">Organiza los productos mediante una jerarquía de categorías.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=categorias&estado=0" class="btn btn-outline-secondary"><i class="fas fa-eye-slash me-1"></i> Ver Inactivas</a>
            <?php else: ?>
                <a href="index.php?ruta=categorias" class="btn btn-outline-secondary"><i class="fas fa-eye me-1"></i> Ver Activas</a>
            <?php endif; ?>
            <a href="index.php?ruta=categorias-crear" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Nueva Categoría</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de la Categoría</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $cat): ?>
                            <tr>
                                <td class="text-muted"><?php echo $cat->getId(); ?></td>
                                <td class="fw-semibold"><?php echo $cat->getNombre(); ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=categorias-editar&idCategoria=<?php echo $cat->getId(); ?>" class="btn-action text-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                            <button class="btn-action text-danger btnEliminarCategoria" idCategoria="<?php echo $cat->getId(); ?>" nombreCategoria="<?php echo $cat->getNombre(); ?>" title="Desactivar"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                            <button class="btn-action text-success btnActivarCategoria" idCategoria="<?php echo $cat->getId(); ?>" nombreCategoria="<?php echo $cat->getNombre(); ?>" title="Reactivar"><i class="fas fa-undo"></i></button>
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