<?php
// Solicitamos la tasa activa al cargar el dashboard
$tasaActual = Tasas::obtenerTasaActiva();
?>
<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h1 class="h3 text-gray-800 mb-2 mb-md-0"><i class="fas fa-chart-line text-primary me-2"></i> Dashboard General</h1>
        
        <div class="d-flex align-items-center bg-white p-2 rounded shadow-sm border">
            <span class="text-muted small me-3 fw-semibold">
                <i class="fas fa-clock text-info me-1"></i> 
                Última captura: 
                <?php 
                    if($tasaActual) {
                        echo date('d/m/Y - h:i A', strtotime($tasaActual->creado_en));
                    } else {
                        echo "Sin datos";
                    }
                ?>
            </span>
            <button class="btn btn-sm btn-outline-primary fw-bold" id="btnForzarSincronizacion">
                <i class="fas fa-sync-alt me-1"></i> Revisar Tasas
            </button>
        </div>
    </div>
    
    <div class="row mb-4">
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start border-primary border-4 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Tasa Oficial (BCV)</div>
                            <div class="h5 mb-0 fw-bold text-dark">Bs. <?php echo $tasaActual ? number_format($tasaActual->tasa_bcv, 4, ',', '.') : '0,0000'; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-university fa-2x text-black-50 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start border-warning border-4 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Tasa USDT (Binance)</div>
                            <div class="h5 mb-0 fw-bold text-dark">Bs. <?php echo $tasaActual ? number_format($tasaActual->tasa_binance, 4, ',', '.') : '0,0000'; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fab fa-bitcoin fa-2x text-black-50 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start border-danger border-4 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Brecha Cambiaria</div>
                            <div class="h5 mb-0 fw-bold text-dark"><?php echo $tasaActual ? $tasaActual->brecha_porcentaje : '0'; ?> %</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-black-50 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

  <div class="alert alert-success border-0 shadow-sm" role="alert">
        <h5 class="alert-heading fw-bold"><i class="fas fa-check-circle me-2"></i>¡Bienvenido, <?php echo $_SESSION["nombre_completo"] ?? "Usuario"; ?>!</h5>
        <p class="mb-0 small">El sistema está operando correctamente bajo tu rol de <strong><?php echo $_SESSION["nombre_rol"] ?? "Usuario"; ?></strong>.</p>
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