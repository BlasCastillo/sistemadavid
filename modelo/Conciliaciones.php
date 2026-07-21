<?php
require_once "config/conexion.php";

class Conciliaciones {

    /* ==============================================================
       1. MOSTRAR PAGOS ELECTRÓNICOS (Con filtro inteligente)
       ============================================================== */
    public static function mdlMostrarPagos($estado) {
        // Excluimos métodos físicos y virtuales internos
        $sql = "SELECT vp.id as pago_id, vp.metodo_pago, vp.moneda, vp.monto_pagado, vp.referencia, vp.fecha_pago,
                       v.numero_factura,
                       cb.id as conciliacion_id, cb.fecha_conciliacion,
                       u.nombre_completo as auditor
                FROM ventas_pagos vp
                INNER JOIN ventas v ON vp.venta_id = v.id
                LEFT JOIN conciliaciones_bancarias cb ON vp.id = cb.pago_id
                LEFT JOIN usuarios u ON cb.usuario_id = u.id
                WHERE vp.metodo_pago NOT IN ('Efectivo', 'Efectivo Bs', 'Efectivo USD', 'Saldo a Favor')
                AND v.estado != 'Anulada'";

        // Filtro dinámico según la pestaña que vea el gerente
        if ($estado == "Pendiente") {
            $sql .= " AND cb.id IS NULL";
        } else if ($estado == "Conciliado") {
            $sql .= " AND cb.id IS NOT NULL";
        }
        
        $sql .= " ORDER BY vp.fecha_pago DESC";
        
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /* ==============================================================
       2. TOTALES CONSOLIDADOS (El dinero que SÍ está en el banco)
       ============================================================== */
    public static function mdlTotalesConsolidados() {
        $stmt = Conexion::conectar()->prepare("
            SELECT 
                vp.moneda,
                SUM(vp.monto_pagado) as total
            FROM ventas_pagos vp
            INNER JOIN conciliaciones_bancarias cb ON vp.id = cb.pago_id
            INNER JOIN ventas v ON vp.venta_id = v.id
            WHERE vp.metodo_pago NOT IN ('Efectivo', 'Efectivo Bs', 'Efectivo USD', 'Saldo a Favor')
            AND v.estado != 'Anulada'
            GROUP BY vp.moneda
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /* ==============================================================
       3. REGISTRAR CONCILIACIÓN (Anti-Duplicidad)
       ============================================================== */
    public static function mdlRegistrarConciliacion($datos) {
        try {
            $stmt = Conexion::conectar()->prepare("INSERT INTO conciliaciones_bancarias (pago_id, usuario_id, estado_conciliacion, fecha_conciliacion) VALUES (:pago_id, :usuario_id, 'Conciliado', NOW())");
            $stmt->bindParam(":pago_id", $datos["pago_id"], PDO::PARAM_INT);
            $stmt->bindParam(":usuario_id", $datos["usuario_id"], PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Si intenta conciliar algo ya conciliado, el UNIQUE KEY saltará aquí y evitamos el error fatal.
            return false; 
        }
    }
}
?>