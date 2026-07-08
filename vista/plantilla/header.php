<header class="p-3 mb-3 border-bottom bg-white shadow-sm w-100">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    
    <div class="d-flex align-items-center">
        <button class="btn btn-outline-secondary" id="btnToggleMenu" title="Ocultar/Mostrar Menú">
            <i class="fas fa-bars"></i>
        </button>
        <a href="index.php?ruta=dashboard"><span class="fs-5 fw-bold text-primary me-2">REDITUS</span></a>
    </div>

    <div class="d-flex align-items-center">
      <div class="dropdown">
        <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fas fa-user-circle fa-md text-secondary"></i> 
          <span class="fw-semibold">
            <?php echo $_SESSION["nombre_completo"] ?? 'Usuario'; ?>
          </span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
          <li>
            <a class="dropdown-item" href="#">
                <i class="fas fa-user-cog me-2"></i> Mi Perfil
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item text-danger" href="index.php?ruta=salir">
                <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
            </a>
          </li>
        </ul>
      </div>
    </div>

  </div>
</header>