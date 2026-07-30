<?php
require_once "modelo/Proveedores.php";
require_once "modelo/Bitacora.php"; // INYECCIÓN GLOBAL

class ProveedoresControlador {

    public static function ctrMostrarProveedores(?int $id = null, int $filtroEstado = 1) {
        if ($id != null) {
            return Proveedores::buscarPorId($id);
        } else {
            return Proveedores::leerTodos($filtroEstado);
        }
    }

    public static function ctrCrearProveedor() {
        if (isset($_POST["nuevoDocProveedor"]) && isset($_POST["nuevaRazonSocial"])) {
            if (preg_match('/^[a-zA-Z0-9-]+$/', $_POST["nuevoDocProveedor"])) {
                
                // Filtro QA para evitar congelamientos por duplicado de documento
                $existe = Proveedores::verificarDocumentoDuplicado($_POST["nuevoDocProveedor"]);
                if ($existe) {
                    $status = ($existe->estado == 1) ? "activo" : "INACTIVO";
                    echo json_encode(["status" => "error", "mensaje" => "El documento ya está registrado bajo un proveedor " . $status . "."]);
                    exit();
                }

                $proveedor = new Proveedores();
                $proveedor->setDocumento($_POST["nuevoDocProveedor"]);
                $proveedor->setRazonSocial($_POST["nuevaRazonSocial"]);
                $proveedor->setTelefono($_POST["nuevoTelefonoProveedor"] ?? null);
                $proveedor->setEmail($_POST["nuevoEmailProveedor"] ?? null);
                $proveedor->setDireccion($_POST["nuevaDireccionProveedor"] ?? null);

                if ($proveedor->crear()) {
                    
                    // ===================================================
                    // BITÁCORA: CREACIÓN DE PROVEEDOR
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Compras/Proveedores", "Creación", "Registró al proveedor: " . $_POST["nuevaRazonSocial"] . " (Doc: " . $_POST["nuevoDocProveedor"] . ")");
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "Proveedor registrado con éxito."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error interno en el servidor."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "El documento contiene caracteres no permitidos."]);
            }
            exit();
        }
    }

    public static function ctrActualizarProveedor() {
        if (isset($_POST["idProveedorEditar"]) && isset($_POST["editarDocProveedor"])) {
            if (preg_match('/^[a-zA-Z0-9-]+$/', $_POST["editarDocProveedor"])) {
                
                $existe = Proveedores::verificarDocumentoDuplicado($_POST["editarDocProveedor"]);
                if ($existe && $existe->id != $_POST["idProveedorEditar"]) {
                    echo json_encode(["status" => "error", "mensaje" => "Ya existe otro proveedor con este mismo documento."]);
                    exit();
                }

                $proveedor = new Proveedores();
                $proveedor->setId($_POST["idProveedorEditar"]);
                $proveedor->setDocumento($_POST["editarDocProveedor"]);
                $proveedor->setRazonSocial($_POST["editarRazonSocial"]);
                $proveedor->setTelefono($_POST["editarTelefonoProveedor"] ?? null);
                $proveedor->setEmail($_POST["editarEmailProveedor"] ?? null);
                $proveedor->setDireccion($_POST["editarDireccionProveedor"] ?? null);

                if ($proveedor->actualizar()) {
                    
                    // ===================================================
                    // BITÁCORA: ACTUALIZACIÓN DE PROVEEDOR
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Compras/Proveedores", "Actualización", "Actualizó los datos del proveedor Doc: " . $_POST["editarDocProveedor"]);
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "Datos del proveedor actualizados."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al actualizar."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "El documento contiene caracteres no permitidos."]);
            }
            exit();
        }
    }

    public static function ctrEliminarProveedor() {
        if (isset($_POST["idProveedorEliminar"])) {
            $proveedor = new Proveedores();
            $proveedor->setId($_POST["idProveedorEliminar"]);

            if ($proveedor->desactivar()) {
                
                // ===================================================
                // BITÁCORA: DESACTIVACIÓN DE PROVEEDOR
                // ===================================================
                Bitacora::registrarAccion($_SESSION["id_usuario"], "Compras/Proveedores", "Desactivación", "Desactivó al proveedor ID: " . $_POST["idProveedorEliminar"]);
                // ===================================================

                echo json_encode(["status" => "success", "mensaje" => "Proveedor desactivado correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al desactivar."]);
            }
            exit();
        }
    }

    public static function ctrActivarProveedor() {
        if (isset($_POST["idProveedorActivar"])) {
            $proveedor = new Proveedores();
            $proveedor->setId($_POST["idProveedorActivar"]);

            if ($proveedor->activar()) {
                
                // ===================================================
                // BITÁCORA: REACTIVACIÓN DE PROVEEDOR
                // ===================================================
                Bitacora::registrarAccion($_SESSION["id_usuario"], "Compras/Proveedores", "Reactivación", "Reactivó al proveedor ID: " . $_POST["idProveedorActivar"]);
                // ===================================================

                echo json_encode(["status" => "success", "mensaje" => "Proveedor reactivado correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al reactivar."]);
            }
            exit();
        }
    }
}
?>