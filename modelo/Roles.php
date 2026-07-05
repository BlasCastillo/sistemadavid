<?php
require_once "config/conexion.php";

class Roles {
    // Propiedades encapsuladas
    private ?int $id = null;
    private ?string $nombre = null;
    private ?string $permisos = null; // NUEVA PROPIEDAD
    private ?string $creado_en = null;

    // Constructor
    public function __construct() {}

    /*=============================================
    GETTERS Y SETTERS
    =============================================*/
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getNombre(): ?string { return $this->nombre; }
    public function setNombre(?string $nombre): void { $this->nombre = $nombre; }

    // NUEVOS GETTERS Y SETTERS PARA PERMISOS
    public function getPermisos(): ?string { return $this->permisos; }
    public function setPermisos(?string $permisos): void { $this->permisos = $permisos; }

    public function getCreadoEn(): ?string { return $this->creado_en; }
    public function setCreadoEn(?string $creado_en): void { $this->creado_en = $creado_en; }


    /*=============================================
    MÉTODOS CRUD (De Instancia)
    =============================================*/
    
    // CREAR ROL
    public function crear(): bool {
        $conexion = Conexion::conectar();
        // Agregamos permisos al INSERT (por si queremos crearlos con permisos por defecto en el futuro)
        $stmt = $conexion->prepare("INSERT INTO roles (nombre, permisos) VALUES (:nombre, :permisos)");
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(":permisos", $this->permisos, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $this->id = $conexion->lastInsertId();
            return true;
        }
        return false;
    }

    // ACTUALIZAR ROL (Nombre)
    public function actualizar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE roles SET nombre = :nombre WHERE id = :id");
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // NUEVO MÉTODO: ACTUALIZAR SOLO PERMISOS
    public function actualizarPermisos(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE roles SET permisos = :permisos WHERE id = :id");
        $stmt->bindParam(":permisos", $this->permisos, PDO::PARAM_STR);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // ELIMINAR ROL (Borrado Físico - Solo si no tiene usuarios asignados)
    public function eliminar(): bool {
        try {
            $stmt = Conexion::conectar()->prepare("DELETE FROM roles WHERE id = :id");
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false; 
        }
    }


    /*=============================================
    MÉTODOS ADICIONALES (Estáticos)
    =============================================*/

    // LEER TODOS LOS ROLES
    public static function leerTodos(): array {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM roles");
        $stmt->execute();
        $registros = $stmt->fetchAll();
        
        $roles = [];
        foreach ($registros as $reg) {
            $roles[] = self::mapearObjeto($reg);
        }
        return $roles;
    }

    // BUSCAR ROL POR ID
    public static function buscarPorId(int $id) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM roles WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch();

        return $registro ? self::mapearObjeto($registro) : false;
    }

    /*=============================================
    MÉTODO AUXILIAR PRIVADO
    =============================================*/
    private static function mapearObjeto($registro): self {
        $rolObj = new self();
        $rolObj->setId($registro->id);
        $rolObj->setNombre($registro->nombre);
        // NUEVA LÍNEA: Mapeamos la columna JSON al objeto
        $rolObj->setPermisos(isset($registro->permisos) ? $registro->permisos : null);
        $rolObj->setCreadoEn($registro->creado_en);
        
        return $rolObj;
    }

    // NUEVO: VERIFICAR SI EL NOMBRE DEL ROL YA EXISTE
    public static function verificarNombreDuplicado(string $nombre) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM roles WHERE nombre = :nombre");
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>