<?php
require_once "config/conexion.php";

class Cierres {

    /* ==============================================================
       1. OBTENER MONTOS ESPERADOS (EL CEREBRO DEL ARQUEO)
       ============================================================== */
    public static function mdlObtenerMontosEsperados($cajero_id) {
        $conexion = Conexion::conectar();
        
        $totales = [
            "efectivo_usd" => 0.00,
            "efectivo_bs" => 0.00,
            "zelle_usd" => 0.00,
            "pago_movil_bs" => 0.00,
            "punto_venta_bs" => 0.00,
            "transferencia_bs" => 0.00
        ];
        
        $stmtPagos = $conexion->prepare("
            SELECT vp.metodo_pago, vp.moneda, SUM(vp.monto_pagado) as total 
            FROM ventas_pagos vp
            INNER JOIN ventas v ON vp.venta_id = v.id
            WHERE v.usuario_id = :cajero_id 
            AND vp.cierre_id IS NULL 
            GROUP BY vp.metodo_pago, vp.moneda
        ");
        $stmtPagos->bindParam(":cajero_id", $cajero_id, PDO::PARAM_INT);
        $stmtPagos->execute();
        $movimientos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

        foreach ($movimientos as $mov) {
            $metodo = strtoupper(trim($mov["metodo_pago"]));
            $moneda = strtoupper(trim($mov["moneda"]));
            $monto = floatval($mov["total"]);

            if ($metodo == "EFECTIVO" && $moneda == "USD") {
                $totales["efectivo_usd"] += $monto;
            } else if ($metodo == "EFECTIVO" && $moneda == "BS") {
                $totales["efectivo_bs"] += $monto;
            } else if (($metodo == "ZELLE" || $metodo == "BINANCE") && $moneda == "USD") {
                $totales["zelle_usd"] += $monto;
            } else if ($metodo == "PAGO MOVIL" && $moneda == "BS") {
                $totales["pago_movil_bs"] += $monto;
            } else if ($metodo == "PUNTO DE VENTA" && $moneda == "BS") {
                $totales["punto_venta_bs"] += $monto;
            } else if ($metodo == "TRANSFERENCIA" && $moneda == "BS") {
                $totales["transferencia_bs"] += $monto;
            }
        }

        $stmtApertura = $conexion->prepare("SELECT MIN(fecha_venta) as fecha_apertura FROM ventas WHERE usuario_id = :cajero_id AND cierre_id IS NULL");
        $stmtApertura->bindParam(":cajero_id", $cajero_id, PDO::PARAM_INT);
        $stmtApertura->execute();
        $apertura = $stmtApertura->fetch(PDO::FETCH_ASSOC);
        
        $fecha_apertura = ($apertura && $apertura["fecha_apertura"]) ? $apertura["fecha_apertura"] : date('Y-m-d H:i:s');

        return [
            "status" => "success",
            "fecha_apertura" => $fecha_apertura,
            "esperados" => $totales
        ];
    }

    /* ==============================================================
       2. GUARDAR EL CIERRE Y CASAR LAS FACTURAS EN BD
       ============================================================== */
    public static function mdlGuardarCierre($datos) {
        $conexion = Conexion::conectar();
        
        try {
            $conexion->beginTransaction();

            // 1. Calcular Diferencias Matemáticas
            $esperados = json_decode($datos["esperados"], true);
            $declarados = json_decode($datos["declarados"], true);

            $difUsd = floatval($declarados["efectivo_usd"]) - floatval($esperados["efectivo_usd"]);
            $difBs = floatval($declarados["efectivo_bs"]) - floatval($esperados["efectivo_bs"]);
            $difPunto = floatval($declarados["punto_venta_bs"]) - floatval($esperados["punto_venta_bs"]);

            $diferencias = [
                "efectivo_usd" => round($difUsd, 2),
                "efectivo_bs" => round($difBs, 2),
                "punto_venta_bs" => round($difPunto, 2)
            ];

            // 2. Determinar Estado
            $estado = "Cuadrado";
            if (abs($difUsd) > 0.05 || abs($difBs) > 0.05 || abs($difPunto) > 0.05) {
                $estado = "Con Descuadre";
            }

            // 3. Obtener la fecha de apertura real (primera factura no cerrada)
            $stmtApertura = $conexion->prepare("SELECT MIN(fecha_venta) as fecha_apertura FROM ventas WHERE usuario_id = :cajero_id AND cierre_id IS NULL");
            $stmtApertura->bindParam(":cajero_id", $datos["cajero_id"], PDO::PARAM_INT);
            $stmtApertura->execute();
            $apertura = $stmtApertura->fetch(PDO::FETCH_ASSOC);
            $fecha_apertura = ($apertura && $apertura["fecha_apertura"]) ? $apertura["fecha_apertura"] : date('Y-m-d H:i:s');
            $fecha_cierre = date('Y-m-d H:i:s');

            // 4. Insertar el Registro del Cierre
            $stmtCierre = $conexion->prepare("
                INSERT INTO cierres_caja 
                (cajero_id, tipo_cierre, fecha_apertura, fecha_cierre, montos_esperados, montos_declarados, diferencias, estado, autorizador_id, observaciones) 
                VALUES 
                (:cajero_id, :tipo_cierre, :fecha_apertura, :fecha_cierre, :montos_esperados, :montos_declarados, :diferencias, :estado, :autorizador_id, :observaciones)
            ");

            $diferenciasJson = json_encode($diferencias);

            $stmtCierre->bindParam(":cajero_id", $datos["cajero_id"], PDO::PARAM_INT);
            $stmtCierre->bindParam(":tipo_cierre", $datos["tipo_cierre"], PDO::PARAM_STR);
            $stmtCierre->bindParam(":fecha_apertura", $fecha_apertura, PDO::PARAM_STR);
            $stmtCierre->bindParam(":fecha_cierre", $fecha_cierre, PDO::PARAM_STR);
            $stmtCierre->bindParam(":montos_esperados", $datos["esperados"], PDO::PARAM_STR);
            $stmtCierre->bindParam(":montos_declarados", $datos["declarados"], PDO::PARAM_STR);
            $stmtCierre->bindParam(":diferencias", $diferenciasJson, PDO::PARAM_STR);
            $stmtCierre->bindParam(":estado", $estado, PDO::PARAM_STR);
            $stmtCierre->bindParam(":autorizador_id", $datos["autorizador_id"], PDO::PARAM_INT);
            $stmtCierre->bindParam(":observaciones", $datos["observaciones"], PDO::PARAM_STR);
            $stmtCierre->execute();

            // 5. Capturar el ID del Cierre recién creado
            $id_cierre_nuevo = $conexion->lastInsertId();

            // 6. CASAR FACTURAS: Actualizar ventas que no tenían cierre
            $stmtUpdateVentas = $conexion->prepare("UPDATE ventas SET cierre_id = :cierre_id WHERE usuario_id = :cajero_id AND cierre_id IS NULL");
            $stmtUpdateVentas->bindParam(":cierre_id", $id_cierre_nuevo, PDO::PARAM_INT);
            $stmtUpdateVentas->bindParam(":cajero_id", $datos["cajero_id"], PDO::PARAM_INT);
            $stmtUpdateVentas->execute();

            // 7. CASAR ABONOS: Actualizar pagos de créditos que no tenían cierre
            $stmtUpdatePagos = $conexion->prepare("
                UPDATE ventas_pagos vp 
                INNER JOIN ventas v ON vp.venta_id = v.id 
                SET vp.cierre_id = :cierre_id 
                WHERE v.usuario_id = :cajero_id AND vp.cierre_id IS NULL
            ");
            $stmtUpdatePagos->bindParam(":cierre_id", $id_cierre_nuevo, PDO::PARAM_INT);
            $stmtUpdatePagos->bindParam(":cajero_id", $datos["cajero_id"], PDO::PARAM_INT);
            $stmtUpdatePagos->execute();

            $conexion->commit();

            return [
                "status" => "success", 
                "mensaje" => "Reporte procesado. Facturas vinculadas exitosamente.",
                "id_cierre" => $id_cierre_nuevo // ¡NUEVO! Enviamos el ID al JavaScript
            ];

        } catch (Exception $e) {
            $conexion->rollBack();
            return ["status" => "error", "mensaje" => "Fallo en Base de Datos: " . $e->getMessage()];


        }
        
    }
    /* ==============================================================
       3. MOSTRAR CIERRES DE CAJA (PANEL DE AUDITORÍA)
       ============================================================== */
    public static function mdlMostrarCierres($item, $valor) {
        $conexion = Conexion::conectar();
        
        if ($item != null) {
            $stmt = $conexion->prepare("
                SELECT c.*, u.usuario as cajero_nombre, auth.usuario as autorizador_nombre 
                FROM cierres_caja c 
                INNER JOIN usuarios u ON c.cajero_id = u.id 
                LEFT JOIN usuarios auth ON c.autorizador_id = auth.id 
                WHERE c.$item = :$item 
                ORDER BY c.id DESC
            ");
            $stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $conexion->prepare("
                SELECT c.*, u.usuario as cajero_nombre, auth.usuario as autorizador_nombre 
                FROM cierres_caja c 
                INNER JOIN usuarios u ON c.cajero_id = u.id 
                LEFT JOIN usuarios auth ON c.autorizador_id = auth.id 
                ORDER BY c.id DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    /* ==============================================================
       6. AUDITORÍA: REGISTRAR MOVIMIENTO INDIVIDUAL
       ============================================================== */
    public static function mdlRegistrarMovimientoAuditoria($datos) {
        $conexion = Conexion::conectar();
        try {
            if ($datos["tipo"] == "gasto") {
                // Si la tienda asume la pérdida
                $stmt = $conexion->prepare("INSERT INTO gastos (concepto, monto, tipo, fecha, estado) VALUES (:concepto, :monto, 'Operativo', CURDATE(), 1)");
            } else {
                // Si el cajero paga el faltante o se registra un sobrante
                $stmt = $conexion->prepare("INSERT INTO ingresos (concepto, monto, moneda, metodo_pago, referencia, fecha, estado) VALUES (:concepto, :monto, :moneda, :metodo_pago, :referencia, CURDATE(), 1)");
                $stmt->bindParam(":moneda", $datos["moneda"], PDO::PARAM_STR);
                $stmt->bindParam(":metodo_pago", $datos["metodo_pago"], PDO::PARAM_STR);
                $stmt->bindParam(":referencia", $datos["referencia"], PDO::PARAM_STR);
            }
            $stmt->bindParam(":concepto", $datos["concepto"], PDO::PARAM_STR);
            $stmt->bindParam(":monto", $datos["monto"], PDO::PARAM_STR);
            
            if($stmt->execute()){ return "ok"; } else { return "error"; }
        } catch (Exception $e) {
            return "error";
        }
    }

    /* ==============================================================
       7. AUDITORÍA: FINALIZAR Y MARCAR COMO AJUSTADO
       ============================================================== */
    public static function mdlFinalizarAuditoriaCaja($id_cierre) {
        $stmt = Conexion::conectar()->prepare("UPDATE cierres_caja SET estado = 'Ajustado' WHERE id = :id_cierre");
        $stmt->bindParam(":id_cierre", $id_cierre, PDO::PARAM_INT);
        if($stmt->execute()){ return "ok"; } else { return "error"; }
    }
}
?>