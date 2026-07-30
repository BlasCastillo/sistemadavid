<?php
require_once "modelo/Lineas.php";
require_once "modelo/Bitacora.php"; // INYECCIÓN GLOBAL

class LineasControlador {

    public static function ctrMostrarLineas(?int $id = null, int $filtroEstado = 1) {
        if ($id != null) {
            return Lineas::buscarPorId($id);
        } else {
            return Lineas::leerTodas($filtroEstado);
        }
    }

    public static function ctrCrearLinea() {
        if (isset($_POST["nombreLinea"])) {
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nombreLinea"])) {
                
                $existe = Lineas::verificarDuplicado($_POST["nombreLinea"]);
                if ($existe) {
                    $status = ($existe->estado == 1) ? "activa" : "INACTIVA";
                    echo json_encode(["status" => "error", "mensaje" => "La línea ya existe y está " . $status . "."]);
                    exit();
                }

                $linea = new Lineas();
                $linea->setNombre($_POST["nombreLinea"]);

                if ($linea->crear()) {
                    
                    // ===================================================
                    // BITÁCORA: CREACIÓN DE LÍNEA
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Archivo/Líneas", "Creación", "Creó la línea de productos: " . $_POST["nombreLinea"]);
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "Línea guardada correctamente."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error interno de base de datos."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "No se permiten caracteres especiales."]);
            }
            exit();
        }
    }

    public static function ctrActualizarLinea() {
        if (isset($_POST["idLineaEditar"]) && isset($_POST["nombreLineaEditar"])) {
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nombreLineaEditar"])) {
                
                $existe = Lineas::verificarDuplicado($_POST["nombreLineaEditar"]);
                if ($existe && $existe->id != $_POST["idLineaEditar"]) {
                    echo json_encode(["status" => "error", "mensaje" => "Ya existe otra línea con ese nombre."]);
                    exit();
                }

                $linea = new Lineas();
                $linea->setId($_POST["idLineaEditar"]);
                $linea->setNombre($_POST["nombreLineaEditar"]);

                if ($linea->actualizar()) {
                    
                    // ===================================================
                    // BITÁCORA: ACTUALIZACIÓN DE LÍNEA
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Archivo/Líneas", "Actualización", "Actualizó el nombre de la línea a: " . $_POST["nombreLineaEditar"]);
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "Línea actualizada correctamente."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al actualizar."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "No se permiten caracteres especiales."]);
            }
            exit();
        }
    }

    public static function ctrEliminarLinea() {
        if (isset($_POST["idLineaEliminar"])) {
            $linea = new Lineas();
            $linea->setId($_POST["idLineaEliminar"]);

            if ($linea->desactivar()) {
                
                // ===================================================
                // BITÁCORA: DESACTIVACIÓN DE LÍNEA
                // ===================================================
                Bitacora::registrarAccion($_SESSION["id_usuario"], "Archivo/Líneas", "Desactivación", "Desactivó la línea ID: " . $_POST["idLineaEliminar"]);
                // ===================================================

                echo json_encode(["status" => "success", "mensaje" => "Línea desactivada correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al desactivar."]);
            }
            exit();
        }
    }

    public static function ctrActivarLinea() {
        if (isset($_POST["idLineaActivar"])) {
            $linea = new Lineas();
            $linea->setId($_POST["idLineaActivar"]);

            if ($linea->activar()) {
                
                // ===================================================
                // BITÁCORA: REACTIVACIÓN DE LÍNEA
                // ===================================================
                Bitacora::registrarAccion($_SESSION["id_usuario"], "Archivo/Líneas", "Reactivación", "Reactivó la línea ID: " . $_POST["idLineaActivar"]);
                // ===================================================

                echo json_encode(["status" => "success", "mensaje" => "Línea reactivada correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al reactivar."]);
            }
            exit();
        }
    }
}
?>