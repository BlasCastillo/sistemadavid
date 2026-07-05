<?php
require_once "config/conexion.php";

class Usuarios {
    // Propiedades encapsuladas y tipadas (PHP 8.0)
    private ?int $id = null;
    private ?int $rol_id = null;
    private ?string $usuario = null;
    private ?string $clave = null;
    private ?string $nombre_completo = null;
    private ?string $pin_autorizacion = null;
    private ?int $estado = null;
    private ?string $creado_en = null;

    // Constructor
    public function __construct() {}

    /*=============================================
    GETTERS Y SETTERS
    =============================================*/
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getRolId(): ?int { return $this->rol_id; }
    public function setRolId(?int $rol_id): void { $this->rol_id = $rol_id; }

    public function getUsuario(): ?string { return $this->usuario; }
    public function setUsuario(?string $usuario): void { $this->usuario = $usuario; }

    public function getClave(): ?string { return $this->clave; }
    public function setClave(?string $clave): void { $this->clave = $clave; }

    public function getNombreCompleto(): ?string { return $this->nombre_completo; }
    public function setNombreCompleto(?string $nombre_completo): void { $this->nombre_completo = $nombre_completo; }

    public function getPinAutorizacion(): ?string { return $this->pin_autorizacion; }
    public function setPinAutorizacion(?string $pin_autorizacion): void { $this->pin_autorizacion = $pin_autorizacion; }

    public function getEstado(): ?int { return $this->estado; }
    public function setEstado(?int $estado): void { $this->estado = $estado; }

    public function getCreadoEn(): ?string { return $this->creado_en; }
    public function setCreadoEn(?string $creado_en): void { $this->creado_en = $creado_en; }


    /*=============================================
    MÉTODOS CRUD (De Instancia)
    =============================================*/
    
    // CREAR USUARIO
    public function crear(): bool {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("INSERT INTO usuarios (rol_id, usuario, clave, nombre_completo, pin_autorizacion, estado) VALUES (:rol_id, :usuario, :clave, :nombre_completo, :pin_autorizacion, :estado)");

        // Hasheamos la clave principal obligatoriamente
        $claveHash = password_hash($this->clave, PASSWORD_BCRYPT);
        
        // El PIN es opcional (solo para Gerente), si viene null, se guarda null. Si trae valor, se hashea.
        $pinHash = ($this->pin_autorizacion != null) ? password_hash($this->pin_autorizacion, PASSWORD_BCRYPT) : null;
        
        // Por defecto, todo usuario nace activo (1)
        $estado = $this->estado ?? 1;

        $stmt->bindParam(":rol_id", $this->rol_id, PDO::PARAM_INT);
        $stmt->bindParam(":usuario", $this->usuario, PDO::PARAM_STR);
        $stmt->bindParam(":clave", $claveHash, PDO::PARAM_STR);
        $stmt->bindParam(":nombre_completo", $this->nombre_completo, PDO::PARAM_STR);
        $stmt->bindParam(":pin_autorizacion", $pinHash, PDO::PARAM_STR);
        $stmt->bindParam(":estado", $estado, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->id = $conexion->lastInsertId(); // Capturamos el ID recién creado
            return true;
        }
        return false;
    }

    // ACTUALIZAR USUARIO (Datos generales, excluyendo la contraseña)
    public function actualizar(): bool {
        // Nota Senior: La actualización de contraseñas siempre debe manejarse en un método/formulario aparte por seguridad.
        $stmt = Conexion::conectar()->prepare("UPDATE usuarios SET rol_id = :rol_id, usuario = :usuario, nombre_completo = :nombre_completo WHERE id = :id");

        $stmt->bindParam(":rol_id", $this->rol_id, PDO::PARAM_INT);
        $stmt->bindParam(":usuario", $this->usuario, PDO::PARAM_STR);
        $stmt->bindParam(":nombre_completo", $this->nombre_completo, PDO::PARAM_STR);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }
    // ACTUALIZAR CONTRASEÑA (Aislado por seguridad)
    public function actualizarClave(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE usuarios SET clave = :clave WHERE id = :id");
        $stmt->bindParam(":clave", $this->clave, PDO::PARAM_STR);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ELIMINAR USUARIO (Borrado Lógico)
    public function eliminar(): bool {
        // No aplicamos DELETE FROM. Cambiamos el estado a 0 para mantener intacto el historial de facturas.
        $stmt = Conexion::conectar()->prepare("UPDATE usuarios SET estado = 0 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }


    /*=============================================
    MÉTODOS ADICIONALES (Estáticos)
    =============================================*/

    // INICIAR SESIÓN
    public static function iniciarSesion(string $usuario_ingresado, string $clave_ingresada) {
        // Solo permitimos el login a usuarios con estado = 1 (Activos)
        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND estado = 1");
        $stmt->bindParam(":usuario", $usuario_ingresado, PDO::PARAM_STR);
        $stmt->execute();
        
        $registro = $stmt->fetch();

        // Si el usuario existe, validamos que la contraseña plana coincida con el Hash de la BD
        if ($registro && password_verify($clave_ingresada, $registro->clave)) {
            return self::mapearObjeto($registro);
        }
        return false;
    }

    // LEER TODOS LOS USUARIOS (Filtro dinámico de estado)
    public static function leerTodos(int $filtroEstado = 1): array {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE estado = :estado");
        $stmt->bindParam(":estado", $filtroEstado, PDO::PARAM_INT);
        $stmt->execute();
        $registros = $stmt->fetchAll();
        
        $usuarios = [];
        foreach ($registros as $reg) {
            $usuarios[] = self::mapearObjeto($reg);
        }
        return $usuarios;
    }

    // NUEVO: VERIFICAR SI EL USUARIO YA EXISTE (Evita el congelamiento)
    public static function verificarUsuarioDuplicado(string $usuario) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
        $stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(); // Retorna el registro si existe, o false si está libre
    }

    // NUEVO: REACTIVAR USUARIO
    public function activar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE usuarios SET estado = 1 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    // BUSCAR USUARIO POR ID
    public static function buscarPorId(int $id) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE id = :id AND estado = 1");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch();

        return $registro ? self::mapearObjeto($registro) : false;
    }

    /*=============================================
    MÉTODO AUXILIAR PRIVADO
    =============================================*/
    // Transforma el registro genérico de la base de datos en una instancia viva de esta clase
    private static function mapearObjeto($registro): self {
        $usuarioObj = new self();
        $usuarioObj->setId($registro->id);
        $usuarioObj->setRolId($registro->rol_id);
        $usuarioObj->setUsuario($registro->usuario);
        $usuarioObj->setClave($registro->clave); // Conserva el hash
        $usuarioObj->setNombreCompleto($registro->nombre_completo);
        $usuarioObj->setPinAutorizacion($registro->pin_autorizacion); // Conserva el hash del PIN si existe
        $usuarioObj->setEstado($registro->estado);
        $usuarioObj->setCreadoEn($registro->creado_en);
        
        return $usuarioObj;
    }
}
?>