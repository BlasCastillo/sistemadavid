<?php
// Requerimos el modelo para interactuar con la base de datos
require_once "modelo/Configuracion.php";
require_once "modelo/Bitacora.php"; // INYECCIÓN GLOBAL

class ConfiguracionControlador {

    /*=============================================
    MOSTRAR CONFIGURACIÓN (Para vistas y tickets)
    =============================================*/
    public static function ctrMostrarConfiguracion() {
        return Configuracion::leerConfiguracion();
    }

    /*=============================================
    ACTUALIZAR CONFIGURACIÓN GLOBAL (VÍA AJAX)
    =============================================*/
    public static function ctrActualizarConfiguracionAjax() {
        
        if (isset($_POST["actualizarConfiguracion"])) {
            
            // Seguridad básica: Validamos Nombre y RIF para evitar inyecciones en la vista
            // Permitimos letras, números, espacios, guiones, puntos y el símbolo & para el nombre
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ \-\.,&]+$/', $_POST["configNombre"]) &&
                preg_match('/^[a-zA-Z0-9\-]+$/', $_POST["configRif"])) {

                // Instanciamos el modelo orientado a objetos
                $config = new Configuracion();
                
                // Poblamos sus propiedades usando la interfaz pública de Setters
                $config->setNombreNegocio($_POST["configNombre"]);
                $config->setRifNegocio($_POST["configRif"]);
                $config->setDireccionFiscal($_POST["configDireccion"]);
                $config->setTelefonoNegocio($_POST["configTelefono"]);
                $config->setCorreoNegocio($_POST["configCorreo"]);
                $config->setImpresoraTickets($_POST["configImpresora"]);

                // Ejecutamos la actualización de instancia
                if ($config->actualizar()) {
                    
                    // ===================================================
                    // BITÁCORA: CAMBIO DE IDENTIDAD CORPORATIVA
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Configuración", "Actualización", "Modificó los datos de identidad corporativa y facturación del negocio.");
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "Identidad corporativa actualizada correctamente."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error interno al actualizar la base de datos."]);
                }

            } else {
                echo json_encode(["status" => "error", "mensaje" => "El Nombre del negocio o el RIF contienen caracteres no permitidos."]);
            }
            exit();
        }
    }

    /*=============================================
    DESBLOQUEAR NÚCLEO SUPER ADMIN (VÍA AJAX)
    =============================================*/
    public static function ctrDesbloquearSuperAdminAjax() {
        
        if (isset($_POST["pinSuperAdmin"])) {
            
            // Validación de QA: Evitar que manden scripts por el input del PIN
            if (preg_match('/^[a-zA-Z0-9]+$/', $_POST["pinSuperAdmin"])) {
                
                $pinIngresado = $_POST["pinSuperAdmin"];
                
                // Llamamos al método estático del modelo para verificar el Hash
                if (Configuracion::verificarPin($pinIngresado)) {
                    
                    // Creamos la bandera de sesión para desbloquear la vista
                    $_SESSION["superadmin_desbloqueado"] = true;
                    
                    // ===================================================
                    // BITÁCORA: ACCESO AL NÚCLEO
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Seguridad", "Desbloqueo de Núcleo", "Ingresó el PIN maestro para acceder a configuraciones críticas del sistema.");
                    // ===================================================

                    echo json_encode(["status" => "success", "mensaje" => "Acceso concedido al núcleo."]);
                    
                } else {
                    
                    // ===================================================
                    // BITÁCORA: ALERTA DE INTENTO FALLIDO AL NÚCLEO
                    // ===================================================
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Seguridad", "Intento Fallido", "Ingresó un PIN maestro incorrecto al intentar desbloquear el núcleo.");
                    // ===================================================

                    echo json_encode(["status" => "error", "mensaje" => "El PIN maestro ingresado es incorrecto."]);
                }

            } else {
                echo json_encode(["status" => "error", "mensaje" => "El formato del PIN no es válido. Solo letras y números."]);
            }
            exit();
        }
    }
}
?>