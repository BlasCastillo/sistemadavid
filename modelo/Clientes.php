<?php
require_once "config/conexion.php";

class Clientes {
    private ?int $id;
    private string $documento;
    private string $nombre;
    private ?string $telefono;
    private ?string $email;
    private ?string $direccion;
    private int $estado;
    private ?string $creado_en;

    public function __construct(?int $id = null, string $documento = "", string $nombre = "", ?string $telefono = null, ?string $email = null, ?string $direccion = null, int $estado = 1, ?string $creado_en = null) {
        $this->id = $id;
        $this->documento = $documento;
        $this->nombre = $nombre;
        $this->telefono = $telefono;
        $this->email = $email;
        $this->direccion = $direccion;
        $this->estado = $estado;
        $this->creado_en = $creado_en;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }
    public function getDocumento(): string { return $this->documento; }
    public function setDocumento(string $documento): void { $this->documento = $documento; }
    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function getTelefono(): ?string { return $this->telefono; }
    public function setTelefono(?string $telefono): void { $this->telefono = $telefono; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): void { $this->email = $email; }
    public function getDireccion(): ?string { return $this->direccion; }
    public function setDireccion(?string $direccion): void { $this->direccion = $direccion; }
    public function getEstado(): int { return $this->estado; }
    public function setEstado(int $estado): void { $this->estado = $estado; }
    public function getCreadoEn(): ?string { return $this->creado_en; }

    public static function leerTodos(int $filtroEstado = 1): array {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM clientes WHERE estado = :estado ORDER BY id DESC");
        $stmt->bindParam(":estado", $filtroEstado, PDO::PARAM_INT);
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $clientes = [];
        foreach ($registros as $reg) {
            $clientes[] = self::mapearObjeto($reg);
        }
        return $clientes;
    }

    public static function buscarPorId(int $id) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $reg = $stmt->fetch(PDO::FETCH_OBJ);
        return $reg ? self::mapearObjeto($reg) : null;
    }

    public static function verificarDocumentoDuplicado(string $documento) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM clientes WHERE documento = :documento");
        $stmt->bindParam(":documento", $documento, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function crear(): bool {
        $stmt = Conexion::conectar()->prepare("INSERT INTO clientes (documento, nombre, telefono, email, direccion, estado) VALUES (:documento, :nombre, :telefono, :email, :direccion, 1)");
        $stmt->bindParam(":documento", $this->documento, PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(":telefono", $this->telefono, PDO::PARAM_STR);
        $stmt->bindParam(":email", $this->email, PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $this->direccion, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function actualizar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE clientes SET documento = :documento, nombre = :nombre, telefono = :telefono, email = :email, direccion = :direccion WHERE id = :id");
        $stmt->bindParam(":documento", $this->documento, PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(":telefono", $this->telefono, PDO::PARAM_STR);
        $stmt->bindParam(":email", $this->email, PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $this->direccion, PDO::PARAM_STR);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function desactivar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE clientes SET estado = 0 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function activar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE clientes SET estado = 1 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private static function mapearObjeto($reg): Clientes {
        return new self($reg->id, $reg->documento, $reg->nombre, $reg->telefono, $reg->email, $reg->direccion, $reg->estado, $reg->creado_en);
    }
}