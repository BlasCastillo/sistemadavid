<?php
session_start();
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    exit("Acceso denegado.");
}

require_once "config/conexion.php";
require_once "modelo/Reportes.php";

// 1. Capturar Filtros
$fechaInicial = isset($_GET["inicio"]) ? $_GET["inicio"] : date("Y-m-01");
$fechaFinal = isset($_GET["fin"]) ? $_GET["fin"] : date("Y-m-t");
$diasStock = isset($_GET["diasStock"]) ? intval($_GET["diasStock"]) : 30;
$diasAntiguedad = isset($_GET["diasAntiguedad"]) ? intval($_GET["diasAntiguedad"]) : 60;

// 2. Extraer Data
$ventas = Reportes::mdlResumenVentas($fechaInicial, $fechaFinal);
$stockMuerto = Reportes::mdlStockMuerto($diasStock);
$antiguedad = Reportes::mdlAntiguedadInventario($diasAntiguedad);
$gastos = Reportes::mdlResumenGastos($fechaInicial, $fechaFinal);
$cogs = Reportes::mdlCostoMercanciaVendida($fechaInicial, $fechaFinal);

// 3. Matemáticas
$totalBs = 0; $totalUsd = 0; $totalFacturas = 0;
foreach ($ventas as $v) {
    $totalBs += $v["total_bs"];
    $totalUsd += $v["total_usdt"];
    $totalFacturas += $v["cantidad_facturas"];
}

$costoMercancia = floatval($cogs["costo_total"]);
$totalGastos = floatval($gastos["total_gastos"]);
$utilidadNeta = ($totalUsd - $costoMercancia) - $totalGastos;

// 4. Cabeceras para forzar descarga como Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Analitica_Logistica_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// 5. Salida HTML estructurada para Excel
echo '<meta charset="utf-8">';
echo '<table border="1">';

// --- SECCIÓN 1: KPIs ---
echo '<tr><th colspan="4" style="background-color:#2c3e50; color:white; font-size:16px;">RESUMEN FINANCIERO Y LOGÍSTICO (' . $fechaInicial . ' al ' . $fechaFinal . ')</th></tr>';
echo '<tr>
        <th style="background-color:#f4f6f6;">Total Facturas</th>
        <th style="background-color:#f4f6f6;">Ingresos Totales (USD)</th>
        <th style="background-color:#f4f6f6;">Egresos Totales (USD)</th>
        <th style="background-color:#f4f6f6;">Balance Neto (USD)</th>
      </tr>';
echo '<tr>
        <td>' . $totalFacturas . '</td>
        <td>' . str_replace('.', ',', round($totalUsd, 2)) . '</td>
        <td>' . str_replace('.', ',', round($totalGastos, 2)) . '</td>
        <td>' . str_replace('.', ',', round($utilidadNeta, 2)) . '</td>
      </tr>';
echo '<tr><td colspan="4"></td></tr>'; // Fila vacía

// --- SECCIÓN 2: FLUJO DE CAJA ---
echo '<tr><th colspan="4" style="background-color:#2980b9; color:white;">1. DESGLOSE DE INGRESOS POR DÍA</th></tr>';
echo '<tr>
        <th style="background-color:#ecf0f1;">Fecha</th>
        <th style="background-color:#ecf0f1;">Facturas Emitidas</th>
        <th style="background-color:#ecf0f1;">Monto (Bs)</th>
        <th style="background-color:#ecf0f1;">Monto (USD)</th>
      </tr>';
foreach ($ventas as $v) {
    echo '<tr>
            <td>' . date('d/m/Y', strtotime($v["fecha"])) . '</td>
            <td>' . $v["cantidad_facturas"] . '</td>
            <td>' . str_replace('.', ',', round($v["total_bs"], 2)) . '</td>
            <td>' . str_replace('.', ',', round($v["total_usdt"], 2)) . '</td>
          </tr>';
}
echo '<tr><td colspan="4"></td></tr>';

// --- SECCIÓN 3: STOCK ESTANCADO ---
echo '<tr><th colspan="4" style="background-color:#e67e22; color:white;">2. STOCK ESTANCADO (> ' . $diasStock . ' Días sin venta)</th></tr>';
echo '<tr>
        <th style="background-color:#ecf0f1;">Código de Barras</th>
        <th style="background-color:#ecf0f1;">Producto</th>
        <th style="background-color:#ecf0f1;">Stock Actual</th>
        <th style="background-color:#ecf0f1;">Capital Inmovilizado (USD)</th>
      </tr>';
foreach ($stockMuerto as $prod) {
    echo '<tr>
            <td style="mso-number-format:\'@\';">' . $prod["codigo_barras"] . '</td>
            <td>' . utf8_decode($prod["nombre"]) . '</td>
            <td>' . $prod["stock"] . '</td>
            <td>' . str_replace('.', ',', round($prod["capital_inmovilizado"], 2)) . '</td>
          </tr>';
}
echo '<tr><td colspan="4"></td></tr>';

// --- SECCIÓN 4: COMPRAS ANTIGUAS ---
echo '<tr><th colspan="4" style="background-color:#8e44ad; color:white;">3. ANTIGÜEDAD DE COMPRAS (> ' . $diasAntiguedad . ' Días en almacén)</th></tr>';
echo '<tr>
        <th style="background-color:#ecf0f1;">Código de Barras</th>
        <th style="background-color:#ecf0f1;">Producto</th>
        <th style="background-color:#ecf0f1;">Fecha de Compra</th>
        <th style="background-color:#ecf0f1;">Capital Inmovilizado (USD)</th>
      </tr>';
foreach ($antiguedad as $item) {
    echo '<tr>
            <td style="mso-number-format:\'@\';">' . $item["codigo_barras"] . '</td>
            <td>' . utf8_decode($item["nombre"]) . '</td>
            <td>' . date('d/m/Y', strtotime($item["fecha_ultima_compra"])) . '</td>
            <td>' . str_replace('.', ',', round($item["capital_inmovilizado"], 2)) . '</td>
          </tr>';
}

echo '</table>';
?>