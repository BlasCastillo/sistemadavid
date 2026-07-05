<?php

class PlantillaControlador {

    /*=============================================
    LLAMADA A LA PLANTILLA
    =============================================*/
    public static function ctrPlantilla() {
        // Incluimos el archivo que contendrá todo el HTML base
        include "vista/plantilla.php";
    }
}
?>