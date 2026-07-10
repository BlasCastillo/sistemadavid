<?php
// 1. Iniciamos la sesión de PHP (Obligatorio para que funcione el Login)
session_start();

// 2. Requerimos los Controladores
require_once "controlador/PlantillaControlador.php";
require_once "controlador/UsuariosControlador.php";
require_once "controlador/RolesControlador.php";
require_once "controlador/TasasControlador.php"; // NUEVO
require_once "controlador/CategoriasControlador.php";
require_once "controlador/LineasControlador.php";
require_once "controlador/ProveedoresControlador.php";
require_once "controlador/SubcategoriasControlador.php";
require_once "controlador/ClientesControlador.php";
require_once "controlador/GastosControlador.php";
require_once "controlador/ProductosControlador.php";
require_once "controlador/ComprasControlador.php";
require_once "controlador/ConfiguracionControlador.php";
require_once "controlador/CuentasPorPagarControlador.php";

// 3. Requerimos los Modelos
require_once "modelo/Usuarios.php";
require_once "modelo/Roles.php";
require_once "modelo/Tasas.php"; // NUEVO
// ==========================================
// INTERCEPTOR DE PETICIONES AJAX
// ==========================================
// Si JavaScript envía los datos del Login, este método los atrapa, imprime el JSON y hace exit()
// deteniendo la carga del HTML para que el JSON viaje limpio.
UsuariosControlador::ctrIngresoUsuario();
TasasControlador::ctrActualizacionManual(); // NUEVO INTERCEPTOR
TasasControlador::ctrSincronizarAjax(); // NUEVO: Interceptor para el gatillo y el botón
// Interceptores del CRUD de Roles
RolesControlador::ctrCrearRol();
RolesControlador::ctrActualizarRol();
RolesControlador::ctrEliminarRol();
RolesControlador::ctrGuardarPermisosRol();
// Interceptores del CRUD de Usuarios (NUEVOS)
UsuariosControlador::ctrCrearUsuario();
UsuariosControlador::ctrActualizarUsuario();
UsuariosControlador::ctrEliminarUsuario();
UsuariosControlador::ctrActualizarClaveUsuario();
UsuariosControlador::ctrActivarUsuario();
// Interceptores del CRUD de Categorías
CategoriasControlador::ctrCrearCategoria();
CategoriasControlador::ctrActualizarCategoria();
CategoriasControlador::ctrEliminarCategoria();
CategoriasControlador::ctrActivarCategoria();
CategoriasControlador::ctrTraerCategoriasPorLineaAjax();
// Interceptores del CRUD de Líneas
LineasControlador::ctrCrearLinea();
LineasControlador::ctrActualizarLinea();
LineasControlador::ctrEliminarLinea();
LineasControlador::ctrActivarLinea();
// Interceptores del CRUD de Proveedores
ProveedoresControlador::ctrCrearProveedor();
ProveedoresControlador::ctrActualizarProveedor();
ProveedoresControlador::ctrEliminarProveedor();
ProveedoresControlador::ctrActivarProveedor();
// Interceptores del CRUD de Subcategorías
SubcategoriasControlador::ctrCrearSubcategoria();
SubcategoriasControlador::ctrActualizarSubcategoria();
SubcategoriasControlador::ctrEliminarSubcategoria();
SubcategoriasControlador::ctrActivarSubcategoria();
SubcategoriasControlador::ctrTraerSubcategoriasPorCategoriaAjax();
// Interceptores del CRUD de Clientes
ClientesControlador::ctrCrearCliente();
ClientesControlador::ctrActualizarCliente();
ClientesControlador::ctrEliminarCliente();
ClientesControlador::ctrActivarCliente();
// Interceptores AJAX del CRUD de Gastos
GastosControlador::ctrCrearGasto();
GastosControlador::ctrActualizarGasto();
GastosControlador::ctrAnularGasto();
GastosControlador::ctrReactivarGasto();
// Interceptores AJAX del CRUD de Productos
ProductosControlador::ctrCrearProducto();
ProductosControlador::ctrActualizarProducto();
ProductosControlador::ctrEliminarProducto();
ProductosControlador::ctrActivarProducto();

// Interceptores AJAX del Módulo de Compras
if(isset($_POST["idProductoCompraSegura"]) || isset($_POST["idTemporalEliminar"]) || isset($_POST["procesarCompraFinal"])) {
    
    if(isset($_POST["idProductoCompraSegura"])) {
        ComprasControlador::ctrAgregarTemporalAjax();
    }
    
    if(isset($_POST["idTemporalEliminar"])) {
        ComprasControlador::ctrEliminarTemporalAjax();
    }

    if(isset($_POST["procesarCompraFinal"])) {
        ComprasControlador::ctrProcesarCompraAjax();
    }
}
if(isset($_POST["cargarTemporalesCompra"])) {
    ComprasControlador::ctrCargarTemporalesAjax();
}
// Interceptor para buscar productos dinámicamente en compras
if(isset($_POST["buscarProductoSelect"])) {
    require_once "controlador/ProductosControlador.php";
    ProductosControlador::ctrBuscarProductosAjax();
}
if(isset($_POST["idCompraDetalle"])) {
    require_once "controlador/ComprasControlador.php";
    ComprasControlador::ctrMostrarDetalleCompraAjax();
}
// --- INICIO DEL REEMPLAZO / AGREGADO ---

// Interceptores AJAX para el Núcleo y Configuración del Super Admin

if(isset($_POST["pinSuperAdmin"]) || isset($_POST["actualizarConfiguracion"])) {

    require_once "controlador/ConfiguracionControlador.php";

    

    if(isset($_POST["pinSuperAdmin"])) {

        ConfiguracionControlador::ctrDesbloquearSuperAdminAjax();

    }

    

    if(isset($_POST["actualizarConfiguracion"])) {

        ConfiguracionControlador::ctrActualizarConfiguracionAjax();

    }

}

// --- FIN DEL REEMPLAZO / AGREGADO ---

// Interceptores AJAX para Cuentas Por Pagar (CxP)
if(isset($_POST["idCuentaPorPagar"]) || isset($_POST["idCuentaAbono"])) {
    require_once "controlador/CuentasPorPagarControlador.php";
    
    if(isset($_POST["idCuentaPorPagar"])) {
        CuentasPorPagarControlador::ctrMostrarPagosAjax();
    }
    if(isset($_POST["idCuentaAbono"])) {
        CuentasPorPagarControlador::ctrRegistrarAbonoAjax();
    }
}
// 4. Instanciamos la plantilla para que se muestre en pantalla
$plantilla = new PlantillaControlador();
$plantilla->ctrPlantilla();