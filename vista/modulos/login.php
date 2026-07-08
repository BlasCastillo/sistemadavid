<div class="d-flex justify-content-center align-items-center vh-100 bg-light w-100 p-3">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden" style="width: 100%; max-width: 420px;">
        
        <div class="card-header bg-white text-center pt-5 pb-4 border-0">
            <div class="mb-3">
                <i class="fas fa-store fa-3x text-primary"></i>
            </div>
            <h3 class="fw-bolder text-dark mb-1">REDITUS</h3>
            <p class="text-muted small mb-0">Sistema de Gestión, Inventario y Venta</p>
        </div>
        
        <div class="card-body px-4 pb-5 pt-0">
            <form id="formLogin" autocomplete="off">
                
                <div class="mb-3">
                    <label for="ingUsuario" class="form-label text-muted small ms-1">Usuario</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light border-0 text-primary">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control bg-light border-0" id="ingUsuario" name="ingUsuario" placeholder="Nombre de usuario" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="ingClave" class="form-label text-muted small ms-1">Contraseña</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light border-0 text-primary">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control bg-light border-0" id="ingClave" name="ingClave" placeholder="••••••••" required>
                        <button class="btn btn-light border-0" type="button" id="btnVerClave">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm" id="btnIngresar">
                        Entrar al Sistema <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>

            </form>
        </div>
        
        <div class="card-footer bg-light text-center py-3 border-0">
            <span class="text-muted small">© <?php echo date("Y"); ?> REDITUS | Sistema de Control</span>
        </div>
    </div>
</div>

<style>
    /* Estilo para que el input al recibir foco se vea premium */
    .form-control:focus {
        background-color: #fff !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1) !important;
    }
    .card { border-radius: 1.5rem !important; }
</style>