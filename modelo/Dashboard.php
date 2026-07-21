<?php
require_once "config/conexion.php";

class Dashboard {

    /* ==============================================================
       1. MÉTRICAS DIARIAS (Ingresos, Clientes, Cajeros, Ticket)
       ============================================================== */
    public static function mdlResumenDiario() {
        // Obtenemos los ingresos totales, el conteo de facturas y los cajeros únicos de HOY
        $stmt = Conexion::conectar()->prepare("
            SELECT 
                IFNULL(SUM(total_bs), 0) as ingresos_bs, 
                IFNULL(SUM(total_usdt), 0) as ingresos_usdt, 
                COUNT(id) as facturas_hoy,
                COUNT(DISTINCT usuario_id) as cajeros_activos
            FROM ventas 
            WHERE DATE(fecha_venta) = CURDATE() 
            AND estado IN ('Pagada', 'Credito')
        ");
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculamos el ticket promedio en PHP (Ambas monedas)
        $ticket_promedio_bs = ($resultado['facturas_hoy'] > 0) ? ($resultado['ingresos_bs'] / $resultado['facturas_hoy']) : 0;
        $ticket_promedio_usdt = ($resultado['facturas_hoy'] > 0) ? ($resultado['ingresos_usdt'] / $resultado['facturas_hoy']) : 0;
        
        $resultado['ticket_promedio_bs'] = $ticket_promedio_bs;
        $resultado['ticket_promedio_usdt'] = $ticket_promedio_usdt;
        
        return $resultado;
    }

    /* ==============================================================
       2. MÉTRICAS GLOBALES (Cuentas por Cobrar, Inventario, Alertas)
       ============================================================== */
    public static function mdlResumenGlobal() {
        $conexion = Conexion::conectar();
        
        // A. Cuentas por cobrar (Solo facturas a Crédito)
        $stmtCxC = $conexion->prepare("SELECT IFNULL(SUM(total_bs), 0) as deuda_bs, IFNULL(SUM(total_usdt), 0) as deuda_usdt FROM ventas WHERE estado = 'Credito'");
        $stmtCxC->execute();
        $cxc = $stmtCxC->fetch(PDO::FETCH_ASSOC);

        // B. Valorización del Inventario y Alertas de Stock (Crítico <= 5)
        $stmtInv = $conexion->prepare("
            SELECT 
                IFNULL(SUM(stock * costo_usdt), 0) as capital_inventario_usdt,
                SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) as productos_criticos
            FROM productos 
            WHERE estado = 1
        ");
        $stmtInv->execute();
        $inv = $stmtInv->fetch(PDO::FETCH_ASSOC);

        // C. Cantidad de Compras del mes actual
        $stmtCom = $conexion->prepare("
            SELECT COUNT(id) as total_compras_mes 
            FROM compras 
            WHERE MONTH(fecha_compra) = MONTH(CURDATE()) 
            AND YEAR(fecha_compra) = YEAR(CURDATE()) 
            AND estado = 1
        ");
        $stmtCom->execute();
        $compras = $stmtCom->fetch(PDO::FETCH_ASSOC);

        return [
            "cxc_bs" => $cxc["deuda_bs"],
            "cxc_usdt" => $cxc["deuda_usdt"],
            "inventario_usdt" => $inv["capital_inventario_usdt"],
            "alerta_stock" => $inv["productos_criticos"],
            "compras_mes" => $compras["total_compras_mes"]
        ];
    }

    /* ==============================================================
       3. ANALÍTICA DE PRODUCTOS (Top 5 Vendidos y Más Comprado)
       ============================================================== */
    public static function mdlTopProductos() {
        $conexion = Conexion::conectar();

        // Top 5 Más Vendidos (Del mes actual)
        $stmtTop = $conexion->prepare("
            SELECT p.nombre, p.codigo_barras, SUM(vd.cantidad) as total_vendido
            FROM ventas_detalle vd
            INNER JOIN ventas v ON vd.venta_id = v.id
            INNER JOIN productos p ON vd.producto_id = p.id
            WHERE MONTH(v.fecha_venta) = MONTH(CURDATE()) AND YEAR(v.fecha_venta) = YEAR(CURDATE())
            GROUP BY vd.producto_id
            ORDER BY total_vendido DESC
            LIMIT 5
        ");
        $stmtTop->execute();
        $top5 = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

        // Producto más comprado a proveedores (Del mes actual)
        $stmtComprado = $conexion->prepare("
            SELECT p.nombre, SUM(cd.cantidad) as total_comprado
            FROM compras_detalle cd
            INNER JOIN compras c ON cd.compra_id = c.id
            INNER JOIN productos p ON cd.producto_id = p.id
            WHERE MONTH(c.fecha_compra) = MONTH(CURDATE()) AND YEAR(c.fecha_compra) = YEAR(CURDATE())
            GROUP BY cd.producto_id
            ORDER BY total_comprado DESC
            LIMIT 1
        ");
        $stmtComprado->execute();
        $masComprado = $stmtComprado->fetch(PDO::FETCH_ASSOC);

        return [
            "top5_vendidos" => $top5,
            "mas_comprado" => $masComprado ? $masComprado : ["nombre" => "Sin compras recientes", "total_comprado" => 0]
        ];
    }

    /* ==============================================================
       4. GRÁFICO DE BARRAS (Ventas de los últimos 7 días)
       ============================================================== */
    public static function mdlGraficoVentas() {
        $stmt = Conexion::conectar()->prepare("
            SELECT DATE(fecha_venta) as fecha, IFNULL(SUM(total_bs), 0) as total_bs
            FROM ventas
            WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            AND estado IN ('Pagada', 'Credito')
            GROUP BY DATE(fecha_venta)
            ORDER BY DATE(fecha_venta) ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}