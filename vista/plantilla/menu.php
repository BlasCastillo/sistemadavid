<?php 
$permisos = $_SESSION["permisos"] ?? []; 
// CONTROL DE SEGURIDAD: Si es Rol 1 (Gerente de la BD) o tiene 'all' en el JSON, tiene acceso total
$modoDios = ($_SESSION["rol_id"] == 1) || in_array("all", $permisos);
?>

<aside id="sidebarMenu" class="bg-dark text-white p-3 vh-100 shadow-sm" style="width: 250px; position: fixed; overflow-y: auto; z-index: 1000;">
    
    <div class="mb-4 text-center mt-2">
        <h5 class="text-uppercase fw-bold text-info border-bottom border-secondary pb-3">
            <i class="fas fa-store me-2"></i> EasyPOS
        </h5>
    </div>

    <ul class="nav flex-column mb-5">

        <?php if ($modoDios || in_array("ver_dashboard", $permisos)): ?>
            <li class="nav-item mb-1">
                <a class="nav-link text-white" href="index.php?ruta=dashboard">
                    <i class="fas fa-chart-line me-2 text-primary"></i> Dashboard
                </a>
            </li>
        <?php endif; ?>

        <?php if ($modoDios || in_array("ver_configuracion", $permisos)): ?>
            <li class="nav-item mb-1 mt-2">
                <a class="nav-link text-white" href="#submenuConfig" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-cogs me-2 text-danger"></i> Configuración <i class="fas fa-caret-down ms-auto float-end mt-1"></i>
                </a>
                <ul class="collapse nav flex-column ms-3 bg-dark rounded" id="submenuConfig">
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=tasas-cambio"><i class="fas fa-exchange-alt me-2"></i> Tasas y Brecha</a></li>
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=usuarios"><i class="fas fa-users me-2"></i> Usuarios</a></li>
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=roles"><i class="fas fa-user-tag me-2"></i> Roles y Permisos</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($modoDios || in_array("ver_archivo", $permisos)): ?>
            <li class="nav-item mb-1 mt-2">
                <a class="nav-link text-white" href="#submenuArchivo" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-database me-2 text-warning"></i> Archivo <i class="fas fa-caret-down ms-auto float-end mt-1"></i>
                </a>
                <ul class="collapse nav flex-column ms-3" id="submenuArchivo">
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=productos"><i class="fas fa-box me-2"></i> Productos</a></li>
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=lineas"><i class="fas fa-tags me-2"></i> Líneas</a></li>
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=categorias"><i class="fas fa-list me-2"></i> Categorías</a></li>
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=subcategorias"><i class="fas fa-list-alt me-2"></i> Subcategorías</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($modoDios || in_array("ver_compras", $permisos)): ?>
            <li class="nav-item mb-1 mt-2">
                <a class="nav-link text-white" href="#submenuCompras" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-truck-loading me-2 text-info"></i> Compras <i class="fas fa-caret-down ms-auto float-end mt-1"></i>
                </a>
                <ul class="collapse nav flex-column ms-3" id="submenuCompras">
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=compras"><i class="fas fa-file-invoice-dollar me-2"></i> Entradas / Compras</a></li>
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=proveedores"><i class="fas fa-building me-2"></i> Proveedores</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($modoDios || in_array("ver_clientes", $permisos)): ?>
            <li class="nav-item mb-1 mt-2">
                <a class="nav-link text-white" href="#submenuClientes" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-users me-2" style="color: #fd7e14;"></i> Clientes <i class="fas fa-caret-down ms-auto float-end mt-1"></i>
                </a>
                <ul class="collapse nav flex-column ms-3" id="submenuClientes">
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=clientes"><i class="fas fa-address-book me-2"></i> Directorio</a></li>
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=creditos"><i class="fas fa-hand-holding-usd me-2"></i> Gestión de Créditos</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($modoDios || in_array("ver_gastos", $permisos)): ?>
            <li class="nav-item mb-1 mt-2">
                <a class="nav-link text-white" href="#submenuGastos" data-bs-toggle="collapse" aria-expanded="false">
                     <i class="fas fa-wallet me-2 text-warning"></i> Gastos / Egresos <i class="fas fa-caret-down ms-auto float-end mt-1"></i>
                </a>
                <ul class="collapse nav flex-column ms-3 bg-dark rounded" id="submenuGastos">
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=gastos"><i class="fas fa-file-invoice-dollar me-2"></i> Control de Gastos</a></li>
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=gastos-crear"><i class="fas fa-plus me-2"></i> Registrar Gasto</a></li>
                </ul>
            </li>
<?php endif; ?>

        <?php if ($modoDios || in_array("ver_reportes", $permisos)): ?>
            <li class="nav-item mb-1 mt-2">
                <a class="nav-link text-white" href="#submenuReportes" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-chart-pie me-2" style="color: #e83e8c;"></i> Reportes <i class="fas fa-caret-down ms-auto float-end mt-1"></i>
                </a>
                <ul class="collapse nav flex-column ms-3" id="submenuReportes">
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=reporte-ventas"><i class="fas fa-file-alt me-2"></i> Analítica de Ventas</a></li>
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=bitacora"><i class="fas fa-history me-2"></i> Bitácora de Inventario</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($modoDios || in_array("ver_procesos", $permisos)): ?>
            <li class="nav-item mb-1 mt-3">
                <a class="nav-link text-white" href="#submenuProcesos" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-cash-register me-2 text-success"></i> Procesos <i class="fas fa-caret-down ms-auto float-end mt-1"></i>
                </a>
                <ul class="collapse nav flex-column ms-3" id="submenuProcesos">
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=ventas"><i class="fas fa-shopping-cart me-2"></i> Ventas</a></li>
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=consulta-precios"><i class="fas fa-barcode me-2"></i> Consulta de Precios</a></li>
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=etiquetas"><i class="fas fa-print me-2"></i> Etiquetas</a></li>
                    <li class="nav-item"><a class="nav-link text-light small py-1" href="index.php?ruta=cierre-caja"><i class="fas fa-lock me-2"></i> Cierre de Caja</a></li>
                    
                    <?php if ($modoDios || in_array("auditar_cierres", $permisos)): ?>
                        <li class="nav-item mt-1"><a class="nav-link text-warning small py-1 border-top border-secondary pt-2" href="index.php?ruta=auditoria-cierres"><i class="fas fa-balance-scale me-2"></i> Auditoría de Cierres</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <hr class="border-secondary mt-4">

        <li class="nav-item mb-3">
            <a class="nav-link text-danger fw-bold" href="index.php?ruta=salir">
                <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
            </a>
        </li>

    </ul>
</aside>

<div style="width: 250px; flex-shrink: 0;" class="d-none d-md-block"></div>