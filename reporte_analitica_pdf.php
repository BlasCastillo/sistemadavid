<?php
session_start();
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    exit("Acceso denegado.");
}

while (ob_get_level()) { ob_end_clean(); }

require_once "config/conexion.php";
require_once "modelo/Reportes.php";
require_once('extensiones/TCPDF/tcpdf.php');

// 1. Capturar Filtros
$fechaInicial = isset($_GET["inicio"]) ? $_GET["inicio"] : date("Y-m-01");
$fechaFinal = isset($_GET["fin"]) ? $_GET["fin"] : date("Y-m-t");
$diasStock = isset($_GET["diasStock"]) ? intval($_GET["diasStock"]) : 30;
$diasAntiguedad = isset($_GET["diasAntiguedad"]) ? intval($_GET["diasAntiguedad"]) : 60;

// 2. Extraer Data de Operaciones y Logística
$ventas = Reportes::mdlResumenVentas($fechaInicial, $fechaFinal);
$stockMuerto = Reportes::mdlStockMuerto($diasStock);
$antiguedad = Reportes::mdlAntiguedadInventario($diasAntiguedad);

// 3. Extraer Data Financiera (NUEVO)
$gastos = Reportes::mdlResumenGastos($fechaInicial, $fechaFinal);
$cogs = Reportes::mdlCostoMercanciaVendida($fechaInicial, $fechaFinal);

// 4. Cálculos Básicos
$totalBs = 0;
$totalUsd = 0;
$totalFacturas = 0;
foreach ($ventas as $v) {
    $totalBs += $v["total_bs"];
    $totalUsd += $v["total_usdt"];
    $totalFacturas += $v["cantidad_facturas"];
}

// ==========================================================
// CÁLCULOS GERENCIALES AVANZADOS (KPIs)
// ==========================================================
$costoMercancia = floatval($cogs["costo_total"]);
$gastosFijos = floatval($gastos["gastos_fijos"]);
$gastosVariables = floatval($gastos["gastos_variables"]);
$totalGastos = floatval($gastos["total_gastos"]);

// A. Utilidad Bruta (Ingresos - Costo de lo que se vendió)
$utilidadBruta = $totalUsd - $costoMercancia;

// B. Balance / Utilidad Neta (Utilidad Bruta - Gastos Totales)
$utilidadNeta = $utilidadBruta - $totalGastos;

// C. Retorno de Inversión (ROI)
// Fórmula: (Utilidad Neta / Inversión Total) * 100
$inversionTotal = $costoMercancia + $totalGastos;
$roi = ($inversionTotal > 0) ? ($utilidadNeta / $inversionTotal) * 100 : 0;

// D. Punto de Equilibrio (BEP en USD)
// Fórmula: Gastos Fijos / Margen de Contribución 
// Margen de Contribución = Utilidad Bruta / Ventas Totales
$margenContribucion = ($totalUsd > 0) ? ($utilidadBruta / $totalUsd) : 0;
$puntoEquilibrio = ($margenContribucion > 0) ? ($gastosFijos / $margenContribucion) : 0;

// Configuración de la Empresa
$stmtConfig = Conexion::conectar()->prepare("SELECT * FROM configuracion WHERE id = 1");
$stmtConfig->execute();
$empresa = $stmtConfig->fetch(PDO::FETCH_OBJ);

// ==========================================================
// CREACIÓN DEL DOCUMENTO
// ==========================================================
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sistema POS');
$pdf->SetTitle('Reporte de Administracion, Logistica y Control');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

// Inyección del Logo Absoluto (Evita errores de renderizado en TCPDF)
$rutaLogo = $_SERVER['DOCUMENT_ROOT'] . '/easypos/vista/img/logo.png';
$logoHtml = '';
if (file_exists($rutaLogo)) {
    // Usamos el path absoluto del servidor para asegurar que TCPDF lo encuentre
    $logoHtml = '<div style="text-align:center;"><img src="'.$rutaLogo.'" width="140"></div><br>';
}

$bloqueHTML = '
<style>
    h1 { color: #2c3e50; font-size: 16pt; text-align: center; font-weight: bold; margin-bottom: 0px;}
    h3 { color: #34495e; font-size: 12pt; border-bottom: 1px solid #bdc3c7; padding-bottom: 5px; margin-top: 15px;}
    .text-muted { color: #7f8c8d; font-size: 9pt; text-align: center; }
    .table { width: 100%; border-collapse: collapse; font-size: 9pt; }
    .table th { background-color: #f4f6f6; color: #2c3e50; font-weight: bold; padding: 6px; border: 1px solid #bdc3c7; text-align: center; }
    .table td { padding: 6px; border: 1px solid #ecf0f1; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .resumen-box { background-color: #fdfefe; border: 1px solid #bdc3c7; padding: 8px; text-align: center; font-size: 10pt; font-weight: bold;}
    .badge-roi { color: '.($roi >= 0 ? '#27ae60' : '#c0392b').'; }
    .badge-balance { color: '.($utilidadNeta >= 0 ? '#27ae60' : '#c0392b').'; }
</style>

' . $logoHtml . '
<h1>' . strtoupper($empresa->nombre_negocio) . '</h1>
<div class="text-muted">REPORTE DE ADMINISTRACIÓN, LOGÍSTICA Y CONTROL</div>
<div class="text-muted">Período evaluado: ' . date('d/m/Y', strtotime($fechaInicial)) . ' al ' . date('d/m/Y', strtotime($fechaFinal)) . '</div>
<br><br>

<!-- KPIs FINANCIEROS Y OPERATIVOS -->
<table class="table" style="border: none;">
    <tr>
        <td class="resumen-box text-center" width="25%">Ingresos (USD)<br><span style="color:#2980b9">$ ' . number_format($totalUsd, 2, ',', '.') . '</span></td>
        <td class="resumen-box text-center" width="25%">Egresos (USD)<br><span style="color:#e67e22">$ ' . number_format($totalGastos, 2, ',', '.') . '</span></td>
        <td class="resumen-box text-center" width="25%">Balance Neto<br><span class="badge-balance">$ ' . number_format($utilidadNeta, 2, ',', '.') . '</span></td>
        <td class="resumen-box text-center" width="25%">ROI<br><span class="badge-roi">' . number_format($roi, 2, ',', '.') . ' %</span></td>
    </tr>
</table>
<table class="table" style="border: none; margin-top: 5px;">
    <tr>
        <td class="resumen-box text-center" width="50%">Punto de Equilibrio (BEP): <span style="color:#8e44ad">$' . number_format($puntoEquilibrio, 2, ',', '.') . '</span></td>
        <td class="resumen-box text-center" width="50%">Documentos Emitidos: <span style="color:#2c3e50">' . $totalFacturas . ' Facturas</span></td>
    </tr>
</table>
<br>

<h3>1. DESGLOSE DE INGRESOS POR DÍA</h3>
<table class="table">
    <thead>
        <tr>
            <th width="20%">Fecha</th>
            <th width="20%">Cant. Facturas</th>
            <th width="30%">Total Operaciones (Bs)</th>
            <th width="30%">Total Referencia (USD)</th>
        </tr>
    </thead>
    <tbody>';

if (count($ventas) > 0) {
    foreach ($ventas as $v) {
        $bloqueHTML .= '
        <tr>
            <td class="text-center" width="20%">' . date('d/m/Y', strtotime($v["fecha"])) . '</td>
            <td class="text-center" width="20%">' . $v["cantidad_facturas"] . '</td>
            <td class="text-right" width="30%">Bs. ' . number_format($v["total_bs"], 2, ',', '.') . '</td>
            <td class="text-right" width="30%">$ ' . number_format($v["total_usdt"], 2, ',', '.') . '</td>
        </tr>';
    }
} else {
    $bloqueHTML .= '<tr><td colspan="4" class="text-center">No se registraron ventas en el rango de fechas seleccionado.</td></tr>';
}

$bloqueHTML .= '
    </tbody>
</table>
<br>

<h3>2. AUDITORÍA LOGÍSTICA: STOCK ESTANCADO (> '.$diasStock.' Días Sin Rotación)</h3>
<table class="table">
    <thead>
        <tr>
            <th width="50%">Código / Producto</th>
            <th width="25%">Fecha Últ. Venta</th>
            <th width="10%">Stock</th>
            <th width="15%">Capital Inmov.</th>
        </tr>
    </thead>
    <tbody>';

if (count($stockMuerto) > 0) {
    foreach ($stockMuerto as $prod) {
        $fechaVenta = !empty($prod["ultima_venta_real"]) ? date('d/m/Y', strtotime($prod["ultima_venta_real"])) : '<span style="color:#c0392b">Nunca se ha vendido</span>';
        $bloqueHTML .= '
        <tr>
            <td width="50%"><strong>' . $prod["nombre"] . '</strong><br><small style="color:#7f8c8d">' . $prod["codigo_barras"] . '</small></td>
            <td class="text-center" width="25%">' . $fechaVenta . '</td>
            <td class="text-center" width="10%">' . $prod["stock"] . '</td>
            <td class="text-right" style="color:#c0392b" width="15%">$' . number_format($prod["capital_inmovilizado"], 2, ',', '.') . '</td>
        </tr>';
    }
} else {
    $bloqueHTML .= '<tr><td colspan="4" class="text-center">El inventario presenta una rotación óptima bajo este parámetro.</td></tr>';
}

$bloqueHTML .= '
    </tbody>
</table>
<br>

<h3>3. AUDITORÍA LOGÍSTICA: ANTIGÜEDAD DE COMPRAS (> '.$diasAntiguedad.' Días en Almacén)</h3>
<table class="table">
    <thead>
        <tr>
            <th width="50%">Código / Producto</th>
            <th width="25%">Fecha de Ingreso</th>
            <th width="10%">Stock</th>
            <th width="15%">Capital Inmov.</th>
        </tr>
    </thead>
    <tbody>';

if (count($antiguedad) > 0) {
    foreach ($antiguedad as $item) {
        $bloqueHTML .= '
        <tr>
            <td width="50%"><strong>' . $item["nombre"] . '</strong><br><small style="color:#7f8c8d">' . $item["codigo_barras"] . '</small></td>
            <td class="text-center" width="25%">' . date('d/m/Y', strtotime($item["fecha_ultima_compra"])) . '</td>
            <td class="text-center" width="10%">' . $item["stock"] . '</td>
            <td class="text-right" style="color:#c0392b" width="15%">$' . number_format($item["capital_inmovilizado"], 2, ',', '.') . '</td>
        </tr>';
    }
} else {
    $bloqueHTML .= '<tr><td colspan="4" class="text-center">No se detectó mercancía antigua bajo este parámetro.</td></tr>';
}

$bloqueHTML .= '
    </tbody>
</table>';

$pdf->writeHTML($bloqueHTML, true, false, true, false, '');

// ==========================================================
// GUARDAR Y MOSTRAR
// ==========================================================
$rutaCarpeta = Reportes::crearRutaReportePDF();
$nombreArchivo = 'Analitica_Logistica_' . date('Ymd_His') . '.pdf';

// Guardar en servidor
$pdf->Output(__DIR__ . '/' . $rutaCarpeta . $nombreArchivo, 'F');

// Mostrar en pantalla
$pdf->Output($nombreArchivo, 'I');
?>