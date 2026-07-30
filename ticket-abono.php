<?php
session_start();
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    exit("Acceso denegado.");
}
if (!isset($_GET["idPago"])) {
    exit("Error: ID de pago no especificado.");
}

while (ob_get_level()) { ob_end_clean(); }

$id_pago = intval($_GET["idPago"]);

require_once "config/conexion.php";
require_once "modelo/Creditos.php";

$abono = Creditos::mdlObtenerDetallePago($id_pago);

if (!$abono) {
    exit("Error: El pago no existe en la base de datos.");
}

$stmtConfig = Conexion::conectar()->prepare("SELECT * FROM configuracion WHERE id = 1");
$stmtConfig->execute();
$empresa = $stmtConfig->fetch(PDO::FETCH_OBJ);

require_once('extensiones/TCPDF/tcpdf.php');

$medidas = array(80, 200); 
$pdf = new TCPDF('P', 'mm', $medidas, true, 'UTF-8', false);

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(5, 5, 5); 
$pdf->SetAutoPageBreak(TRUE, 5);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 12);
$pdf->MultiCell(70, 5, strtoupper($empresa->nombre_negocio), 0, 'C', false);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(70, 4, "RIF: " . $empresa->rif_negocio, 0, 'C', false);
$pdf->MultiCell(70, 4, $empresa->direccion_fiscal, 0, 'C', false);

$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->MultiCell(70, 5, "RECIBO DE ABONO", 0, 'C', false);

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(70, 4, "ID Pago: #" . str_pad($abono->id, 6, "0", STR_PAD_LEFT), 0, 'C', false);
$pdf->MultiCell(70, 4, "Fecha: " . date("d/m/Y H:i A", strtotime($abono->fecha_pago)), 0, 'C', false);
$pdf->MultiCell(70, 4, "Cajero: @" . $abono->cajero_nombre, 0, 'C', false);

$pdf->Ln(2);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->MultiCell(70, 4, "Abono a la Factura: " . $abono->numero_factura, 0, 'C', false);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(70, 4, "Cliente: " . $abono->cliente_nombre, 0, 'L', false);
$pdf->MultiCell(70, 4, "C.I/RIF: " . $abono->cliente_doc, 0, 'L', false);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 5, 'METODO:', 0, 0, 'L');
$pdf->Cell(30, 5, $abono->metodo_pago, 0, 1, 'R');

if(!empty($abono->referencia) && $abono->referencia != "N/A"){
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(40, 5, 'Referencia:', 0, 0, 'L');
    $pdf->Cell(30, 5, $abono->referencia, 0, 1, 'R');
}

$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(40, 6, 'MONTO ABONADO:', 0, 0, 'L');

if($abono->moneda == "BS"){
    $pdf->Cell(30, 6, "Bs " . number_format($abono->monto_pagado, 2), 0, 1, 'R');
    $pdf->SetFont('helvetica', 'I', 9);
    $equivalenteUsd = $abono->monto_pagado / $abono->tasa_bcv;
    $pdf->Cell(70, 4, "(Equivale a $" . number_format($equivalenteUsd, 2) . ")", 0, 1, 'R');
} else {
    $pdf->Cell(30, 6, "$" . number_format($abono->monto_pagado, 2), 0, 1, 'R');
    $pdf->SetFont('helvetica', 'I', 9);
    $equivalenteBs = $abono->monto_pagado * $abono->tasa_bcv;
    $pdf->Cell(70, 4, "(Equivale a Bs " . number_format($equivalenteBs, 2) . ")", 0, 1, 'R');
}

$pdf->Ln(3);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->MultiCell(70, 5, "Firma del Cliente:\n\n\n___________________________", 0, 'C', false);

// ==========================================================
// CREACIÓN DE DIRECTORIOS Y GUARDADO BLINDADO
// ==========================================================
$fechaMes = date("Y-m");
$rutaCarpeta = "Facturacion/Abonos/" . $fechaMes . "/";
// __DIR__ detecta la ruta absoluta sin importar el servidor o nombre de carpeta
$rutaAbsoluta = __DIR__ . '/' . $rutaCarpeta;

// Si la carpeta del mes no existe, la creamos
if (!file_exists($rutaAbsoluta)) { 
    mkdir($rutaAbsoluta, 0777, true); 
}

$nombreArchivo = 'abono_' . $abono->id . '.pdf';

// 1. Guardar copia inalterable en el servidor
$pdf->Output($rutaAbsoluta . $nombreArchivo, 'F');

// 2. Mostrar al usuario para impresión
$pdf->Output($nombreArchivo, 'I');
?>