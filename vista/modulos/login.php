<div class="d-flex justify-content-center align-items-center vh-100 bg-light w-100">
    <div class="card shadow-lg border-0 rounded-lg" style="width: 100%; max-width: 400px;">
        
        <div class="card-header bg-primary text-white text-center py-4 border-0 rounded-top">
            <h3 class="mb-0 fw-bold"><i class="fas fa-store me-2"></i> EasyPOS</h3>
            <p class="mb-0 small opacity-75">Control Logístico e Inventario</p>
        </div>
        
        <div class="card-body p-4 p-md-5">
            <form id="formLogin" autocomplete="off">
                
                <div class="mb-4">
                    <label for="ingUsuario" class="form-label fw-semibold text-secondary small">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-primary">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" id="ingUsuario" name="ingUsuario" placeholder="Ingrese su usuario" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="ingClave" class="form-label fw-semibold text-secondary small">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-primary">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control border-start-0 ps-0" id="ingClave" name="ingClave" placeholder="Ingrese su contraseña" required>
                        <button class="btn btn-outline-secondary border-start-0 bg-white" type="button" id="btnVerClave">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold" id="btnIngresar">
                        Iniciar Sesión <i class="fas fa-sign-in-alt ms-2"></i>
                    </button>
                </div>

            </form>
        </div>
        
        <div class="card-footer bg-white text-center py-3 border-0 rounded-bottom">
            <span class="text-muted small">© <?php echo date("Y"); ?> Todos los derechos reservados</span>
        </div>

    </div>
</div>