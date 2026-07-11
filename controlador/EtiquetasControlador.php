<?php
require_once "modelo/ConsultaPrecios.php"; 

class EtiquetasControlador {

    public function ctrGenerarEtiquetasPDF() {
        if (isset($_GET["datos"]) && isset($_GET["formato"])) {

            while (ob_get_level()) { ob_end_clean(); }

            $listaEtiquetas = json_decode($_GET["datos"], true);
            $formato = $_GET["formato"];

            require_once "extensiones/TCPDF/tcpdf.php";

            // Configuramos los tamaños
            if ($formato === "zebra_pequena") {
                $pdf = new \TCPDF('L', 'mm', array(57, 40), true, 'UTF-8', false);
            } elseif ($formato === "zebra_grande") {
                $pdf = new \TCPDF('L', 'mm', array(89, 59), true, 'UTF-8', false);
            } else {
                $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            }

            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(5, 5, 5);
            $pdf->SetAutoPageBreak(false); // Desactivamos el salto automático para controlar el grid

            if ($formato === 'a4_grid') {
                $pdf->AddPage();
                $x = 10; $y = 10;
                $contadorEtiquetas = 0;
            }

            foreach ($listaEtiquetas as $item) {
                $producto = ConsultaPrecios::buscarProductoPorCodigo($item["codigo"]);
                if ($producto) {
                    $precioFinalUsdt = ($producto->getTieneOfertaActiva() == 1) ? $producto->getPrecioOfertaUsdt() : ($producto->getCostoUsdt() * (1 + ($producto->getMargenGanancia() / 100)));

                    for ($i = 0; $i < $item["cantidad"]; $i++) {
                        
                        // Lógica Zebra (Corta el papel por etiqueta)
                        if ($formato !== 'a4_grid') {
                            $pdf->AddPage();
                            $this->dibujarEtiqueta($pdf, $producto, $precioFinalUsdt, 0, 0, 50, 30);
                        } else {
                            // Lógica A4 (Imprime en cuadrícula)
                            $this->dibujarEtiqueta($pdf, $producto, $precioFinalUsdt, $x, $y, 90, 60);
                            $x += 100;
                            if ($x > 200) { $x = 10; $y += 70; }
                            if ($y > 250) { $pdf->AddPage(); $x = 10; $y = 10; }
                        }
                    }
                }
            }

            $pdf->Output('etiquetas_reditus.pdf', 'I');
            exit;
        }
    }

    private function dibujarEtiqueta($pdf, $producto, $precio, $x, $y, $w, $h) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 5, substr($producto->getNombre(), 0, 30), 0, 1, 'C');
        
        // DIBUJAR CÓDIGO DE BARRAS
        $style = array('position' => '', 'align' => 'C', 'stretch' => false, 'fitwidth' => true, 'cellfitalign' => '', 'border' => false, 'padding' => 0, 'fgcolor' => array(0,0,0), 'bgcolor' => false, 'text' => true);
        $pdf->write1DBarcode($producto->getCodigoBarras(), 'C128', $x+10, $y+8, $w-20, 15, 0.4, $style, 'N');
        
        $pdf->SetXY($x, $y+25);
        $pdf->SetFont('helvetica', '', 14);
        $pdf->Cell($w, 10, '$ ' . number_format($precio, 2), 0, 1, 'C');
    }
}
?>