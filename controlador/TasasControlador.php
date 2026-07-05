<?php
require_once "modelo/Tasas.php";

date_default_timezone_set('America/Caracas');

class TasasControlador {

    /*=============================================
    /*=============================================
    API 1: OBTENER TASA BINANCE P2P (USDT/VES)
    =============================================*/
    public static function apiBinance() {
        // La verdadera API de Binance P2P (Es un endpoint POST, no GET)
        $url = "https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search";
        
        // Parámetros JSON: Buscamos anuncios de compra de USDT con VES
        $parametros = json_encode([
            "fiat" => "VES",
            "page" => 1,
            "rows" => 1, // Solo necesitamos el primer resultado (el mejor precio)
            "tradeType" => "BUY",
            "asset" => "USDT",
            "countries" => [],
            "payTypes" => [],
            "publisherType" => "merchant"
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true); // Indicamos que es método POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parametros); // Enviamos los parámetros
        
        // Headers obligatorios: JSON y User-Agent
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $respuesta = curl_exec($ch);
        curl_close($ch);

        if ($respuesta) {
            $data = json_decode($respuesta, true);
            
            // Navegamos por el JSON real de Binance P2P hasta el precio del anuncio
            if (isset($data['data']) && count($data['data']) > 0) {
                $precioP2P = $data['data'][0]['adv']['price'];
                return round(floatval($precioP2P), 4);
            }
        }
        return false;
    }

    /*=============================================
    API 2: SCRAPING TASA BCV (FUENTE PRINCIPAL)
    =============================================*/
    public static function apiBCV() {
        $url = "https://www.bcv.org.ve/";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 segundos máximo antes de rendirse
        $html = curl_exec($ch);
        curl_close($ch);

        if ($html) {
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            $nodo = $xpath->query('//div[@id="dolar"]//strong');
            
            if ($nodo->length > 0) {
                $valorTexto = trim($nodo->item(0)->nodeValue);
                $valorNumerico = str_replace(',', '.', $valorTexto);
                return round(floatval($valorNumerico), 4);
            }
        }
        return false;
    }

    /*=============================================
    API 3: SCRAPING ALTERNO (FALLBACK ALCAMBIO)
    =============================================*/
    public static function apiAlCambioFallback() {
        // Nota: La URL y la estructura del DOM (XPath) dependerán del sitio web exacto que uses de respaldo.
        // Este es un ejemplo genérico apuntando a una estructura común de portales de tasas.
        $url = "https://alcambio.app/"; // URL de ejemplo de la fuente alterna
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $html = curl_exec($ch);
        curl_close($ch);

        if ($html) {
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            // Aquí buscaríamos el selector específico donde AlCambio imprime la tasa oficial
            // (Es vital inspeccionar el HTML de la página elegida para ajustar este query)
            $nodo = $xpath->query('//div[contains(@class, "tasa-oficial")]//span[contains(@class, "monto")]');
            
            if ($nodo->length > 0) {
                $valorTexto = trim($nodo->item(0)->nodeValue);
                // Limpiamos texto adicional como "Bs." y dejamos solo el número
                $valorLimpio = preg_replace('/[^0-9,.]/', '', $valorTexto);
                $valorNumerico = str_replace(',', '.', $valorLimpio);
                return round(floatval($valorNumerico), 4);
            }
        }
        return false;
    }

    /*=============================================
    MÉTODO ORQUESTADOR: ACTUALIZAR TASAS EN BD
    =============================================*/
    public static function ctrSincronizarTasas() {
        
        $tasaActual = Tasas::obtenerTasaActiva();
        $horaActual = date("H");
        $diaSemana = date("N");

        $nuevoBCV = false;
        
        // REGLA: Intentar de Lunes a Viernes después de las 5 PM OJO ESTO SE MODIFICA AQUI SI SE QUIERE CAMBIAR LA HORA
        if ($diaSemana >= 1 && $diaSemana <= 5 && $horaActual >= 17) {
            
            $nuevoBCV = self::apiBCV(); // Intento 1: Oficial
            
            if (!$nuevoBCV) {
                // Si la oficial falla (return false), activamos el Plan B
                $nuevoBCV = self::apiAlCambioFallback();
            }
        }

        $nuevoBinance = self::apiBinance();

        // Si todas las APIs fallan, conservamos los datos intactos
        if (!$nuevoBCV && (!$nuevoBinance || $nuevoBinance == 0)) {
            return false;
        }

        $valorFinalBCV = ($nuevoBCV) ? $nuevoBCV : $tasaActual->tasa_bcv;
        $valorFinalBinance = ($nuevoBinance) ? $nuevoBinance : $tasaActual->tasa_binance;

        $brechaFinal = (($valorFinalBinance - $valorFinalBCV) / $valorFinalBCV) * 100;
        $brechaFinal = round($brechaFinal, 2);

        // Evitar inserciones redundantes
        if ($valorFinalBCV == $tasaActual->tasa_bcv && $valorFinalBinance == $tasaActual->tasa_binance) {
            return false;
        }

        $tasa = new Tasas();
        $tasa->setTasaBcv($valorFinalBCV);
        $tasa->setTasaBinance($valorFinalBinance);
        $tasa->setBrechaPorcentaje($brechaFinal);
        
        return $tasa->crear();
    }

    /*=============================================
    ACTUALIZACIÓN MANUAL (SOBREESCRITURA POR GERENTE)
    =============================================*/
    public static function ctrActualizacionManual() {
        if (isset($_POST["nuevaTasaBcvManual"]) && isset($_POST["nuevaTasaBinanceManual"])) {
            
            // Solo el Gerente (Rol 1) puede hacer esto
            if ($_SESSION["rol_id"] == 1) {
                $bcv = floatval($_POST["nuevaTasaBcvManual"]);
                $binance = floatval($_POST["nuevaTasaBinanceManual"]);
                
                // Si envían una brecha manual forzada la tomamos, si no, la calculamos
                if (!empty($_POST["brechaForzadaManual"])) {
                    $brecha = floatval($_POST["brechaForzadaManual"]);
                } else {
                    $brecha = (($binance - $bcv) / $bcv) * 100;
                }

                $tasa = new Tasas();
                $tasa->setTasaBcv($bcv);
                $tasa->setTasaBinance($binance);
                $tasa->setBrechaPorcentaje(round($brecha, 2));

                if ($tasa->crear()) {
                    echo json_encode(["status" => "success", "mensaje" => "Tasa forzada y actualizada correctamente."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al guardar la nueva tasa en la base de datos."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "No tienes permisos para esta acción."]);
            }
            exit();
        }
    }
    /*=============================================
    SINCRONIZACIÓN VÍA AJAX (GATILLO Y BOTÓN)
    =============================================*/
    public static function ctrSincronizarAjax() {
        if (isset($_POST["tipoSincronizacion"])) {
            
            $tasaActual = Tasas::obtenerTasaActiva();
            $tipo = $_POST["tipoSincronizacion"];
            
            // Si es silencioso, verificamos si ha pasado al menos 1 hora (60 minutos)
            if ($tipo == "silencioso" && $tasaActual) {
                $fechaUltima = new DateTime($tasaActual->creado_en);
                $fechaAhora = new DateTime();
                
                // Calculamos la diferencia en minutos reales
                $diferenciaMinutos = ($fechaAhora->getTimestamp() - $fechaUltima->getTimestamp()) / 60;
                
                if ($diferenciaMinutos < 60) {
                    echo json_encode(["status" => "skip", "mensaje" => "No ha pasado una hora. Sincronización omitida."]);
                    exit();
                }
            }

            // Si es "forzado", o si ya pasó 1 hora en "silencioso", ejecutamos el orquestador
            $resultado = self::ctrSincronizarTasas();

            if ($resultado) {
                echo json_encode(["status" => "success", "mensaje" => "Tasas sincronizadas y actualizadas con éxito."]);
            } else {
                echo json_encode(["status" => "info", "mensaje" => "Las tasas se mantienen iguales a la última revisión, o hubo un fallo de conexión."]);
            }
            
            exit();
        }
    }
}
?>