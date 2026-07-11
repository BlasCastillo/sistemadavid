<?php
require_once "config/conexion.php";

class Ofertas {
    
    // Propiedades encapsuladas de la tabla productos_ofertas
    private ?int $id;
    private int $producto_id;
    private float $porcentaje_descuento;
    private float $precio_oferta_usdt;
    private string $fecha_inicio;
    private string $fecha_fin;
    private int $estado;
    private ?string $fecha_creacion;

    // Propiedades virtuales para las vistas (Traídas con JOIN desde productos)
    public string $codigo_barras = "";
    public string $producto_nombre = "";
    public ?string $imagen = null;
    public float $costo_usdt = 0.0;
    public float $margen_ganancia = 0.0;
    public int $estado_dinamico = 0; // 1: Activa, 2: Programada, 0: Vencida

    public function __construct(?int $id = null, int $producto_id = 0, float $porcentaje_descuento = 0.0, float $precio_oferta_usdt = 0.0, string $fecha_inicio = "", string $fecha_fin = "", int $estado = 1, ?string $fecha_creacion = null) {
        $this->id = $id;
        $this->producto_id = $producto_id;
        $this->porcentaje_descuento = $porcentaje_descuento;
        $this->precio_oferta_usdt = $precio_oferta_usdt;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_fin = $fecha_fin;
        $this->estado = $estado;
        $this->fecha_creacion = $fecha_creacion;
    }

    /*=============================================
    GETTERS
    =============================================*/
    public function getId(): ?int { return $this->id; }
    public function getProductoId(): int { return $this->producto_id; }
    public function getPorcentajeDescuento(): float { return $this->porcentaje_descuento; }
    public function getPrecioOfertaUsdt(): float { return $this->precio_oferta_usdt; }
    public function getFechaInicio(): string { return $this->fecha_inicio; }
    public function getFechaFin(): string { return $this->fecha_fin; }
    public function getEstado(): int { return $this->estado; }

    /*=============================================
    SETTERS
    =============================================*/
    public function setId(?int $id): void { $this->id = $id; }
    public function setProductoId(int $producto_id): void { $this->producto_id = $producto_id; }
    public function setPorcentajeDescuento(float $porcentaje): void { $this->porcentaje_descuento = $porcentaje; }
    public function setPrecioOfertaUsdt(float $precio): void { $this->precio_oferta_usdt = $precio; }
    public function setFechaInicio(string $fecha): void { $this->fecha_inicio = $fecha; }
    public function setFechaFin(string $fecha): void { $this->fecha_fin = $fecha; }

    /*=============================================
    1. LEER TODAS LAS OFERTAS
    =============================================*/
    public static function leerTodas(): array {
        $stmt = Conexion::conectar()->prepare("
            SELECT o.*, p.codigo_barras, p.nombre as producto_nombre, p.imagen, p.costo_usdt, p.margen_ganancia,
            (CASE 
                WHEN CURDATE() BETWEEN o.fecha_inicio AND o.fecha_fin THEN 1 
                WHEN CURDATE() < o.fecha_inicio THEN 2 
                ELSE 0 
            END) as estado_dinamico
            FROM productos_ofertas o
            INNER JOIN productos p ON o.producto_id = p.id
            ORDER BY o.id DESC
        ");
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $ofertas = [];
        foreach ($registros as $reg) {
            $ofertas[] = self::mapearObjeto($reg);
        }
        return $ofertas;
    }

    /*=============================================
    2. CREAR NUEVA OFERTA (Con método instanciado)
    =============================================*/
    public function crear(): string {
        $conexion = Conexion::conectar();
        
        try {
            // Anti-choque: Validar que el producto no tenga oferta activa en ese rango
            $stmtCheck = $conexion->prepare("SELECT id FROM productos_ofertas WHERE producto_id = :producto_id AND fecha_fin >= :fecha_inicio");
            $stmtCheck->bindParam(":producto_id", $this->producto_id, PDO::PARAM_INT);
            $stmtCheck->bindParam(":fecha_inicio", $this->fecha_inicio, PDO::PARAM_STR);
            $stmtCheck->execute();
            
            if($stmtCheck->fetch()){
                return "duplicado"; 
            }

            $stmt = $conexion->prepare("INSERT INTO productos_ofertas (producto_id, porcentaje_descuento, precio_oferta_usdt, fecha_inicio, fecha_fin, estado) VALUES (:producto_id, :porcentaje, :precio_oferta, :fecha_inicio, :fecha_fin, 1)");

            $stmt->bindParam(":producto_id", $this->producto_id, PDO::PARAM_INT);
            $stmt->bindParam(":porcentaje", $this->porcentaje_descuento, PDO::PARAM_STR);
            $stmt->bindParam(":precio_oferta", $this->precio_oferta_usdt, PDO::PARAM_STR);
            $stmt->bindParam(":fecha_inicio", $this->fecha_inicio, PDO::PARAM_STR);
            $stmt->bindParam(":fecha_fin", $this->fecha_fin, PDO::PARAM_STR);

            return $stmt->execute() ? "ok" : "error";

        } catch (Exception $e) {
            return "error";
        }
    }

    /*=============================================
    3. ELIMINAR OFERTA
    =============================================*/
    public function eliminar(): bool {
        $stmt = Conexion::conectar()->prepare("DELETE FROM productos_ofertas WHERE id = :id");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /*=============================================
    MAPEO DE OBJETOS (Igual a Productos.php)
    =============================================*/
    private static function mapearObjeto($reg): Ofertas {
        $obj = new self($reg->id, $reg->producto_id, (float)$reg->porcentaje_descuento, (float)$reg->precio_oferta_usdt, $reg->fecha_inicio, $reg->fecha_fin, $reg->estado, $reg->fecha_creacion);
        
        if(isset($reg->codigo_barras)) $obj->codigo_barras = $reg->codigo_barras;
        if(isset($reg->producto_nombre)) $obj->producto_nombre = $reg->producto_nombre;
        if(isset($reg->imagen)) $obj->imagen = $reg->imagen;
        if(isset($reg->costo_usdt)) $obj->costo_usdt = (float)$reg->costo_usdt;
        if(isset($reg->margen_ganancia)) $obj->margen_ganancia = (float)$reg->margen_ganancia;
        if(isset($reg->estado_dinamico)) $obj->estado_dinamico = (int)$reg->estado_dinamico;
        
        return $obj;
    }
}
?>