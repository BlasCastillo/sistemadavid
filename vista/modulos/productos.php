<?php
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$productos = ProductosControlador::ctrMostrarProductos(null, $estadoFiltro);
?>
<div class="container-fluid px-0">
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="module-title">
            <h4 class="fw-bold mb-0 text-dark">
                <i class="fas fa-box me-2 text-primary"></i> <?php echo $estadoFiltro == 1 ? "Catálogo de Productos" : "Productos Inactivos"; ?>
            </h4>
            <small class="text-muted d-block mt-1">Administra los productos, costos y márgenes de ganancia del inventario.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=productos&estado=0" class="btn btn-outline-secondary">
                    <i class="fas fa-eye-slash me-1"></i> Ver Inactivos
                </a>
            <?php else: ?>
                <a href="index.php?ruta=productos" class="btn btn-outline-secondary">
                    <i class="fas fa-eye me-1"></i> Ver Activos
                </a>
            <?php endif; ?>
            <a href="index.php?ruta=productos-crear" class="btn btn-dodger">
                <i class="fas fa-plus"></i> Nuevo Producto
            </a>
        </div>
    </div>

    <div class="card card-reditus border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Img</th>
                            <th>Código / Nombre</th>
                            <th>Jerarquía</th>
                            <th class="text-end">Costo Base</th>
                            <th class="text-end">Precio Venta</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p):
                            $precioVenta = $p->getCostoUsdt() * (1 + ($p->getMargenGanancia() / 100));
                        ?>
                            <tr>
                                <td>
                                    <img src="<?php echo $p->getImagen() ? $p->getImagen() : 'vista/img/productos/default.png'; ?>"
                                         class="rounded-2 border" style="width:48px;height:48px;object-fit:cover;">
                                </td>
                                <td>
                                    <div class="fw-semibold"><?php echo $p->getNombre(); ?></div>
                                    <span class="badge badge-info"><i class="fas fa-barcode me-1"></i><?php echo $p->getCodigoBarras(); ?></span>
                                </td>
                                <td>
                                    <div class="small"><strong>L:</strong> <?php echo $p->linea_nombre; ?></div>
                                    <div class="small"><strong>C:</strong> <?php echo $p->categoria_nombre; ?></div>
                                    <div class="small"><strong>S:</strong> <?php echo $p->subcategoria_nombre; ?></div>
                                </td>
                                <td class="text-end fw-semibold text-danger">$ <?php echo number_format($p->getCostoUsdt(), 2); ?></td>
                                <td class="text-end">
                                    <div class="fw-bold text-success">$ <?php echo number_format($precioVenta, 2); ?></div>
                                    <span class="badge badge-warning">Margen: <?php echo $p->getMargenGanancia(); ?>%</span>
                                </td>
                                <td class="text-center">
                                    <?php if($p->getStock() <= 5): ?>
                                        <span class="badge badge-danger rounded-pill"><?php echo $p->getStock(); ?> und</span>
                                    <?php else: ?>
                                        <span class="badge badge-success rounded-pill"><?php echo $p->getStock(); ?> und</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=productos-editar&idProducto=<?php echo $p->getId(); ?>" class="btn-action text-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                            <button class="btn-action text-danger btnEliminarProducto" idProducto="<?php echo $p->getId(); ?>" nombreProducto="<?php echo $p->getNombre(); ?>" title="Desactivar"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                            <button class="btn-action text-success btnActivarProducto" idProducto="<?php echo $p->getId(); ?>" nombreProducto="<?php echo $p->getNombre(); ?>" title="Reactivar"><i class="fas fa-undo"></i></button>
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
