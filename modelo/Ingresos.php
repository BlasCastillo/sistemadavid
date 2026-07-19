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

    // Aquí luego agregaremos las funciones para anular o crear ingresos manuales
}