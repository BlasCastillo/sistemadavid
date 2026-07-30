<?php
session_start();
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    exit("Acceso denegado. Debe iniciar sesión.");
}

// Limpieza de buffer
while (ob_get_level()) { ob_end_clean(); }

require_once "config/conexion.php";
require_once "controlador/CierreZControlador.php";
require_once "modelo/CierreZ.php";
require_once('extensiones/TCPDF/tcpdf.php');

if (!isset($_GET["idCierreZ"])) {
    exit("Falta el ID del Cierre.");
}

$idCierre = $_GET["idCierreZ"];
$cierre = CierreZControlador::ctrMostrarCierresZ("id", $idCierre);

if (!$cierre) {
    exit("El Cierre Z no existe.");
}

// Extraer pagos electrónicos del JSON
$pagosElectronicos = json_decode($cierre["pagos_electronicos"], true);
$zelle = isset($pagosElectronicos["zelle_usd"]) ? floatval($pagosElectronicos["zelle_usd"]) : 0;
$pm = isset($pagosElectronicos["pago_movil_bs"]) ? floatval($pagosElectronicos["pago_movil_bs"]) : 0;

// Configuración de Empresa
$stmtConfig = Conexion::conectar()->prepare("SELECT * FROM configuracion WHERE id = 1");
$stmtConfig->execute();
$empresa = $stmtConfig->fetch(PDO::FETCH_OBJ);

// INICIALIZAMOS TCPDF (Ticket 80mm)
$medidas = array(80, 250); 
$pdf = new TCPDF('P', 'mm', $medidas, true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(5, 5, 5); 
$pdf->SetAutoPageBreak(TRUE, 5);
$pdf->AddPage();

// CABECERA
$pdf->SetFont('helvetica', 'B', 12);
$pdf->MultiCell(70, 5, strtoupper($empresa->nombre_negocio), 0, 'C', false);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->MultiCell(70, 5, "REPORTE Z (CIERRE DE TIENDA)", 0, 'C', false);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');

// DATOS DEL REPORTE
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(35, 4, 'Reporte Nro:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(35, 4, str_pad($cierre["id"], 6, "0", STR_PAD_LEFT), 0, 1, 'R');

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(35, 4, 'Fecha y Hora:', 0, 0, 'L');
$pdf->Cell(35, 4, date("d/m/Y H:i", strtotime($cierre["fecha_cierre"])), 0, 1, 'R');

$pdf->Cell(35, 4, 'Gerente:', 0, 0, 'L');
$pdf->Cell(35, 4, '@' . strtoupper($cierre["nombre_gerente"]), 0, 1, 'R');

// CONSOLIDADO FÍSICO
$pdf->Ln(2);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(70, 5, 'CONSOLIDADO FÍSICO (CAJAS)', 0, 1, 'C');
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(35, 4, 'Efectivo ($):', 0, 0, 'L');
$pdf->Cell(35, 4, '$ ' . number_format($cierre["efectivo_caja_usd"], 2), 0, 1, 'R');
$pdf->Cell(35, 4, 'Efectivo (Bs):', 0, 0, 'L');
$pdf->Cell(35, 4, 'Bs ' . number_format($cierre["efectivo_caja_bs"], 2), 0, 1, 'R');
$pdf->Cell(35, 4, 'Punto de Venta:', 0, 0, 'L');
$pdf->Cell(35, 4, 'Bs ' . number_format($cierre["punto_venta_bs"], 2), 0, 1, 'R');

// CONSOLIDADO ELECTRÓNICO
$pdf->Ln(2);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(70, 5, 'CONSOLIDADO ELECTRÓNICO', 0, 1, 'C');
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(35, 4, 'Zelle / Binance:', 0, 0, 'L');
$pdf->Cell(35, 4, '$ ' . number_format($zelle, 2), 0, 1, 'R');
$pdf->Cell(35, 4, 'Pago Móvil:', 0, 0, 'L');
$pdf->Cell(35, 4, 'Bs ' . number_format($pm, 2), 0, 1, 'R');

// MOVIMIENTOS EXTRAS
$pdf->Ln(2);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(70, 5, 'MOVIMIENTOS DE CAJA PRINCIPAL', 0, 1, 'C');
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(35, 4, 'Gastos del Día:', 0, 0, 'L');
$pdf->Cell(35, 4, '- $ ' . number_format($cierre["gastos_totales"], 2), 0, 1, 'R');
$pdf->Cell(35, 4, 'Ingresos Extras:', 0, 0, 'L');
$pdf->Cell(35, 4, '+ $ ' . number_format($cierre["ingresos_extras"], 2), 0, 1, 'R');

// FIRMAS Y PIE DE PÁGINA
$pdf->Ln(15);
$pdf->Cell(70, 0, '__________________________________', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(70, 4, 'FIRMA GERENTE', 0, 1, 'C');
$pdf->Ln(5);
$pdf->MultiCell(70, 4, "Este reporte empaqueta todos los Cierres X de la jornada operativa.\nFIN DEL DÍA FINANCIERO", 0, 'C', false);

// ==========================================================
// CREACIÓN DE DIRECTORIOS Y GUARDADO BLINDADO
// ==========================================================
$fechaMes = date("Y-m");
$rutaCarpeta = "Facturacion/Cierres_Z/" . $fechaMes . "/";
// __DIR__ detecta la ruta absoluta sin importar el servidor o nombre de carpeta
$rutaAbsoluta = __DIR__ . '/' . $rutaCarpeta;

// Si la carpeta del mes no existe, la creamos
if(!file_exists($rutaAbsoluta)){
    mkdir($rutaAbsoluta, 0777, true);
}

$nombreArchivo = 'ReporteZ_' . $cierre["id"] . '.pdf';

// 1. Guardar copia inalterable en el servidor
$pdf->Output($rutaAbsoluta . $nombreArchivo, 'F');

// 2. Mostrar al usuario para impresión
$pdf->Output($nombreArchivo, 'I');
?>