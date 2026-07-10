<?php
// Llamamos a la conexión y al modelo
require_once "modelo/Configuracion.php";

// Instanciamos la clase y usamos el método que ya programaste
$config = new Configuracion();

if($config->actualizarPin("123456")){
    echo "<h2 style='color:green; font-family:sans-serif;'>¡PIN reseteado con éxito a: 123456!</h2>";
    echo "<p style='font-family:sans-serif;'>Por seguridad, elimina este archivo (reset.php) y vuelve a intentar el login.</p>";
} else {
    echo "Hubo un error al conectar con la base de datos.";
}
?>