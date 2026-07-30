<?php
// Validamos que el usuario tenga permiso para estar aquí
$modoDios = ($_SESSION["rol_id"] == 1);
$permisos = $_SESSION["permisos"] ?? [];
if (!$modoDios && !in_array("all", $permisos) && !in_array("ver_reportes", $permisos)) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}

// Traemos los usuarios de la base de datos para llenar el Select Dinámicamente
$listaUsuarios = UsuariosControlador::ctrMostrarUsuarios(null, 1); 
?>

<div class="content-header mb-3">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3 class="m-0 text-dark"><i class="fas fa-history text-secondary me-2"></i> Bitácora del Sistema</h3>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <!-- Tarjeta de Filtros Avanzados -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body bg-light rounded">
                <div class="row align-items-end">
                    
                    <div class="col-md-2">
                        <label class="form-label text-muted small fw-bold">Fecha Inicio</label>
                        <input type="date" class="form-control" id="filtroFechaInicio">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label text-muted small fw-bold">Fecha Fin</label>
                        <input type="date" class="form-control" id="filtroFechaFin">
                    </div>
                    
                    <div class="col-md-3 mt-3 mt-md-0">
                        <label class="form-label text-muted small fw-bold">Filtrar por Usuario</label>
                        <select id="filtroUsuario" class="form-select select2">
                            <option value="">Todos los Usuarios</option>
                            <?php foreach($listaUsuarios as $user): ?>
                                <option value="<?php echo $user->getId(); ?>">@<?php echo $user->getUsuario(); ?> - <?php echo $user->getNombreCompleto(); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 mt-3 mt-md-0">
                        <label class="form-label text-muted small fw-bold">Filtrar por Módulo</label>
                        <select id="filtroModulo" class="form-select select2">
                            <option value="">Todos los Módulos</option>
                            <option value="Seguridad">Seguridad (Login / Claves)</option>
                            <option value="Configuración">Configuración General</option>
                            <option value="Configuración/Roles">Roles y Permisos</option>
                            <option value="Configuración/Tasas">Tasas de Cambio</option>
                            <option value="Archivo/Productos">Catálogo de Productos</option>
                            <option value="Compras">Compras y Abastecimiento</option>
                            <option value="Compras/Proveedores">Proveedores</option>
                            <option value="Compras/CxP">Cuentas Por Pagar (CxP)</option>
                            <option value="Directorio/Clientes">Directorio de Clientes</option>
                            <option value="Clientes/CxC">Cuentas Por Cobrar (CxC)</option>
                            <option value="Finanzas/Gastos">Gastos Operativos</option>
                            <option value="Finanzas/Ingresos">Ingresos Extras</option>
                            <option value="Tesorería/Conciliaciones">Tesorería / Bancos</option>
                            <option value="Procesos/Ventas">Ventas y Facturación (POS)</option>
                            <option value="Procesos/Cierres X">Cierres de Caja (X)</option>
                            <option value="Procesos/Cierre Z">Cierre de Tienda (Z)</option>
                            <option value="Auditoría">Auditoría de Sobrantes/Faltantes</option>
                        </select>
                    </div>

                    <div class="col-md-2 mt-3 mt-md-0 d-flex gap-2">
                        <button class="btn btn-primary w-100" id="btnFiltrarBitacora" title="Buscar">
                            <i class="fas fa-search"></i>
                        </button>
                        <button class="btn btn-outline-secondary w-100" id="btnLimpiarFiltros" title="Limpiar Filtros">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Tarjeta de la Tabla -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th style="width: 15%">Fecha y Hora</th>
                                <th style="width: 15%">Usuario</th>
                                <th style="width: 15%">Módulo</th>
                                <th style="width: 15%">Acción</th>
                                <th style="width: 30%">Detalles</th>
                                <th style="width: 10%">Rastreo IP</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoBitacora">
                            <!-- JS inyectará las filas aquí -->
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div> Cargando auditoría...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Paginación Bootstrap Nativa -->
            <div class="card-footer bg-white d-flex justify-content-between align-items-center border-top">
                <span class="text-muted small" id="infoPaginacion">Mostrando 0 a 0 de 0 registros</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="paginacionBitacora">
                        <!-- JS inyectará los botones de página aquí -->
                    </ul>
                </nav>
            </div>
        </div>

    </div>
</section>