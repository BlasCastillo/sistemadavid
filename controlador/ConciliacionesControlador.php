<?php
require_once "modelo/Conciliaciones.php";
require_once "modelo/Bitacora.php"; // INYECCIÓN GLOBAL

class ConciliacionesControlador {

    /* ==============================================================
       1. EXTRAER TOTALES PARA LAS TARJETAS SUPERIORES
       ============================================================== */
    public static function ctrTotalesConsolidados() {
        $respuesta = Conciliaciones::mdlTotalesConsolidados();
        
        // Formateamos la salida para que siempre devuelva Bs y USD
        $totales = ["USD" => 0, "BS" => 0];
        foreach ($respuesta as $item) {
            if ($item->moneda == "USD") { $totales["USD"] = $item->total; }
            if ($item->moneda == "BS" || $item->moneda == "Bs") { $totales["BS"] = $item->total; }
        }
        return $totales;
    }

    /* ==============================================================
       2. ENDPOINT AJAX PARA CARGAR TABLAS
       ============================================================== */
    public static function ctrMostrarPagosAjax() {
        if(isset($_POST["estadoConciliacion"])) {
            
            // BLINDAJE DE SESIÓN 1: Solo logueados
            if (!isset($_SESSION["id_usuario"])) {
                echo json_encode(["status" => "error", "mensaje" => "Sesión caducada."]);
                exit();
            }

            $estado = $_POST["estadoConciliacion"];
            $respuesta = Conciliaciones::mdlMostrarPagos($estado);
            echo json_encode(["data" => $respuesta]);
            exit();
        }
    }

    /* ==============================================================
       3. ENDPOINT AJAX PARA APROBAR CONCILIACIÓN (NÚCLEO DE SEGURIDAD)
       ============================================================== */
    public static function ctrRegistrarConciliacionAjax() {
        if(isset($_POST["idPagoConciliar"])) {
            
            // BLINDAJE DE SESIÓN 2: Anti API-Tampering
            if (!isset($_SESSION["id_usuario"])) {
                echo json_encode(["status" => "error", "mensaje" => "Su sesión ha expirado por inactividad. Recargue la página."]);
                exit();
            }

            // BLINDAJE RBAC 3: Solo Gerentes / Administradores pueden aprobar dinero
            $esAdmin = ($_SESSION["rol_id"] == 1);
            $permisos = $_SESSION["permisos"] ?? [];
            if (!$esAdmin && !in_array("all", $permisos) && !in_array("conciliar_pagos", $permisos)) {
                
                // ===================================================
                // BITÁCORA: INTENTO DE ACCESO NO AUTORIZADO
                // ===================================================
                Bitacora::registrarAccion($_SESSION["id_usuario"], "Tesorería/Conciliaciones", "Bloqueo de Seguridad", "Intentó aprobar una conciliación sin tener los permisos necesarios.");
                // ===================================================
                
                echo json_encode(["status" => "error", "mensaje" => "Acceso Denegado: Su rol no tiene autorización para auditar tesorería."]);
                exit();
            }

            // Sanitización estricta del ID entrante
            $datos = [
                "pago_id" => intval($_POST["idPagoConciliar"]),
                "usuario_id" => intval($_SESSION["id_usuario"])
            ];

            $respuesta = Conciliaciones::mdlRegistrarConciliacion($datos);

            if($respuesta){
                
                // ===================================================
                // BITÁCORA: CONCILIACIÓN APROBADA
                // ===================================================
                Bitacora::registrarAccion($_SESSION["id_usuario"], "Tesorería/Conciliaciones", "Aprobación", "Auditó y aprobó el pago bancario ID: " . $_POST["idPagoConciliar"]);
                // ===================================================

                echo json_encode(["status" => "success", "mensaje" => "Transacción validada correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error o transacción ya conciliada previamente."]);
            }
            exit();
        }
    }
}
?>