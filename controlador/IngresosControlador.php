<?php
class IngresosControlador {

    /* ==============================================================
       1. MOSTRAR INGRESOS EXTRAORDINARIOS
       ============================================================== */
    public static function ctrMostrarIngresos($item, $valor) {
        $respuesta = Ingresos::mdlMostrarIngresos($item, $valor);
        return $respuesta;
    }

}