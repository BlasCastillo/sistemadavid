<?php
require_once "config/conexion.php";

class CuentasPorPagar {
    
    // Propiedades encapsuladas
    private ?int $id = null;
    private ?int $compra_id = null;
    private ?int $proveedor_id = null;
    private ?float $total_deuda_usdt = null;
    private ?float $saldo_restante_usdt = null;
    private ?string $fecha_vencimiento = null;
    private ?string $estado = null;

    public function __construct() {}

    /*=============================================
    GETTERS Y SETTERS
    =============================================*/
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getSaldoRestanteUsdt(): ?float { return $this->saldo_restante_usdt; }
    public function setSaldoRestanteUsdt(?float $saldo): void { $this->saldo_restante_usdt = $saldo; }

    /*=============================================
    1. LEER TODAS LAS CUENTAS (Para la tabla principal)
    =============================================*/
    public static function leerTodasLasCuentas() {
        // Hacemos un JOIN con proveedores y compras para traer toda la info en una sola consulta
        $stmt = Conexion::conectar()->prepare("
            SELECT c.*, p.razon_social as proveedor_nombre, f.numero_factura, f.fecha_compra
            FROM cuentas_por_pagar c
            INNER JOIN proveedores p ON c.proveedor_id = p.id
            INNER JOIN compras f ON c.compra_id = f.id
            ORDER BY c.estado DESC, c.fecha_vencimiento ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /*=============================================
    2. LEER HISTORIAL DE ABONOS DE UNA FACTURA
    =============================================*/
    public static function leerPagosPorCuenta(int $cxp_id) {
        $stmt = Conexion::conectar()->prepare("
            SELECT p.*, u.usuario as cajero
            FROM pagos_cxp p
            INNER JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.cxp_id = :cxp_id
            ORDER BY p.id DESC
        ");
        $stmt->bindParam(":cxp_id", $cxp_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /*=============================================
    3. REGISTRAR ABONO Y LIQUIDAR DEUDA (Transacción)
    =============================================*/
    public static function registrarAbono(int $cxp_id, int $usuario_id, float $abono_usdt, float $abono_bs, float $tasa, string $metodo, string $referencia) {
        $conexion = Conexion::conectar();
        
        try {
            // Iniciamos la transacción (Congelamos BD)
            $conexion->beginTransaction();

            // 1. Registrar el pago en el historial (pagos_cxp)
            $stmtPago = $conexion->prepare("INSERT INTO pagos_cxp (cxp_id, usuario_id, monto_pagado_usdt, monto_pagado_bs, tasa_bcv, metodo_pago, referencia) VALUES (:cxp_id, :usuario_id, :monto_usdt, :monto_bs, :tasa, :metodo, :ref)");
            $stmtPago->bindParam(":cxp_id", $cxp_id, PDO::PARAM_INT);
            $stmtPago->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
            $stmtPago->bindParam(":monto_usdt", $abono_usdt, PDO::PARAM_STR);
            $stmtPago->bindParam(":monto_bs", $abono_bs, PDO::PARAM_STR);
            $stmtPago->bindParam(":tasa", $tasa, PDO::PARAM_STR);
            $stmtPago->bindParam(":metodo", $metodo, PDO::PARAM_STR);
            $stmtPago->bindParam(":ref", $referencia, PDO::PARAM_STR);
            $stmtPago->execute();

            // 2. Descontar el saldo de la cuenta por pagar maestra
            $stmtActualizarCxP = $conexion->prepare("UPDATE cuentas_por_pagar SET saldo_restante_usdt = saldo_restante_usdt - :abono WHERE id = :id");
            $stmtActualizarCxP->bindParam(":abono", $abono_usdt, PDO::PARAM_STR);
            $stmtActualizarCxP->bindParam(":id", $cxp_id, PDO::PARAM_INT);
            $stmtActualizarCxP->execute();

            // 3. Control Financiero: Verificar si la cuenta llegó a cero (0)
            $stmtCheck = $conexion->prepare("SELECT saldo_restante_usdt, compra_id FROM cuentas_por_pagar WHERE id = :id");
            $stmtCheck->bindParam(":id", $cxp_id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $cuenta = $stmtCheck->fetch(PDO::FETCH_OBJ);

            // Usamos 0.001 para evitar errores de precisión con decimales flotantes
            if (floatval($cuenta->saldo_restante_usdt) <= 0.001) {
                
                // Liquidamos la CxP dejándola en estado 'Pagada'
                $stmtLiquidarCxP = $conexion->prepare("UPDATE cuentas_por_pagar SET estado = 'Pagada', saldo_restante_usdt = 0 WHERE id = :id");
                $stmtLiquidarCxP->bindParam(":id", $cxp_id, PDO::PARAM_INT);
                $stmtLiquidarCxP->execute();

                // Viajamos al Módulo de Compras y también la liberamos
                $stmtLiquidarCompra = $conexion->prepare("UPDATE compras SET estado_pago = 'Pagado' WHERE id = :compra_id");
                $stmtLiquidarCompra->bindParam(":compra_id", $cuenta->compra_id, PDO::PARAM_INT);
                $stmtLiquidarCompra->execute();
            }

            // Si nada falla, hacemos Commit y guardamos
            $conexion->commit();
            return "ok";

        } catch (Exception $e) {
            // Si ocurre un error, revertimos la transacción
            $conexion->rollBack();
            return "error";
        }
    }
    /*=============================================
    4. BUSCAR CUENTA POR ID (Para la vista de abono dedicada)
    =============================================*/
    public static function buscarCuentaPorId(int $id) {
        $stmt = Conexion::conectar()->prepare("
            SELECT c.*, p.razon_social as proveedor_nombre, p.documento as proveedor_documento, f.numero_factura, f.fecha_compra, f.moneda as moneda_compra
            FROM cuentas_por_pagar c
            INNER JOIN proveedores p ON c.proveedor_id = p.id
            INNER JOIN compras f ON c.compra_id = f.id
            WHERE c.id = :id
        ");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Retornamos un solo objeto, no un array
        return $stmt->fetch(PDO::FETCH_OBJ); 
    }
}
?>