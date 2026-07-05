<?php
// Requerimos el modelo para poder instanciar la clase y acceder a la BD
require_once "modelo/Roles.php";

class RolesControlador {

    /*=============================================
    MOSTRAR ROLES (Para tablas y selects)
    =============================================*/
    public static function ctrMostrarRoles(?int $id = null) {
        if ($id != null) {
            return Roles::buscarPorId($id);
        } else {
            return Roles::leerTodos();
        }
    }

    /*=============================================
    CREAR ROL (Vía AJAX)
    =============================================*/
    public static function ctrCrearRol() {
        if (isset($_POST["nombreRol"])) {
            
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nombreRol"])) {
                
                // VALIDACIÓN DE QA: Evita el congelamiento por restricción UNIQUE en BD
                $existe = Roles::verificarNombreDuplicado($_POST["nombreRol"]);
                if ($existe) {
                    echo json_encode(["status" => "error", "mensaje" => "El rol '" . $_POST["nombreRol"] . "' ya existe en el sistema."]);
                    exit();
                }

                $rol = new Roles();
                $rol->setNombre($_POST["nombreRol"]);
                // Por defecto al crear un rol, lo dejamos sin permisos especiales (array vacío en JSON)
                $rol->setPermisos('[]'); 

                if ($rol->crear()) {
                    echo json_encode(["status" => "success", "mensaje" => "El rol ha sido guardado correctamente."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Ocurrió un error al guardar en la base de datos."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "El nombre no puede llevar caracteres especiales."]);
            }
            exit(); 
        }
    }

    /*=============================================
    ACTUALIZAR ROL (Vía AJAX)
    =============================================*/
    public static function ctrActualizarRol() {
        if (isset($_POST["idRolEditar"]) && isset($_POST["nombreRolEditar"])) {
            
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nombreRolEditar"])) {
                
                // VALIDACIÓN DE QA: Evita duplicar el nombre con otro registro existente al editar
                $existe = Roles::verificarNombreDuplicado($_POST["nombreRolEditar"]);
                if ($existe && $existe->id != $_POST["idRolEditar"]) {
                    echo json_encode(["status" => "error", "mensaje" => "Ya existe otro rol registrado con el nombre '" . $_POST["nombreRolEditar"] . "'."]);
                    exit();
                }

                $rol = new Roles();
                $rol->setId($_POST["idRolEditar"]);
                $rol->setNombre($_POST["nombreRolEditar"]);

                if ($rol->actualizar()) {
                    echo json_encode(["status" => "success", "mensaje" => "El rol ha sido actualizado correctamente."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Ocurrió un error al actualizar el rol."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "El nombre no puede llevar caracteres especiales."]);
            }
            exit();
        }
    }

    /*=============================================
    ELIMINAR ROL (Vía AJAX)
    =============================================*/
    public static function ctrEliminarRol() {
        if (isset($_POST["idRolEliminar"])) {
            $rol = new Roles();
            $rol->setId($_POST["idRolEliminar"]);

            if ($rol->eliminar()) {
                echo json_encode(["status" => "success", "mensaje" => "El rol ha sido borrado correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "No se puede borrar el rol porque tiene usuarios asignados."]);
            }
            exit();
        }
    }

    /*=============================================
    NUEVO: GUARDAR PERMISOS DINÁMICOS (Vía AJAX)
    =============================================*/
    public static function ctrGuardarPermisosRol() {
        if (isset($_POST["rolIdPermisos"])) {
            
            // Regla de seguridad: Solo el Gerente General (Rol 1) puede alterar los permisos
            if ($_SESSION["rol_id"] != 1) {
                echo json_encode(["status" => "error", "mensaje" => "No tienes autorización para alterar los roles."]);
                exit();
            }

            $idRol = intval($_POST["rolIdPermisos"]);
            
            // Si no se marcó ningún checkbox, guardamos un array vacío
            $arrayPermisos = isset($_POST["permisosActivos"]) ? $_POST["permisosActivos"] : [];
            $permisosJson = json_encode($arrayPermisos);

            $rolObj = new Roles();
            $rolObj->setId($idRol);
            $rolObj->setPermisos($permisosJson);

            if ($rolObj->actualizarPermisos()) {
                echo json_encode(["status" => "success", "mensaje" => "Permisos actualizados y consolidados con éxito."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error interno al guardar los cambios en la base de datos."]);
            }
            exit();
        }
    }
}
?>