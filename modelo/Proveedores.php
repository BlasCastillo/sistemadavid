<?php
require_once "config/conexion.php";

class Proveedores {
    private ?int $id;
    private string $documento;
    private string $razon_social;
    private ?string $telefono;
    private ?string $email;
    private ?string $direccion;
    private int $estado;
    private ?string $creado_en;

    public function __construct(?int $id = null, string $documento = "", string $razon_social = "", ?string $telefono = null, ?string $email = null, ?string $direccion = null, int $estado = 1, ?string $creado_en = null) {
        $this->id = $id;
        $this->documento = $documento;
        $this->razon_social = $razon_social;
        $this->telefono = $telefono;
        $this->email = $email;
        $this->direccion = $direccion;
        $this->estado = $estado;
        $this->creado_en = $creado_en;
    }

    // Getters y Setters básicos
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }
    public function getDocumento(): string { return $this->documento; }
    public function setDocumento(string $documento): void { $this->documento = $documento; }
    public function getRazonSocial(): string { return $this->razon_social; }
    public function setRazonSocial(string $razon_social): void { $this->razon_social = $razon_social; }
    public function getTelefono(): ?string { return $this->telefono; }
    public function setTelefono(?string $telefono): void { $this->telefono = $telefono; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): void { $this->email = $email; }
    public function getDireccion(): ?string { return $this->direccion; }
    public function setDireccion(?string $direccion): void { $this->direccion = $direccion; }
    public function getEstado(): int { return $this->estado; }
    public function setEstado(int $estado): void { $this->estado = $estado; }
    public function getCreadoEn(): ?string { return $this->creado_en; }

    // Consultas a Base de Datos
    public static function leerTodos(int $filtroEstado = 1): array {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM proveedores WHERE estado = :estado ORDER BY id DESC");
        $stmt->bindParam(":estado", $filtroEstado, PDO::PARAM_INT);
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $proveedores = [];
        foreach ($registros as $reg) {
            $proveedores[] = self::mapearObjeto($reg);
        }
        return $proveedores;
    }

    public static function buscarPorId(int $id) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM proveedores WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $reg = $stmt->fetch(PDO::FETCH_OBJ);
        return $reg ? self::mapearObjeto($reg) : null;
    }

    public static function verificarDocumentoDuplicado(string $documento) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM proveedores WHERE documento = :documento");
        $stmt->bindParam(":documento", $documento, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function crear(): bool {
        $stmt = Conexion::conectar()->prepare("INSERT INTO proveedores (documento, razon_social, telefono, email, direccion, estado) VALUES (:documento, :razon_social, :telefono, :email, :direccion, 1)");
        $stmt->bindParam(":documento", $this->documento, PDO::PARAM_STR);
        $stmt->bindParam(":razon_social", $this->razon_social, PDO::PARAM_STR);
        $stmt->bindParam(":telefono", $this->telefono, PDO::PARAM_STR);
        $stmt->bindParam(":email", $this->email, PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $this->direccion, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function actualizar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE proveedores SET documento = :documento, razon_social = :razon_social, telefono = :telefono, email = :email, direccion = :direccion WHERE id = :id");
        $stmt->bindParam(":documento", $this->documento, PDO::PARAM_STR);
        $stmt->bindParam(":razon_social", $this->razon_social, PDO::PARAM_STR);
        $stmt->bindParam(":telefono", $this->telefono, PDO::PARAM_STR);
        $stmt->bindParam(":email", $this->email, PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $this->direccion, PDO::PARAM_STR);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function desactivar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE proveedores SET estado = 0 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function activar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE proveedores SET estado = 1 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private static function mapearObjeto($reg): Proveedores {
        return new self($reg->id, $reg->documento, $reg->razon_social, $reg->telefono, $reg->email, $reg->direccion, $reg->estado, $reg->creado_en);
    }
}