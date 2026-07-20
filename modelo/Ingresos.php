<?php
require_once "config/conexion.php";

class Ingresos {

    /* ==============================================================
       1. MOSTRAR INGRESOS EXTRAORDINARIOS
       ============================================================== */
    public static function mdlMostrarIngresos($item, $valor) {
        if ($item != null) {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM ingresos WHERE $item = :$item ORDER BY id DESC");
            $stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM ingresos ORDER BY id DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /* ==============================================================
       2. REGISTRAR NUEVO INGRESO MANUAL
       ============================================================== */
    public static function mdlCrearIngreso($datos) {
        $stmt = Conexion::conectar()->prepare("INSERT INTO ingresos (concepto, monto, moneda, metodo_pago, referencia, fecha, estado) VALUES (:concepto, :monto, :moneda, :metodo_pago, :referencia, CURDATE(), 1)");

        $stmt->bindParam(":concepto", $datos["concepto"], PDO::PARAM_STR);
        $stmt->bindParam(":monto", $datos["monto"], PDO::PARAM_STR);
        $stmt->bindParam(":moneda", $datos["moneda"], PDO::PARAM_STR);
        $stmt->bindParam(":metodo_pago", $datos["metodo_pago"], PDO::PARAM_STR);
        $stmt->bindParam(":referencia", $datos["referencia"], PDO::PARAM_STR);

        if($stmt->execute()){
            return "ok";
        } else {
            return "error";
        }
    }

    /* ==============================================================
       3. ANULAR INGRESO (BORRADO LÓGICO)
       ============================================================== */
    public static function mdlAnularIngreso($id) {
        $stmt = Conexion::conectar()->prepare("UPDATE ingresos SET estado = 0 WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        
        if($stmt->execute()){
            return "ok";
        } else {
            return "error";
        }
    }
}