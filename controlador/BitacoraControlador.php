<?php
require_once "modelo/Bitacora.php";

class BitacoraControlador {

    /*=============================================
    MOSTRAR HISTORIAL DE LA BITÁCORA
    =============================================*/
    public static function ctrMostrarBitacora($fechaInicio = null, $fechaFin = null, $usuario_id = null, $modulo = null) {
        
        $respuesta = Bitacora::leerHistorial($fechaInicio, $fechaFin, $usuario_id, $modulo);
        return $respuesta;
    }
}
?>