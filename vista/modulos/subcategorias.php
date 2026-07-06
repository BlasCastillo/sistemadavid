<?php
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$subcategorias = SubcategoriasControlador::ctrMostrarSubcategorias(null, $estadoFiltro);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-list-alt text-primary me-2"></i> <?php echo $estadoFiltro == 1 ? "Subcategorías de Productos" : "Subcategorías Inactivas"; ?></h1>
        
        <div>
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=subcategorias&estado=0" class="btn btn-outline-secondary fw-bold me-2"><i class="fas fa-eye-slash me-1"></i> Ver Inactivas</a>
            <?php else: ?>
                <a href="index.php?ruta=subcategorias" class="btn btn-outline-success fw-bold me-2"><i class="fas fa-eye me-1"></i> Ver Activas</a>
            <?php endif; ?>

            <a href="index.php?ruta=subcategorias-crear" class="btn btn-primary fw-bold shadow-sm">
                <i class="fas fa-plus me-1"></i> Nueva Subcategoría
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
                            <th>Nombre de Subcategoría</th>
                            <th>Categoría Principal (Padre)</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subcategorias as $subcat): ?>
                            <tr>
                                <td><?php echo $subcat->getId(); ?></td>
                                <td class="fw-bold text-dark"><?php echo $subcat->getNombre(); ?></td>
                                <td><span class="badge bg-info text-dark"><?php echo $subcat->getCategoriaNombre(); ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=subcategorias-editar&idSubcategoria=<?php echo $subcat->getId(); ?>" class="btn btn-sm btn-warning text-dark" title="Editar"><i class="fas fa-edit"></i></a>
                                            <button class="btn btn-sm btn-danger btnEliminarSubcategoria" idSubcategoria="<?php echo $subcat->getId(); ?>" nombreSubcategoria="<?php echo $subcat->getNombre(); ?>" title="Desactivar"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success btnActivarSubcategoria" idSubcategoria="<?php echo $subcat->getId(); ?>" nombreSubcategoria="<?php echo $subcat->getNombre(); ?>"><i class="fas fa-undo"></i> Reactivar</button>
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