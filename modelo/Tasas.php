<?php
require_once "config/conexion.php";

class Tasas {
    private ?int $id = null;
    private ?float $tasa_bcv = null;
    private ?float $tasa_binance = null;
    private ?float $brecha_porcentaje = null;
    private ?string $creado_en = null;

    public function __construct() {}

    // Getters y Setters
    public function getId(): ?int { return $this->id; }
    public function getTasaBcv(): ?float { return $this->tasa_bcv; }
    public function setTasaBcv(?float $tasa_bcv): void { $this->tasa_bcv = $tasa_bcv; }
    public function getTasaBinance(): ?float { return $this->tasa_binance; }
    public function setTasaBinance(?float $tasa_binance): void { $this->tasa_binance = $tasa_binance; }
    public function getBrechaPorcentaje(): ?float { return $this->brecha_porcentaje; }
    public function setBrechaPorcentaje(?float $brecha_porcentaje): void { $this->brecha_porcentaje = $brecha_porcentaje; }
    public function getCreadoEn(): ?string { return $this->creado_en; }

    // GUARDAR NUEVA TASA
    public function crear(): bool {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("INSERT INTO tasas_cambio (tasa_bcv, tasa_binance, brecha_porcentaje) VALUES (:bcv, :binance, :brecha)");

        $stmt->bindParam(":bcv", $this->tasa_bcv, PDO::PARAM_STR);
        $stmt->bindParam(":binance", $this->tasa_binance, PDO::PARAM_STR);
        $stmt->bindParam(":brecha", $this->brecha_porcentaje, PDO::PARAM_STR);

        return $stmt->execute();
    }

    // OBTENER LA TASA ACTIVA (La última registrada)
    public static function obtenerTasaActiva() {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM tasas_cambio ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        // Cambiamos FETCH_OBJ a fetch() estándar, ya que PDO está configurado en conexion.php para devolver objetos globales
        return $stmt->fetch(); 
    }
}
?>