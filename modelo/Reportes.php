<?php
require_once "config/conexion.php";

class Reportes {

    public static function mdlResumenVentas($fechaInicial, $fechaFinal) {
        try {
            $inicio = $fechaInicial . " 00:00:00";
            $fin = $fechaFinal . " 23:59:59";

            $sql = "SELECT 
                        DATE(fecha_venta) as fecha,
                        COUNT(id) as cantidad_facturas,
                        IFNULL(SUM(total_bs), 0) as total_bs,
                        IFNULL(SUM(total_usdt), 0) as total_usdt
                    FROM ventas 
                    WHERE fecha_venta BETWEEN :fechaInicial AND :fechaFinal 
                    AND estado IN ('Pagada', 'Credito')
                    GROUP BY DATE(fecha_venta)
                    ORDER BY fecha_venta ASC";
            
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindParam(":fechaInicial", $inicio, PDO::PARAM_STR);
            $stmt->bindParam(":fechaFinal", $fin, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en mdlResumenVentas: " . $e->getMessage());
            return []; 
        }
    }

    public static function mdlStockMuerto($dias) {
        try {
            // Renombramos la salida de fecha_ultima_venta a 'ultima_venta_real' 
            // para mantener compatibilidad con la vista
            $sql = "SELECT codigo_barras, nombre, stock, costo_usdt, fecha_ultima_venta as ultima_venta_real,
                           (stock * costo_usdt) as capital_inmovilizado
                    FROM productos 
                    WHERE estado = 1 
                    AND stock > 0 
                    AND (fecha_ultima_venta IS NULL OR fecha_ultima_venta <= DATE_SUB(CURDATE(), INTERVAL :dias DAY))
                    ORDER BY capital_inmovilizado DESC";

            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindParam(":dias", $dias, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }

    public static function mdlAntiguedadInventario($dias) {
        try {
            $sql = "SELECT codigo_barras, nombre, stock, costo_usdt, fecha_ultima_compra,
                           (stock * costo_usdt) as capital_inmovilizado
                    FROM productos 
                    WHERE estado = 1 
                    AND stock > 0 
                    AND fecha_ultima_compra IS NOT NULL 
                    AND fecha_ultima_compra <= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
                    ORDER BY fecha_ultima_compra ASC";

            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindParam(":dias", $dias, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }

    /* ==============================================================
       4. UTILIDADES PDF (Creación de Directorios de Auditoría)
       ============================================================== */
    public static function crearRutaReportePDF(): string {
        $fechaMes = date("Y-m"); // Organizamos por Año-Mes
        $rutaBase = "Reportes_Auditoria/" . $fechaMes . "/";
        
        if (!file_exists($rutaBase)) { 
            mkdir($rutaBase, 0777, true); 
        }
        return $rutaBase;
    }
    /* ==============================================================
       5. FINANZAS: EGRESOS Y GASTOS (Fijos y Variables)
       ============================================================== */
    public static function mdlResumenGastos($fechaInicial, $fechaFinal) {
        try {
            $inicio = $fechaInicial . " 00:00:00";
            $fin = $fechaFinal . " 23:59:59";
            
            $sql = "SELECT 
                        IFNULL(SUM(monto), 0) as total_gastos,
                        IFNULL(SUM(CASE WHEN tipo = 'Fijo' THEN monto ELSE 0 END), 0) as gastos_fijos,
                        IFNULL(SUM(CASE WHEN tipo = 'Variable' THEN monto ELSE 0 END), 0) as gastos_variables
                    FROM gastos 
                    WHERE fecha BETWEEN :fechaInicial AND :fechaFinal 
                    AND estado = 1";
            
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindParam(":fechaInicial", $inicio, PDO::PARAM_STR);
            $stmt->bindParam(":fechaFinal", $fin, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { 
            return ["total_gastos" => 0, "gastos_fijos" => 0, "gastos_variables" => 0]; 
        }
    }

    /* ==============================================================
       6. FINANZAS: COSTO DE MERCANCÍA VENDIDA (COGS)
       ============================================================== */
    public static function mdlCostoMercanciaVendida($fechaInicial, $fechaFinal) {
        try {
            $inicio = $fechaInicial . " 00:00:00";
            $fin = $fechaFinal . " 23:59:59";
            
            $sql = "SELECT IFNULL(SUM(vd.cantidad * p.costo_usdt), 0) as costo_total
                    FROM ventas_detalle vd
                    INNER JOIN ventas v ON vd.venta_id = v.id
                    INNER JOIN productos p ON vd.producto_id = p.id
                    WHERE v.fecha_venta BETWEEN :fechaInicial AND :fechaFinal 
                    AND v.estado IN ('Pagada', 'Credito')";
            
            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindParam(":fechaInicial", $inicio, PDO::PARAM_STR);
            $stmt->bindParam(":fechaFinal", $fin, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { 
            return ["costo_total" => 0]; 
        }
    }
}
?>