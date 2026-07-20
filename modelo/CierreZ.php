<?php
require_once "config/conexion.php";

class CierreZ {

    /* ==============================================================
       1. RADAR: DETECTAR CAJEROS CON VENTAS HUÉRFANAS (SIN REPORTE X)
       ============================================================== */
    public static function mdlDetectarSesionesAbiertas() {
        $conexion = Conexion::conectar();
        
        try {
            // Buscamos ventas donde cierre_id es NULL (facturas flotantes del día)
            $stmt = $conexion->prepare("
                SELECT 
                    v.usuario_id, 
                    u.usuario as nombre_cajero, 
                    COUNT(v.id) as cantidad_facturas, 
                    SUM(v.total_usdt) as total_ventas_usd,
                    SUM(v.total_bs) as total_ventas_bs
                FROM ventas v
                INNER JOIN usuarios u ON v.usuario_id = u.id
                WHERE v.cierre_id IS NULL OR v.cierre_id = 0
                GROUP BY v.usuario_id
            ");
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return "error";
        }
    }

    /* ==============================================================
       2. MATEMÁTICA Y EJECUCIÓN DEL CIERRE Z (BLINDADO)
       ============================================================== */
    public static function mdlEjecutarCierreZ($usuario_id) {
        $conexion = Conexion::conectar();
        
        try {
            $conexion->beginTransaction();

            // 1. BUSCAR REPORTES 'X'
            $stmtX = $conexion->prepare("SELECT id, montos_declarados, montos_esperados FROM cierres_caja WHERE cierre_z_id IS NULL AND estado != 'Abierto'");
            $stmtX->execute();
            $cierresPendientes = $stmtX->fetchAll(PDO::FETCH_ASSOC);

            if(count($cierresPendientes) == 0) {
                return "sin_cajas";
            }

            $total_efectivo_usd = 0; $total_efectivo_bs = 0; $total_punto_bs = 0;
            $total_zelle = 0; $total_pm = 0;

            foreach($cierresPendientes as $cierre) {
                // Si el JSON viene vacío, asignamos un array vacío de emergencia
                $declarados = json_decode($cierre["montos_declarados"], true) ?: [];
                $esperados = json_decode($cierre["montos_esperados"], true) ?: [];

                $total_efectivo_usd += floatval($declarados["efectivo_usd"] ?? 0);
                $total_efectivo_bs += floatval($declarados["efectivo_bs"] ?? 0);
                $total_punto_bs += floatval($declarados["punto_venta_bs"] ?? 0);
                $total_zelle += floatval($esperados["zelle_usd"] ?? 0);
                $total_pm += floatval($esperados["pago_movil_bs"] ?? 0);
            }

            $pagos_electronicos = json_encode([
                "zelle_usd" => $total_zelle,
                "pago_movil_bs" => $total_pm
            ]);

            // 2. SUMAR GASTOS E INGRESOS (Protegido contra nulos)
            $stmtG = $conexion->prepare("SELECT SUM(monto) as total FROM gastos WHERE fecha = CURDATE() AND estado = 1");
            $stmtG->execute();
            $rowG = $stmtG->fetch(PDO::FETCH_ASSOC);
            $total_gastos = ($rowG && $rowG["total"] != null) ? floatval($rowG["total"]) : 0.00;

            $stmtI = $conexion->prepare("SELECT SUM(monto) as total FROM ingresos WHERE fecha = CURDATE() AND estado = 1");
            $stmtI->execute();
            $rowI = $stmtI->fetch(PDO::FETCH_ASSOC);
            $total_ingresos = ($rowI && $rowI["total"] != null) ? floatval($rowI["total"]) : 0.00;

            // 3. INSERTAR EL REPORTE Z
            $stmtZ = $conexion->prepare("INSERT INTO cierres_z (usuario_id, fecha_cierre, gastos_totales, ingresos_extras, efectivo_caja_usd, efectivo_caja_bs, punto_venta_bs, pagos_electronicos) 
                                         VALUES (:uid, NOW(), :gastos, :ingresos, :usd, :bs, :punto, :pagos)");
            
            $stmtZ->bindParam(":uid", $usuario_id, PDO::PARAM_INT);
            $stmtZ->bindParam(":gastos", $total_gastos, PDO::PARAM_STR);
            $stmtZ->bindParam(":ingresos", $total_ingresos, PDO::PARAM_STR);
            $stmtZ->bindParam(":usd", $total_efectivo_usd, PDO::PARAM_STR);
            $stmtZ->bindParam(":bs", $total_efectivo_bs, PDO::PARAM_STR);
            $stmtZ->bindParam(":punto", $total_punto_bs, PDO::PARAM_STR);
            $stmtZ->bindParam(":pagos", $pagos_electronicos, PDO::PARAM_STR);
            $stmtZ->execute();
            
            $idCierreZ = $conexion->lastInsertId();

            // 4. SELLAR LAS CAJAS
            $stmtUpdate = $conexion->prepare("UPDATE cierres_caja SET cierre_z_id = :z_id WHERE cierre_z_id IS NULL AND estado != 'Abierto'");
            $stmtUpdate->bindParam(":z_id", $idCierreZ, PDO::PARAM_INT);
            $stmtUpdate->execute();

            $conexion->commit();
            return $idCierreZ;

        } catch (Throwable $e) { // Usamos Throwable para atrapar fallos letales de PHP 8+
            if($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return "error";
        }
    }
    /* ==============================================================
       3. FORZAR CIERRE X DE UN CAJERO (CORTE REMOTO)
       ============================================================== */
    public static function mdlForzarCierreX($idCajero) {
        try {
            // 1. Obtenemos lo que el sistema esperaba de este cajero usando la clase original
            $dataEsperados = Cierres::mdlObtenerMontosEsperados($idCajero);
            
            if($dataEsperados["status"] == "success") {
                $esperados = $dataEsperados["esperados"];
                
                // 2. Simulamos lo declarado: $0.00 en caja física, pero respetamos lo electrónico
                $declarados = [
                    "efectivo_usd" => 0.00,
                    "efectivo_bs" => 0.00,
                    "punto_venta_bs" => 0.00,
                    "zelle_usd" => floatval($esperados["zelle_usd"]),
                    "pago_movil_bs" => floatval($esperados["pago_movil_bs"]),
                    "transferencia_bs" => floatval($esperados["transferencia_bs"])
                ];

                /// 3. Empaquetamos los datos simulando el envío del formulario
                $datosCierre = [
                    "cajero_id" => $idCajero,
                    "tipo_cierre" => "Definitivo",
                    "esperados" => json_encode($esperados),
                    "declarados" => json_encode($declarados),
                    "autorizador_id" => $_SESSION["id_usuario"], // <-- Variable corregida
                    "observaciones" => "CIERRE FORZADO. Ejecutado remotamente por gerencia durante preparación de Reporte Z."
                ];

                // 4. Ejecutamos el guardado real en la base de datos
                $respuestaGuardar = Cierres::mdlGuardarCierre($datosCierre);

                if($respuestaGuardar["status"] == "success") {
                    return "ok";
                }
            }
            return "error";
            
        } catch (Exception $e) {
            return "error";
        }
    }

    /* ==============================================================
       4. MOSTRAR CIERRES Z (PARA TICKET E HISTORIAL)
       ============================================================== */
    public static function mdlMostrarCierresZ($item, $valor) {
        $conexion = Conexion::conectar();
        
        if($item != null) {
            $stmt = $conexion->prepare("SELECT cz.*, u.usuario as nombre_gerente FROM cierres_z cz INNER JOIN usuarios u ON cz.usuario_id = u.id WHERE cz.$item = :$item");
            $stmt->bindParam(":".$item, $valor, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $conexion->prepare("SELECT cz.*, u.usuario as nombre_gerente FROM cierres_z cz INNER JOIN usuarios u ON cz.usuario_id = u.id ORDER BY cz.id DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

}