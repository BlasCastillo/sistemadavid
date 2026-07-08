<?php
// Evitamos que PHP arroje warnings si la sesión ya fue iniciada en el index
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyPOS - Sistema de Gestión</title>

    <link href="vista/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="vista/css/bootstrap.min.css">
    
    <link rel="stylesheet" href="vista/css/sweetalert2.min.css">
    

    <link rel="stylesheet" href="vista/css/all.min.css">

    <link rel="stylesheet" href="vista/css/custom.css">
</head>
<body>

    <?php
    // Si la variable de sesión existe y es "ok", el usuario está logueado
    if (isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] == "ok") {
        
        echo '<div class="wrapper">';
        echo '<div class="sidebar-overlay"></div>';

        // 1. INCLUIMOS EL MENÚ LATERAL
        include "vista/plantilla/menu.php";

        echo '<div class="main-content">';

        // 2. INCLUIMOS EL HEADER (Barra superior)
        include "vista/plantilla/header.php";

        // 3. CONTENIDO DINÁMICO (El "Body")
        // Verificamos qué ruta solicita el usuario mediante la URL (Front Controller)
        if (isset($_GET["ruta"])) {
            
            // Lista blanca de rutas permitidas (Iremos agregando más conforme programemos)
            $rutasPermitidas = ["dashboard", "usuarios", "usuarios-crear", "usuarios-editar", "usuarios-clave", "roles", "roles-crear", "roles-editar", "salir", "tasas-cambio", "categorias", "categorias-crear", "categorias-editar", "lineas", "lineas-crear", "lineas-editar", "proveedores", "proveedores-crear", "proveedores-editar", "subcategorias", "subcategorias-crear", "subcategorias-editar", "clientes", "clientes-crear", "clientes-editar", "gastos", "gastos-crear", "gastos-editar", "productos", "productos-crear", "productos-editar", "compras", "compras-crear"];

            if (in_array($_GET["ruta"], $rutasPermitidas)) {
                include "vista/modulos/" . $_GET["ruta"] . ".php";
            } else {
                include "vista/modulos/404.php"; // Página de error si la ruta no existe
            }
        } else {
            // Si no hay ruta en la URL, por defecto cargamos el Dashboard
            include "vista/modulos/dashboard.php";
        }

        // 4. INCLUIMOS EL FOOTER
        include "vista/plantilla/footer.php";

        echo '</div>'; // Fin main-content
        echo '</div>'; // Fin wrapper

    } else {
        // Si no está logueado, inyectamos EXCLUSIVAMENTE la pantalla de Login
        include "vista/modulos/login.php";
    }
    ?>

    <script src="vista/js/jquery.js"></script>
    
    <script src="vista/js/bootstrap.bundle.min.js"></script>
    <script src="vista/js/select2.min.js"></script>
    <script src="vista/js/sweetalert2.all.min.js"></script>
    <script src="vista/js/login.js"></script>
    <script src="vista/js/tasas.js"></script>

    <script src="vista/js/dashboard.js"></script>
    <script src="vista/js/plantilla.js"></script>
    <script src="vista/js/roles.js"></script>
    <script src="vista/js/usuarios.js"></script>
    <script src="vista/js/categorias.js"></script>
    <script src="vista/js/lineas.js"></script>
    <script src="vista/js/proveedores.js"></script>
    <script src="vista/js/subcategorias.js"></script>
    <script src="vista/js/clientes.js"></script>
    <script src="vista/js/gastos.js"></script>
    <script src="vista/js/productos.js"></script>
    <script src="vista/js/compras.js"></script>
    </body>
</html>