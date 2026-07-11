<?php
require_once "modelo/ConsultaPrecios.php";

class ConsultaPreciosControlador {

    /*=============================================
    MOSTRAR CARRUSEL DE OFERTAS
    =============================================*/
    public static function ctrMostrarCarrusel() {
        return ConsultaPrecios::obtenerOfertasActivasParaCarrusel();
    }

    /*=============================================
    INTERCEPTOR AJAX: BUSCAR POR CÓDIGO
    =============================================*/
    public static function ctrBuscarCodigoAjax() {
        if (isset($_POST["codigoBarrasConsulta"])) {
            
            // Validación estricta: Solo letras, números, guiones y guiones bajos para el código de barras
            if (preg_match('/^[a-zA-Z0-9\-\_]+$/', $_POST["codigoBarrasConsulta"])) {
                
                $codigo = $_POST["codigoBarrasConsulta"];
                $producto = ConsultaPrecios::buscarProductoPorCodigo($codigo);

                if ($producto) {
                    
                    // Traemos la tasa de cambio actual directamente de la base de datos para máxima precisión
                    require_once "modelo/Tasas.php";
                    $stmt = Conexion::conectar()->prepare("SELECT tasa_bcv FROM tasas_cambio ORDER BY id DESC LIMIT 1");
                    $stmt->execute();
                    $tasaData = $stmt->fetch(PDO::FETCH_OBJ);
                    $tasaBcv = $tasaData ? floatval($tasaData->tasa_bcv) : 1.0;

                    // Matemáticas basadas estrictamente en los Getters del objeto instanciado
                    $costoUsdt = $producto->getCostoUsdt();
                    $margen = $producto->getMargenGanancia();
                    
                    $precioRegularUsdt = $costoUsdt * (1 + ($margen / 100));
                    $precioRegularBs = $precioRegularUsdt * $tasaBcv;

                    $precioOfertaUsdt = $producto->getPrecioOfertaUsdt();
                    $precioOfertaBs = $precioOfertaUsdt * $tasaBcv;

                    // Respuesta JSON estandarizada para ser consumida por el Frontend
                    echo json_encode([
                        "status" => "success",
                        "data" => [
                            "nombre" => $producto->getNombre(),
                            "imagen" => $producto->getImagen(),
                            "codigo" => $producto->getCodigoBarras(),
                            "precio_regular_usdt" => round($precioRegularUsdt, 4),
                            "precio_regular_bs" => round($precioRegularBs, 2),
                            "tiene_oferta" => $producto->getTieneOfertaActiva(),
                            "porcentaje_descuento" => $producto->getPorcentajeDescuento(),
                            "precio_oferta_usdt" => round($precioOfertaUsdt, 4),
                            "precio_oferta_bs" => round($precioOfertaBs, 2)
                        ]
                    ]);

                } else {
                    echo json_encode(["status" => "error", "mensaje" => "El producto no se encuentra en el catálogo o está inactivo."]);
                }

            } else {
                echo json_encode(["status" => "error", "mensaje" => "El código de barras contiene caracteres inválidos."]);
            }
            
            exit();
        }
    }
}
?>