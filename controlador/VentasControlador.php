<?php
require_once "modelo/Ventas.php";
require_once "modelo/ConsultaPrecios.php"; 
require_once "modelo/Tasas.php";

class VentasControlador {

    /* ==============================================================
       1. GESTIÓN DEL CARRITO TEMPORAL (AJAX)
       ============================================================== */
    public static function ctrAgregarTemporalAjax() {
        if(isset($_POST["codigoProductoVenta"])) {
            
            // --- BLINDAJE DE SESIÓN ---
            $usuario_id = $_SESSION["id_usuario"] ?? $_SESSION["id"] ?? null;
            if (!$usuario_id) {
                echo json_encode(["status" => "error", "mensaje" => "Su sesión ha expirado o no es válida. Por favor, recargue la página e inicie sesión nuevamente."]);
                exit();
            }
            // --------------------------

            $codigo = trim($_POST["codigoProductoVenta"]);
            $cantidad = intval($_POST["cantidadVenta"]);

            $stmt = Conexion::conectar()->prepare("SELECT id, stock FROM productos WHERE codigo_barras = :codigo AND estado = 1");
            $stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);
            $stmt->execute();
            $prodBD = $stmt->fetch(PDO::FETCH_OBJ);

            if(!$prodBD) {
                echo json_encode(["status" => "error", "mensaje" => "El producto no existe o está inactivo en el sistema."]);
                exit();
            }

            $producto_id = $prodBD->id;
            $stockReal = $prodBD->stock;

            $carritoActivo = Ventas::leerTemporales($usuario_id);
            $cantidadEnCarrito = 0;
            
            foreach($carritoActivo as $item) {
                if($item->producto_id == $producto_id) {
                    $cantidadEnCarrito += $item->cantidad;
                }
            }

            if(($cantidadEnCarrito + $cantidad) > $stockReal) {
                echo json_encode(["status" => "error", "mensaje" => "Stock insuficiente. Quedan " . ($stockReal - $cantidadEnCarrito) . " unidades disponibles."]);
                exit();
            }

            $productoPrecio = ConsultaPrecios::buscarProductoPorCodigo($codigo);
            
            if ($productoPrecio) {
                $costoUsdt = $productoPrecio->getCostoUsdt();
                $margen = $productoPrecio->getMargenGanancia();
                $precioRegularUsdt = $costoUsdt * (1 + ($margen / 100));
                
                $tieneOferta = $productoPrecio->getTieneOfertaActiva();
                $precioVentaUsdt = ($tieneOferta == 1) ? $productoPrecio->getPrecioOfertaUsdt() : $precioRegularUsdt;
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al calcular el precio del producto."]);
                exit();
            }

            $existeItem = Ventas::verificarProductoActivo($usuario_id, $producto_id);
            if($existeItem) {
                $nuevaCant = $existeItem->cantidad + $cantidad;
                $respuesta = Ventas::actualizarCantidad($existeItem->id, $nuevaCant);
            } else {
                $respuesta = Ventas::agregarTemporal($usuario_id, $producto_id, $cantidad, $precioVentaUsdt, 0);
            }

            if($respuesta){
                echo json_encode(["status" => "success", "mensaje" => "Producto en carrito."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error interno al guardar en el carrito temporal."]);
            }
            exit();
        }
    }

    public static function ctrCargarTemporalesAjax() {
        if(isset($_POST["cargarTemporalesVenta"])) {
            $usuario_id = $_SESSION["id_usuario"] ?? $_SESSION["id"] ?? null;
            if (!$usuario_id) {
                echo json_encode([]);
                exit();
            }

            $respuesta = Ventas::leerTemporales($usuario_id);
            echo json_encode($respuesta);
            exit();
        }
    }

    public static function ctrEliminarTemporalAjax() {
        if(isset($_POST["idTemporalVentaEliminar"])) {
            $id = intval($_POST["idTemporalVentaEliminar"]);
            $respuesta = Ventas::eliminarTemporal($id);
            
            if($respuesta){
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "No se pudo retirar el producto de la lista."]);
            }
            exit();
        }
    }

    public static function ctrActualizarCantidadAjax() {
        if(isset($_POST["idItemActualizar"]) && isset($_POST["nuevaCantidad"])) {
            $id_temporal = intval($_POST["idItemActualizar"]);
            $nueva_cantidad = intval($_POST["nuevaCantidad"]);

            $stmt = Conexion::conectar()->prepare("SELECT producto_id FROM ventas_temporales WHERE id = :id");
            $stmt->bindParam(":id", $id_temporal, PDO::PARAM_INT);
            $stmt->execute();
            $temp = $stmt->fetch(PDO::FETCH_OBJ);

            if($temp) {
                $stmtStock = Conexion::conectar()->prepare("SELECT stock FROM productos WHERE id = :id");
                $stmtStock->bindParam(":id", $temp->producto_id, PDO::PARAM_INT);
                $stmtStock->execute();
                $prod = $stmtStock->fetch(PDO::FETCH_OBJ);

                if($prod && $nueva_cantidad > $prod->stock) {
                    echo json_encode(["status" => "error", "mensaje" => "Stock insuficiente. Solo quedan " . $prod->stock . " unidades disponibles."]);
                    exit();
                }

                Ventas::actualizarCantidad($id_temporal, $nueva_cantidad);
                echo json_encode(["status" => "success"]);
            }
            exit();
        }
    }

    // --- MÉTODO PARA APLICAR DESCUENTO A UN PRODUCTO EN EL CARRITO ---
    public static function ctrAplicarDescuentoItemAjax() {
        if(isset($_POST["idItemDescuento"]) && isset($_POST["montoDescuento"])) {
            $id_temporal = intval($_POST["idItemDescuento"]);
            $descuento = floatval($_POST["montoDescuento"]);
            $respuesta = Ventas::actualizarDescuentoItem($id_temporal, $descuento);
            
            if($respuesta){
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "No se pudo aplicar el descuento."]);
            }
            exit();
        }
    }

    /* ==============================================================
       2. SISTEMA DE SUSPENSIÓN DE FACTURAS (Anticolas)
       ============================================================== */
    public static function ctrSuspenderFacturaAjax() {
        if(isset($_POST["cedulaSuspender"])) {
            $usuario_id = $_SESSION["id_usuario"] ?? $_SESSION["id"] ?? null;
            if (!$usuario_id) {
                echo json_encode(["status" => "error", "mensaje" => "Sesión expirada. Recargue la página."]);
                exit();
            }

            $cedula = trim($_POST["cedulaSuspender"]);
            $carrito = Ventas::leerTemporales($usuario_id);
            if(count($carrito) == 0){
                echo json_encode(["status" => "warning", "mensaje" => "No hay productos en el carrito para suspender."]);
                exit();
            }

            $respuesta = Ventas::suspenderCarrito($usuario_id, $cedula);
            
            if($respuesta){
                echo json_encode(["status" => "success", "mensaje" => "La factura de $cedula ha sido puesta en espera."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al suspender la factura."]);
            }
            exit();
        }
    }

    public static function ctrListarSuspendidasAjax() {
        if(isset($_POST["listarSuspendidas"])) {
            $usuario_id = $_SESSION["id_usuario"] ?? $_SESSION["id"] ?? null;
            if (!$usuario_id) {
                echo json_encode([]);
                exit();
            }

            $respuesta = Ventas::listarSuspendidas($usuario_id);
            echo json_encode($respuesta);
            exit();
        }
    }

    public static function ctrRecuperarFacturaAjax() {
        if(isset($_POST["cedulaRecuperar"])) {
            $usuario_id = $_SESSION["id_usuario"] ?? $_SESSION["id"] ?? null;
            if (!$usuario_id) {
                echo json_encode(["status" => "error", "mensaje" => "Sesión expirada. Recargue la página."]);
                exit();
            }

            $cedula = trim($_POST["cedulaRecuperar"]);
            $respuesta = Ventas::recuperarCarrito($usuario_id, $cedula);
            
            if($respuesta){
                echo json_encode(["status" => "success", "mensaje" => "Factura recuperada con éxito. Ya puede proceder al cobro."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al intentar restaurar la factura."]);
            }
            exit();
        }
    }

    /* ==============================================================
       3. LIQUIDACIÓN, CLIENTE EXPRESS Y MULTIPAGO
       ============================================================== */
    public static function ctrProcesarVentaAjax() {
        if(isset($_POST["procesarVentaFinal"])) {
            $usuario_id = $_SESSION["id_usuario"] ?? $_SESSION["id"] ?? null;
            if (!$usuario_id) {
                echo json_encode(["status" => "error", "mensaje" => "Su sesión expiró. Venta cancelada por seguridad."]);
                exit();
            }

            $carrito = Ventas::leerTemporales($usuario_id);
            if(count($carrito) == 0){
                echo json_encode(["status" => "error", "mensaje" => "Intento de cobro vacío rechazado por el sistema."]);
                exit();
            }

            $docCliente = trim($_POST["docClienteFinal"]);
            $nomCliente = strtoupper(trim($_POST["nomClienteFinal"]));
            $telCliente = $_POST["telClienteFinal"] ?? "";
            $emaCliente = $_POST["emaClienteFinal"] ?? "";
            $dirCliente = $_POST["dirClienteFinal"] ?? "Sin Dirección";

            $cliente_id = Ventas::crearClienteExpress($docCliente, $nomCliente, $telCliente, $emaCliente, $dirCliente);
            if(!$cliente_id) {
                echo json_encode(["status" => "error", "mensaje" => "Fallo de base de datos al validar al cliente."]);
                exit();
            }

            $stmt = Conexion::conectar()->prepare("SELECT tasa_bcv FROM tasas_cambio ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $tasaActual = $stmt->fetch(PDO::FETCH_OBJ);
            $tasaBcvSegura = $tasaActual ? floatval($tasaActual->tasa_bcv) : 1;

            $autorizador_id = !empty($_POST["autorizadorFinal"]) ? intval($_POST["autorizadorFinal"]) : null;
            $descuentoGlobal = isset($_POST["descuentoGlobalFinal"]) ? floatval($_POST["descuentoGlobalFinal"]) : 0;
            
            $esCredito = (isset($_POST["ventaCredito"]) && $_POST["ventaCredito"] == "1") ? true : false;
            $estadoFactura = $esCredito ? "Credito" : "Pagada";

            $totalUsdtCalculado = 0;
            foreach($carrito as $item) {
                $totalUsdtCalculado += ($item->cantidad * $item->precio_venta_usdt) - $item->descuento_aplicado;
            }
            
            $totalUsdtCalculado -= $descuentoGlobal;
            $totalBsCalculado = $totalUsdtCalculado * $tasaBcvSegura;

            $datosPagos = json_decode($_POST["listaPagosFinal"], true);
            if((!is_array($datosPagos) || count($datosPagos) == 0) && !$esCredito) {
                echo json_encode(["status" => "error", "mensaje" => "No se registraron métodos de pago para esta factura."]);
                exit();
            }

            foreach ($datosPagos as $pago) {
                if ($pago["metodo"] === "Saldo a Favor") {
                    $stmtCheck = Conexion::conectar()->prepare("SELECT monto_usd FROM notas_credito WHERE codigo_nota = :codigo");
                    $stmtCheck->bindParam(":codigo", $pago["referencia"], PDO::PARAM_STR);
                    $stmtCheck->execute();
                    $nota = $stmtCheck->fetch(PDO::FETCH_OBJ);

                    if($nota) {
                        $monto_nota = floatval($nota->monto_usd);
                        $total_compra = floatval($totalUsdtCalculado);
                        $diferencia = $total_compra - $monto_nota;

                        if (!$esCredito && $total_compra < ($monto_nota - 0.05)) {
                            echo json_encode([
                                "status" => "error", 
                                "mensaje" => "El total de la compra ($" . number_format($total_compra, 2) . ") es menor al Saldo a Favor ($" . number_format($monto_nota, 2) . "). Debe agregar más productos.",
                                "debug" => [
                                    "motivo" => "Consumo Insuficiente Billetera",
                                    "total_carrito" => round($total_compra, 4),
                                    "saldo_bd" => round($monto_nota, 4),
                                    "faltan" => round(abs($diferencia), 4)
                                ]
                            ]);
                            exit();
                        }
                    }
                }
            }

            $totalPagadoUsdt = 0;
            if(is_array($datosPagos)) {
                foreach($datosPagos as $pago) {
                    $montoPago = floatval($pago["monto"]);
                    if($pago["moneda"] === "BS") {
                        $totalPagadoUsdt += ($montoPago / $tasaBcvSegura);
                    } else {
                        $totalPagadoUsdt += $montoPago;
                    }
                }
            }
            
            $ajuste_redondeo = 0;
            if(!$esCredito) {
                $ajuste_redondeo = $totalUsdtCalculado - $totalPagadoUsdt;
            }

            $stmtMax = Conexion::conectar()->prepare("SELECT MAX(id) as max_id FROM ventas");
            $stmtMax->execute();
            $resultado = $stmtMax->fetch(PDO::FETCH_OBJ);
            $siguienteId = $resultado->max_id ? $resultado->max_id + 1 : 1;
            $numero_factura = str_pad($siguienteId, 6, "0", STR_PAD_LEFT);
            
            $datosCabecera = [
                "usuario_id" => $usuario_id,
                "cliente_id" => $cliente_id,
                "autorizador_id" => $autorizador_id, 
                "numero_factura" => $numero_factura,
                "tasa_bcv" => $tasaBcvSegura,
                "total_usdt" => round($totalUsdtCalculado, 4),
                "total_bs" => round($totalBsCalculado, 2),
                "ajuste_redondeo" => round($ajuste_redondeo, 4), 
                "estado" => $estadoFactura 
            ];

            $venta_id = Ventas::procesarVentaFinal($datosCabecera, $carrito, $datosPagos);

            if($venta_id === "error_fraude_billetera") {
                echo json_encode([
                    "status" => "error", 
                    "mensaje" => "La Nota de Crédito asignada no existe, ya fue utilizada o intentó cobrar más dinero del disponible.",
                    "debug" => ["motivo" => "Fraude Billetera BD"]
                ]);
            } else if($venta_id){
                echo json_encode([
                    "status" => "success", 
                    "mensaje" => "Venta procesada con éxito. Inventario actualizado.", 
                    "id_venta" => $venta_id 
                ]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error crítico estructural (Rollback ejecutado)."]);
            }
            exit();
        }
    }

    /* ==============================================================
       6. MOSTRAR HISTORIAL DE VENTAS
       ============================================================== */
    public static function ctrMostrarHistorialVentas() {
        if(isset($_SESSION["rol_id"]) && (isset($_SESSION["id_usuario"]) || isset($_SESSION["id"]))) {
            $rol_id = $_SESSION["rol_id"];
            $usuario_id = $_SESSION["id_usuario"] ?? $_SESSION["id"];
            $respuesta = Ventas::mdlMostrarHistorialVentas($rol_id, $usuario_id);
            return $respuesta;
        } else {
            return [];
        }
    }

   /* ==============================================================
       7. AUTORIZACIÓN ESTRICTA DE SUPERVISOR (DOBLE FACTOR)
       ============================================================== */
    public static function ctrAutorizarSupervisorAjax() {
        if(isset($_POST["supUsuario"]) && isset($_POST["supPin"])) {
            
            $usuario = $_POST["supUsuario"];
            $pin_ingresado = $_POST["supPin"];

            $stmt = Conexion::conectar()->prepare("SELECT u.*, r.permisos FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id WHERE u.usuario = :usuario AND u.estado = 1");
            $stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);
            $stmt->execute();
            $supervisor = $stmt->fetch(PDO::FETCH_OBJ);

            if($supervisor) {
                $clave_valida = false;
                if (password_verify($pin_ingresado, $supervisor->pin_autorizacion) || $pin_ingresado === $supervisor->pin_autorizacion) {
                    $clave_valida = true;
                }

                if($clave_valida) {
                    $permisos = json_decode($supervisor->permisos, true);
                    if (is_array($permisos) && (in_array("all", $permisos) || in_array("anular_ventas", $permisos))) {
                        echo json_encode([
                            "status" => "success", 
                            "id_supervisor" => $supervisor->id, 
                            "nombre_supervisor" => $supervisor->nombre_completo 
                        ]);
                    } else {
                        echo json_encode(["status" => "error", "mensaje" => "Credenciales correctas, pero este usuario carece del permiso gerencial."]);
                    }
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Clave/PIN incorrecto."]);
                }
            } else {
                 echo json_encode(["status" => "error", "mensaje" => "Usuario no encontrado o inactivo."]);
            }
            exit; 
        }
    }

    /* ==============================================================
       8. EXTRAER VENTA COMPLETA
       ============================================================== */
    public static function ctrMostrarVentaCompleta($id_venta) {
        if($id_venta != null) {
            $cabecera = Ventas::leerVentaCabecera($id_venta);
            $detalles = Ventas::leerVentaDetalle($id_venta);
            $pagos = Ventas::leerVentaPagos($id_venta);
            
            return [
                "cabecera" => $cabecera,
                "detalles" => $detalles,
                "pagos" => $pagos
            ];
        }
        return false;
    }

    /* ==============================================================
       9. PROCESAR DEVOLUCIÓN Y NOTA DE CRÉDITO (AJAX)
       ============================================================== */
    public static function ctrProcesarDevolucionAjax() {
        if(isset($_POST["procesarDevolucionAjax"])) {
            
            $datosDevolucion = [
                "idVentaOriginal" => intval($_POST["idVentaOriginal"]),
                "metodoReembolso" => $_POST["metodoReembolso"],
                "totalReembolso"  => floatval($_POST["totalReembolso"])
            ];

            $itemsReversar = json_decode($_POST["itemsReversar"]);

            if(is_array($itemsReversar) && count($itemsReversar) > 0) {
                
                $respuesta = Ventas::mdlProcesarDevolucion($datosDevolucion, $itemsReversar);
                
                // ---> CAPA 2 DE SEGURIDAD: INTERPRETACIÓN DEL MODELO <---
                if($respuesta == "error_es_credito") {
                    echo json_encode(["status" => "error", "mensaje" => "Las facturas a crédito no admiten devoluciones ni notas de crédito. El cliente debe cancelar su deuda."]);
                } else if($respuesta == "error_cantidad") {
                    echo json_encode(["status" => "error", "mensaje" => "Se intentó devolver una cantidad de artículos mayor a la que fue comprada originalmente."]);
                } else if($respuesta != "error") {
                    echo json_encode(["status" => "success", "mensaje" => "Reembolso procesado.", "codigo_nota" => $respuesta]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al ejecutar la transacción en la base de datos."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "No se recibieron productos para reversar."]);
            }
        }
    }

    /* ==============================================================
       10. BUSCAR BILLETERA / NOTA DE CRÉDITO DEL DÍA (AJAX)
       ============================================================== */
    public static function ctrBuscarBilleteraAjax() {
        if(isset($_POST["buscarBilleteraAjax"])) {
            $documento = trim($_POST["documentoCliente"]);
            $respuesta = Ventas::mdlBuscarBilletera($documento);

            if($respuesta) {
                echo json_encode([
                    "status" => "success", 
                    "codigo_nota" => $respuesta->codigo_nota, 
                    "monto_usd" => $respuesta->monto_usd
                ]);
            } else {
                echo json_encode([
                    "status" => "error", 
                    "mensaje" => "No hay saldo"
                ]);
            }
        }
    }
}
?>