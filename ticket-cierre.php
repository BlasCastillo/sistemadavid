<?php
session_start();
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    exit("Acceso denegado.");
}
if (!isset($_GET["idCierre"])) {
    exit("Error: ID de cierre no especificado.");
}

// 1. Limpiamos buffer residual (Misma técnica que en ventas)
while (ob_get_level()) { ob_end_clean(); }

// 2. Dependencias Principales
require_once "config/conexion.php";
require_once "modelo/Ventas.php"; // Lo requerimos para usar tu función de rutas
require_once('extensiones/TCPDF/tcpdf.php');

$id_cierre = intval($_GET["idCierre"]);

// 3. Obtener Datos del Cierre
$stmt = Conexion::conectar()->prepare("
    SELECT c.*, u.usuario as nombre_cajero, auth.usuario as nombre_gerente 
    FROM cierres_caja c 
    INNER JOIN usuarios u ON c.cajero_id = u.id 
    LEFT JOIN usuarios auth ON c.autorizador_id = auth.id
    WHERE c.id = :id
");
$stmt->bindParam(":id", $id_cierre, PDO::PARAM_INT);
$stmt->execute();
$cierre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cierre) {
    exit("Error: El reporte de cierre no existe en la base de datos.");
}

// Datos de la empresa
$stmtConfig = Conexion::conectar()->prepare("SELECT * FROM configuracion WHERE id = 1");
$stmtConfig->execute();
$empresa = $stmtConfig->fetch(PDO::FETCH_OBJ);

// 4. PREPARACIÓN DE DATOS JSON
$esperados = json_decode($cierre["montos_esperados"], true);
$declarados = json_decode($cierre["montos_declarados"], true);
$diferencias = json_decode($cierre["diferencias"], true);

// 5. INICIALIZAMOS TCPDF (Mismo formato de 80mm de tu ticket-factura)
$medidas = array(80, 270); 
$pdf = new TCPDF('P', 'mm', $medidas, true, 'UTF-8', false);

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(5, 5, 5); 
$pdf->SetAutoPageBreak(TRUE, 5);
$pdf->AddPage();

// --- CABECERA DE LA EMPRESA ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->MultiCell(70, 5, strtoupper($empresa->nombre_negocio), 0, 'C', false);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(70, 4, "RIF: " . $empresa->rif_negocio, 0, 'C', false);
$pdf->MultiCell(70, 4, $empresa->direccion_fiscal, 0, 'C', false);

$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->MultiCell(70, 5, "REPORTE X - CIERRE TURNO", 0, 'C', false);

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(70, 4, "Reporte N°: " . str_pad($cierre["id"], 6, "0", STR_PAD_LEFT), 0, 'C', false);
$pdf->MultiCell(70, 4, "Fecha: " . date("d/m/Y h:i A", strtotime($cierre["fecha_cierre"])), 0, 'C', false);
$pdf->MultiCell(70, 4, "Cajero: @" . $cierre["nombre_cajero"], 0, 'C', false);

$pdf->Ln(2);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(70, 5, 'ARQUEO FÍSICO (CAJA)', 0, 1, 'C');
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');

// Función auxiliar para imprimir las filas de dinero físico
function imprimirFilaArqueo($pdf, $titulo, $declarado, $esperado, $diferencia, $simbolo) {
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(70, 5, $titulo, 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);
    
    $pdf->Cell(35, 4, 'Sistema:', 0, 0, 'L');
    $pdf->Cell(35, 4, $simbolo . number_format($esperado, 2), 0, 1, 'R');
    
    $pdf->Cell(35, 4, 'Cajero:', 0, 0, 'L');
    $pdf->Cell(35, 4, $simbolo . number_format($declarado, 2), 0, 1, 'R');
    
    // Solo mostramos la diferencia si no es 0
    if(abs($diferencia) > 0.05) {
        $pdf->SetFont('helvetica', 'B', 8);
        $textoDif = ($diferencia > 0) ? "SOBRANTE:" : "FALTANTE:";
        $pdf->Cell(35, 4, $textoDif, 0, 0, 'L');
        $pdf->Cell(35, 4, $simbolo . number_format(abs($diferencia), 2), 0, 1, 'R');
    }
    $pdf->Ln(2);
}

// Imprimimos el dinero físico
imprimirFilaArqueo($pdf, "Efectivo Dólares ($)", $declarados["efectivo_usd"], $esperados["efectivo_usd"], $diferencias["efectivo_usd"], "$");
imprimirFilaArqueo($pdf, "Efectivo Bolívares (Bs)", $declarados["efectivo_bs"], $esperados["efectivo_bs"], $diferencias["efectivo_bs"], "Bs ");
imprimirFilaArqueo($pdf, "Lote Punto de Venta (Bs)", $declarados["punto_venta_bs"], $esperados["punto_venta_bs"], $diferencias["punto_venta_bs"], "Bs ");

$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(70, 5, 'PAGOS ELECTRÓNICOS', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(70, 4, '(Calculados automáticamente)', 0, 1, 'C');
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');

// Imprimimos los pagos automáticos
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(35, 5, 'Zelle/Binance:', 0, 0, 'L');
$pdf->Cell(35, 5, "$" . number_format($esperados["zelle_usd"], 2), 0, 1, 'R');

$pdf->Cell(35, 5, 'Pago Móvil:', 0, 0, 'L');
$pdf->Cell(35, 5, "Bs " . number_format($esperados["pago_movil_bs"], 2), 0, 1, 'R');

$pdf->Cell(35, 5, 'Transferencias:', 0, 0, 'L');
$pdf->Cell(35, 5, "Bs " . number_format($esperados["transferencia_bs"], 2), 0, 1, 'R');

$pdf->Ln(2);
$pdf->Cell(70, 0, '------------------------------------------------------------------', 0, 1, 'C');

// --- ESTADO Y AUTORIZACIÓN ---
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(35, 5, 'ESTADO CAJA:', 0, 0, 'L');

if($cierre["estado"] == "Cuadrado") {
    $pdf->Cell(35, 5, 'CUADRADO', 0, 1, 'R');
} else {
    $pdf->Cell(35, 5, 'DESCUADRE', 0, 1, 'R');
    if(!empty($cierre["nombre_gerente"])) {
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->MultiCell(70, 4, "\nAutorizado por: " . $cierre["nombre_gerente"], 0, 'C', false);
    }
}

// --- FIRMAS ---
$pdf->Ln(15);
$pdf->Cell(70, 0, '__________________________________', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(70, 4, 'Firma Cajero: ' . $cierre["nombre_cajero"], 0, 1, 'C');

$pdf->Ln(12);
$pdf->Cell(70, 0, '__________________________________', 0, 1, 'C');
$pdf->Cell(70, 4, 'Firma Supervisor / Gerente', 0, 1, 'C');

// --- GUARDAR Y MOSTRAR (USANDO TU PROPIO MODELO) ---
// Usamos tu función Ventas para crear la carpeta y obtener la ruta (Facturacion/YYYY-MM-DD/cajero/)
$rutaCarpeta = Ventas::crearRutaFacturaPDF($cierre["nombre_cajero"]);
$nombreArchivoPDF = "ReporteX_N" . $cierre["id"] . ".pdf";

// 1. Guardar silenciosamente en el servidor
$pdf->Output(__DIR__ . '/' . $rutaCarpeta . $nombreArchivoPDF, 'F');

// 2. Mostrar en el navegador para impresión
$pdf->Output($nombreArchivoPDF, 'I');
?>