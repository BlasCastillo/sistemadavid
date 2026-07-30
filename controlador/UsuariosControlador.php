<?php
// Requerimos el modelo para interactuar con la base de datos
require_once "modelo/Usuarios.php";
// INYECCIÓN GLOBAL DE LA BITÁCORA PARA TODO ESTE CONTROLADOR
require_once "modelo/Bitacora.php"; 

class UsuariosControlador {

    /*=============================================
    INGRESO DE USUARIO (LOGIN VÍA AJAX)
    =============================================*/
    public static function ctrIngresoUsuario() {
        
        if (isset($_POST["ingUsuario"]) && isset($_POST["ingClave"])) {
            
            if (preg_match('/^[a-zA-Z0-9_]+$/', $_POST["ingUsuario"])) {
                
                $usuarioValidado = Usuarios::iniciarSesion($_POST["ingUsuario"], $_POST["ingClave"]);

                if ($usuarioValidado) {
                    $_SESSION["iniciarSesion"] = "ok";
                    $_SESSION["id_usuario"] = $usuarioValidado->getId();
                    $_SESSION["rol_id"] = $usuarioValidado->getRolId();
                    $_SESSION["usuario"] = $usuarioValidado->getUsuario();
                    $_SESSION["nombre_completo"] = $usuarioValidado->getNombreCompleto();
                    
                    require_once "modelo/Roles.php";
                    $rolDelUsuario = Roles::buscarPorId($usuarioValidado->getRolId());
                    if ($rolDelUsuario) {
                        $_SESSION["nombre_rol"] = $rolDelUsuario->getNombre(); 
                        $_SESSION["permisos"] = json_decode($rolDelUsuario->getPermisos() ?? '[]', true); 
                    } else {
                        $_SESSION["nombre_rol"] = "Sin Rol";
                        $_SESSION["permisos"] = [];
                    }

                    // ===================================================
                    // BITÁCORA: REGISTRO DE INICIO DE SESIÓN
                    // ===================================================
                    Bitacora::registrarAccion(
                        $_SESSION["id_usuario"], 
                        "Seguridad", 
                        "Inicio de Sesión", 
                        "El usuario @" . $usuarioValidado->getUsuario() . " ingresó al sistema."
                    );
                    // ===================================================

                    $rutaDestino = "dashboard"; 
                    
                    if ($_SESSION["rol_id"] != 1 && !in_array("ver_dashboard", $_SESSION["permisos"])) {
                        $rutaDestino = "ventas-crear";
                    }

                    echo json_encode(["status" => "success", "mensaje" => "Acceso concedido.", "ruta" => $rutaDestino]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Usuario o contraseña incorrectos, o usuario inactivo."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "El usuario contiene caracteres no permitidos."]);
            }
            exit();
        }
    }

    /*=============================================
    MOSTRAR USUARIOS (Para tablas y selects)
    =============================================*/
    public static function ctrMostrarUsuarios(?int $id = null, int $filtroEstado = 1) {
        if ($id != null) {
            return Usuarios::buscarPorId($id);
        } else {
            return Usuarios::leerTodos($filtroEstado);
        }
    }

    /*=============================================
    CREAR USUARIO (VÍA AJAX)
    =============================================*/
    public static function ctrCrearUsuario() {
        
        if (isset($_POST["nuevoUsuario"])) {
            
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoNombre"]) &&
                preg_match('/^[a-zA-Z0-9_]+$/', $_POST["nuevoUsuario"]) &&
                preg_match('/^[a-zA-Z0-9]+$/', $_POST["nuevaClave"])) {
                
                $existe = Usuarios::verificarUsuarioDuplicado($_POST["nuevoUsuario"]);
                if ($existe) {
                    $estadoActual = ($existe->estado == 1) ? "activo" : "INACTIVO";
                    echo json_encode(["status" => "error", "mensaje" => "El nombre de usuario '@" . $_POST["nuevoUsuario"] . "' ya existe y se encuentra " . $estadoActual . ". Por favor, elija otro o reactívelo."]);
                    exit();
                }
                
                $usuario = new Usuarios();
                $usuario->setRolId($_POST["nuevoRol"]);
                $usuario->setUsuario($_POST["nuevoUsuario"]);
                $usuario->setClave($_POST["nuevaClave"]);
                $usuario->setNombreCompleto($_POST["nuevoNombre"]);
                
                if (!empty($_POST["nuevoPin"])) {
                    $usuario->setPinAutorizacion($_POST["nuevoPin"]);
                }

                if ($usuario->crear()) {
                    
                    // ===================================================
                    // BITÁCORA: CREACIÓN DE USUARIO
                    // ===================================================
                    Bitacora::registrarAccion(
                        $_SESSION["id_usuario"], 
                        "Usuarios", 
                        "Creación", 
                        "Creó al usuario: @" . $_POST["nuevoUsuario"] . " (" . $_POST["nuevoNombre"] . ")"
                    );
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "El usuario ha sido guardado correctamente."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Ocurrió un error al guardar el usuario."]);
                }

            } else {
                echo json_encode(["status" => "error", "mensaje" => "Los campos no pueden llevar caracteres especiales."]);
            }
            exit();
        }
    }

    /*=============================================
    ACTUALIZAR USUARIO (VÍA AJAX)
    =============================================*/
    public static function ctrActualizarUsuario() {
        if (isset($_POST["editarIdUsuario"]) && isset($_POST["editarUsuario"])) {
            
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["editarNombre"]) &&
                preg_match('/^[a-zA-Z0-9_]+$/', $_POST["editarUsuario"])) {

                $usuario = new Usuarios();
                $usuario->setId($_POST["editarIdUsuario"]);
                $usuario->setRolId($_POST["editarRol"]);
                $usuario->setUsuario($_POST["editarUsuario"]);
                $usuario->setNombreCompleto($_POST["editarNombre"]);
                
                if (!empty($_POST["editarPin"])) {
                    $pinHash = password_hash($_POST["editarPin"], PASSWORD_BCRYPT);
                    $usuario->setPinAutorizacion($pinHash);
                } else {
                    $usuario->setPinAutorizacion(null);
                }
                
                if ($usuario->actualizar()) {
                    
                    // ===================================================
                    // BITÁCORA: EDICIÓN DE USUARIO
                    // ===================================================
                    Bitacora::registrarAccion(
                        $_SESSION["id_usuario"], 
                        "Usuarios", 
                        "Actualización", 
                        "Modificó los datos del usuario: @" . $_POST["editarUsuario"]
                    );
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "El usuario ha sido actualizado."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al actualizar el usuario."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Los campos no pueden llevar caracteres especiales."]);
            }
            exit();
        }
    }

    /*=============================================
    ELIMINAR USUARIO (BORRADO LÓGICO VÍA AJAX)
    =============================================*/
    public static function ctrEliminarUsuario() {
        
        if (isset($_POST["idUsuarioEliminar"])) {
            
            $usuario = new Usuarios();
            $usuario->setId($_POST["idUsuarioEliminar"]);

            if ($usuario->eliminar()) {
                
                // ===================================================
                // BITÁCORA: DESACTIVACIÓN DE USUARIO
                // ===================================================
                Bitacora::registrarAccion(
                    $_SESSION["id_usuario"], 
                    "Usuarios", 
                    "Desactivación", 
                    "Desactivó al usuario con ID: " . $_POST["idUsuarioEliminar"]
                );
                // ===================================================

                echo json_encode(["status" => "success", "mensaje" => "El usuario ha sido desactivado del sistema."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al intentar desactivar el usuario."]);
            }
            exit();
        }
    }
    /*=============================================
    ACTUALIZAR CONTRASEÑA (VÍA AJAX)
    =============================================*/
    public static function ctrActualizarClaveUsuario() {
        if (isset($_POST["editarClaveId"]) && isset($_POST["nuevaClaveSegura"])) {
            
            if (preg_match('/^[a-zA-Z0-9]+$/', $_POST["nuevaClaveSegura"])) {
                
                $usuario = new Usuarios();
                $usuario->setId($_POST["editarClaveId"]);
                
                $claveHash = password_hash($_POST["nuevaClaveSegura"], PASSWORD_BCRYPT);
                $usuario->setClave($claveHash);

                if ($usuario->actualizarClave()) {
                    
                    // ===================================================
                    // BITÁCORA: CAMBIO DE CONTRASEÑA
                    // ===================================================
                    Bitacora::registrarAccion(
                        $_SESSION["id_usuario"], 
                        "Usuarios", 
                        "Cambio de Clave", 
                        "Cambió la contraseña del usuario con ID: " . $_POST["editarClaveId"]
                    );
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "La contraseña ha sido actualizada con éxito."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error interno al actualizar la credencial."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "La contraseña solo puede contener letras y números."]);
            }
            exit();
        }
    }

    /*=============================================
    REACTIVAR USUARIO (VÍA AJAX)
    =============================================*/
    public static function ctrActivarUsuario() {
        if (isset($_POST["idUsuarioActivar"])) {
            $usuario = new Usuarios();
            $usuario->setId($_POST["idUsuarioActivar"]);

            if ($usuario->activar()) {
                
                // ===================================================
                // BITÁCORA: REACTIVACIÓN
                // ===================================================
                Bitacora::registrarAccion(
                    $_SESSION["id_usuario"], 
                    "Usuarios", 
                    "Reactivación", 
                    "Reactivó al usuario con ID: " . $_POST["idUsuarioActivar"]
                );
                // ===================================================

                echo json_encode(["status" => "success", "mensaje" => "El usuario ha sido reactivado correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al intentar reactivar el usuario."]);
            }
            exit();
        }
    }
}
?>