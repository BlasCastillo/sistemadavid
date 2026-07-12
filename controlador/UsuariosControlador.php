<?php
// Requerimos el modelo para interactuar con la base de datos
require_once "modelo/Usuarios.php";

class UsuariosControlador {

    /*=============================================
    INGRESO DE USUARIO (LOGIN VÍA AJAX)
    =============================================*/
    public static function ctrIngresoUsuario() {
        
        // Verificamos que vengan las credenciales desde el formulario (AJAX)
        if (isset($_POST["ingUsuario"]) && isset($_POST["ingClave"])) {
            
            // Seguridad básica: evitar inyecciones en el input
            if (preg_match('/^[a-zA-Z0-9_]+$/', $_POST["ingUsuario"])) {
                
                // Llamamos al método estático del modelo
                $usuarioValidado = Usuarios::iniciarSesion($_POST["ingUsuario"], $_POST["ingClave"]);

                if ($usuarioValidado) {
                    // Si el modelo retorna la instancia del usuario, iniciamos variables de sesión
                    // (Nota: session_start() debe estar en tu index.php principal)
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
                    echo json_encode(["status" => "success", "mensaje" => "Acceso concedido."]);
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
            
            // Validamos que los campos obligatorios cumplan con expresiones regulares para seguridad
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoNombre"]) &&
                preg_match('/^[a-zA-Z0-9_]+$/', $_POST["nuevoUsuario"]) &&
                preg_match('/^[a-zA-Z0-9]+$/', $_POST["nuevaClave"])) {
                // VALIDACIÓN DE QA: Evitar duplicados y congelamientos
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
                
                // Si viene un PIN (Solo para Gerentes), lo asignamos, si no, null
                if (!empty($_POST["nuevoPin"])) {
                    $usuario->setPinAutorizacion($_POST["nuevoPin"]);
                }

                if ($usuario->crear()) {
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
                
                // Procesamos el PIN si el usuario escribió uno nuevo
                if (!empty($_POST["editarPin"])) {
                    $pinHash = password_hash($_POST["editarPin"], PASSWORD_BCRYPT);
                    $usuario->setPinAutorizacion($pinHash);
                } else {
                    $usuario->setPinAutorizacion(null);
                }
                
                if ($usuario->actualizar()) {
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
            
            // Validamos que no tenga inyecciones raras
            if (preg_match('/^[a-zA-Z0-9]+$/', $_POST["nuevaClaveSegura"])) {
                
                $usuario = new Usuarios();
                $usuario->setId($_POST["editarClaveId"]);
                
                // Encriptamos la nueva contraseña antes de enviarla al modelo
                $claveHash = password_hash($_POST["nuevaClaveSegura"], PASSWORD_BCRYPT);
                $usuario->setClave($claveHash);

                if ($usuario->actualizarClave()) {
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
                echo json_encode(["status" => "success", "mensaje" => "El usuario ha sido reactivado correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al intentar reactivar el usuario."]);
            }
            exit();
        }
    }
}
?>