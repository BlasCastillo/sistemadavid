<?php
require_once "config/conexion.php";

class Lineas {
    private ?int $id;
    private string $nombre;
    private int $estado;
    private ?string $creado_en;

    public function __construct(?int $id = null, string $nombre = "", int $estado = 1, ?string $creado_en = null) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->estado = $estado;
        $this->creado_en = $creado_en;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }
    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function getEstado(): int { return $this->estado; }
    public function setEstado(int $estado): void { $this->estado = $estado; }
    public function getCreadoEn(): ?string { return $this->creado_en; }

    public static function leerTodas(int $filtroEstado = 1): array {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM lineas WHERE estado = :estado ORDER BY id DESC");
        $stmt->bindParam(":estado", $filtroEstado, PDO::PARAM_INT);
        $stmt->execute();
        $registros = $stmt->fetchAll();
        
        $lineas = [];
        foreach ($registros as $reg) {
            $lineas[] = self::mapearObjeto($reg);
        }
        return $lineas;
    }

    public static function buscarPorId(int $id) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM lineas WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $reg = $stmt->fetch();
        return $reg ? self::mapearObjeto($reg) : null;
    }

    public static function verificarDuplicado(string $nombre) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM lineas WHERE nombre = :nombre");
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function crear(): bool {
        $stmt = Conexion::conectar()->prepare("INSERT INTO lineas (nombre, estado) VALUES (:nombre, 1)");
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function actualizar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE lineas SET nombre = :nombre WHERE id = :id");
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function desactivar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE lineas SET estado = 0 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function activar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE lineas SET estado = 1 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private static function mapearObjeto($reg): Lineas {
        return new self($reg->id, $reg->nombre, $reg->estado, $reg->creado_en);
    }
}