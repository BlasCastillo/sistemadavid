<?php
session_start();
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    exit("Acceso denegado.");
}
if (!isset($_GET["codigo"])) {
    exit("Error: Código de nota no especificado.");
}

// 1. EL TRUCO MAGICO: Limpiamos cualquier buffer residual antes de llamar a TCPDF
while (ob_get_level()) { ob_end_clean(); }

$codigo_nota = $_GET["codigo"];

// 2. Requerimos las dependencias desde la raíz
require_once "config/conexion.php";
require_once "modelo/Ventas.php";

// Obtenemos los datos de la nota de crédito mediante una consulta directa
$conexion = Conexion::conectar();
$stmt = $conexion->prepare("
    SELECT nc.*, c.nombre as cliente_nombre, c.documento as cliente_doc, v.numero_factura, v.tasa_bcv, u.usuario as cajero_original
    FROM notas_credito nc
    INNER JOIN clientes c ON nc.cliente_id = c.id
    INNER JOIN ventas v ON nc.venta_original_id = v.id
    INNER JOIN usuarios u ON v.usuario_id = u.id
    WHERE nc.codigo_nota = :codigo
");
$stmt->bindParam(":codigo", $codigo_nota, PDO::PARAM_STR);
$stmt->execute();
$nota = $stmt->fetch(PDO::FETCH_OBJ);

if (!$nota) {
    exit("Error: La Nota de Crédito no existe en la base de datos.");
}

// Obtenemos los datos de la empresa
$stmtConfig = $conexion->prepare("SELECT * FROM configuracion WHERE id = 1");
$stmtConfig->execute();
$empresa = $stmtConfig->fetch(PDO::FETCH_OBJ);

// 3. Incluimos TCPDF
require_once('extensiones/TCPDF/tcpdf.php');

// Un poco más corto que la factura normal ya que no lleva lista de productos
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
$pdf->MultiCell(70, 5, "COMPROBANTE DE DEVOLUCIÓN", 0, 'C', false);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->MultiCell(70, 5, "NOTA DE CRÉDITO: " . $nota->codigo_nota, 0, 'C', false);

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(70, 4, "Fecha: " . date("d/m/Y h:i A", strtotime($nota->fecha_emision)), 0, 'C', false);

// Usamos el cajero que está logueado haciendo la devolución
$cajero_actual = isset($_SESSION["usuario"]) ? $_SESSION["usuario"] : $nota->cajero_original;
$pdf->MultiCell(70, 4, "Cajero: @" . $cajero_actual, 0, 'C', false);

$pdf->Ln(2);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->MultiCell(70, 4, "Cliente: " . $nota->cliente_nombre, 0, 'L', false);
$pdf->MultiCell(70, 4, "C.I/RIF: " . $nota->cliente_doc, 0, 'L', false);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');

$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(70, 4, "Aplica sobre Factura Original: F-" . $nota->numero_factura, 0, 'C', false);

$pdf->Ln(4);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(70, 6, "SALDO A FAVOR: $" . number_format($nota->monto_usd, 2), 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
// Reflejamos el valor en Bs usando la tasa de la factura original
$pdf->Cell(70, 4, "Equivalente ref. (Bs): " . number_format($nota->monto_usd * $nota->tasa_bcv, 2), 0, 1, 'C');

$pdf->Ln(3);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');

$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->MultiCell(70, 4, "ESTE COMPROBANTE REPRESENTA UN SALDO A FAVOR VÁLIDO EXCLUSIVAMENTE PARA EL DÍA DE SU EMISIÓN.", 0, 'C', false);

// Crear carpeta y guardar en el servidor
$rutaCarpeta = Ventas::crearRutaFacturaPDF($cajero_actual);
$nombreArchivo = $nota->codigo_nota . '.pdf';
$pdf->Output($_SERVER['DOCUMENT_ROOT'] . '/easypos/' . $rutaCarpeta . $nombreArchivo, 'F');

// Lanzar al navegador
$pdf->Output($nombreArchivo, 'I');
?>