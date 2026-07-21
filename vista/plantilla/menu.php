<?php 
$permisos = $_SESSION["permisos"] ?? []; 
$modoDios = ($_SESSION["rol_id"] == 1) || in_array("all", $permisos);
?>

<aside id="sidebarMenu">
    <div class="brand-link">
        <i class="fas fa-store me-2"></i> REDITUS
    </div>

    <ul class="nav flex-column mt-3">

        <?php if ($modoDios || in_array("ver_dashboard", $permisos)): ?>
            <li class="nav-item">
                <a class="nav-link" href="index.php?ruta=dashboard">
                    <i class="fas fa-chart-line text-primary"></i> Dashboard
                </a>
            </li>
        <?php endif; ?>

        <?php if ($modoDios || in_array("ver_configuracion", $permisos)): ?>
            <li class="nav-item">
                <a class="nav-link" href="#submenuConfig" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-cogs text-danger"></i> Configuración 
                    <i class="fas fa-caret-down ms-auto"></i>
                </a>
                <ul class="collapse nav flex-column" id="submenuConfig">
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=tasas-cambio"><i class="fas fa-exchange-alt"></i> Tasas y Brecha</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=usuarios"><i class="fas fa-users"></i> Usuarios</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=roles"><i class="fas fa-user-tag"></i> Roles y Permisos</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($modoDios || in_array("ver_archivo", $permisos)): ?>
            <li class="nav-item">
                <a class="nav-link" href="#submenuArchivo" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-database text-warning"></i> Archivo 
                    <i class="fas fa-caret-down ms-auto"></i>
                </a>
                <ul class="collapse nav flex-column" id="submenuArchivo">
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=productos"><i class="fas fa-box"></i> Productos</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=ofertas"><i class="fas fa-tags text-danger"></i> Ofertas / Promo</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=lineas"><i class="fas fa-tags"></i> Líneas</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=categorias"><i class="fas fa-list"></i> Categorías</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=subcategorias"><i class="fas fa-list-alt"></i> Subcategorías</a></li>
                </ul>
            </li>
        <?php endif; ?>
        
        <?php if ($modoDios || in_array("ver_compras", $permisos)): ?>
            <li class="nav-item">
                <a class="nav-link" href="#submenuCompras" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-truck-loading text-info"></i> Compras 
                    <i class="fas fa-caret-down ms-auto"></i>
                </a>
                <ul class="collapse nav flex-column" id="submenuCompras">
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=compras"><i class="fas fa-file-invoice-dollar"></i> Entradas / Compras</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=proveedores"><i class="fas fa-building"></i> Proveedores</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=cuentas-por-pagar"><i class="fas fa-hand-holding-usd"></i> Cuentas por Pagar</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($modoDios || in_array("ver_clientes", $permisos)): ?>
            <li class="nav-item">
                <a class="nav-link" href="#submenuClientes" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-users" style="color: #fd7e14;"></i> Clientes 
                    <i class="fas fa-caret-down ms-auto"></i>
                </a>
                <ul class="collapse nav flex-column" id="submenuClientes">
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=clientes"><i class="fas fa-address-book"></i> Directorio</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=creditos"><i class="fas fa-hand-holding-usd"></i> Gestión de Créditos</a></li>
                </ul>
            </li>
        <?php endif; ?>

       <?php if ($modoDios || in_array("ver_gastos", $permisos) || in_array("conciliar_pagos", $permisos)): ?>
            <li class="nav-item">
                <a class="nav-link" href="#submenuGastos" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-wallet text-warning"></i> Ingresos / Gastos 
                    <i class="fas fa-caret-down ms-auto"></i>
                </a>
                <ul class="collapse nav flex-column" id="submenuGastos">
                    <?php if ($modoDios || in_array("ver_gastos", $permisos)): ?>
                        <li class="nav-item border-bottom border-secondary mb-1 pb-1">
                            <a class="nav-link text-success" href="index.php?ruta=ingresos"><i class="fas fa-hand-holding-usd"></i> Ingresos Extras</a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="index.php?ruta=gastos"><i class="fas fa-file-invoice-dollar"></i> Control de Gastos</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?ruta=gastos-crear"><i class="fas fa-plus"></i> Registrar Gasto</a></li>
                    <?php endif; ?>

                    <!-- NUEVO BOTÓN: CONCILIACIONES BANCARIAS -->
                    <?php if ($modoDios || in_array("conciliar_pagos", $permisos)): ?>
                        <li class="nav-item mt-1 pt-1 border-top border-secondary">
                            <a class="nav-link text-info fw-bold" href="index.php?ruta=conciliaciones"><i class="fas fa-university"></i> Conciliaciones</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($modoDios || in_array("ver_reportes", $permisos)): ?>
            <li class="nav-item">
                <a class="nav-link" href="#submenuReportes" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-chart-pie" style="color: #e83e8c;"></i> Reportes 
                    <i class="fas fa-caret-down ms-auto"></i>
                </a>
                <ul class="collapse nav flex-column" id="submenuReportes">
                    <!-- Ajustaremos estas rutas en el Nivel 3 -->
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=reporte-ventas"><i class="fas fa-file-alt"></i> Analítica de Ventas</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=bitacora"><i class="fas fa-history"></i> Bitácora de Inventario</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($modoDios || in_array("ver_procesos", $permisos)): ?>
            <li class="nav-item">
                <a class="nav-link" href="#submenuProcesos" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fas fa-cash-register text-success"></i> Procesos 
                    <i class="fas fa-caret-down ms-auto"></i>
                </a>
                <ul class="collapse nav flex-column" id="submenuProcesos">
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=ventas-crear"><i class="fas fa-shopping-cart"></i> Ventas / POS</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=ventas"><i class="fas fa-list-ul"></i> Historial de Ventas</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=consulta-precios"><i class="fas fa-barcode"></i> Consulta de Precios</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=etiquetas"><i class="fas fa-print"></i> Etiquetas</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?ruta=cierre-caja"><i class="fas fa-lock"></i> Cierre de Caja (X)</a></li>
                    
                    <?php if ($modoDios || in_array("auditar_cierres", $permisos)): ?>
                        <li class="nav-item mt-2 border-top border-secondary pt-2">
                            <a class="nav-link text-warning" href="index.php?ruta=auditoria-cierres"><i class="fas fa-balance-scale"></i> Auditoría de Cierres</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger fw-bold" href="index.php?ruta=cierre-z"><i class="fas fa-store-slash"></i> Ejecutar Cierre Z</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-secondary" href="index.php?ruta=historial-z"><i class="fas fa-history"></i> Historial Cierres Z</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <li class="nav-item mt-4">
            <a class="nav-link text-danger" href="index.php?ruta=salir">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </li>
    </ul>
</aside>