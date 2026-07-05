<?php
require_once "config/conexion.php";

class Categorias {
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

    // Getters y Setters
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }
    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function getEstado(): int { return $this->estado; }
    public function setEstado(int $estado): void { $this->estado = $estado; }
    public function getCreadoEn(): ?string { return $this->creado_en; }

    // OPERACIONES DE BASE DE DATOS
    public static function leerTodas(int $filtroEstado = 1): array {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM categorias WHERE estado = :estado ORDER BY id DESC");
        $stmt->bindParam(":estado", $filtroEstado, PDO::PARAM_INT);
        $stmt->execute();
        $registros = $stmt->fetchAll();
        
        $categorias = [];
        foreach ($registros as $reg) {
            $categorias[] = self::mapearObjeto($reg);
        }
        return $categorias;
    }

    public static function buscarPorId(int $id) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM categorias WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $reg = $stmt->fetch();
        return $reg ? self::mapearObjeto($reg) : null;
    }

    public static function verificarDuplicado(string $nombre) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM categorias WHERE nombre = :nombre");
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function crear(): bool {
        $stmt = Conexion::conectar()->prepare("INSERT INTO categorias (nombre, estado) VALUES (:nombre, 1)");
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function actualizar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE categorias SET nombre = :nombre WHERE id = :id");
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
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
        return new self($reg->id, $reg->nombre, $reg->estado, $reg->creado_en);
    }
}