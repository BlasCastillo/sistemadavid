<?php
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$productos = ProductosControlador::ctrMostrarProductos(null, $estadoFiltro);
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-box text-primary me-2"></i> <?php echo $estadoFiltro == 1 ? "Catálogo de Productos" : "Productos Inactivos"; ?></h1>
        <div>
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=productos&estado=0" class="btn btn-outline-secondary fw-bold me-2"><i class="fas fa-eye-slash me-1"></i> Ver Inactivos</a>
            <?php else: ?>
                <a href="index.php?ruta=productos" class="btn btn-outline-success fw-bold me-2"><i class="fas fa-eye me-1"></i> Ver Activos</a>
            <?php endif; ?>
            <a href="index.php?ruta=productos-crear" class="btn btn-primary fw-bold shadow-sm"><i class="fas fa-plus me-1"></i> Nuevo Producto</a>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px;">Img</th>
                            <th>Código / Nombre</th>
                            <th>Jerarquía</th>
                            <th>Costo Base</th>
                            <th>Precio Venta</th>
                            <th>Stock</th>
                            <th class="text-center" style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p): 
                            // Cálculo matemático del precio de venta sugerido al vuelo
                            $precioVenta = $p->getCostoUsdt() * (1 + ($p->getMargenGanancia() / 100));
                        ?>
                            <tr>
                                <td>
                                    <?php if($p->getImagen()): ?>
                                        <img src="<?php echo $p->getImagen(); ?>" class="img-thumbnail rounded shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <img src="vista/img/productos/default.png" class="img-thumbnail rounded shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?php echo $p->getNombre(); ?></div>
                                    <span class="badge bg-secondary"><i class="fas fa-barcode"></i> <?php echo $p->getCodigoBarras(); ?></span>
                                </td>
                                <td>
                                    <div class="small"><strong>L:</strong> <?php echo $p->linea_nombre; ?></div>
                                    <div class="small text-muted"><strong>C:</strong> <?php echo $p->categoria_nombre; ?></div>
                                    <div class="small text-muted"><strong>S:</strong> <?php echo $p->subcategoria_nombre; ?></div>
                                </td>
                                <td class="text-danger fw-bold">$<?php echo number_format($p->getCostoUsdt(), 2); ?></td>
                                <td>
                                    <div class="text-success fw-bold fs-6">$<?php echo number_format($precioVenta, 2); ?></div>
                                    <span class="badge bg-info text-dark">Margen: <?php echo $p->getMargenGanancia(); ?>%</span>
                                </td>
                                <td>
                                    <?php if($p->getStock() <= 5): ?>
                                        <span class="badge bg-danger rounded-pill"><?php echo $p->getStock(); ?> und</span>
                                    <?php else: ?>
                                        <span class="badge bg-success rounded-pill"><?php echo $p->getStock(); ?> und</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=productos-editar&idProducto=<?php echo $p->getId(); ?>" class="btn btn-sm btn-warning text-dark" title="Editar"><i class="fas fa-edit"></i></a>
                                            <button class="btn btn-sm btn-danger btnEliminarProducto" idProducto="<?php echo $p->getId(); ?>" nombreProducto="<?php echo $p->getNombre(); ?>" title="Desactivar"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success btnActivarProducto" idProducto="<?php echo $p->getId(); ?>" nombreProducto="<?php echo $p->getNombre(); ?>"><i class="fas fa-undo"></i></button>
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