<?php
require_once "modelo/Compras.php";

class ComprasControlador {

    /* ==============================================================
       1. GESTIÓN DEL CARRITO TEMPORAL (AJAX) - BLINDADO
       ============================================================== */
       
    public static function ctrAgregarTemporalAjax() {
        // Escuchamos la nueva variable segura que envía el JS
        if(isset($_POST["idProductoCompraSegura"])) {
            
            $usuario_id = $_SESSION["id_usuario"];
            
            $producto_id = intval($_POST["idProductoCompraSegura"]);
            $cantidad = intval($_POST["cantidadCompra"]);
            $costo_nominal = floatval($_POST["costoNominalCompra"]);
            $moneda = $_POST["monedaCompra"];

            // 1. Obtener las tasas oficiales directamente de la base de datos
            $stmt = Conexion::conectar()->prepare("SELECT tasa_bcv, brecha_porcentaje FROM tasas_cambio ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $tasaActual = $stmt->fetch(PDO::FETCH_OBJ);

            $tasaBcv = $tasaActual ? floatval($tasaActual->tasa_bcv) : 1;
            $brecha = $tasaActual ? floatval($tasaActual->brecha_porcentaje) : 0;

            // 2. EL NÚCLEO FINANCIERO (100% Backend)
            $costo_real_usdt = 0;
            
            if ($moneda === "Bs") {
                $costoBaseUsd = $costo_nominal / $tasaBcv;
                $costo_real_usdt = $costoBaseUsd * (1 + ($brecha / 100));
            } 
            else if ($moneda === "USD_Fisico") {
                $costo_real_usdt = $costo_nominal * (1 + ($brecha / 100));
            } 
            else if ($moneda === "USDT") {
                $costo_real_usdt = $costo_nominal;
            }

            // Redondeamos a 4 decimales para mantener consistencia financiera
            $costo_real_usdt = round($costo_real_usdt, 4);

            $respuesta = Compras::agregarTemporal($usuario_id, $producto_id, $cantidad, $costo_nominal, $costo_real_usdt);

            if($respuesta){
                echo json_encode(["status" => "success", "mensaje" => "Producto agregado a la factura."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al agregar el producto a la tabla temporal."]);
            }
            exit();
        }
    }

    public static function ctrMostrarTemporales() {
        $usuario_id = $_SESSION["id_usuario"];
        return Compras::leerTemporales($usuario_id);
    }

    public static function ctrEliminarTemporalAjax() {
        if(isset($_POST["idTemporalEliminar"])) {
            $id = intval($_POST["idTemporalEliminar"]);
            $respuesta = Compras::eliminarTemporal($id);

            if($respuesta){
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al retirar el ítem de la factura."]);
            }
            exit();
        }
    }

    /* ==============================================================
       2. PROCESAR COMPRA FINAL (Cierre de Factura) - BLINDADO
       ============================================================== */
       
    public static function ctrProcesarCompraAjax() {
        if(isset($_POST["procesarCompraFinal"])) {
            
            $usuario_id = $_SESSION["id_usuario"];
            
            // 1. Validar por seguridad que la tabla temporal no esté vacía
            $carrito = Compras::leerTemporales($usuario_id);
            if(count($carrito) == 0){
                echo json_encode(["status" => "error", "mensaje" => "Operación rechazada: La factura no tiene productos."]);
                exit();
            }

            // 2. Seguridad: Recalcular totales reales desde la base de datos (Ignorando el POST del HTML)
            $total_nominal_calculado = 0;
            $total_usdt_calculado = 0;
            
            foreach($carrito as $item) {
                $total_nominal_calculado += ($item->cantidad * $item->costo_nominal);
                $total_usdt_calculado += ($item->cantidad * $item->costo_real_usdt);
            }

            // 3. Seguridad: Obtener tasas del servidor
            $stmt = Conexion::conectar()->prepare("SELECT tasa_bcv, brecha_porcentaje FROM tasas_cambio ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $tasaActual = $stmt->fetch(PDO::FETCH_OBJ);
            $tasaBcvSegura = $tasaActual ? floatval($tasaActual->tasa_bcv) : 1;
            $brechaSegura = $tasaActual ? floatval($tasaActual->brecha_porcentaje) : 0;

            // 4. Lógica Financiera de Contado/Crédito
            $condicionPago = $_POST["condicionPagoCompra"];
            $diasCredito = ($condicionPago === "Credito") ? intval($_POST["diasCreditoCompra"]) : 0;
            $estadoPago = ($condicionPago === "Credito") ? "Pendiente" : "Pagado";

            // 5. Empaquetar los datos de la Cabecera 100% verificados
            $datosCabecera = array(
                "proveedor_id" => intval($_POST["idProveedorCompra"]),
                "usuario_id" => $usuario_id,
                "numero_factura" => $_POST["numeroFacturaCompra"],
                "moneda" => $_POST["monedaCompra"],
                "tasa_bcv" => $tasaBcvSegura,
                "porcentaje_brecha" => $brechaSegura,
                "total_nominal" => round($total_nominal_calculado, 2),
                "total_usdt" => round($total_usdt_calculado, 4),
                "observaciones" => $_POST["observacionesCompra"],
                "fecha_compra" => $_POST["fechaCompra"],
                "condicion_pago" => $condicionPago,
                "dias_credito" => $diasCredito,
                "estado_pago" => $estadoPago
            );

            // 6. Gatillar la transacción ACID en el Modelo
            $respuesta = Compras::procesarCompraFinal($datosCabecera, $carrito);

            if($respuesta == "ok"){
                echo json_encode(["status" => "success", "mensaje" => "Compra liquidada correctamente. Costos e inventario actualizados."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Fallo crítico estructural. Se ejecutó RollBack para proteger la base de datos."]);
            }
            exit();
        }
    }

    /* ==============================================================
       3. HISTORIAL DE COMPRAS (Reportes)
       ============================================================== */
       
    public static function ctrMostrarHistorial() {
        return Compras::leerHistorialCompras();
    }

    public static function ctrCargarTemporalesAjax() {
        if(isset($_POST["cargarTemporalesCompra"])) {
            $usuario_id = $_SESSION["id_usuario"]; 
            $respuesta = Compras::leerTemporales($usuario_id);
            echo json_encode($respuesta);
            exit();
        }
    }
    
    public static function ctrMostrarDetalleCompraAjax() {
        if(isset($_POST["idCompraDetalle"])) {
            $idCompra = intval($_POST["idCompraDetalle"]);
            $respuesta = Compras::leerDetallePorCompra($idCompra);
            
            echo json_encode($respuesta);
            exit();
        }
    }
}