<?php
require_once "modelo/Compras.php";

class ComprasControlador {

    /* ==============================================================
       1. GESTIÓN DEL CARRITO TEMPORAL (AJAX)
       ============================================================== */
       
    public static function ctrAgregarTemporalAjax() {
        if(isset($_POST["idProductoCompra"])) {
            
            // TODO: Cambiar por $_SESSION["id_usuario"] cuando exista el Login
           $usuario_id = $_SESSION["id_usuario"];
            
            $producto_id = intval($_POST["idProductoCompra"]);
            $cantidad = intval($_POST["cantidadCompra"]);
            $costo_nominal = floatval($_POST["costoNominalCompra"]);
            $costo_real_usdt = floatval($_POST["costoRealUsdtCompra"]);

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
        // TODO: Cambiar por $_SESSION["id_usuario"] cuando exista el Login
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
       2. PROCESAR COMPRA FINAL (Cierre de Factura)
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

            // 2. Empaquetar los datos de la Cabecera
            $datosCabecera = array(
                "proveedor_id" => intval($_POST["idProveedorCompra"]),
                "usuario_id" => $usuario_id,
                "numero_factura" => $_POST["numeroFacturaCompra"],
                "moneda" => $_POST["monedaCompra"],
                "tasa_bcv" => floatval($_POST["tasaBcvCompra"]),
                "porcentaje_brecha" => floatval($_POST["brechaCompra"]),
                "total_nominal" => floatval($_POST["totalNominalCompra"]),
                "total_usdt" => floatval($_POST["totalUsdtCompra"]),
                "observaciones" => $_POST["observacionesCompra"],
                "fecha_compra" => $_POST["fechaCompra"]
            );

            // 3. Gatillar la transacción ACID en el Modelo
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