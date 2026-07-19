<?php
session_start();
require_once "controlador/CierreZControlador.php";
require_once "modelo/CierreZ.php";

if (!isset($_GET["idCierreZ"])) {
    echo "Falta el ID del Cierre.";
    exit;
}

$idCierre = $_GET["idCierreZ"];
$cierre = CierreZControlador::ctrMostrarCierresZ("id", $idCierre);

if (!$cierre) {
    echo "El Cierre Z no existe.";
    exit;
}

// Extraer pagos electrónicos del JSON
$pagosElectronicos = json_decode($cierre["pagos_electronicos"], true);
$zelle = isset($pagosElectronicos["zelle_usd"]) ? floatval($pagosElectronicos["zelle_usd"]) : 0;
$pm = isset($pagosElectronicos["pago_movil_bs"]) ? floatval($pagosElectronicos["pago_movil_bs"]) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Z - REDITUS</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 14px; margin: 0; padding: 0; background-color: #f0f0f0; }
        .ticket { width: 80mm; max-width: 80mm; background-color: white; padding: 15px; margin: 0 auto; box-sizing: border-box; }
        .centrado { text-align: center; }
        .negrita { font-weight: bold; }
        .separador { border-bottom: 1px dashed #000; margin: 10px 0; }
        .fila { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .titulo { font-size: 18px; margin-bottom: 5px; }
        .pie { font-size: 12px; margin-top: 20px; text-align: center; }
        @media print { body { background-color: white; } .ticket { margin: 0; padding: 0; } }
    </style>
</head>
<body>

<div class="ticket">
    <div class="centrado">
        <div class="negrita titulo">REDITUS - SISTEMA POS</div>
        <div>REPORTE Z (CIERRE DE TIENDA)</div>
        <div class="separador"></div>
    </div>

    <div class="fila">
        <span>Reporte Nro:</span>
        <span class="negrita"><?php echo str_pad($cierre["id"], 6, "0", STR_PAD_LEFT); ?></span>
    </div>
    <div class="fila">
        <span>Fecha y Hora:</span>
        <span><?php echo date("d/m/Y H:i", strtotime($cierre["fecha_cierre"])); ?></span>
    </div>
    <div class="fila">
        <span>Gerente:</span>
        <span>@<?php echo strtoupper($cierre["nombre_gerente"]); ?></span>
    </div>

    <div class="separador"></div>
    <div class="centrado negrita">CONSOLIDADO FÍSICO (CAJAS)</div>
    <div class="separador"></div>

    <div class="fila">
        <span>Efectivo ($):</span>
        <span>$ <?php echo number_format($cierre["efectivo_caja_usd"], 2); ?></span>
    </div>
    <div class="fila">
        <span>Efectivo (Bs):</span>
        <span>Bs <?php echo number_format($cierre["efectivo_caja_bs"], 2); ?></span>
    </div>
    <div class="fila">
        <span>Punto de Venta:</span>
        <span>Bs <?php echo number_format($cierre["punto_venta_bs"], 2); ?></span>
    </div>

    <div class="separador"></div>
    <div class="centrado negrita">CONSOLIDADO ELECTRÓNICO</div>
    <div class="separador"></div>

    <div class="fila">
        <span>Zelle / Binance:</span>
        <span>$ <?php echo number_format($zelle, 2); ?></span>
    </div>
    <div class="fila">
        <span>Pago Móvil:</span>
        <span>Bs <?php echo number_format($pm, 2); ?></span>
    </div>

    <div class="separador"></div>
    <div class="centrado negrita">MOVIMIENTOS DE CAJA PRINCIPAL</div>
    <div class="separador"></div>

    <div class="fila">
        <span>Gastos del Día:</span>
        <span style="color:red;">- $ <?php echo number_format($cierre["gastos_totales"], 2); ?></span>
    </div>
    <div class="fila">
        <span>Ingresos Extras:</span>
        <span>+ $ <?php echo number_format($cierre["ingresos_extras"], 2); ?></span>
    </div>

    <div class="separador"></div>
    <br><br><br>
    <div class="centrado">
        <div class="separador" style="width: 70%; margin: 0 auto; border-bottom: 1px solid #000;"></div>
        <div>FIRMA GERENTE</div>
    </div>
    
    <div class="pie">
        <p>Este reporte empaqueta todos los Cierres X de la jornada operativa.</p>
        <p>FIN DEL DÍA FINANCIERO</p>
    </div>
</div>

<script>
    // Imprimir automáticamente al abrir
    window.onload = function() { window.print(); }
</script>

</body>
</html>