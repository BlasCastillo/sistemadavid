<?php
require_once "config/conexion.php";

class ConsultaPrecios {
    
    // Propiedades tipadas y encapsuladas
    private string $codigo_barras;
    private string $nombre;
    private ?string $imagen;
    private float $costo_usdt;
    private float $margen_ganancia;
    private float $porcentaje_descuento;
    private float $precio_oferta_usdt;
    private int $tiene_oferta_activa;

    // Constructor con inicialización por defecto
    public function __construct(string $codigo_barras = "", string $nombre = "", ?string $imagen = null, float $costo_usdt = 0.0, float $margen_ganancia = 0.0, float $porcentaje_descuento = 0.0, float $precio_oferta_usdt = 0.0, int $tiene_oferta_activa = 0) {
        $this->codigo_barras = $codigo_barras;
        $this->nombre = $nombre;
        $this->imagen = $imagen;
        $this->costo_usdt = $costo_usdt;
        $this->margen_ganancia = $margen_ganancia;
        $this->porcentaje_descuento = $porcentaje_descuento;
        $this->precio_oferta_usdt = $precio_oferta_usdt;
        $this->tiene_oferta_activa = $tiene_oferta_activa;
    }

    // Getters
    public function getCodigoBarras(): string { return $this->codigo_barras; }
    public function getNombre(): string { return $this->nombre; }
    public function getImagen(): ?string { return $this->imagen; }
    public function getCostoUsdt(): float { return $this->costo_usdt; }
    public function getMargenGanancia(): float { return $this->margen_ganancia; }
    public function getPorcentajeDescuento(): float { return $this->porcentaje_descuento; }
    public function getPrecioOfertaUsdt(): float { return $this->precio_oferta_usdt; }
    public function getTieneOfertaActiva(): int { return $this->tiene_oferta_activa; }

    // Setters
    public function setCodigoBarras(string $codigo_barras): void { $this->codigo_barras = $codigo_barras; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function setImagen(?string $imagen): void { $this->imagen = $imagen; }
    public function setCostoUsdt(float $costo_usdt): void { $this->costo_usdt = $costo_usdt; }
    public function setMargenGanancia(float $margen_ganancia): void { $this->margen_ganancia = $margen_ganancia; }
    public function setPorcentajeDescuento(float $porcentaje_descuento): void { $this->porcentaje_descuento = $porcentaje_descuento; }
    public function setPrecioOfertaUsdt(float $precio_oferta_usdt): void { $this->precio_oferta_usdt = $precio_oferta_usdt; }
    public function setTieneOfertaActiva(int $tiene_oferta_activa): void { $this->tiene_oferta_activa = $tiene_oferta_activa; }

    /*=============================================
    BUSCAR PRODUCTO POR CÓDIGO DE BARRAS
    =============================================*/
    public static function buscarProductoPorCodigo(string $codigo_barras) {
        // LEFT JOIN para unificar producto y oferta si existe. Se usa IFNULL para evitar vacíos si no hay oferta.
        $stmt = Conexion::conectar()->prepare("
            SELECT p.codigo_barras, p.nombre, p.imagen, p.costo_usdt, p.margen_ganancia,
                   IFNULL(o.porcentaje_descuento, 0) as porcentaje_descuento, 
                   IFNULL(o.precio_oferta_usdt, 0) as precio_oferta_usdt,
                   (CASE WHEN CURDATE() BETWEEN o.fecha_inicio AND o.fecha_fin AND o.estado = 1 THEN 1 ELSE 0 END) as tiene_oferta_activa
            FROM productos p
            LEFT JOIN productos_ofertas o ON p.id = o.producto_id AND o.estado = 1 AND CURDATE() BETWEEN o.fecha_inicio AND o.fecha_fin
            WHERE p.codigo_barras = :codigo_barras AND p.estado = 1
        ");
        $stmt->bindParam(":codigo_barras", $codigo_barras, PDO::PARAM_STR);
        $stmt->execute();
        $reg = $stmt->fetch(PDO::FETCH_OBJ);
        
        // Retorna la instancia hidratada o null
        return $reg ? self::mapearObjeto($reg) : null;
    }

    /*=============================================
    TRAER OFERTAS ACTIVAS (Para el Carrusel)
    =============================================*/
    public static function obtenerOfertasActivasParaCarrusel(): array {
        $stmt = Conexion::conectar()->prepare("
            SELECT p.codigo_barras, p.nombre, p.imagen, p.costo_usdt, p.margen_ganancia, 
                   o.porcentaje_descuento, o.precio_oferta_usdt,
                   1 as tiene_oferta_activa
            FROM productos_ofertas o
            INNER JOIN productos p ON o.producto_id = p.id
            WHERE o.estado = 1 AND CURDATE() BETWEEN o.fecha_inicio AND o.fecha_fin AND p.estado = 1
        ");
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $carrusel = [];
        foreach ($registros as $reg) {
            $carrusel[] = self::mapearObjeto($reg);
        }
        return $carrusel;
    }

    /*=============================================
    MAPEO DE OBJETO (Estándar de hidratación)
    =============================================*/
    private static function mapearObjeto($reg): ConsultaPrecios {
        return new self(
            $reg->codigo_barras, 
            $reg->nombre, 
            $reg->imagen, 
            (float)$reg->costo_usdt, 
            (float)$reg->margen_ganancia, 
            (float)$reg->porcentaje_descuento, 
            (float)$reg->precio_oferta_usdt, 
            (int)$reg->tiene_oferta_activa
        );
    }
}
?>