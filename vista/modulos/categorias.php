<?php
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$categorias = CategoriasControlador::ctrMostrarCategorias(null, $estadoFiltro);
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-list text-primary me-2"></i> <?php echo $estadoFiltro == 1 ? "Categorías de Productos" : "Categorías Inactivas"; ?></h1>
        <div>
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=categorias&estado=0" class="btn btn-outline-secondary fw-bold me-2"><i class="fas fa-eye-slash me-1"></i> Ver Inactivas</a>
            <?php else: ?>
                <a href="index.php?ruta=categorias" class="btn btn-outline-success fw-bold me-2"><i class="fas fa-eye me-1"></i> Ver Activas</a>
            <?php endif; ?>
            <a href="index.php?ruta=categorias-crear" class="btn btn-primary fw-bold shadow-sm"><i class="fas fa-plus me-1"></i> Nueva Categoría</a>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Nombre de Categoría</th>
                            <th>Línea Principal</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $cat): ?>
                            <tr>
                                <td><?php echo $cat->getId(); ?></td>
                                <td class="fw-bold text-dark"><?php echo $cat->getNombre(); ?></td>
                                <td><span class="badge bg-info text-dark"><?php echo $cat->linea_nombre; ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=categorias-editar&idCategoria=<?php echo $cat->getId(); ?>" class="btn btn-sm btn-warning text-dark" title="Editar"><i class="fas fa-edit"></i></a>
                                            <button class="btn btn-sm btn-danger btnEliminarCategoria" idCategoria="<?php echo $cat->getId(); ?>" nombreCategoria="<?php echo $cat->getNombre(); ?>" title="Desactivar"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success btnActivarCategoria" idCategoria="<?php echo $cat->getId(); ?>" nombreCategoria="<?php echo $cat->getNombre(); ?>"><i class="fas fa-undo"></i> Reactivar</button>
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