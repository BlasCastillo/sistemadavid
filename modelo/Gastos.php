<?php
require_once "config/conexion.php";

class Gastos {
    private ?int $id;
    private string $concepto;
    private float $monto;
    private string $tipo;
    private string $fecha;
    private int $estado;
    private ?string $creado_en;

    public function __construct(?int $id = null, string $concepto = "", float $monto = 0.0, string $tipo = "", string $fecha = "", int $estado = 1, ?string $creado_en = null) {
        $this->id = $id;
        $this->concepto = $concepto;
        $this->monto = $monto;
        $this->tipo = $tipo;
        $this->fecha = $fecha;
        $this->estado = $estado;
        $this->creado_en = $creado_en;
    }

    public function getId(): ?int { return $this->id; }
    public function getConcepto(): string { return $this->concepto; }
    public function setConcepto(string $concepto): void { $this->concepto = $concepto; }
    public function getMonto(): float { return $this->monto; }
    public function setMonto(float $monto): void { $this->monto = $monto; }
    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): void { $this->tipo = $tipo; }
    public function getFecha(): string { return $this->fecha; }
    public function setFecha(string $fecha): void { $this->fecha = $fecha; }
    public function getEstado(): int { return $this->estado; }
    public function setEstado(int $estado): void { $this->estado = $estado; }
    public function getCreadoEn(): ?string { return $this->creado_en; }

    public static function leerTodos(int $filtroEstado = 1): array {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM gastos WHERE estado = :estado ORDER BY fecha DESC, id DESC");
        $stmt->bindParam(":estado", $filtroEstado, PDO::PARAM_INT);
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $gastos = [];
        foreach ($registros as $reg) {
            $gastos[] = self::mapearObjeto($reg);
        }
        return $gastos;
    }

    public static function buscarPorId(int $id) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM gastos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $reg = $stmt->fetch(PDO::FETCH_OBJ);
        return $reg ? self::mapearObjeto($reg) : null;
    }

    public function crear(): bool {
        $stmt = Conexion::conectar()->prepare("INSERT INTO gastos (concepto, monto, tipo, fecha, estado) VALUES (:concepto, :monto, :tipo, :fecha, 1)");
        $stmt->bindParam(":concepto", $this->concepto, PDO::PARAM_STR);
        $stmt->bindParam(":monto", $this->monto, PDO::PARAM_STR);
        $stmt->bindParam(":tipo", $this->tipo, PDO::PARAM_STR);
        $stmt->bindParam(":fecha", $this->fecha, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function actualizar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE gastos SET concepto = :concepto, monto = :monto, tipo = :tipo, fecha = :fecha WHERE id = :id");
        $stmt->bindParam(":concepto", $this->concepto, PDO::PARAM_STR);
        $stmt->bindParam(":monto", $this->monto, PDO::PARAM_STR);
        $stmt->bindParam(":tipo", $this->tipo, PDO::PARAM_STR);
        $stmt->bindParam(":fecha", $this->fecha, PDO::PARAM_STR);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function anular(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE gastos SET estado = 0 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function reactivar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE gastos SET estado = 1 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private static function mapearObjeto($reg): Gastos {
        return new self($reg->id, $reg->concepto, (float)$reg->monto, $reg->tipo, $reg->fecha, $reg->estado, $reg->creado_en);
    }
}