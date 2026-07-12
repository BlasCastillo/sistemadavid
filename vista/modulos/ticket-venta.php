<?php
// Validamos que venga el ID de la venta
if (!isset($_GET["idVenta"])) {
    echo "Error: ID de venta no especificado.";
    exit;
}

$id_venta = intval($_GET["idVenta"]);

// Requerimos modelos necesarios
require_once "modelo/Ventas.php";
require_once "modelo/Configuracion.php"; 

$venta = Ventas::leerVentaCabecera($id_venta);
$detalles = Ventas::leerVentaDetalle($id_venta);
$pagos = Ventas::leerVentaPagos($id_venta);

if (!$venta) {
    echo "Error: La factura no existe.";
    exit;
}

// Obtenemos los datos de la empresa
$stmtConfig = Conexion::conectar()->prepare("SELECT * FROM configuracion WHERE id = 1");
$stmtConfig->execute();
$empresa = $stmtConfig->fetch(PDO::FETCH_OBJ);

// Incluimos TCPDF
require_once('extensiones/TCPDF/tcpdf.php');

// Configuramos el ticket (Formato de 80mm de ancho, altura dinámica)
$medidas = array(80, 250); // 80mm ancho, 250mm largo (ajustable)
$pdf = new TCPDF('P', 'mm', $medidas, true, 'UTF-8', false);

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(5, 5, 5); // Márgenes pequeños para ticket
$pdf->SetAutoPageBreak(TRUE, 5);
$pdf->AddPage();

// FUENTE Y CABECERA DEL NEGOCIO
$pdf->SetFont('helvetica', 'B', 12);
$pdf->MultiCell(70, 5, strtoupper($empresa->nombre_negocio), 0, 'C', false);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(70, 4, "RIF: " . $empresa->rif_negocio, 0, 'C', false);
$pdf->MultiCell(70, 4, $empresa->direccion_fiscal, 0, 'C', false);
$pdf->MultiCell(70, 4, "Tel: " . $empresa->telefono_negocio, 0, 'C', false);

$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->MultiCell(70, 5, "FACTURA NO. " . $venta->numero_factura, 0, 'C', false);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(70, 4, "Fecha: " . date("d/m/Y H:i A", strtotime($venta->fecha_venta)), 0, 'C', false);
$pdf->MultiCell(70, 4, "Cajero: @" . $venta->cajero_nombre, 0, 'C', false);

// DATOS DEL CLIENTE
$pdf->Ln(2);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->MultiCell(70, 4, "Cliente: " . $venta->cliente_nombre, 0, 'L', false);
$pdf->MultiCell(70, 4, "C.I/RIF: " . $venta->cliente_doc, 0, 'L', false);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');

// DETALLE DE PRODUCTOS
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(10, 4, 'CANT', 0, 0, 'C');
$pdf->Cell(35, 4, 'DESCRIPCION', 0, 0, 'L');
$pdf->Cell(25, 4, 'TOTAL ($)', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 8);

foreach ($detalles as $item) {
    $subtotal = ($item->cantidad * $item->precio_unitario_usdt) - $item->descuento_usdt;
    
    // El nombre del producto puede ser largo, usamos MultiCell para que baje de línea
    // Guardamos la posición Y para alinear la cantidad y el precio
    $startY = $pdf->GetY();
    
    $pdf->SetXY(5, $startY);
    $pdf->Cell(10, 4, $item->cantidad, 0, 0, 'C');
    
    $pdf->SetXY(15, $startY);
    $pdf->MultiCell(35, 4, $item->nombre, 0, 'L', false);
    
    $endY = $pdf->GetY(); // Dónde terminó el texto largo
    
    $pdf->SetXY(50, $startY);
    $pdf->Cell(25, 4, "$" . number_format($subtotal, 2), 0, 1, 'R');
    
    // Ajustamos la posición Y general para el siguiente producto
    $pdf->SetY($endY);
}

// TOTALES
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 5, 'TOTAL A PAGAR:', 0, 0, 'L');
$pdf->Cell(30, 5, "$" . number_format($venta->total_usdt, 2), 0, 1, 'R');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(40, 4, 'Equivalente (Bs):', 0, 0, 'L');
$pdf->Cell(30, 4, number_format($venta->total_bs, 2), 0, 1, 'R');
$pdf->Cell(40, 4, 'Tasa BCV:', 0, 0, 'L');
$pdf->Cell(30, 4, number_format($venta->tasa_bcv, 2), 0, 1, 'R');

// PAGOS RECIBIDOS
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(70, 5, 'METODOS DE PAGO', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);

foreach ($pagos as $pago) {
    $simbolo = ($pago->moneda == "BS") ? "Bs " : "$ ";
    $pdf->Cell(40, 4, $pago->metodo_pago . " (Ref: ".$pago->referencia.")", 0, 0, 'L');
    $pdf->Cell(30, 4, $simbolo . number_format($pago->monto_pagado, 2), 0, 1, 'R');
}

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->MultiCell(70, 5, "¡GRACIAS POR SU COMPRA!", 0, 'C', false);

// ==========================================================
// GUARDAR EN DISCO (Cumpliendo el Punto 16)
// ==========================================================
$rutaCarpeta = Ventas::crearRutaFacturaPDF($venta->cajero_nombre);
$nombreArchivo = $venta->numero_factura . '.pdf';

// Guarda el PDF silenciosamente en el servidor
$pdf->Output($_SERVER['DOCUMENT_ROOT'] . '/easypos/' . $rutaCarpeta . $nombreArchivo, 'F');

// ==========================================================
// MOSTRAR EN PANTALLA
// ==========================================================
// Muestra el PDF en el navegador para que el cajero lo imprima
$pdf->Output($nombreArchivo, 'I');
?>