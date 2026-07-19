<?php
class CierreZControlador {

    /* ==============================================================
       1. RADAR: MOSTRAR SESIONES ABIERTAS EN EL PANEL Z
       ============================================================== */
    public static function ctrDetectarSesionesAbiertas() {
        $respuesta = CierreZ::mdlDetectarSesionesAbiertas();
        return $respuesta;
    }
    /* ==============================================================
       2. EJECUTAR EL CIERRE Z MAESTRO (CON AUTORIZACIÓN GERENCIAL)
       ============================================================== */
    public static function ctrEjecutarCierreZ() {
        
        $userGerente = isset($_POST["userGerente"]) ? $_POST["userGerente"] : null;
        $pinGerente = isset($_POST["pinGerente"]) ? $_POST["pinGerente"] : null;

        // 1. Validar Credenciales usando password_verify para Bcrypt
        if ($userGerente != null && $pinGerente != null) {
            
            $stmtAuth = Conexion::conectar()->prepare("
                SELECT id, rol_id, pin_autorizacion 
                FROM usuarios 
                WHERE usuario = :usuario 
                LIMIT 1
            ");
            $stmtAuth->bindParam(":usuario", $userGerente, PDO::PARAM_STR);
            $stmtAuth->execute();
            $gerente = $stmtAuth->fetch(PDO::FETCH_ASSOC);

            if ($gerente && password_verify($pinGerente, $gerente["pin_autorizacion"])) {
                // Si el PIN es correcto, ejecutamos el Z a nombre de este gerente
                $autorizador_id = $gerente["id"];
                $respuesta = CierreZ::mdlEjecutarCierreZ($autorizador_id);
                return $respuesta;
            } else {
                return "credenciales_invalidas";
            }
        }
        
        return "error";
    }
    /* ==============================================================
       3. FORZAR CIERRE X DE UN CAJERO (CORTE REMOTO)
       ============================================================== */
    public static function ctrForzarCierreX() {
        if(isset($_POST["idCajeroForzado"])) {
            $idCajero = $_POST["idCajeroForzado"];
            $respuesta = CierreZ::mdlForzarCierreX($idCajero);
            return $respuesta;
        }
    }
    /* ==============================================================
       4. MOSTRAR CIERRES Z
       ============================================================== */
    public static function ctrMostrarCierresZ($item, $valor) {
        $respuesta = CierreZ::mdlMostrarCierresZ($item, $valor);
        return $respuesta;
    }

}