<?php
require_once "config/conexion.php";

class Compras {

    /* ==============================================================
       1. GESTIÓN DEL CARRITO TEMPORAL (Antishock)
       ============================================================== */
       
    public static function agregarTemporal(int $usuario_id, int $producto_id, int $cantidad, float $costo_nominal, float $costo_real_usdt) {
        $stmt = Conexion::conectar()->prepare("INSERT INTO compras_temporales (usuario_id, producto_id, cantidad, costo_nominal, costo_real_usdt) VALUES (:usuario_id, :producto_id, :cantidad, :costo_nominal, :costo_real_usdt)");
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(":producto_id", $producto_id, PDO::PARAM_INT);
        $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
        $stmt->bindParam(":costo_nominal", $costo_nominal, PDO::PARAM_STR);
        $stmt->bindParam(":costo_real_usdt", $costo_real_usdt, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public static function leerTemporales(int $usuario_id) {
        $stmt = Conexion::conectar()->prepare("
            SELECT t.*, p.nombre as producto_nombre, p.codigo_barras, p.imagen
            FROM compras_temporales t
            INNER JOIN productos p ON t.producto_id = p.id
            WHERE t.usuario_id = :usuario_id
            ORDER BY t.id DESC
        ");
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function eliminarTemporal(int $id) {
        $stmt = Conexion::conectar()->prepare("DELETE FROM compras_temporales WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /* ==============================================================
       2. PROCESAMIENTO DE LA COMPRA FINAL (Transacción Segura)
       ============================================================== */
       
    public static function procesarCompraFinal($datosCabecera, $datosDetalle) {
        $conexion = Conexion::conectar();
        
        try {
            // Iniciamos la transacción (Se congela la BD)
            $conexion->beginTransaction();

            // A. Insertar Cabecera de Factura
            $stmt = $conexion->prepare("INSERT INTO compras (proveedor_id, usuario_id, numero_factura, moneda, tasa_bcv, porcentaje_brecha, total_nominal, total_usdt, observaciones, fecha_compra, condicion_pago, dias_credito, estado_pago) VALUES (:proveedor_id, :usuario_id, :numero_factura, :moneda, :tasa_bcv, :porcentaje_brecha, :total_nominal, :total_usdt, :observaciones, :fecha_compra, :condicion_pago, :dias_credito, :estado_pago)");

            $stmt->bindParam(":proveedor_id", $datosCabecera["proveedor_id"], PDO::PARAM_INT);
            $stmt->bindParam(":usuario_id", $datosCabecera["usuario_id"], PDO::PARAM_INT);
            $stmt->bindParam(":numero_factura", $datosCabecera["numero_factura"], PDO::PARAM_STR);
            $stmt->bindParam(":moneda", $datosCabecera["moneda"], PDO::PARAM_STR);
            $stmt->bindParam(":tasa_bcv", $datosCabecera["tasa_bcv"], PDO::PARAM_STR);
            $stmt->bindParam(":porcentaje_brecha", $datosCabecera["porcentaje_brecha"], PDO::PARAM_STR);
            $stmt->bindParam(":total_nominal", $datosCabecera["total_nominal"], PDO::PARAM_STR);
            $stmt->bindParam(":total_usdt", $datosCabecera["total_usdt"], PDO::PARAM_STR);
            $stmt->bindParam(":observaciones", $datosCabecera["observaciones"], PDO::PARAM_STR);
            $stmt->bindParam(":fecha_compra", $datosCabecera["fecha_compra"], PDO::PARAM_STR);
            
            // Nuevas variables de Crédito
            $stmt->bindParam(":condicion_pago", $datosCabecera["condicion_pago"], PDO::PARAM_STR);
            $stmt->bindParam(":dias_credito", $datosCabecera["dias_credito"], PDO::PARAM_INT);
            $stmt->bindParam(":estado_pago", $datosCabecera["estado_pago"], PDO::PARAM_STR);
            $stmt->execute();
            
            // Capturamos el ID de la cabecera recién creada
            $compra_id = $conexion->lastInsertId();

            // A.1. LÓGICA DE DEUDA: Si es a crédito, nace la Cuenta por Pagar
            if($datosCabecera["condicion_pago"] === "Crédito") {
                $stmtCxP = $conexion->prepare("INSERT INTO cuentas_por_pagar (compra_id, proveedor_id, total_deuda_usdt, saldo_restante_usdt, fecha_vencimiento, estado) VALUES (:compra_id, :proveedor_id, :total_deuda, :saldo_restante, DATE_ADD(:fecha_compra, INTERVAL :dias_credito DAY), 'Pendiente')");

                $stmtCxP->bindParam(":compra_id", $compra_id, PDO::PARAM_INT);
                $stmtCxP->bindParam(":proveedor_id", $datosCabecera["proveedor_id"], PDO::PARAM_INT);
                $stmtCxP->bindParam(":total_deuda", $datosCabecera["total_usdt"], PDO::PARAM_STR);
                $stmtCxP->bindParam(":saldo_restante", $datosCabecera["total_usdt"], PDO::PARAM_STR); // El saldo inicial es igual a la deuda total
                $stmtCxP->bindParam(":fecha_compra", $datosCabecera["fecha_compra"], PDO::PARAM_STR);
                $stmtCxP->bindParam(":dias_credito", $datosCabecera["dias_credito"], PDO::PARAM_INT);
                $stmtCxP->execute();
            }

            // B. Recorremos el carrito temporal y procesamos los detalles
            foreach ($datosDetalle as $item) {
                // 1. Guardar el ítem en la factura definitiva (compras_detalle)
                $stmtDetalle = $conexion->prepare("INSERT INTO compras_detalle (compra_id, producto_id, cantidad, costo_nominal, costo_real_usdt) VALUES (:compra_id, :producto_id, :cantidad, :costo_nominal, :costo_real_usdt)");
                $stmtDetalle->bindParam(":compra_id", $compra_id, PDO::PARAM_INT);
                $stmtDetalle->bindParam(":producto_id", $item->producto_id, PDO::PARAM_INT);
                $stmtDetalle->bindParam(":cantidad", $item->cantidad, PDO::PARAM_INT);
                $stmtDetalle->bindParam(":costo_nominal", $item->costo_nominal, PDO::PARAM_STR);
                $stmtDetalle->bindParam(":costo_real_usdt", $item->costo_real_usdt, PDO::PARAM_STR);
                $stmtDetalle->execute();

                // 2. Actualizar el Inventario Maestro (Sumar stock y fijar nuevo costo)
                $stmtProd = $conexion->prepare("UPDATE productos SET stock = stock + :cantidad, costo_usdt = :nuevo_costo, fecha_ultima_compra = NOW() WHERE id = :producto_id");
                $stmtProd->bindParam(":cantidad", $item->cantidad, PDO::PARAM_INT);
                $stmtProd->bindParam(":nuevo_costo", $item->costo_real_usdt, PDO::PARAM_STR);
                $stmtProd->bindParam(":producto_id", $item->producto_id, PDO::PARAM_INT);
                $stmtProd->execute();
            }

            // C. Vaciar el carrito temporal del usuario actual
            $stmtVaciar = $conexion->prepare("DELETE FROM compras_temporales WHERE usuario_id = :usuario_id");
            $stmtVaciar->bindParam(":usuario_id", $datosCabecera["usuario_id"], PDO::PARAM_INT);
            $stmtVaciar->execute();

            // Si llegamos hasta aquí sin que explote nada, confirmamos y guardamos todo de golpe
            $conexion->commit();
            return "ok";

        } catch (Exception $e) {
            // Si ocurre un error en cualquier punto, revertimos todos los cambios
            $conexion->rollBack();
            return "error";
        }
    }

    /* ==============================================================
       3. REPORTES / HISTORIAL
       ============================================================== */
       
    public static function leerHistorialCompras() {
        $stmt = Conexion::conectar()->prepare("
            SELECT c.*, p.razon_social as proveedor_nombre, usuario as usuario_nombre
            FROM compras c
            INNER JOIN proveedores p ON c.proveedor_id = p.id
            INNER JOIN usuarios u ON c.usuario_id = u.id
            ORDER BY c.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    public static function leerDetallePorCompra(int $compra_id) {
        $stmt = Conexion::conectar()->prepare("
            SELECT d.*, p.codigo_barras, p.nombre as producto_nombre
            FROM compras_detalle d
            INNER JOIN productos p ON d.producto_id = p.id
            WHERE d.compra_id = :compra_id
        ");
        $stmt->bindParam(":compra_id", $compra_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}