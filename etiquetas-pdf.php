<?php

require_once "controlador/EtiquetasControlador.php";

$impresion = new EtiquetasControlador();
$impresion->ctrGenerarEtiquetasPDF();
?>