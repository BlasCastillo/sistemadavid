<?php
session_start();
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    exit("Acceso denegado.");
}
if (!isset($_GET["idVenta"])) {
    exit("Error: ID de venta no especificado.");
}

// 1. EL TRUCO MAGICO: Limpiamos cualquier buffer residual antes de llamar a TCPDF
while (ob_get_level()) { ob_end_clean(); }

$id_venta = intval($_GET["idVenta"]);

// 2. Requerimos las dependencias desde la raíz
require_once "config/conexion.php";
require_once "modelo/Ventas.php";

$venta = Ventas::leerVentaCabecera($id_venta);
$detalles = Ventas::leerVentaDetalle($id_venta);
$pagos = Ventas::leerVentaPagos($id_venta);

if (!$venta) {
    exit("Error: La factura no existe en la base de datos.");
}

// Obtenemos los datos de la empresa
$stmtConfig = Conexion::conectar()->prepare("SELECT * FROM configuracion WHERE id = 1");
$stmtConfig->execute();
$empresa = $stmtConfig->fetch(PDO::FETCH_OBJ);

// 3. Incluimos TCPDF
require_once('extensiones/TCPDF/tcpdf.php');

$medidas = array(80, 250); 
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
$pdf->SetFont('helvetica', 'B', 10);
$pdf->MultiCell(70, 5, "FACTURA NO. " . $venta->numero_factura, 0, 'C', false);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(70, 4, "Fecha: " . date("d/m/Y H:i A", strtotime($venta->fecha_venta)), 0, 'C', false);
$pdf->MultiCell(70, 4, "Cajero: @" . $venta->cajero_nombre, 0, 'C', false);

$pdf->Ln(2);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->MultiCell(70, 4, "Cliente: " . $venta->cliente_nombre, 0, 'L', false);
$pdf->MultiCell(70, 4, "C.I/RIF: " . $venta->cliente_doc, 0, 'L', false);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(10, 4, 'CANT', 0, 0, 'C');
$pdf->Cell(35, 4, 'DESCRIPCION', 0, 0, 'L');
$pdf->Cell(25, 4, 'TOTAL ($)', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 8);

foreach ($detalles as $item) {
    $subtotal = ($item->cantidad * $item->precio_unitario_usdt) - $item->descuento_usdt;
    $startY = $pdf->GetY();
    
    $pdf->SetXY(5, $startY);
    $pdf->Cell(10, 4, $item->cantidad, 0, 0, 'C');
    
    $pdf->SetXY(15, $startY);
    $pdf->MultiCell(35, 4, $item->nombre, 0, 'L', false);
    
    $endY = $pdf->GetY();
    
    $pdf->SetXY(50, $startY);
    $pdf->Cell(25, 4, "$" . number_format($subtotal, 2), 0, 1, 'R');
    $pdf->SetY($endY);
}

$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 5, 'TOTAL A PAGAR:', 0, 0, 'L');
$pdf->Cell(30, 5, "$" . number_format($venta->total_usdt, 2), 0, 1, 'R');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(40, 4, 'Equivalente (Bs):', 0, 0, 'L');
$pdf->Cell(30, 4, number_format($venta->total_bs, 2), 0, 1, 'R');

$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(70, 5, 'METODOS DE PAGO', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);

foreach ($pagos as $pago) {
    $simbolo = ($pago->moneda == "BS") ? "Bs " : "$ ";
    $pdf->Cell(40, 4, $pago->metodo_pago, 0, 0, 'L');
    $pdf->Cell(30, 4, $simbolo . number_format($pago->monto_pagado, 2), 0, 1, 'R');
}

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->MultiCell(70, 5, "¡GRACIAS POR SU COMPRA!", 0, 'C', false);

// Crear carpeta y guardar en el servidor
$rutaCarpeta = Ventas::crearRutaFacturaPDF($venta->cajero_nombre);
$nombreArchivo = $venta->numero_factura . '.pdf';
$pdf->Output($_SERVER['DOCUMENT_ROOT'] . '/easypos/' . $rutaCarpeta . $nombreArchivo, 'F');

// Lanzar al navegador
$pdf->Output($nombreArchivo, 'I');
?>