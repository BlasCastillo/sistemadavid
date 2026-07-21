<?php
require_once "modelo/Dashboard.php";

class DashboardControlador {

    public static function ctrResumenDiario() {
        return Dashboard::mdlResumenDiario();
    }

    public static function ctrResumenGlobal() {
        return Dashboard::mdlResumenGlobal();
    }

    public static function ctrTopProductos() {
        return Dashboard::mdlTopProductos();
    }

    public static function ctrGraficoVentas() {
        return Dashboard::mdlGraficoVentas();
    }
    // Interceptor AJAX para el Gráfico
    public static function ctrGraficoVentasAjax() {
        if(isset($_POST["cargarGraficoVentas"])) {
            $respuesta = Dashboard::mdlGraficoVentas();
            echo json_encode($respuesta);
            exit();
        }
    }
}