<?php
require_once "config/conexion.php";

class Subcategorias {
    private ?int $id;
    private int $categoria_id;
    private string $nombre;
    private int $estado;
    private ?string $creado_en;
    
    // Propiedad virtual (No existe en la tabla subcategorias, pero viene del INNER JOIN)
    private string $categoria_nombre; 

    public function __construct(?int $id = null, int $categoria_id = 0, string $nombre = "", int $estado = 1, ?string $creado_en = null, string $categoria_nombre = "") {
        $this->id = $id;
        $this->categoria_id = $categoria_id;
        $this->nombre = $nombre;
        $this->estado = $estado;
        $this->creado_en = $creado_en;
        $this->categoria_nombre = $categoria_nombre;
    }

    // Getters y Setters
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }
    public function getCategoriaId(): int { return $this->categoria_id; }
    public function setCategoriaId(int $categoria_id): void { $this->categoria_id = $categoria_id; }
    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function getEstado(): int { return $this->estado; }
    public function setEstado(int $estado): void { $this->estado = $estado; }
    public function getCreadoEn(): ?string { return $this->creado_en; }
    public function getCategoriaNombre(): string { return $this->categoria_nombre; }

    // CONSULTAS CON INNER JOIN
    public static function leerTodas(int $filtroEstado = 1): array {
        $stmt = Conexion::conectar()->prepare("
            SELECT s.*, c.nombre as categoria_nombre 
            FROM subcategorias s
            INNER JOIN categorias c ON s.categoria_id = c.id 
            WHERE s.estado = :estado 
            ORDER BY s.id DESC
        ");
        $stmt->bindParam(":estado", $filtroEstado, PDO::PARAM_INT);
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $subcategorias = [];
        foreach ($registros as $reg) {
            $subcategorias[] = self::mapearObjeto($reg);
        }
        return $subcategorias;
    }

    public static function buscarPorId(int $id) {
        $stmt = Conexion::conectar()->prepare("
            SELECT s.*, c.nombre as categoria_nombre 
            FROM subcategorias s
            INNER JOIN categorias c ON s.categoria_id = c.id 
            WHERE s.id = :id
        ");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $reg = $stmt->fetch(PDO::FETCH_OBJ);
        return $reg ? self::mapearObjeto($reg) : null;
    }

    public static function verificarDuplicado(string $nombre) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM subcategorias WHERE nombre = :nombre");
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function crear(): bool {
        $stmt = Conexion::conectar()->prepare("INSERT INTO subcategorias (categoria_id, nombre, estado) VALUES (:categoria_id, :nombre, 1)");
        $stmt->bindParam(":categoria_id", $this->categoria_id, PDO::PARAM_INT);
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function actualizar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE subcategorias SET categoria_id = :categoria_id, nombre = :nombre WHERE id = :id");
        $stmt->bindParam(":categoria_id", $this->categoria_id, PDO::PARAM_INT);
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function desactivar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE subcategorias SET estado = 0 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function activar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE subcategorias SET estado = 1 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private static function mapearObjeto($reg): Subcategorias {
        // Al mapear, pasamos la propiedad virtual 'categoria_nombre' si existe en la consulta
        $catNombre = isset($reg->categoria_nombre) ? $reg->categoria_nombre : "";
        return new self($reg->id, $reg->categoria_id, $reg->nombre, $reg->estado, $reg->creado_en, $catNombre);
    }
}