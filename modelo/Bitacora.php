<?php
require_once "config/conexion.php";

class Bitacora {
    
    // Propiedades encapsuladas
    private ?int $id = null;
    private ?int $usuario_id = null;
    private ?string $modulo = null;
    private ?string $accion = null;
    private ?string $detalles = null;
    private ?string $ip_usuario = null;
    private ?string $fecha = null;

    public function __construct() {}

    /*=============================================
    GETTERS Y SETTERS
    =============================================*/
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getUsuarioId(): ?int { return $this->usuario_id; }
    public function setUsuarioId(?int $usuario_id): void { $this->usuario_id = $usuario_id; }

    public function getModulo(): ?string { return $this->modulo; }
    public function setModulo(?string $modulo): void { $this->modulo = $modulo; }

    public function getAccion(): ?string { return $this->accion; }
    public function setAccion(?string $accion): void { $this->accion = $accion; }

    public function getDetalles(): ?string { return $this->detalles; }
    public function setDetalles(?string $detalles): void { $this->detalles = $detalles; }

    public function getIpUsuario(): ?string { return $this->ip_usuario; }
    public function setIpUsuario(?string $ip_usuario): void { $this->ip_usuario = $ip_usuario; }

    public function getFecha(): ?string { return $this->fecha; }
    public function setFecha(?string $fecha): void { $this->fecha = $fecha; }


    /*=============================================
    1. REGISTRAR ACCIÓN (El "Gran Hermano")
    =============================================*/
    // Es estático para inyectarlo en 1 sola línea de código en cualquier controlador
    public static function registrarAccion(int $usuario_id, string $modulo, string $accion, string $detalles): bool {
        try {
            $conexion = Conexion::conectar();
            $stmt = $conexion->prepare("INSERT INTO bitacora_sistema (usuario_id, modulo, accion, detalles, ip_usuario) VALUES (:usuario_id, :modulo, :accion, :detalles, :ip_usuario)");
            
            // Capturamos la IP real de la computadora/teléfono que hizo la acción
            $ip = self::obtenerIP();

            $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(":modulo", $modulo, PDO::PARAM_STR);
            $stmt->bindParam(":accion", $accion, PDO::PARAM_STR);
            $stmt->bindParam(":detalles", $detalles, PDO::PARAM_STR);
            $stmt->bindParam(":ip_usuario", $ip, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            // Guardamos el error en el log interno de PHP por si falla la BD, para que no tumbe el sistema
            error_log("Error crítico en Bitacora: " . $e->getMessage());
            return false;
        }
    }

    /*=============================================
    2. LEER HISTORIAL (Con Filtros Dinámicos Combinados)
    =============================================*/
    public static function leerHistorial($fechaInicio = null, $fechaFin = null, $usuario_id = null, $modulo = null): array {
        try {
            $conexion = Conexion::conectar();
            
            $sql = "SELECT b.*, u.nombre_completo, u.usuario as username 
                    FROM bitacora_sistema b 
                    LEFT JOIN usuarios u ON b.usuario_id = u.id ";
            
            $condiciones = [];
            
            // 1. Armamos las condiciones dinámicamente según lo que venga lleno
            if (!empty($fechaInicio) && !empty($fechaFin)) {
                $condiciones[] = "b.fecha BETWEEN :inicio AND :fin";
            }
            if (!empty($usuario_id)) {
                $condiciones[] = "b.usuario_id = :usuario_id";
            }
            if (!empty($modulo)) {
                $condiciones[] = "b.modulo = :modulo";
            }
            
            // 2. Si hay al menos una condición, la inyectamos con WHERE y AND
            if (count($condiciones) > 0) {
                $sql .= " WHERE " . implode(" AND ", $condiciones);
            }
            
            $sql .= " ORDER BY b.fecha DESC LIMIT 2000"; 

            $stmt = $conexion->prepare($sql);

            // 3. Enlazamos solo los parámetros que realmente entraron en la consulta
            if (!empty($fechaInicio) && !empty($fechaFin)) {
                $inicio = $fechaInicio . " 00:00:00";
                $fin = $fechaFin . " 23:59:59";
                $stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
                $stmt->bindParam(":fin", $fin, PDO::PARAM_STR);
            }
            if (!empty($usuario_id)) {
                $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
            }
            if (!empty($modulo)) {
                $stmt->bindParam(":modulo", $modulo, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error al leer Bitacora: " . $e->getMessage());
            return [];
        }
    }

    /*=============================================
    3. UTILIDAD: OBTENER IP DEL CLIENTE
    =============================================*/
    private static function obtenerIP(): string {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Maneja el caso si están usando un proxy o balanceador
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
        }
        return trim($ip);
    }
}
?>