<?php
require_once "config/conexion.php";

class Categorias {
    private int $linea_id;
    public string $linea_nombre = "";
    private ?int $id;
    private string $nombre;
    private int $estado;
    private ?string $creado_en;

    public function __construct(?int $id = null, int $linea_id = 0, string $nombre = "", int $estado = 1, ?string $creado_en = null) {
        $this->id = $id;
        $this->linea_id = $linea_id;
        $this->nombre = $nombre;
        $this->estado = $estado;
        $this->creado_en = $creado_en;
    }

    // Getters y Setters
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }
    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function getEstado(): int { return $this->estado; }
    public function setEstado(int $estado): void { $this->estado = $estado; }
    public function getCreadoEn(): ?string { return $this->creado_en; }
    public function getLineaId(): int { return $this->linea_id; }
    public function setLineaId(int $linea_id): void { $this->linea_id = $linea_id; }


    // OPERACIONES DE BASE DE DATOS
        public static function leerTodas(int $filtroEstado = 1): array {
        $stmt = Conexion::conectar()->prepare("
            SELECT c.*, l.nombre as linea_nombre 
            FROM categorias c 
            LEFT JOIN lineas l ON c.linea_id = l.id 
            WHERE c.estado = :estado 
            ORDER BY c.id DESC
        ");
        $stmt->bindParam(":estado", $filtroEstado, PDO::PARAM_INT);
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $categorias = [];
        foreach ($registros as $reg) {
            $obj = self::mapearObjeto($reg);
            $obj->linea_nombre = isset($reg->linea_nombre) ? $reg->linea_nombre : "Sin Línea";
            $categorias[] = $obj;
        }
        return $categorias;
    }

    public static function buscarPorId(int $id) {
        $stmt = Conexion::conectar()->prepare("
            SELECT c.*, l.nombre as linea_nombre 
            FROM categorias c 
            LEFT JOIN lineas l ON c.linea_id = l.id 
            WHERE c.id = :id
        ");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $reg = $stmt->fetch(PDO::FETCH_OBJ);
        
        if ($reg) {
            $obj = self::mapearObjeto($reg);
            $obj->linea_nombre = isset($reg->linea_nombre) ? $reg->linea_nombre : "Sin Línea";
            return $obj;
        }
        return null;
    }

    public static function verificarDuplicado(string $nombre) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM categorias WHERE nombre = :nombre");
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function crear(): bool {
        $stmt = Conexion::conectar()->prepare("INSERT INTO categorias (linea_id, nombre, estado) VALUES (:linea_id, :nombre, 1)");
        $stmt->bindParam(":linea_id", $this->linea_id, PDO::PARAM_INT);
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR); 
        return $stmt->execute();
    }

    public function actualizar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE categorias SET linea_id = :linea_id, nombre = :nombre WHERE id = :id");
        $stmt->bindParam(":linea_id", $this->linea_id, PDO::PARAM_INT);
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function desactivar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE categorias SET estado = 0 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function activar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE categorias SET estado = 1 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private static function mapearObjeto($reg): Categorias {
        $lineaId = isset($reg->linea_id) ? (int)$reg->linea_id : 0;
        return new self($reg->id, $lineaId, $reg->nombre, $reg->estado, $reg->creado_en);
    }
    
}