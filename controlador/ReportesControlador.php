<?php
require_once "modelo/Reportes.php";

class ReportesControlador {

    /* ==============================================================
       1. INTERCEPTOR AJAX: VENTAS POR RANGO DE FECHAS (Blindado)
       ============================================================== */
    public static function ctrResumenVentasAjax() {
        if (isset($_POST["fechaInicial"]) && isset($_POST["fechaFinal"])) {
            
            // 1. BLINDAJE DE SESIÓN ESTRICTO
            if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
                echo json_encode(["status" => "error", "mensaje" => "Sesión no válida o caducada."]);
                exit();
            }

            // 2. BLINDAJE DE PERMISOS (RBAC) - Evita minería de datos interna
            $esAdmin = ($_SESSION["rol_id"] == 1);
            $permisos = isset($_SESSION["permisos"]) ? $_SESSION["permisos"] : [];
            
            if (!$esAdmin && !in_array("all", $permisos) && !in_array("ver_reportes", $permisos)) {
                echo json_encode(["status" => "error", "mensaje" => "Acceso Denegado: No tiene autorización para consultar finanzas."]);
                exit();
            }

            $fechaInicial = $_POST["fechaInicial"];
            $fechaFinal = $_POST["fechaFinal"];

            // 3. VALIDACIÓN DE FORMATO (Expresión Regular Anti-Tampering)
            // Asegura que nadie inyecte código o formatos inválidos en las variables de fecha
            $patronFecha = '/^\d{4}-\d{2}-\d{2}$/';

            if($fechaInicial == "" || $fechaFinal == ""){
                $fechaInicial = date("Y-m-01"); // Primer día del mes actual
                $fechaFinal = date("Y-m-t");    // Último día del mes actual
            } else if (!preg_match($patronFecha, $fechaInicial) || !preg_match($patronFecha, $fechaFinal)) {
                echo json_encode(["status" => "error", "mensaje" => "Formato de fecha manipulado o inválido."]);
                exit();
            }

            // 4. EXTRACCIÓN Y RESPUESTA
            $respuesta = Reportes::mdlResumenVentas($fechaInicial, $fechaFinal);
            
            echo json_encode(["data" => $respuesta]);
            exit();
        }
    }

    /* ==============================================================
       2. EXTRACCIÓN DE DATOS: STOCK MUERTO (Tipado Estricto)
       ============================================================== */
    public static function ctrStockMuerto($dias = 30) {
        // Forzamos la variable a entero absoluto. Neutraliza intentos de inyección por $_GET
        $diasInt = intval($dias);
        if ($diasInt <= 0) $diasInt = 30; // Valor por defecto seguro
        
        return Reportes::mdlStockMuerto($diasInt);
    }

    /* ==============================================================
       3. EXTRACCIÓN DE DATOS: ANTIGÜEDAD DE INVENTARIO (Tipado Estricto)
       ============================================================== */
    public static function ctrAntiguedadInventario($dias = 60) {
        // Forzamos la variable a entero absoluto.
        $diasInt = intval($dias);
        if ($diasInt <= 0) $diasInt = 60; // Valor por defecto seguro
        
        return Reportes::mdlAntiguedadInventario($diasInt);
    }
}
?>