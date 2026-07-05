<?php
// Validamos que solo el Gerente (Rol 1) acceda a esta ruta
if ($_SESSION["rol_id"] != 1) {
    echo '<script>window.location = "index.php?ruta=dashboard";</script>';
    exit;
}
$tasaActual = Tasas::obtenerTasaActiva();
?>

<div class="container-fluid py-4">
    <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-exchange-alt text-danger me-2"></i> Configuración de Tasas</h1>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 fw-bold text-primary">Ajuste Manual de Emergencia</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Útilice este panel solo si las APIs fallan o el mercado presenta alta volatilidad y necesita proteger su costo de reposición inmediatamente.</p>
                    
                    <form id="formTasasManual">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Tasa BCV (Oficial)</label>
                            <input type="number" step="0.0001" class="form-control" id="nuevaTasaBcvManual" name="nuevaTasaBcvManual" value="<?php echo $tasaActual ? $tasaActual->tasa_bcv : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Tasa USDT (Binance / Mercado)</label>
                            <input type="number" step="0.0001" class="form-control" id="nuevaTasaBinanceManual" name="nuevaTasaBinanceManual" value="<?php echo $tasaActual ? $tasaActual->tasa_binance : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-danger">Forzar Brecha % (Opcional)</label>
                            <input type="number" step="0.01" class="form-control border-danger" id="brechaForzadaManual" name="brechaForzadaManual" placeholder="Dejar en blanco para calcular automáticamente">
                            <small class="text-muted">Si lo deja vacío, el sistema calculará: ((USDT - BCV) / BCV) * 100</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold" id="btnGuardarTasa">
                            <i class="fas fa-save me-2"></i> Forzar Tasas en Sistema
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 fw-bold text-success">Última Actualización</h6>
                </div>
                <div class="card-body text-center py-5">
                    <i class="fas fa-clock fa-4x text-gray-300 mb-3 opacity-50"></i>
                    <h4 class="fw-bold">
                        <?php 
                        if ($tasaActual) {
                            // Convertimos la fecha de la base de datos a un formato legible
                            $fechaObj = new DateTime($tasaActual->creado_en);
                            echo $fechaObj->format('d/m/Y - h:i A');
                        } else {
                            echo "Sin registros";
                        }
                        ?>
                    </h4>
                    <p class="text-muted mt-2">La sincronización automática mediante APIs se ejecuta en segundo plano (BCV después de las 5:00 PM).</p>
                </div>
            </div>
        </div>
    </div>
</div>