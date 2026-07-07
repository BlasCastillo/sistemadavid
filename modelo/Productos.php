<?php
require_once "config/conexion.php";

class Productos {
    private ?int $id;
    private int $linea_id;
    private int $categoria_id;
    private int $subcategoria_id;
    private string $codigo_barras;
    private string $nombre;
    private ?string $imagen;
    private float $costo_usdt;
    private float $margen_ganancia;
    private int $stock;
    private ?string $fecha_ultima_compra;
    private ?string $fecha_ultima_venta;
    private int $estado;
    private ?string $creado_en;

    // Propiedades virtuales para las vistas
    public string $linea_nombre = "";
    public string $categoria_nombre = "";
    public string $subcategoria_nombre = "";

    public function __construct(?int $id = null, int $linea_id = 0, int $categoria_id = 0, int $subcategoria_id = 0, string $codigo_barras = "", string $nombre = "", ?string $imagen = null, float $costo_usdt = 0.0, float $margen_ganancia = 20.0, int $stock = 0, ?string $fecha_ultima_compra = null, ?string $fecha_ultima_venta = null, int $estado = 1, ?string $creado_en = null) {
        $this->id = $id;
        $this->linea_id = $linea_id;
        $this->categoria_id = $categoria_id;
        $this->subcategoria_id = $subcategoria_id;
        $this->codigo_barras = $codigo_barras;
        $this->nombre = $nombre;
        $this->imagen = $imagen;
        $this->costo_usdt = $costo_usdt;
        $this->margen_ganancia = $margen_ganancia;
        $this->stock = $stock;
        $this->fecha_ultima_compra = $fecha_ultima_compra;
        $this->fecha_ultima_venta = $fecha_ultima_venta;
        $this->estado = $estado;
        $this->creado_en = $creado_en;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getLineaId(): int { return $this->linea_id; }
    public function getCategoriaId(): int { return $this->categoria_id; }
    public function getSubcategoriaId(): int { return $this->subcategoria_id; }
    public function getCodigoBarras(): string { return $this->codigo_barras; }
    public function getNombre(): string { return $this->nombre; }
    public function getImagen(): ?string { return $this->imagen; }
    public function getCostoUsdt(): float { return $this->costo_usdt; }
    public function getMargenGanancia(): float { return $this->margen_ganancia; }
    public function getStock(): int { return $this->stock; }
    public function getEstado(): int { return $this->estado; }

    
    // Setters
    public function setId(?int $id): void { $this->id = $id; }
    public function setLineaId(int $linea_id): void { $this->linea_id = $linea_id; }
    public function setCategoriaId(int $categoria_id): void { $this->categoria_id = $categoria_id; }
    public function setSubcategoriaId(int $subcategoria_id): void { $this->subcategoria_id = $subcategoria_id; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function setImagen(?string $imagen): void { $this->imagen = $imagen; }
    public function setMargenGanancia(float $margen_ganancia): void { $this->margen_ganancia = $margen_ganancia; }

    public static function leerTodos(int $filtroEstado = 1): array {
        $stmt = Conexion::conectar()->prepare("
            SELECT p.*, l.nombre as linea_nombre, c.nombre as categoria_nombre, s.nombre as subcategoria_nombre 
            FROM productos p
            INNER JOIN lineas l ON p.linea_id = l.id
            INNER JOIN categorias c ON p.categoria_id = c.id
            INNER JOIN subcategorias s ON p.subcategoria_id = s.id
            WHERE p.estado = :estado 
            ORDER BY p.id DESC
        ");
        $stmt->bindParam(":estado", $filtroEstado, PDO::PARAM_INT);
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $productos = [];
        foreach ($registros as $reg) {
            $obj = self::mapearObjeto($reg);
            $obj->linea_nombre = $reg->linea_nombre;
            $obj->categoria_nombre = $reg->categoria_nombre;
            $obj->subcategoria_nombre = $reg->subcategoria_nombre;
            $productos[] = $obj;
        }
        return $productos;
    }

    public static function buscarPorId(int $id) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM productos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $reg = $stmt->fetch(PDO::FETCH_OBJ);
        return $reg ? self::mapearObjeto($reg) : null;
    }

    public static function verificarDuplicado(string $nombre) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM productos WHERE nombre = :nombre");
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function crear(): bool {
        $stmt = Conexion::conectar()->prepare("INSERT INTO productos (linea_id, categoria_id, subcategoria_id, codigo_barras, nombre, imagen, costo_usdt, margen_ganancia, estado) VALUES (:linea_id, :categoria_id, :subcategoria_id, :codigo_barras, :nombre, :imagen, :costo_usdt, :margen_ganancia, 1)");
        
        $stmt->bindParam(":linea_id", $this->linea_id, PDO::PARAM_INT);
        $stmt->bindParam(":categoria_id", $this->categoria_id, PDO::PARAM_INT);
        $stmt->bindParam(":subcategoria_id", $this->subcategoria_id, PDO::PARAM_INT);
        $stmt->bindParam(":codigo_barras", $this->codigo_barras, PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(":imagen", $this->imagen, PDO::PARAM_STR);
        $stmt->bindParam(":costo_usdt", $this->costo_usdt, PDO::PARAM_STR);
        $stmt->bindParam(":margen_ganancia", $this->margen_ganancia, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    public function actualizar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE productos SET linea_id = :linea_id, categoria_id = :categoria_id, subcategoria_id = :subcategoria_id, nombre = :nombre, imagen = :imagen, margen_ganancia = :margen_ganancia WHERE id = :id");
        
        $stmt->bindParam(":linea_id", $this->linea_id, PDO::PARAM_INT);
        $stmt->bindParam(":categoria_id", $this->categoria_id, PDO::PARAM_INT);
        $stmt->bindParam(":subcategoria_id", $this->subcategoria_id, PDO::PARAM_INT);
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(":imagen", $this->imagen, PDO::PARAM_STR);
        $stmt->bindParam(":margen_ganancia", $this->margen_ganancia, PDO::PARAM_STR);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function desactivar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE productos SET estado = 0 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function activar(): bool {
        $stmt = Conexion::conectar()->prepare("UPDATE productos SET estado = 1 WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private static function mapearObjeto($reg): Productos {
        return new self($reg->id, $reg->linea_id, $reg->categoria_id, $reg->subcategoria_id, $reg->codigo_barras, $reg->nombre, $reg->imagen, (float)$reg->costo_usdt, (float)$reg->margen_ganancia, $reg->stock, $reg->fecha_ultima_compra, $reg->fecha_ultima_venta, $reg->estado, $reg->creado_en);
    }
}