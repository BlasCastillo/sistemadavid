<?php
require_once "config/conexion.php";

class Ventas {

    /* ==============================================================
       1. GESTIÓN DEL CARRITO TEMPORAL Y SUSPENSIONES
       ============================================================== */
    public static function agregarTemporal(int $usuario_id, int $producto_id, int $cantidad, float $precio_venta, float $descuento = 0) {
        $stmt = Conexion::conectar()->prepare("INSERT INTO ventas_temporales (usuario_id, producto_id, cantidad, precio_venta_usdt, descuento_aplicado) VALUES (:usuario_id, :producto_id, :cantidad, :precio_venta, :descuento)");
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(":producto_id", $producto_id, PDO::PARAM_INT);
        $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
        $stmt->bindParam(":precio_venta", $precio_venta, PDO::PARAM_STR);
        $stmt->bindParam(":descuento", $descuento, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public static function leerTemporales(int $usuario_id, $identificador_cliente = null) {
        $sql = "SELECT t.*, p.nombre as producto_nombre, p.codigo_barras, p.stock 
                FROM ventas_temporales t 
                INNER JOIN productos p ON t.producto_id = p.id 
                WHERE t.usuario_id = :usuario_id";
        if ($identificador_cliente == null) {
            $sql .= " AND t.identificador_cliente IS NULL";
        } else {
            $sql .= " AND t.identificador_cliente = :identificador";
        }
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        if ($identificador_cliente != null) { $stmt->bindParam(":identificador", $identificador_cliente, PDO::PARAM_STR); }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function eliminarTemporal(int $id) {
        $stmt = Conexion::conectar()->prepare("DELETE FROM ventas_temporales WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function actualizarCantidad(int $id_temporal, int $nueva_cantidad) {
        $stmt = Conexion::conectar()->prepare("UPDATE ventas_temporales SET cantidad = :cantidad WHERE id = :id");
        $stmt->bindParam(":cantidad", $nueva_cantidad, PDO::PARAM_INT);
        $stmt->bindParam(":id", $id_temporal, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // --- NUEVO: FUNCIÓN PARA DESCUENTOS PARCIALES ---
    public static function actualizarDescuentoItem(int $id_temporal, float $descuento) {
        $stmt = Conexion::conectar()->prepare("UPDATE ventas_temporales SET descuento_aplicado = :descuento WHERE id = :id");
        $stmt->bindParam(":descuento", $descuento, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id_temporal, PDO::PARAM_INT);
        return $stmt->execute();
    }
    // ------------------------------------------------

    public static function verificarProductoActivo(int $usuario_id, int $producto_id) {
        $stmt = Conexion::conectar()->prepare("SELECT id, cantidad FROM ventas_temporales WHERE usuario_id = :usuario_id AND producto_id = :producto_id AND identificador_cliente IS NULL");
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(":producto_id", $producto_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public static function suspenderCarrito(int $usuario_id, string $cedula) {
        $stmt = Conexion::conectar()->prepare("UPDATE ventas_temporales SET identificador_cliente = :cedula WHERE usuario_id = :usuario_id AND identificador_cliente IS NULL");
        $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function recuperarCarrito(int $usuario_id, string $cedula) {
        $conexion = Conexion::conectar();
        $stmtLimpiar = $conexion->prepare("DELETE FROM ventas_temporales WHERE usuario_id = :usuario_id AND identificador_cliente IS NULL");
        $stmtLimpiar->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmtLimpiar->execute();
        $stmt = $conexion->prepare("UPDATE ventas_temporales SET identificador_cliente = NULL WHERE usuario_id = :usuario_id AND identificador_cliente = :cedula");
        $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function listarSuspendidas(int $usuario_id) {
        $stmt = Conexion::conectar()->prepare("SELECT identificador_cliente, SUM(precio_venta_usdt * cantidad) as total, COUNT(id) as items FROM ventas_temporales WHERE usuario_id = :usuario_id AND identificador_cliente IS NOT NULL GROUP BY identificador_cliente");
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /* ==============================================================
       2. CREACIÓN EXPRESS DE CLIENTES
       ============================================================== */
    public static function crearClienteExpress(string $documento, string $nombre, string $telefono, string $email, string $direccion) {
        $conexion = Conexion::conectar();
        $stmtCheck = $conexion->prepare("SELECT id FROM clientes WHERE documento = :documento");
        $stmtCheck->bindParam(":documento", $documento, PDO::PARAM_STR);
        $stmtCheck->execute();
        $existe = $stmtCheck->fetch(PDO::FETCH_OBJ);
        if ($existe) { return $existe->id; }

        $stmt = $conexion->prepare("INSERT INTO clientes (documento, nombre, telefono, email, direccion, estado) VALUES (:documento, :nombre, :telefono, :email, :direccion, 1)");
        $stmt->bindParam(":documento", $documento, PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->bindParam(":telefono", $telefono, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $direccion, PDO::PARAM_STR);
        if ($stmt->execute()) { return $conexion->lastInsertId(); }
        return false;
    }

    /* ==============================================================
       3. PROCESAMIENTO DE LA VENTA (REGLA DE CONSUMO TOTAL)
       ============================================================== */
    public static function procesarVentaFinal($datosCabecera, $datosDetalle, $datosPagos) {
        $conexion = Conexion::conectar();
        
        // --- VALIDACIÓN ESTRICTA DE BILLETERA (Evitar Hackeos) ---
        foreach ($datosPagos as $pago) {
            if ($pago["metodo"] === "Saldo a Favor") {
                $stmtCheck = $conexion->prepare("SELECT id, monto_usd FROM notas_credito WHERE codigo_nota = :codigo AND cliente_id = :cliente AND estado = 'Disponible'");
                $stmtCheck->bindParam(":codigo", $pago["referencia"], PDO::PARAM_STR);
                $stmtCheck->bindParam(":cliente", $datosCabecera["cliente_id"], PDO::PARAM_INT);
                $stmtCheck->execute();
                $notaValida = $stmtCheck->fetch(PDO::FETCH_OBJ);
                
                // Tolerancia de redondeo de JS
                if(!$notaValida || (floatval($pago["monto"]) > (floatval($notaValida->monto_usd) + 0.02))) {
                    return "error_fraude_billetera"; 
                }
            }
        }

        try {
            $conexion->beginTransaction();

            // MODIFICADO: Se inserta el campo autorizador_id
            $stmt = $conexion->prepare("INSERT INTO ventas (usuario_id, cliente_id, autorizador_id, numero_factura, tasa_bcv, total_usdt, total_bs, ajuste_redondeo, estado, fecha_venta) VALUES (:usuario_id, :cliente_id, :autorizador_id, :numero_factura, :tasa_bcv, :total_usdt, :total_bs, :ajuste_redondeo, :estado, NOW())");
            
            $stmt->bindParam(":usuario_id", $datosCabecera["usuario_id"], PDO::PARAM_INT);
            $stmt->bindParam(":cliente_id", $datosCabecera["cliente_id"], PDO::PARAM_INT);
            
            // Lógica para aceptar NULL si no hay supervisor
            if(isset($datosCabecera["autorizador_id"]) && $datosCabecera["autorizador_id"] != null){
                $stmt->bindParam(":autorizador_id", $datosCabecera["autorizador_id"], PDO::PARAM_INT);
            } else {
                $stmt->bindValue(":autorizador_id", null, PDO::PARAM_NULL);
            }

            $stmt->bindParam(":numero_factura", $datosCabecera["numero_factura"], PDO::PARAM_STR);
            $stmt->bindParam(":tasa_bcv", $datosCabecera["tasa_bcv"], PDO::PARAM_STR);
            $stmt->bindParam(":total_usdt", $datosCabecera["total_usdt"], PDO::PARAM_STR);
            $stmt->bindParam(":total_bs", $datosCabecera["total_bs"], PDO::PARAM_STR);
            $stmt->bindParam(":ajuste_redondeo", $datosCabecera["ajuste_redondeo"], PDO::PARAM_STR);
            $stmt->bindParam(":estado", $datosCabecera["estado"], PDO::PARAM_STR);
            $stmt->execute();
            $venta_id = $conexion->lastInsertId();

            foreach ($datosDetalle as $item) {
                $stmtDetalle = $conexion->prepare("INSERT INTO ventas_detalle (venta_id, producto_id, cantidad, precio_unitario_usdt, descuento_usdt) VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario, :descuento)");
                $stmtDetalle->bindParam(":venta_id", $venta_id, PDO::PARAM_INT);
                $stmtDetalle->bindParam(":producto_id", $item->producto_id, PDO::PARAM_INT);
                $stmtDetalle->bindParam(":cantidad", $item->cantidad, PDO::PARAM_INT);
                $stmtDetalle->bindParam(":precio_unitario", $item->precio_venta_usdt, PDO::PARAM_STR);
                $stmtDetalle->bindParam(":descuento", $item->descuento_aplicado, PDO::PARAM_STR);
                $stmtDetalle->execute();

                $stmtStock = $conexion->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :producto_id");
                $stmtStock->bindParam(":cantidad", $item->cantidad, PDO::PARAM_INT);
                $stmtStock->bindParam(":producto_id", $item->producto_id, PDO::PARAM_INT);
                $stmtStock->execute();
            }

            foreach ($datosPagos as $pago) {
                $stmtPago = $conexion->prepare("INSERT INTO ventas_pagos (venta_id, metodo_pago, moneda, monto_pagado, referencia) VALUES (:venta_id, :metodo_pago, :moneda, :monto_pagado, :referencia)");
                $stmtPago->bindParam(":venta_id", $venta_id, PDO::PARAM_INT);
                $stmtPago->bindParam(":metodo_pago", $pago["metodo"], PDO::PARAM_STR);
                $stmtPago->bindParam(":moneda", $pago["moneda"], PDO::PARAM_STR);
                $stmtPago->bindParam(":monto_pagado", $pago["monto"], PDO::PARAM_STR);
                $stmtPago->bindParam(":referencia", $pago["referencia"], PDO::PARAM_STR);
                $stmtPago->execute();

                // D. QUEMAR LA NOTA DE CRÉDITO (SIN DAR VUELTO)
                if ($pago["metodo"] === "Saldo a Favor") {
                    $stmtNC = $conexion->prepare("UPDATE notas_credito SET estado = 'Usada' WHERE codigo_nota = :codigo");
                    $stmtNC->bindParam(":codigo", $pago["referencia"], PDO::PARAM_STR);
                    $stmtNC->execute();
                }
            }

            $stmtVaciar = $conexion->prepare("DELETE FROM ventas_temporales WHERE usuario_id = :usuario_id AND identificador_cliente IS NULL");
            $stmtVaciar->bindParam(":usuario_id", $datosCabecera["usuario_id"], PDO::PARAM_INT);
            $stmtVaciar->execute();

            $conexion->commit();
            return $venta_id;

        } catch (Exception $e) {
            $conexion->rollBack();
            return false;
        }
    }
    /* ==============================================================
       4. UTILIDADES PDF
       ============================================================== */
    public static function crearRutaFacturaPDF(string $nombreCajero): string {
        $fechaHoy = date("Y-m-d");
        $cajeroLimpio = str_replace(' ', '_', $nombreCajero); 
        $rutaBase = "Facturacion/" . $fechaHoy . "/" . $cajeroLimpio . "/";
        if (!file_exists($rutaBase)) { mkdir($rutaBase, 0777, true); }
        return $rutaBase;
    }

    public static function leerVentaCabecera(int $id_venta) {
        // MODIFICADO: Se inyecta el JOIN para traer el nombre del autorizador
        $stmt = Conexion::conectar()->prepare("SELECT v.*, c.documento as cliente_doc, c.nombre as cliente_nombre, c.direccion as cliente_direccion, u.usuario as cajero_nombre, sup.nombre_completo as autorizador_nombre FROM ventas v INNER JOIN clientes c ON v.cliente_id = c.id INNER JOIN usuarios u ON v.usuario_id = u.id LEFT JOIN usuarios sup ON v.autorizador_id = sup.id WHERE v.id = :id");
        $stmt->bindParam(":id", $id_venta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public static function leerVentaDetalle(int $id_venta) {
        $stmt = Conexion::conectar()->prepare("SELECT d.*, p.codigo_barras, p.nombre FROM ventas_detalle d INNER JOIN productos p ON d.producto_id = p.id WHERE d.venta_id = :id");
        $stmt->bindParam(":id", $id_venta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function leerVentaPagos(int $id_venta) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM ventas_pagos WHERE venta_id = :id");
        $stmt->bindParam(":id", $id_venta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /* ==============================================================
       6. HISTORIAL DE VENTAS (BUG 002 RESUELTO: Join con notas_credito)
       ============================================================== */
    public static function mdlMostrarHistorialVentas($rol_id, $usuario_id) {
        $conexion = Conexion::conectar();
        
        // Se inyecta (SELECT COUNT(id) FROM notas_credito...) para saber si pintar el botón rojo o no
        if ($rol_id == 1) {
            $stmt = $conexion->prepare("
                SELECT v.*, c.nombre as cliente_nombre, u.usuario as cajero_nombre,
                (SELECT COUNT(id) FROM notas_credito WHERE venta_original_id = v.id) as tiene_devolucion 
                FROM ventas v 
                INNER JOIN clientes c ON v.cliente_id = c.id 
                INNER JOIN usuarios u ON v.usuario_id = u.id 
                ORDER BY v.id DESC
            ");
        } else {
            $stmt = $conexion->prepare("
                SELECT v.*, c.nombre as cliente_nombre, u.usuario as cajero_nombre,
                (SELECT COUNT(id) FROM notas_credito WHERE venta_original_id = v.id) as tiene_devolucion 
                FROM ventas v 
                INNER JOIN clientes c ON v.cliente_id = c.id 
                INNER JOIN usuarios u ON v.usuario_id = u.id 
                WHERE v.usuario_id = :usuario_id 
                AND DATE(v.fecha_venta) = CURDATE() 
                ORDER BY v.id DESC
            ");
            $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /* ==============================================================
       9. EJECUTAR TRANSACCIÓN DE DEVOLUCIÓN (BUG 003 RESUELTO)
       ============================================================== */
    public static function mdlProcesarDevolucion($datosDevolucion, $items) {
        $conexion = Conexion::conectar();
        
        // --- VALIDACIÓN DE CANTIDADES ESTRICTA Y VINCULADA ---
        foreach ($items as $item) {
            $cant_intentada = floatval($item->cantidad_devuelta);
            
            // Validamos que el ID_DETALLE pertenezca realmente a esta FACTURA (Evita inyección de IDs ajenos)
            $stmtVal = $conexion->prepare("SELECT cantidad FROM ventas_detalle WHERE id = :id AND venta_id = :venta_id");
            $stmtVal->bindParam(":id", $item->id_detalle, PDO::PARAM_INT);
            $stmtVal->bindParam(":venta_id", $datosDevolucion["idVentaOriginal"], PDO::PARAM_INT);
            $stmtVal->execute();
            $det = $stmtVal->fetch(PDO::FETCH_OBJ);
            
            if(!$det || $det->cantidad < $cant_intentada || $cant_intentada <= 0) {
                return "error_cantidad"; // Bloquea devoluciones negativas, nulas o excedidas
            }
        }
        // --------------------------------

        try {
            $conexion->beginTransaction();

            $stmtV = $conexion->prepare("SELECT cliente_id, tasa_bcv FROM ventas WHERE id = :id");
            $stmtV->bindParam(":id", $datosDevolucion["idVentaOriginal"], PDO::PARAM_INT);
            $stmtV->execute();
            $ventaOriginal = $stmtV->fetch(PDO::FETCH_OBJ);
            
            $cliente_id = $ventaOriginal->cliente_id;
            $tasa_bcv_historica = $ventaOriginal->tasa_bcv;

            foreach ($items as $item) {
                $stmtInv = $conexion->prepare("UPDATE productos SET stock = stock + :cant WHERE id = :id_prod");
                $stmtInv->bindParam(":cant", $item->cantidad_devuelta, PDO::PARAM_INT);
                $stmtInv->bindParam(":id_prod", $item->id_producto, PDO::PARAM_INT);
                $stmtInv->execute();

                $stmtDet = $conexion->prepare("UPDATE ventas_detalle SET cantidad = cantidad - :cant WHERE id = :id_detalle");
                $stmtDet->bindParam(":cant", $item->cantidad_devuelta, PDO::PARAM_INT);
                $stmtDet->bindParam(":id_detalle", $item->id_detalle, PDO::PARAM_INT);
                $stmtDet->execute();
            }

            $codigo_nc = "NC-" . date("Ymd") . "-" . $datosDevolucion["idVentaOriginal"] . rand(10,99);

            if ($datosDevolucion["metodoReembolso"] == "credito_usd") {
                $stmtNC = $conexion->prepare("INSERT INTO notas_credito (codigo_nota, venta_original_id, cliente_id, monto_usd, estado) VALUES (:codigo, :venta_id, :cliente_id, :monto, 'Disponible')");
                $stmtNC->bindParam(":codigo", $codigo_nc, PDO::PARAM_STR);
                $stmtNC->bindParam(":venta_id", $datosDevolucion["idVentaOriginal"], PDO::PARAM_INT);
                $stmtNC->bindParam(":cliente_id", $cliente_id, PDO::PARAM_INT);
                $stmtNC->bindParam(":monto", $datosDevolucion["totalReembolso"], PDO::PARAM_STR);
                $stmtNC->execute();
            } else {
                $monto_gasto = $datosDevolucion["totalReembolso"];
                $tipo_gasto = "Variable";
                $metodo_texto = ($datosDevolucion["metodoReembolso"] == "efectivo_bs") ? "Efectivo Bs" : "Efectivo USD";
                
                if ($datosDevolucion["metodoReembolso"] == "efectivo_bs") {
                    $monto_gasto = $datosDevolucion["totalReembolso"] * $tasa_bcv_historica;
                }
                
                $concepto = "Reembolso F-" . $datosDevolucion["idVentaOriginal"] . " | NC: " . $codigo_nc . " (" . $metodo_texto . ")";
                $stmtGasto = $conexion->prepare("INSERT INTO gastos (concepto, monto, tipo, fecha, estado) VALUES (:concepto, :monto, :tipo, CURDATE(), 1)");
                $stmtGasto->bindParam(":concepto", $concepto, PDO::PARAM_STR);
                $stmtGasto->bindParam(":monto", $monto_gasto, PDO::PARAM_STR);
                $stmtGasto->bindParam(":tipo", $tipo_gasto, PDO::PARAM_STR);
                $stmtGasto->execute();
            }

            $conexion->commit();
            return $codigo_nc; 

        } catch (Exception $e) {
            $conexion->rollBack();
            return "error";
        }
    }

    /* ==============================================================
       10. BUSCAR NOTA DE CRÉDITO DEL CLIENTE
       ============================================================== */
    public static function mdlBuscarBilletera($documento) {
        $stmt = Conexion::conectar()->prepare("
            SELECT nc.* 
            FROM notas_credito nc 
            INNER JOIN clientes c ON nc.cliente_id = c.id 
            WHERE c.documento = :documento 
            AND nc.estado = 'Disponible' 
            AND DATE(nc.fecha_emision) = CURDATE() 
            ORDER BY nc.id DESC LIMIT 1
        ");
        $stmt->bindParam(":documento", $documento, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}
?>