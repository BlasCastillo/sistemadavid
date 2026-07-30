<?php
require_once "modelo/Cierres.php";
require_once "modelo/Bitacora.php"; // INYECCIÓN GLOBAL

class CierresControlador {

    /* ==============================================================
       1. DEVOLVER MONTOS ESPERADOS PARA EL ARQUEO VÍA AJAX
       ============================================================== */
    public static function ctrObtenerMontosEsperadosAjax() {
        if (isset($_POST["idCajeroArqueo"])) {
            $cajero_id = intval($_POST["idCajeroArqueo"]);
            $respuesta = Cierres::mdlObtenerMontosEsperados($cajero_id);
            echo json_encode($respuesta);
            exit();
        }
    }

    /* ==============================================================
       2. RECIBIR Y PROCESAR EL ARQUEO DE CAJA (BLINDAJE ANTI-BYPASS)
       ============================================================== */
    public static function ctrProcesarCierreTurnoAjax() {
        
        // Identificamos al cajero en sesión
        $cajero_id = isset($_SESSION["id"]) ? $_SESSION["id"] : (isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0);
        
        $userGerente = isset($_POST["userGerente"]) ? $_POST["userGerente"] : null;
        $pinGerente = isset($_POST["pinGerente"]) ? $_POST["pinGerente"] : null;
        $autorizador_id = null;

        $esperados = json_decode($_POST["esperados"], true);
        $declarados = json_decode($_POST["declarados"], true);

        // 1. Validar Credenciales usando password_verify para Bcrypt
        if ($userGerente != null && $pinGerente != null) {
            
            // Primero obtenemos al usuario
            $stmtAuth = Conexion::conectar()->prepare("
                SELECT id, rol_id, pin_autorizacion 
                FROM usuarios 
                WHERE usuario = :usuario 
                LIMIT 1
            ");
            $stmtAuth->bindParam(":usuario", $userGerente, PDO::PARAM_STR);
            $stmtAuth->execute();
            $gerente = $stmtAuth->fetch(PDO::FETCH_ASSOC);

            // Validamos que exista y verificamos el hash con el PIN ingresado
            if ($gerente && password_verify($pinGerente, $gerente["pin_autorizacion"])) {
                $autorizador_id = $gerente["id"];
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Credenciales incorrectas o PIN inválido."]);
                exit();
            }
        }

        // 1.5. BLINDAJE DE SERVIDOR (Anti-Bypass de Consola)
        $difUsd = abs(floatval($declarados["efectivo_usd"]) - floatval($esperados["efectivo_usd"]));
        $difBs = abs(floatval($declarados["efectivo_bs"]) - floatval($esperados["efectivo_bs"]));
        $difPunto = abs(floatval($declarados["punto_venta_bs"]) - floatval($esperados["punto_venta_bs"]));

        if (($difUsd > 0.05 || $difBs > 0.05 || $difPunto > 0.05) && $autorizador_id == null) {
            
            // ===================================================
            // BITÁCORA: INTENTO DE EVASIÓN
            // ===================================================
            Bitacora::registrarAccion($_SESSION["id_usuario"], "Seguridad/Cierres", "Intento de Evasión", "El cajero intentó forzar un cierre X con descuadre sin autorización gerencial.");
            // ===================================================

            echo json_encode([
                "status" => "error", 
                "mensaje" => "ALERTA DE SEGURIDAD: Operación bloqueada por el servidor. No puede guardar un descuadre sin autorización gerencial."
            ]);
            exit();
        }

        // 2. Preparar los datos para el Modelo
        $datos = [
            "cajero_id" => $cajero_id,
            "tipo_cierre" => "Parcial",
            "esperados" => $_POST["esperados"],
            "declarados" => $_POST["declarados"],
            "observaciones" => $_POST["observaciones"],
            "autorizador_id" => $autorizador_id
        ];

        // 3. Ejecutar el guardado
        $respuesta = Cierres::mdlGuardarCierre($datos);
        
        // ===================================================
        // BITÁCORA: CIERRE X EXITOSO
        // ===================================================
        if ($respuesta == "ok") {
            $detallesLog = "Procesó el Cierre X del cajero ID: " . $cajero_id;
            if ($autorizador_id != null) {
                $detallesLog .= " (Autorizado por el Gerente ID: $autorizador_id debido a un descuadre)";
            }
            Bitacora::registrarAccion($_SESSION["id_usuario"], "Procesos/Cierres X", "Corte de Caja", $detallesLog);
        }
        // ===================================================

        echo json_encode($respuesta);
        exit();
    }
    /* ==============================================================
       4. MOSTRAR CIERRES DE CAJA (PANEL DE AUDITORÍA)
       ============================================================== */
    public static function ctrMostrarCierres($item, $valor) {
        $respuesta = Cierres::mdlMostrarCierres($item, $valor);
        return $respuesta;
    }
    /* ==============================================================
       5. MOSTRAR DETALLE DE CIERRE VÍA AJAX (MODAL)
       ============================================================== */
    public static function ctrMostrarDetalleCierreAjax() {
        if(isset($_POST["idCierreDetalle"])){
            
            $item = "id";
            $valor = $_POST["idCierreDetalle"];
            
            // Reutilizamos el modelo para traer un solo cierre
            $respuesta = Cierres::mdlMostrarCierres($item, $valor);
            
            echo json_encode($respuesta);
            exit();
        }
    }
    /* ==============================================================
       6. RECIBIR PETICIONES DE AUDITORÍA VÍA AJAX
       ============================================================== */
    public static function ctrAuditoriaAjax() {
        if(isset($_POST["accionAuditoria"])) {
            
            // Si es el ajuste de una moneda específica
            if($_POST["accionAuditoria"] == "movimiento_linea") {
                $datos = [
                    "tipo" => $_POST["tipo"],
                    "monto" => $_POST["monto"],
                    "moneda" => $_POST["moneda"],
                    "concepto" => $_POST["concepto"],
                    "metodo_pago" => isset($_POST["metodo_pago"]) ? $_POST["metodo_pago"] : null,
                    "referencia" => isset($_POST["referencia"]) ? $_POST["referencia"] : null
                ];
                $respuesta = Cierres::mdlRegistrarMovimientoAuditoria($datos);
                
                // ===================================================
                // BITÁCORA: AJUSTE DE AUDITORÍA
                // ===================================================
                if ($respuesta == "ok") {
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Auditoría", "Ajuste de Caja", "Realizó ajuste de " . $_POST["tipo"] . " por " . $_POST["monto"] . " " . $_POST["moneda"] . " (" . $_POST["concepto"] . ")");
                }
                // ===================================================

                echo json_encode(["status" => $respuesta]);
                exit();
            }

            // Si es el cierre definitivo de la auditoría
            if($_POST["accionAuditoria"] == "finalizar") {
                $respuesta = Cierres::mdlFinalizarAuditoriaCaja($_POST["idCierre"]);
                
                // ===================================================
                // BITÁCORA: FIN DE AUDITORÍA
                // ===================================================
                if ($respuesta == "ok") {
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Auditoría", "Cierre de Auditoría", "Finalizó e imprimió la auditoría del Cierre X ID: " . $_POST["idCierre"]);
                }
                // ===================================================

                echo json_encode(["status" => $respuesta]);
                exit();
            }
        }
    }
}
?>