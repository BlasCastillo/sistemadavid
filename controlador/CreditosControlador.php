<?php
require_once "modelo/Creditos.php";

class CreditosControlador {

    public static function ctrMostrarCreditos() {
        $respuesta = Creditos::mdlMostrarCreditos();
        return $respuesta;
    }

    public static function ctrRegistrarAbonoAjax() {
        if(isset($_POST["idVentaAbono"])) {
            
            $datos = [
                "venta_id" => intval($_POST["idVentaAbono"]),
                "metodo_pago" => trim($_POST["metodoAbono"]),
                "moneda" => trim($_POST["monedaAbono"]),
                "monto_pagado" => floatval($_POST["montoAbono"]),
                "referencia" => trim($_POST["referenciaAbono"])
            ];

            $respuesta = Creditos::mdlRegistrarAbono($datos);

            // Modificado para capturar el ID del pago en el JSON
            if($respuesta["estado"] == "Saldada") {
                echo json_encode(["status" => "success", "mensaje" => "Abono registrado. La factura ha sido saldada.", "id_pago" => $respuesta["id_pago"]]);
            } else if($respuesta["estado"] == "Abonada") {
                echo json_encode(["status" => "success", "mensaje" => "Abono sumado a la deuda pendiente.", "id_pago" => $respuesta["id_pago"]]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error de base de datos al guardar el abono."]);
            }
            exit();
        }
    }

    // NUEVO: Controlador para alimentar el modal de Historial
    public static function ctrMostrarPagosVentaAjax() {
        if(isset($_POST["idVentaHistorial"])) {
            $id_venta = intval($_POST["idVentaHistorial"]);
            $respuesta = Creditos::mdlMostrarPagosVenta($id_venta);
            echo json_encode($respuesta);
            exit();
        }
    }
}
?>