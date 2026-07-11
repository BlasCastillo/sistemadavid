<?php
// Requerimos el modelo para interactuar con la base de datos
require_once "modelo/CuentasPorPagar.php";

class CuentasPorPagarControlador {

    /*=============================================
    1. MOSTRAR TODAS LAS CUENTAS (Para la tabla principal)
    =============================================*/
    public static function ctrMostrarCuentas() {
        return CuentasPorPagar::leerTodasLasCuentas();
    }

    /*=============================================
    2. MOSTRAR HISTORIAL DE ABONOS (VÍA AJAX)
    =============================================*/
    public static function ctrMostrarPagosAjax() {
        if (isset($_POST["idCuentaPorPagar"])) {
            $cxp_id = intval($_POST["idCuentaPorPagar"]);
            $respuesta = CuentasPorPagar::leerPagosPorCuenta($cxp_id);
            
            echo json_encode($respuesta);
            exit();
        }
    }

    /*=============================================
    3. REGISTRAR UN NUEVO ABONO (VÍA AJAX)
    =============================================*/
    public static function ctrRegistrarAbonoAjax() {
        
        if (isset($_POST["idCuentaAbono"]) && isset($_POST["montoAbono"])) {
            
            // Seguridad: Validación de caracteres para evitar inyecciones
            if (preg_match('/^[a-zA-Z0-9\-\/ ]+$/', $_POST["referenciaAbono"]) || empty($_POST["referenciaAbono"])) {

                $cxp_id = intval($_POST["idCuentaAbono"]);
                $usuario_id = $_SESSION["id_usuario"];
                $monto_ingresado = floatval($_POST["montoAbono"]);
                $moneda = $_POST["monedaAbono"];
                $metodo = $_POST["metodoPagoAbono"];
                $referencia = empty($_POST["referenciaAbono"]) ? "N/A" : trim($_POST["referenciaAbono"]);

                // 1. Obtener la tasa BCV oficial del día directamente del servidor (Seguridad)
                    $stmt = Conexion::conectar()->prepare("SELECT tasa_bcv FROM tasas_cambio ORDER BY id DESC LIMIT 1");
                    $stmt->execute();
                    $tasaActual = $stmt->fetch(PDO::FETCH_OBJ);
                    $tasaBcvSegura = $tasaActual ? floatval($tasaActual->tasa_bcv) : 1;

                // 2. Lógica Financiera: Conversión de Divisas
                $abono_usdt = 0;
                $abono_bs = 0;

                if ($moneda === "Bs") {
                    $abono_bs = $monto_ingresado;
                    // Convertimos los Bs a USDT usando la tasa oficial
                    $abono_usdt = $abono_bs / $tasaBcvSegura; 
                } else {
                    // USD Físico o USDT
                    $abono_usdt = $monto_ingresado;
                    // Calculamos el equivalente en Bs para el registro contable
                    $abono_bs = $abono_usdt * $tasaBcvSegura; 
                }

                // Redondeamos para mantener la limpieza en la base de datos
                $abono_usdt = round($abono_usdt, 4);
                $abono_bs = round($abono_bs, 2);

                // 3. Ejecutar la transacción en el Modelo
                $respuesta = CuentasPorPagar::registrarAbono($cxp_id, $usuario_id, $abono_usdt, $abono_bs, $tasaBcvSegura, $metodo, $referencia);

                if ($respuesta == "ok") {
                    echo json_encode([
                        "status" => "success", 
                        "mensaje" => "El abono se procesó correctamente y el saldo ha sido actualizado."
                    ]);
                } else {
                    echo json_encode([
                        "status" => "error", 
                        "mensaje" => "Fallo crítico en la transacción. El abono fue revertido."
                    ]);
                }

            } else {
                echo json_encode([
                    "status" => "error", 
                    "mensaje" => "El número de referencia contiene caracteres no permitidos."
                ]);
            }
            exit();
        }
    }
    /*=============================================
    4. MOSTRAR CUENTA POR ID (Llamada directa desde la Vista)
    =============================================*/
    public static function ctrMostrarCuentaPorId($id) {
        // Validamos que el ID sea un número por seguridad
        if (is_numeric($id)) {
            return CuentasPorPagar::buscarCuentaPorId(intval($id));
        }
        return false;
    }
}
?>