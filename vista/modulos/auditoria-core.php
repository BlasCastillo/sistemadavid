<?php
// Obtenemos la configuración instanciada desde el controlador
$config = ConfiguracionControlador::ctrMostrarConfiguracion();
?>

<div class="container-fluid px-0">

    <?php if (!isset($_SESSION["superadmin_desbloqueado"]) || $_SESSION["superadmin_desbloqueado"] !== true): ?>
        
        <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
            <div class="col-md-5 col-sm-8">
                <div class="card-reditus p-4 shadow border-0 border-top border-danger border-3 text-center p-4">
                    <div class="card-body">
                        <div class="text-danger mb-3">
                            <i class="fas fa-user-shield fa-3x"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Núcleo del Sistema</h4>
                        <p class="text-muted small mb-4">Introduzca el PIN Maestro de Super Admin para desbloquear las configuraciones globales.</p>
                        
                        <form id="formDesbloquearCore" autocomplete="off">
                            <div class="mb-4">
                                <input type="password" class="form-control text-center fs-4 fw-bold" name="pinSuperAdmin" placeholder="••••••" maxlength="10" style="letter-spacing: 0.5rem;" required>
                            </div>
                            <button type="submit" class="btn btn-dodger w-100 justify-content-center fw-bold shadow-sm">
                                <i class="fas fa-key me-2"></i> Autorizar Acceso
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 text-gray-800"><i class="fas fa-cogs text-primary me-2"></i> Configuración Global</h1>
            <span class="badge badge-success px-3 py-2 fs-6 shadow-sm"><i class="fas fa-check-circle me-1"></i> Núcleo Desbloqueado</span>
        </div>

        <div class="row">
            <div class="col-xl-9 col-lg-10">
                <div class="card-reditus p-4 shadow border-0 border-top border-primary border-3">
                    <div class="card-body p-4">
                        <form id="formActualizarConfiguracion" autocomplete="off">
                            <input type="hidden" name="actualizarConfiguracion" value="true">
                            
                            <div class="row mb-4 bg-light p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                    <h6 class="text-primary fw-bold mb-0"><i class="fas fa-file-invoice me-2"></i>Identidad Corporativa (Tickets y Facturas)</h6>
                                </div>
                                
                                <div class="col-md-8 mb-3">
                                    <label class="form-label fw-semibold small">Nombre o Razón Social <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-building"></i></span>
                                        <input type="text" class="form-control" name="configNombre" value="<?php echo htmlspecialchars($config->getNombreNegocio()); ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold small">RIF del Negocio <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" name="configRif" value="<?php echo htmlspecialchars($config->getRifNegocio()); ?>" placeholder="Ej: J-12345678-9" required>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold small">Dirección Fiscal <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-map-marker-alt"></i></span>
                                        <textarea class="form-control" name="configDireccion" rows="2" required><?php echo htmlspecialchars($config->getDireccionFiscal()); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <h6 class="text-muted fw-bold mb-3 small text-uppercase"><i class="fas fa-phone-alt me-2"></i>Contacto y Salida</h6>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold small">Teléfono de Atención</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-phone"></i></span>
                                        <input type="text" class="form-control" name="configTelefono" value="<?php echo htmlspecialchars($config->getTelefonoNegocio()); ?>">
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold small">Correo del Negocio</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" name="configCorreo" value="<?php echo htmlspecialchars($config->getCorreoNegocio()); ?>">
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold small">Hardware de Impresión</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-print"></i></span>
                                        <select class="form-select" name="configImpresora">
                                            <option value="Tickets_80mm" <?php echo ($config->getImpresoraTickets() == 'Tickets_80mm') ? 'selected' : ''; ?>>Ticketera Térmica (80mm)</option>
                                            <option value="Carta_A4" <?php echo ($config->getImpresoraTickets() == 'Carta_A4') ? 'selected' : ''; ?>>Impresora Normal (Carta/A4)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <hr class="mt-2 mb-4">
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-success fw-bold px-4"><i class="fas fa-save me-2"></i> Guardar Configuración</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>


