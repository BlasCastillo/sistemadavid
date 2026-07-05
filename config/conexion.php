<?php
class Conexion {
    
    public static function conectar() {
        
        $host = "localhost";
        $db = "easypos_db";
        $user = "root"; // Usuario por defecto en XAMPP
        $password = ""; // Contraseña por defecto en XAMPP (vacía)

        try {
            $link = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $password);
            
            // Configuramos PDO para que lance excepciones en caso de errores SQL
            $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Forzamos que los datos se manejen como objetos para mayor limpieza
            $link->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

            return $link;

        } catch (PDOException $e) {
            die("Error crítico de conexión a la Base de Datos: " . $e->getMessage());
        }
    }
}
?>