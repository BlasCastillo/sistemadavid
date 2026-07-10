<?php
require_once "config/conexion.php";

class Configuracion {
    // Propiedades encapsuladas y tipadas (PHP 8.0)
    private ?int $id = null;
    private ?string $nombre_negocio = null;
    private ?string $rif_negocio = null;
    private ?string $direccion_fiscal = null;
    private ?string $telefono_negocio = null;
    private ?string $correo_negocio = null;
    private ?string $impresora_tickets = null;
    private ?string $pin_superadmin = null;

    // Constructor
    public function __construct() {}

    /*=============================================
    GETTERS Y SETTERS
    =============================================*/
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getNombreNegocio(): ?string { return $this->nombre_negocio; }
    public function setNombreNegocio(?string $nombre_negocio): void { $this->nombre_negocio = $nombre_negocio; }

    public function getRifNegocio(): ?string { return $this->rif_negocio; }
    public function setRifNegocio(?string $rif_negocio): void { $this->rif_negocio = $rif_negocio; }

    public function getDireccionFiscal(): ?string { return $this->direccion_fiscal; }
    public function setDireccionFiscal(?string $direccion_fiscal): void { $this->direccion_fiscal = $direccion_fiscal; }

    public function getTelefonoNegocio(): ?string { return $this->telefono_negocio; }
    public function setTelefonoNegocio(?string $telefono_negocio): void { $this->telefono_negocio = $telefono_negocio; }

    public function getCorreoNegocio(): ?string { return $this->correo_negocio; }
    public function setCorreoNegocio(?string $correo_negocio): void { $this->correo_negocio = $correo_negocio; }

    public function getImpresoraTickets(): ?string { return $this->impresora_tickets; }
    public function setImpresoraTickets(?string $impresora_tickets): void { $this->impresora_tickets = $impresora_tickets; }

    public function getPinSuperadmin(): ?string { return $this->pin_superadmin; }
    public function setPinSuperadmin(?string $pin_superadmin): void { $this->pin_superadmin = $pin_superadmin; }

    /*=============================================
    MÉTODOS CRUD (De Instancia)
    =============================================*/

    // ACTUALIZAR CONFIGURACIÓN GLOBAL (Fila única ID = 1)
    public function actualizar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE configuracion SET nombre_negocio = :nombre_negocio, rif_negocio = :rif_negocio, direccion_fiscal = :direccion_fiscal, telefono_negocio = :telefono_negocio, correo_negocio = :correo_negocio, impresora_tickets = :impresora_tickets WHERE id = 1");

        $stmt->bindParam(":nombre_negocio", $this->nombre_negocio, PDO::PARAM_STR);
        $stmt->bindParam(":rif_negocio", $this->rif_negocio, PDO::PARAM_STR);
        $stmt->bindParam(":direccion_fiscal", $this->direccion_fiscal, PDO::PARAM_STR);
        $stmt->bindParam(":telefono_negocio", $this->telefono_negocio, PDO::PARAM_STR);
        $stmt->bindParam(":correo_negocio", $this->correo_negocio, PDO::PARAM_STR);
        $stmt->bindParam(":impresora_tickets", $this->impresora_tickets, PDO::PARAM_STR);

        return $stmt->execute();
    }

    // ACTUALIZAR PIN MAESTRO (Aislado por seguridad)
    public function actualizarPin(string $nuevoPinPlano): bool {
        $pinHash = password_hash($nuevoPinPlano, PASSWORD_BCRYPT);
        
        $stmt = Conexion::conectar()->prepare("UPDATE configuracion SET pin_superadmin = :pin_superadmin WHERE id = 1");
        $stmt->bindParam(":pin_superadmin", $pinHash, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /*=============================================
    MÉTODOS ADICIONALES (Estáticos)
    =============================================*/

    // LEER CONFIGURACIÓN (Retorna una instancia mapeada del objeto o false)
    public static function leerConfiguracion() {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM configuracion WHERE id = 1");
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_OBJ); 

        return $registro ? self::mapearObjeto($registro) : false;
    }

    // VERIFICAR PIN MAESTRO SUPER ADMIN
    public static function verificarPin(string $pinIngresado): bool {
        $stmt = Conexion::conectar()->prepare("SELECT pin_superadmin FROM configuracion WHERE id = 1");
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_OBJ);

        if ($registro && password_verify($pinIngresado, $registro->pin_superadmin)) {
            return true;
        }
        return false;
    }

    /*=============================================
    MÉTODO AUXILIAR PRIVADO DE MAPEO
    =============================================*/
    // Transforma el registro genérico de la base de datos en una instancia viva de esta clase
    private static function mapearObjeto($registro): self {
        $configObj = new self();
        $configObj->setId($registro->id);
        $configObj->setNombreNegocio($registro->nombre_negocio);
        $configObj->setRifNegocio($registro->rif_negocio);
        $configObj->setDireccionFiscal($registro->direccion_fiscal);
        $configObj->setTelefonoNegocio($registro->telefono_negocio);
        $configObj->setCorreoNegocio($registro->correo_negocio);
        $configObj->setImpresoraTickets($registro->impresora_tickets);
        $configObj->setPinSuperadmin($registro->pin_superadmin);
        
        return $configObj;
    }
}
?>