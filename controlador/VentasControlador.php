<?php
require_once "modelo/Ventas.php";
require_once "modelo/ConsultaPrecios.php"; // Reutilizamos tu validador de ofertas
require_once "modelo/Tasas.php";

class VentasControlador {

    /* ==============================================================
       1. GESTIÓN DEL CARRITO TEMPORAL (AJAX)
       ============================================================== */
       
    public static function ctrAgregarTemporalAjax() {
        if(isset($_POST["codigoProductoVenta"])) {
            
            $usuario_id = $_SESSION["id_usuario"];
            $codigo = trim($_POST["codigoProductoVenta"]);
            $cantidad = intval($_POST["cantidadVenta"]);

            // 1. Buscamos el ID y el Stock directamente en la base de datos por seguridad
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

            // 2. Validación Estricta de Inventario (Stock Físico - Stock en Carrito)
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

            // 3. Captura del Precio Exacto usando el motor de ConsultaPrecios
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

            // 4. Inserción o Actualización al carrito
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
            $usuario_id = $_SESSION["id_usuario"];
            // Trae solo los activos (donde identificador_cliente es NULL)
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

            // Obtener el producto_id
            $stmt = Conexion::conectar()->prepare("SELECT producto_id FROM ventas_temporales WHERE id = :id");
            $stmt->bindParam(":id", $id_temporal, PDO::PARAM_INT);
            $stmt->execute();
            $temp = $stmt->fetch(PDO::FETCH_OBJ);

            if($temp) {
                // Verificar stock real para no vender de más
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
    /* ==============================================================
       2. SISTEMA DE SUSPENSIÓN DE FACTURAS (Anticolas)
       ============================================================== */
       
    public static function ctrSuspenderFacturaAjax() {
        if(isset($_POST["cedulaSuspender"])) {
            $usuario_id = $_SESSION["id_usuario"];
            $cedula = trim($_POST["cedulaSuspender"]);

            // Validamos que el carrito no esté vacío
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
            $usuario_id = $_SESSION["id_usuario"];
            $respuesta = Ventas::listarSuspendidas($usuario_id);
            echo json_encode($respuesta);
            exit();
        }
    }

    public static function ctrRecuperarFacturaAjax() {
        if(isset($_POST["cedulaRecuperar"])) {
            $usuario_id = $_SESSION["id_usuario"];
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
            $usuario_id = $_SESSION["id_usuario"];

            // 1. Validar que exista el carrito
            $carrito = Ventas::leerTemporales($usuario_id);
            if(count($carrito) == 0){
                echo json_encode(["status" => "error", "mensaje" => "Intento de cobro vacío rechazado por el sistema."]);
                exit();
            }

            // 2. Gestión del Cliente Express "Silencioso"
            $docCliente = trim($_POST["docClienteFinal"]);
            $nomCliente = strtoupper(trim($_POST["nomClienteFinal"]));
            // Datos opcionales
            $telCliente = $_POST["telClienteFinal"] ?? "";
            $emaCliente = $_POST["emaClienteFinal"] ?? "";
            $dirCliente = $_POST["dirClienteFinal"] ?? "Sin Dirección";

            $cliente_id = Ventas::crearClienteExpress($docCliente, $nomCliente, $telCliente, $emaCliente, $dirCliente);
            if(!$cliente_id) {
                echo json_encode(["status" => "error", "mensaje" => "Fallo de base de datos al validar al cliente."]);
                exit();
            }

            // 3. Capturar Tasas Frescas
            $stmt = Conexion::conectar()->prepare("SELECT tasa_bcv FROM tasas_cambio ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $tasaActual = $stmt->fetch(PDO::FETCH_OBJ);
            $tasaBcvSegura = $tasaActual ? floatval($tasaActual->tasa_bcv) : 1;

            // 4. Calcular Totales Rigurosos en Backend
            $totalUsdtCalculado = 0;
            foreach($carrito as $item) {
                $totalUsdtCalculado += ($item->cantidad * $item->precio_venta_usdt) - $item->descuento_aplicado;
            }
            $totalBsCalculado = $totalUsdtCalculado * $tasaBcvSegura;

            // 5. Array de Pagos
            $datosPagos = json_decode($_POST["listaPagosFinal"], true);
            if(!is_array($datosPagos) || count($datosPagos) == 0) {
                echo json_encode(["status" => "error", "mensaje" => "No se registraron métodos de pago para esta factura."]);
                exit();
            }

            // 6. Generador de Correlativo Secuencial (000001)
            $stmtMax = Conexion::conectar()->prepare("SELECT MAX(id) as max_id FROM ventas");
            $stmtMax->execute();
            $resultado = $stmtMax->fetch(PDO::FETCH_OBJ);
            
            // Si hay ventas, sumamos 1 al último ID. Si está vacía, empezamos en 1.
            $siguienteId = $resultado->max_id ? $resultado->max_id + 1 : 1;
            
            // str_pad rellena con ceros a la izquierda hasta tener 6 dígitos
            $numero_factura = str_pad($siguienteId, 6, "0", STR_PAD_LEFT);
            // 7. Empaquetar el Contenedor
            $datosCabecera = [
                "usuario_id" => $usuario_id,
                "cliente_id" => $cliente_id,
                "numero_factura" => $numero_factura,
                "tasa_bcv" => $tasaBcvSegura,
                "total_usdt" => round($totalUsdtCalculado, 4),
                "total_bs" => round($totalBsCalculado, 2),
                "estado" => "Pagada"
            ];

            // 8. Ejecutar Transacción Final
            $venta_id = Ventas::procesarVentaFinal($datosCabecera, $carrito, $datosPagos);

            if($venta_id === "error_fraude_billetera") {
                echo json_encode(["status" => "error", "mensaje" => "error_fraude_billetera"]);
            } else if($venta_id){
                echo json_encode([
                    "status" => "success", 
                    "mensaje" => "Venta procesada con éxito. Inventario actualizado.", 
                    "id_venta" => $venta_id // Devolvemos el ID para mandarlo al PDF
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
        
        // Validamos que existan las variables de sesión por seguridad
        if(isset($_SESSION["rol_id"]) && isset($_SESSION["id_usuario"])) {
            
            $rol_id = $_SESSION["rol_id"];
            $usuario_id = $_SESSION["id_usuario"];
            
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

            // 1. Buscamos al usuario y traemos su matriz de permisos
            $stmt = Conexion::conectar()->prepare("SELECT u.*, r.permisos FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id WHERE u.usuario = :usuario AND u.estado = 1");
            $stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);
            $stmt->execute();
            $supervisor = $stmt->fetch(PDO::FETCH_OBJ);

            if($supervisor) {
                // 2. Validamos el PIN (Usando pin_autorizacion de la BD)
                $clave_valida = false;
                if (password_verify($pin_ingresado, $supervisor->pin_autorizacion) || $pin_ingresado === $supervisor->pin_autorizacion) {
                    $clave_valida = true;
                }

                if($clave_valida) {
                    // 3. Evaluamos la matriz de permisos
                    $permisos = json_decode($supervisor->permisos, true);
                    
                    if (is_array($permisos) && (in_array("all", $permisos) || in_array("anular_ventas", $permisos))) {
                        echo json_encode([
                            "status" => "success", 
                            "id_supervisor" => $supervisor->id, 
                            "nombre_supervisor" => $supervisor->nombre_completo // Corregido: usando nombre_completo de la BD
                        ]);
                    } else {
                        echo json_encode(["status" => "error", "mensaje" => "Credenciales correctas, pero este usuario carece del permiso de anulación."]);
                    }
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Clave/PIN incorrecto."]);
                }
            } else {
                 echo json_encode(["status" => "error", "mensaje" => "Usuario no encontrado o inactivo."]);
            }
            
            // EL FRENO DE EMERGENCIA: Obliga a PHP a detenerse aquí mismo.
            exit; 
        }
    }
    /* ==============================================================
       8. EXTRAER VENTA COMPLETA (PARA DEVOLUCIONES / NOTAS DE CRÉDITO)
       ============================================================== */
    public static function ctrMostrarVentaCompleta($id_venta) {
        if($id_venta != null) {
            
            // Reutilizamos los métodos exactos que ya usas para el PDF
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

            // Decodificamos el array de productos que envió JS
            $itemsReversar = json_decode($_POST["itemsReversar"]);

            if(is_array($itemsReversar) && count($itemsReversar) > 0) {
                
                $respuesta = Ventas::mdlProcesarDevolucion($datosDevolucion, $itemsReversar);
                
                if($respuesta == "error_cantidad") {
                    echo json_encode(["status" => "error", "mensaje" => "Se intentó devolver una cantidad mayor al stock registrado en la factura original."]);
                } else if($respuesta != "error") {
                    // Si todo salió bien, la respuesta es el código (Ej: NC-2026...)
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
                // Si encontró una nota válida, mandamos el código y el monto
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