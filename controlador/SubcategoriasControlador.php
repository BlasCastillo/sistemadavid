<?php
require_once "modelo/Subcategorias.php";
require_once "modelo/Bitacora.php"; // INYECCIÓN GLOBAL

class SubcategoriasControlador {

    public static function ctrMostrarSubcategorias(?int $id = null, int $filtroEstado = 1) {
        if ($id != null) {
            return Subcategorias::buscarPorId($id);
        } else {
            return Subcategorias::leerTodas($filtroEstado);
        }
    }

    public static function ctrCrearSubcategoria() {
        if (isset($_POST["nombreSubcategoria"]) && isset($_POST["idCategoriaPadre"])) {
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nombreSubcategoria"])) {
                
                $existe = Subcategorias::verificarDuplicado($_POST["nombreSubcategoria"]);
                if ($existe) {
                    $status = ($existe->estado == 1) ? "activa" : "INACTIVA";
                    echo json_encode(["status" => "error", "mensaje" => "La subcategoría ya existe y está " . $status . "."]);
                    exit();
                }

                $subcat = new Subcategorias();
                $subcat->setCategoriaId(intval($_POST["idCategoriaPadre"]));
                $subcat->setNombre($_POST["nombreSubcategoria"]);

                if ($subcat->crear()) {
                    
                    // ===================================================
                    // BITÁCORA: CREACIÓN DE SUBCATEGORÍA
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Archivo/Subcategorías", "Creación", "Registró la subcategoría: " . $_POST["nombreSubcategoria"]);
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "Subcategoría guardada correctamente."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error interno al guardar en BD."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "No se permiten caracteres especiales."]);
            }
            exit();
        }
    }

    public static function ctrActualizarSubcategoria() {
        if (isset($_POST["idSubcategoriaEditar"]) && isset($_POST["nombreSubcategoriaEditar"]) && isset($_POST["idCategoriaPadreEditar"])) {
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nombreSubcategoriaEditar"])) {
                
                $existe = Subcategorias::verificarDuplicado($_POST["nombreSubcategoriaEditar"]);
                if ($existe && $existe->id != $_POST["idSubcategoriaEditar"]) {
                    echo json_encode(["status" => "error", "mensaje" => "Ya existe otra subcategoría con ese nombre."]);
                    exit();
                }

                $subcat = new Subcategorias();
                $subcat->setId($_POST["idSubcategoriaEditar"]);
                $subcat->setCategoriaId(intval($_POST["idCategoriaPadreEditar"]));
                $subcat->setNombre($_POST["nombreSubcategoriaEditar"]);

                if ($subcat->actualizar()) {
                    
                    // ===================================================
                    // BITÁCORA: ACTUALIZACIÓN DE SUBCATEGORÍA
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Archivo/Subcategorías", "Actualización", "Actualizó la subcategoría a: " . $_POST["nombreSubcategoriaEditar"]);
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "Subcategoría actualizada correctamente."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al actualizar."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "No se permiten caracteres especiales."]);
            }
            exit();
        }
    }

    public static function ctrEliminarSubcategoria() {
        if (isset($_POST["idSubcategoriaEliminar"])) {
            $subcat = new Subcategorias();
            $subcat->setId($_POST["idSubcategoriaEliminar"]);

            if ($subcat->desactivar()) {
                
                // ===================================================
                // BITÁCORA: DESACTIVACIÓN DE SUBCATEGORÍA
                // ===================================================
                Bitacora::registrarAccion($_SESSION["id_usuario"], "Archivo/Subcategorías", "Desactivación", "Desactivó la subcategoría ID: " . $_POST["idSubcategoriaEliminar"]);
                // ===================================================

                echo json_encode(["status" => "success", "mensaje" => "Subcategoría desactivada correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al desactivar."]);
            }
            exit();
        }
    }

    public static function ctrActivarSubcategoria() {
        if (isset($_POST["idSubcategoriaActivar"])) {
            $subcat = new Subcategorias();
            $subcat->setId($_POST["idSubcategoriaActivar"]);

            if ($subcat->activar()) {
                
                // ===================================================
                // BITÁCORA: REACTIVACIÓN DE SUBCATEGORÍA
                // ===================================================
                Bitacora::registrarAccion($_SESSION["id_usuario"], "Archivo/Subcategorías", "Reactivación", "Reactivó la subcategoría ID: " . $_POST["idSubcategoriaActivar"]);
                // ===================================================

                echo json_encode(["status" => "success", "mensaje" => "Subcategoría reactivada correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al reactivar."]);
            }
            exit();
        }
    }
    // Método para Select Dinámico en Cascada
    public static function ctrTraerSubcategoriasPorCategoriaAjax() {
        if(isset($_POST["idCategoriaAjax"])) {
            $idCategoria = $_POST["idCategoriaAjax"];
            $stmt = Conexion::conectar()->prepare("SELECT id, nombre FROM subcategorias WHERE categoria_id = :categoria_id AND estado = 1 ORDER BY nombre ASC");
            $stmt->bindParam(":categoria_id", $idCategoria, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit();
        }
    }
}
?>