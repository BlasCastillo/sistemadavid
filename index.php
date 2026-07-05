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
// Interceptores del CRUD de Líneas
LineasControlador::ctrCrearLinea();
LineasControlador::ctrActualizarLinea();
LineasControlador::ctrEliminarLinea();
LineasControlador::ctrActivarLinea();
// 4. Instanciamos la plantilla para que se muestre en pantalla
$plantilla = new PlantillaControlador();
$plantilla->ctrPlantilla();