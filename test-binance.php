<?php
// =========================================================================
// SCRIPT DE AUDITORÍA: CONEXIÓN RAW A BINANCE P2P
// =========================================================================

echo "<h2>Auditoría de API Binance P2P</h2>";
echo "Hora de la prueba: " . date('Y-m-d H:i:s') . "<hr>";

$url = "https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search";

// Replicamos la carga exacta que usa tu TasasControlador
$parametros = json_encode([
    "fiat" => "VES",
    "page" => 1,
    "rows" => 1, 
    "tradeType" => "BUY",
    "asset" => "USDT",
    "countries" => [],
    "payTypes" => [],
    "publisherType" => "merchant" 
]);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 15, // Si tarda más de 15 seg, la red está fallando
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $parametros,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Cache-Control: no-cache"
    ],
]);

// Ejecutamos y capturamos
$respuesta = curl_exec($curl);
$error = curl_error($curl);
$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// =========================================================================
// ANÁLISIS DE RESULTADOS
// =========================================================================

echo "<h3>1. Código de Estado HTTP: <span style='color: " . ($http_status == 200 ? "green" : "red") . "'>" . $http_status . "</span></h3>";

if ($http_status == 200) {
    echo "<p style='color: green; font-weight: bold;'>✅ Conexión exitosa. Binance NO nos ha bloqueado.</p>";
} elseif ($http_status == 403 || $http_status == 429) {
    echo "<p style='color: red; font-weight: bold;'>❌ ALERTA: Binance está bloqueando las peticiones (Rate Limit o IP Ban).</p>";
}

if ($error) {
    echo "<h3>2. Error interno de cURL:</h3>";
    echo "<p style='color: red;'>" . $error . "</p>";
} else {
    echo "<h3>3. Data Cruda devuelta por Binance:</h3>";
    $data_decodificada = json_decode($respuesta, true);
    
    if (isset($data_decodificada['data'][0]['adv']['price'])) {
        $precio = $data_decodificada['data'][0]['adv']['price'];
        echo "<h4>🔥 Precio actual detectado en el Scrapeo: <span style='background: yellow;'>Bs. " . $precio . "</span></h4>";
    }

    // Imprimimos todo el arreglo JSON para ver qué está leyendo realmente
    echo "<div style='background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 5px; overflow-x: auto; font-family: monospace;'>";
    echo "<pre>";
    print_r($data_decodificada);
    echo "</pre>";
    echo "</div>";
}
?>