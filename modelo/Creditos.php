<?php
require_once "config/conexion.php";

class Creditos {

    /* ==============================================================
       1. MOSTRAR TODAS LAS CUENTAS POR COBRAR (CRÉDITOS ACTIVOS)
       ============================================================== */
    public static function mdlMostrarCreditos() {
        $stmt = Conexion::conectar()->prepare("
            SELECT v.id, v.numero_factura, v.fecha_venta, v.total_usdt, v.tasa_bcv, 
                   c.nombre as cliente_nombre, c.documento as cliente_doc, c.telefono as cliente_telefono,
                   IFNULL((SELECT SUM(CASE WHEN moneda = 'BS' THEN (monto_pagado / v.tasa_bcv) ELSE monto_pagado END) FROM ventas_pagos WHERE venta_id = v.id), 0) as total_abonado
            FROM ventas v
            INNER JOIN clientes c ON v.cliente_id = c.id
            WHERE v.estado = 'Credito'
            ORDER BY v.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /* ==============================================================
       2. REGISTRAR UN NUEVO ABONO EN CAJA Y CERRAR DEUDA
       ============================================================== */
    public static function mdlRegistrarAbono($datos) {
        $conexion = Conexion::conectar();
        
        try {
            $conexion->beginTransaction();

            $stmtPago = $conexion->prepare("INSERT INTO ventas_pagos (venta_id, metodo_pago, moneda, monto_pagado, referencia) VALUES (:venta_id, :metodo_pago, :moneda, :monto_pagado, :referencia)");
            $stmtPago->bindParam(":venta_id", $datos["venta_id"], PDO::PARAM_INT);
            $stmtPago->bindParam(":metodo_pago", $datos["metodo_pago"], PDO::PARAM_STR);
            $stmtPago->bindParam(":moneda", $datos["moneda"], PDO::PARAM_STR);
            $stmtPago->bindParam(":monto_pagado", $datos["monto_pagado"], PDO::PARAM_STR);
            $stmtPago->bindParam(":referencia", $datos["referencia"], PDO::PARAM_STR);
            $stmtPago->execute();
            
            // NUEVO: Capturamos el ID del abono recién insertado para poder imprimir el ticket
            $id_pago_insertado = $conexion->lastInsertId();

            $stmtValidar = $conexion->prepare("
                SELECT v.total_usdt, v.tasa_bcv,
                IFNULL((SELECT SUM(CASE WHEN moneda = 'BS' THEN (monto_pagado / v.tasa_bcv) ELSE monto_pagado END) FROM ventas_pagos WHERE venta_id = v.id), 0) as total_abonado
                FROM ventas v WHERE v.id = :venta_id
            ");
            $stmtValidar->bindParam(":venta_id", $datos["venta_id"], PDO::PARAM_INT);
            $stmtValidar->execute();
            $estadoDeuda = $stmtValidar->fetch(PDO::FETCH_OBJ);

            $restante = floatval($estadoDeuda->total_usdt) - floatval($estadoDeuda->total_abonado);
            
            if ($restante <= 0.05) {
                $stmtUpdate = $conexion->prepare("UPDATE ventas SET estado = 'Pagada' WHERE id = :venta_id");
                $stmtUpdate->bindParam(":venta_id", $datos["venta_id"], PDO::PARAM_INT);
                $stmtUpdate->execute();
                $estadoFinal = "Saldada";
            } else {
                $estadoFinal = "Abonada";
            }

            $conexion->commit();
            // NUEVO: Ahora retornamos un arreglo con el estado y el ID
            return ["estado" => $estadoFinal, "id_pago" => $id_pago_insertado];

        } catch (Exception $e) {
            $conexion->rollBack();
            return ["estado" => "error"];
        }
    }

    /* ==============================================================
       3. NUEVO: MOSTRAR HISTORIAL DE ABONOS DE UNA FACTURA
       ============================================================== */
    public static function mdlMostrarPagosVenta($id_venta) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM ventas_pagos WHERE venta_id = :id ORDER BY id DESC");
        $stmt->bindParam(":id", $id_venta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /* ==============================================================
       4. NUEVO: TRAER DATOS DE UN ABONO ESPECÍFICO PARA EL TICKET PDF
       ============================================================== */
    public static function mdlObtenerDetallePago($id_pago) {
        $stmt = Conexion::conectar()->prepare("
            SELECT p.*, v.numero_factura, v.tasa_bcv, c.nombre as cliente_nombre, c.documento as cliente_doc, u.usuario as cajero_nombre 
            FROM ventas_pagos p 
            INNER JOIN ventas v ON p.venta_id = v.id 
            INNER JOIN clientes c ON v.cliente_id = c.id 
            INNER JOIN usuarios u ON v.usuario_id = u.id 
            WHERE p.id = :id_pago
        ");
        $stmt->bindParam(":id_pago", $id_pago, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}
?>