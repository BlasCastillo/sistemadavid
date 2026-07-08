<?php
// Solicitamos la tasa activa
$tasaActual = Tasas::obtenerTasaActiva();
?>

<div class="container-fluid">
    
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="module-title">
            <h4 class="fw-bold mb-0 text-dark">
                <i class="fas fa-chart-line me-2 text-primary"></i> Dashboard General
            </h4>
            <small class="text-muted">Resumen operativo y tasas de cambio al día.</small>
        </div>
        
        <div class="d-flex align-items-center bg-white p-2 px-3 rounded shadow-sm border border-light">
            <span class="text-muted small me-3">
                <i class="fas fa-clock text-info me-1"></i> 
                Actualizado: 
                <strong class="text-dark">
                    <?php echo $tasaActual ? date('d/m/Y - h:i A', strtotime($tasaActual->creado_en)) : "Sin datos"; ?>
                </strong>
            </span>
            <button class="btn btn-sm btn-outline-primary" id="btnForzarSincronizacion">
                <i class="fas fa-sync-alt me-1"></i> Sincronizar
            </button>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded text-primary">
                        <i class="fas fa-university fa-lg"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-xs fw-bold text-muted text-uppercase mb-1">Tasa Oficial (BCV)</div>
                        <div class="h5 mb-0 fw-bold text-dark">Bs. <?php echo $tasaActual ? number_format($tasaActual->tasa_bcv, 4, ',', '.') : '0,0000'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded text-warning">
                        <i class="fab fa-bitcoin fa-lg"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-xs fw-bold text-muted text-uppercase mb-1">Tasa USDT (Binance)</div>
                        <div class="h5 mb-0 fw-bold text-dark">Bs. <?php echo $tasaActual ? number_format($tasaActual->tasa_binance, 4, ',', '.') : '0,0000'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 bg-danger bg-opacity-10 p-3 rounded text-danger">
                        <i class="fas fa-percentage fa-lg"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-xs fw-bold text-muted text-uppercase mb-1">Brecha Cambiaria</div>
                        <div class="h5 mb-0 fw-bold text-dark"><?php echo $tasaActual ? $tasaActual->brecha_porcentaje : '0'; ?> %</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm bg-success bg-opacity-10">
        <div class="card-body">
            <h5 class="fw-bold text-success mb-1">
                <i class="fas fa-check-circle me-2"></i>Bienvenido, <?php echo $_SESSION["nombre_completo"] ?? "Usuario"; ?>
            </h5>
            <p class="mb-0 text-muted">
                Has iniciado sesión con el rol de <strong><?php echo $_SESSION["nombre_rol"] ?? "Usuario"; ?></strong>. Todo está listo para gestionar tus operaciones.
            </p>
        </div>
    </div>
</div><?php
/*
// =========================================================================
// BANDERA TEMPORAL DE DIAGNÓSTICO QA (Eliminar al terminar la auditoría)
// =========================================================================
echo '<div class="alert alert-dark border-0 shadow-sm m-3 font-monospace small" style="z-index: 2000; position: relative;">';
echo '<h5 class="text-warning fw-bold"><i class="fas fa-bug"></i> Panel de Auditoría de Sesión</h5>';
echo '<hr class="border-secondary my-2">';
echo '<strong>1. Datos en $_SESSION nativa:</strong><br>';
echo '• id_usuario: ' . ($_SESSION["id_usuario"] ?? '<span class="text-danger">NO DEFINIDO</span>') . '<br>';
echo '• usuario: ' . ($_SESSION["usuario"] ?? '<span class="text-danger">NO DEFINIDO</span>') . '<br>';
echo '• rol_id (ID de la BD): ' . ($_SESSION["rol_id"] ?? '<span class="text-danger">NO DEFINIDO</span>') . '<br>';
echo '• nombre_rol (El que falló): ' . ($_SESSION["nombre_rol"] ?? '<span class="text-danger">NO DEFINIDO (UNDEFINED)</span>') . '<br>';
echo '• Array JSON en Memoria: ' . json_encode($_SESSION["permisos"] ?? "Sin inicializar") . '<br><br>';

echo '<strong>2. Consulta en Tiempo Real al Modelo Roles:</strong><br>';
try {
    require_once "modelo/Roles.php";
    if (isset($_SESSION["rol_id"])) {
        $checkRol = Roles::buscarPorId(intval($_SESSION["rol_id"]));
        if ($checkRol) {
            echo '<span class="text-success"><strong>[ÉXITO]</strong> El Modelo Roles respondió. El ID ' . $_SESSION["rol_id"] . ' corresponde al nombre: "' . $checkRol->getNombre() . '"</span><br>';
            echo '• Permisos actuales en BD: <code class="text-info">' . ($checkRol->getPermisos() ?? 'NULL / Vacío') . '</code>';
        } else {
            echo '<span class="text-danger"><strong>[FALLO]</strong> El Modelo ejecutó la consulta pero el ID ' . $_SESSION["rol_id"] . ' no devolvió ningún registro en la tabla "roles".</span>';
        }
    } else {
        echo '<span class="text-danger"><strong>[BLOQUEO]</strong> No se puede consultar el Modelo porque rol_id no existe en la sesión.</span>';
    }
} catch (Exception $e) {
    echo '<span class="text-danger"><strong>[CRÍTICO] Error de Conexión o Sintaxis:</strong> ' . $e->getMessage() . '</span>';
}
echo '</div>';
// =========================================================================
?>
*/