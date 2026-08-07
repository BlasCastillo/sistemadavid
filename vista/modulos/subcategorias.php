<?php
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$subcategorias = SubcategoriasControlador::ctrMostrarSubcategorias(null, $estadoFiltro);
?>

<div class="container-fluid px-0">
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-list-alt me-2 text-primary"></i> <?php echo $estadoFiltro == 1 ? "Subcategorías de Productos" : "Subcategorías Inactivas"; ?></h4>
            <small class="text-muted d-block mt-1">Subcategorías vinculadas a su categoría principal (Padre).</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=subcategorias&estado=0" class="btn btn-outline-secondary"><i class="fas fa-eye-slash me-1"></i> Ver Inactivas</a>
            <?php else: ?>
                <a href="index.php?ruta=subcategorias" class="btn btn-outline-secondary"><i class="fas fa-eye me-1"></i> Ver Activas</a>
            <?php endif; ?>
            <a href="index.php?ruta=subcategorias-crear" class="btn btn-dodger"><i class="fas fa-plus me-1"></i> Nueva Subcategoría</a>
        </div>
    </div>

    <div class="card-reditus p-4 border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Subcategoría</th>
                            <th>Categoría Principal (Padre)</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subcategorias as $subcat): ?>
                            <tr>
                                <td class="text-muted"><?php echo $subcat->getId(); ?></td>
                                <td class="fw-semibold"><?php echo $subcat->getNombre(); ?></td>
                                <td><span class="badge badge-info"><?php echo $subcat->getCategoriaNombre(); ?></span></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=subcategorias-editar&idSubcategoria=<?php echo $subcat->getId(); ?>" class="btn-action text-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                            <button class="btn-action text-danger btnEliminarSubcategoria" idSubcategoria="<?php echo $subcat->getId(); ?>" nombreSubcategoria="<?php echo $subcat->getNombre(); ?>" title="Desactivar"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                            <button class="btn-action text-success btnActivarSubcategoria" idSubcategoria="<?php echo $subcat->getId(); ?>" nombreSubcategoria="<?php echo $subcat->getNombre(); ?>" title="Reactivar"><i class="fas fa-undo"></i></button>
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
