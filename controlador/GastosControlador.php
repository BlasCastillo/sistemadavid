<?php
require_once "modelo/Gastos.php";
require_once "modelo/Bitacora.php"; // INYECCIÓN GLOBAL

class GastosControlador {

    public static function ctrMostrarGastos(?int $id = null, int $filtroEstado = 1) {
        if ($id != null) {
            return Gastos::buscarPorId($id);
        } else {
            return Gastos::leerTodos($filtroEstado);
        }
    }

    public static function ctrCrearGasto() {
        if (isset($_POST["conceptoGasto"]) && isset($_POST["montoGasto"])) {
            if (is_numeric($_POST["montoGasto"]) && $_POST["montoGasto"] > 0) {
                
                $gasto = new Gastos();
                $gasto->setConcepto($_POST["conceptoGasto"]);
                $gasto->setMonto(floatval($_POST["montoGasto"]));
                $gasto->setTipo($_POST["tipoGasto"]);
                $gasto->setFecha($_POST["fechaGasto"]);

                if ($gasto->crear()) {
                    
                    // ===================================================
                    // BITÁCORA: REGISTRO DE GASTO
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Finanzas/Gastos", "Creación", "Registró un gasto operativo por $" . $_POST["montoGasto"] . " (" . $_POST["conceptoGasto"] . ")");
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "Gasto operativo registrado con éxito."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error interno en la base de datos."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "El monto debe ser un valor numérico mayor a cero."]);
            }
            exit();
        }
    }

    public static function ctrActualizarGasto() {
        if (isset($_POST["idGastoEditar"]) && isset($_POST["conceptoGastoEditar"])) {
            if (is_numeric($_POST["montoGastoEditar"]) && $_POST["montoGastoEditar"] > 0) {
                
                $gasto = new Gastos();
                $gasto->setId(intval($_POST["idGastoEditar"]));
                $gasto->setConcepto($_POST["conceptoGastoEditar"]);
                $gasto->setMonto(floatval($_POST["montoGastoEditar"]));
                $gasto->setTipo($_POST["tipoGastoEditar"]);
                $gasto->setFecha($_POST["fechaGastoEditar"]);

                if ($gasto->actualizar()) {
                    
                    // ===================================================
                    // BITÁCORA: ACTUALIZACIÓN DE GASTO
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Finanzas/Gastos", "Actualización", "Actualizó los detalles del gasto ID: " . $_POST["idGastoEditar"]);
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "Registro de gasto actualizado."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al actualizar el registro."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "El monto debe ser numérico y mayor a cero."]);
            }
            exit();
        }
    }

    public static function ctrAnularGasto() {
        if (isset($_POST["idGastoAnular"])) {
            $gasto = new Gastos();
            $gasto->setId($_POST["idGastoAnular"]);

            if ($gasto->anular()) {
                
                // ===================================================
                // BITÁCORA: ANULACIÓN DE GASTO
                // ===================================================
                Bitacora::registrarAccion($_SESSION["id_usuario"], "Finanzas/Gastos", "Anulación", "Anuló el registro de gasto ID: " . $_POST["idGastoAnular"]);
                // ===================================================

                echo json_encode(["status" => "success", "mensaje" => "El gasto ha sido anulado correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al procesar la anulación."]);
            }
            exit();
        }
    }

    public static function ctrReactivarGasto() {
        if (isset($_POST["idGastoReactivar"])) {
            $gasto = new Gastos();
            $gasto->setId($_POST["idGastoReactivar"]);

            if ($gasto->reactivar()) {
                
                // ===================================================
                // BITÁCORA: REACTIVACIÓN DE GASTO
                // ===================================================
                Bitacora::registrarAccion($_SESSION["id_usuario"], "Finanzas/Gastos", "Reactivación", "Restauró el registro de gasto ID: " . $_POST["idGastoReactivar"]);
                // ===================================================

                echo json_encode(["status" => "success", "mensaje" => "El gasto ha sido restaurado."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al restaurar."]);
            }
            exit();
        }
    }
}
?>