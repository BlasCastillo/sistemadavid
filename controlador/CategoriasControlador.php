<?php
require_once "modelo/Categorias.php";

class CategoriasControlador {

    public static function ctrMostrarCategorias(?int $id = null, int $filtroEstado = 1) {
        if ($id != null) {
            return Categorias::buscarPorId($id);
        } else {
            return Categorias::leerTodas($filtroEstado);
        }
    }

    public static function ctrCrearCategoria() {
        if (isset($_POST["nombreCategoria"])) {
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nombreCategoria"])) {
                
                $existe = Categorias::verificarDuplicado($_POST["nombreCategoria"]);
                if ($existe) {
                    $status = ($existe->estado == 1) ? "activa" : "INACTIVA";
                    echo json_encode(["status" => "error", "mensaje" => "La categoría ya existe y está " . $status . "."]);
                    exit();
                }

                $categoria = new Categorias();
                $categoria->setLineaId(intval($_POST["idLineaPadre"]));
                $categoria->setNombre($_POST["nombreCategoria"]);

                if ($categoria->crear()) {
                    echo json_encode(["status" => "success", "mensaje" => "Categoría guardada correctamente."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error interno de base de datos."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "No se permiten caracteres especiales."]);
            }
            exit();
        }
    }

    public static function ctrActualizarCategoria() {
        if (isset($_POST["idCategoriaEditar"]) && isset($_POST["nombreCategoriaEditar"])) {
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nombreCategoriaEditar"])) {
                
                $existe = Categorias::verificarDuplicado($_POST["nombreCategoriaEditar"]);
                if ($existe && $existe->id != $_POST["idCategoriaEditar"]) {
                    echo json_encode(["status" => "error", "mensaje" => "Ya existe otra categoría con ese nombre."]);
                    exit();
                }

                $categoria = new Categorias();
                $categoria->setId($_POST["idCategoriaEditar"]);
                $categoria->setLineaId(intval($_POST["idLineaPadreEditar"]));
                $categoria->setNombre($_POST["nombreCategoriaEditar"]);

                if ($categoria->actualizar()) {
                    echo json_encode(["status" => "success", "mensaje" => "Categoría actualizada correctamente."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al actualizar."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "No se permiten caracteres especiales."]);
            }
            exit();
        }
    }

    public static function ctrEliminarCategoria() {
        if (isset($_POST["idCategoriaEliminar"])) {
            $categoria = new Categorias();
            $categoria->setId($_POST["idCategoriaEliminar"]);

            if ($categoria->desactivar()) {
                echo json_encode(["status" => "success", "mensaje" => "Categoría desactivada correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al desactivar."]);
            }
            exit();
        }
    }

    public static function ctrActivarCategoria() {
            if (isset($_POST["idCategoriaActivar"])) {
                $categoria = new Categorias();
                $categoria->setId($_POST["idCategoriaActivar"]);

                if ($categoria->activar()) {
                    echo json_encode(["status" => "success", "mensaje" => "Categoría reactivada correctamente."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al reactivar."]);
                }
                exit();
            }
        
        }
        // Método para Select Dinámico en Cascada
            public static function ctrTraerCategoriasPorLineaAjax() {
                if(isset($_POST["idLineaAjax"])) {
                    $idLinea = $_POST["idLineaAjax"];
                    $stmt = Conexion::conectar()->prepare("SELECT id, nombre FROM categorias WHERE linea_id = :linea_id AND estado = 1 ORDER BY nombre ASC");
                    $stmt->bindParam(":linea_id", $idLinea, PDO::PARAM_INT);
                    $stmt->execute();
                    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                    exit();
                }
        }
}