<?php
require_once "modelo/Ofertas.php";
require_once "modelo/Bitacora.php"; // INYECCIÓN GLOBAL

class OfertasControlador {

    /*=============================================
    MOSTRAR OFERTAS
    =============================================*/
    public static function ctrMostrarOfertas() {
        return Ofertas::leerTodas();
    }

    /*=============================================
    CREAR OFERTA (INTERCEPTOR AJAX)
    =============================================*/
    public static function ctrCrearOfertaAjax() {
        if (isset($_POST["idProductoOferta"]) && isset($_POST["fechaInicioOferta"])) {
            
            // Seguridad: Validamos que el ID del producto sea estrictamente numérico
            if (preg_match('/^[0-9]+$/', $_POST["idProductoOferta"])) {
                
                $oferta = new Ofertas();
                $oferta->setProductoId(intval($_POST["idProductoOferta"]));
                $oferta->setPorcentajeDescuento(floatval($_POST["porcentajeOferta"]));
                $oferta->setPrecioOfertaUsdt(floatval($_POST["precioFinalOferta"]));
                $oferta->setFechaInicio($_POST["fechaInicioOferta"]);
                $oferta->setFechaFin($_POST["fechaFinOferta"]);

                $respuesta = $oferta->crear();

                if ($respuesta == "ok") {
                    
                    // ===================================================
                    // BITÁCORA: CREACIÓN DE OFERTA
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Archivo/Ofertas", "Creación", "Programó una oferta para el producto ID: " . $_POST["idProductoOferta"] . " con " . $_POST["porcentajeOferta"] . "% de descuento.");
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "La oferta ha sido programada y calculada correctamente."]);
                } else if ($respuesta == "duplicado") {
                    echo json_encode(["status" => "warning", "mensaje" => "Este producto ya tiene una oferta activa que interfiere con este rango de fechas."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error crítico al intentar guardar en la base de datos."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "El ID del producto contiene caracteres inválidos."]);
            }
            exit();
        }
    }

    /*=============================================
    ELIMINAR OFERTA (INTERCEPTOR AJAX)
    =============================================*/
    public static function ctrEliminarOfertaAjax() {
        if (isset($_POST["idOfertaEliminar"])) {
            
            // Seguridad: Validar ID
            if (preg_match('/^[0-9]+$/', $_POST["idOfertaEliminar"])) {
                
                $oferta = new Ofertas();
                $oferta->setId(intval($_POST["idOfertaEliminar"]));
                
                if ($oferta->eliminar()) {
                    
                    // ===================================================
                    // BITÁCORA: ELIMINACIÓN DE OFERTA
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Archivo/Ofertas", "Eliminación", "Eliminó la oferta ID: " . $_POST["idOfertaEliminar"]);
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "La oferta ha sido eliminada del sistema."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al intentar eliminar el registro."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Identificador de oferta inválido."]);
            }
            exit();
        }
    }
}
?>