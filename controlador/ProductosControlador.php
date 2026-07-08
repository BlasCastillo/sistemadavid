<?php
require_once "modelo/Productos.php";

class ProductosControlador {

    public static function ctrMostrarProductos(?int $id = null, int $filtroEstado = 1) {
        if ($id != null) {
            return Productos::buscarPorId($id);
        } else {
            return Productos::leerTodos($filtroEstado);
        }
    }

    /**
     * Generador Interno de Código de Barras EAN-13
     */
    private static function generarCodigoEAN13(int $idLinea, int $idCategoria, int $idSubcategoria): string {
        $prefijo = "20"; 
        $lin = str_pad($idLinea, 2, "0", STR_PAD_LEFT);
        $cat = str_pad($idCategoria, 2, "0", STR_PAD_LEFT);
        $sub = str_pad($idSubcategoria, 2, "0", STR_PAD_LEFT);
        
        $secuencia = str_pad(mt_rand(1, 9999), 4, "0", STR_PAD_LEFT);
        $codigoBase = $prefijo . $lin . $cat . $sub . $secuencia; 
        
        $sumaPares = 0; $sumaImpares = 0;
        for ($i = 0; $i < 12; $i++) {
            if ($i % 2 === 0) { $sumaImpares += (int)$codigoBase[$i]; } 
            else { $sumaPares += (int)$codigoBase[$i]; }
        }
        
        $sumaTotal = $sumaImpares + ($sumaPares * 3);
        $residuo = $sumaTotal % 10;
        $digitoVerificador = $residuo === 0 ? 0 : 10 - $residuo;
        
        return $codigoBase . $digitoVerificador; 
    }

    /**
     * Procesador de Imágenes Seguro (Redimensiona y guarda)
     */
    private static function procesarImagen($archivo, $codigoBarras) {
        if (isset($archivo["tmp_name"]) && !empty($archivo["tmp_name"])) {
            $directorio = "vista/img/productos/";
            if (!file_exists($directorio)) { mkdir($directorio, 0755, true); }
            
            $rutaDestino = $directorio . $codigoBarras . ".jpg"; // Normalizamos el guardado a JPG
            
            // Usamos getimagesize para leer el ADN real del archivo, ignorando la extensión
            $infoImagen = getimagesize($archivo["tmp_name"]);
            
            if ($infoImagen !== false) {
                $tipoMime = $infoImagen['mime']; // Obtiene el tipo MIME real (image/jpeg o image/png)
                
                if ($tipoMime == "image/jpeg") {
                    $origen = imagecreatefromjpeg($archivo["tmp_name"]);
                } elseif ($tipoMime == "image/png") {
                    $origen = imagecreatefrompng($archivo["tmp_name"]);
                } else {
                    return null; // Si intentan subir un GIF, WEBP o PDF, lo ignora
                }

                if (!$origen) return null; // Seguro extra por si la imagen está corrupta

                $anchoOrigen = imagesx($origen);
                $altoOrigen = imagesy($origen);
                $nuevoAncho = 500;
                $nuevoAlto = floor($altoOrigen * ($nuevoAncho / $anchoOrigen));
                
                $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                
                // Truco UX: Rellenar con fondo blanco por si el PNG era transparente
                $fondoBlanco = imagecolorallocate($destino, 255, 255, 255);
                imagefill($destino, 0, 0, $fondoBlanco);
                
                imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $anchoOrigen, $altoOrigen);
                
                imagejpeg($destino, $rutaDestino, 80); // Calidad 80%
                
                imagedestroy($origen);
                imagedestroy($destino);
                
                return $rutaDestino;
            }
        }
        return null; // Imagen genérica si falla algo
    }

    public static function ctrCrearProducto() {
        if (isset($_POST["nombreProducto"]) && isset($_POST["idSubcategoriaProducto"])) {
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ \-\.]+$/', $_POST["nombreProducto"])) {
                
                $existe = Productos::verificarDuplicado($_POST["nombreProducto"]);
                if ($existe) {
                    echo json_encode(["status" => "error", "mensaje" => "El producto ya existe en el catálogo."]);
                    exit();
                }

                $idLinea = intval($_POST["idLineaProducto"]);
                $idCat = intval($_POST["idCategoriaProducto"]);
                $idSub = intval($_POST["idSubcategoriaProducto"]);
                
                $codigoBarras = self::generarCodigoEAN13($idLinea, $idCat, $idSub);
                
                // Procesar imagen si viene adjunta
                $rutaImagen = "vista/img/productos/default.png"; // Placeholder por defecto
                if (isset($_FILES["fotoProducto"]) && $_FILES["fotoProducto"]["name"] != "") {
                    $rutaGuardada = self::procesarImagen($_FILES["fotoProducto"], $codigoBarras);
                    if ($rutaGuardada) { $rutaImagen = $rutaGuardada; }
                }

                // El Costo base siempre entra por defecto en 0.00 a menos que se cree desde la ventana de Compras
                $costoInicial = isset($_POST["costoInicialProducto"]) ? floatval($_POST["costoInicialProducto"]) : 0.00;
                $margen = isset($_POST["margenProducto"]) ? floatval($_POST["margenProducto"]) : 20.00;

                $producto = new Productos(null, $idLinea, $idCat, $idSub, $codigoBarras, $_POST["nombreProducto"], $rutaImagen, $costoInicial, $margen);

                if ($producto->crear()) {
                    echo json_encode(["status" => "success", "mensaje" => "Producto registrado. Código EAN-13 generado: " . $codigoBarras]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al guardar en la base de datos."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "El nombre contiene caracteres inválidos."]);
            }
            exit();
        }
    }
    
        public static function ctrActualizarProducto() {
        if (isset($_POST["idProductoEditar"]) && isset($_POST["nombreProductoEditar"])) {
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ \-\.]+$/', $_POST["nombreProductoEditar"])) {
                
                $existe = Productos::verificarDuplicado($_POST["nombreProductoEditar"]);
                if ($existe && $existe->id != $_POST["idProductoEditar"]) {
                    echo json_encode(["status" => "error", "mensaje" => "Ya existe otro producto con ese nombre."]);
                    exit();
                }

                // Conservar la imagen actual por defecto
                $rutaImagen = $_POST["imagenActualProducto"]; 
                
                // Si el usuario sube una imagen nueva al editar
                if (isset($_FILES["fotoProductoEditar"]) && $_FILES["fotoProductoEditar"]["name"] != "") {
                    // Reutilizamos el código de barras existente para el nombre del archivo
                    $codigoBarras = $_POST["codigoBarrasActual"]; 
                    $rutaNueva = self::procesarImagen($_FILES["fotoProductoEditar"], $codigoBarras);
                    if ($rutaNueva) { 
                        $rutaImagen = $rutaNueva; 
                    }
                }

                $producto = new Productos();
                $producto->setId(intval($_POST["idProductoEditar"]));
                $producto->setLineaId(intval($_POST["idLineaProductoEditar"]));
                $producto->setCategoriaId(intval($_POST["idCategoriaProductoEditar"]));
                $producto->setSubcategoriaId(intval($_POST["idSubcategoriaProductoEditar"]));
                $producto->setNombre($_POST["nombreProductoEditar"]);
                $producto->setImagen($rutaImagen);
                $producto->setMargenGanancia(floatval($_POST["margenProductoEditar"]));

                if ($producto->actualizar()) {
                    echo json_encode(["status" => "success", "mensaje" => "Catálogo de producto actualizado."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al actualizar en la base de datos."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "El nombre contiene caracteres inválidos."]);
            }
            exit();
        }
    }

    public static function ctrEliminarProducto() {
        if (isset($_POST["idProductoEliminar"])) {
            $producto = new Productos();
            $producto->setId($_POST["idProductoEliminar"]);

            if ($producto->desactivar()) {
                echo json_encode(["status" => "success", "mensaje" => "Producto retirado del catálogo activo."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al desactivar."]);
            }
            exit();
        }
    }

    public static function ctrActivarProducto() {
        if (isset($_POST["idProductoActivar"])) {
            $producto = new Productos();
            $producto->setId($_POST["idProductoActivar"]);

            if ($producto->activar()) {
                echo json_encode(["status" => "success", "mensaje" => "Producto reactivado correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al reactivar."]);
            }
            exit();
        }
    }
    /* ==============================================================
       BUSCADOR DINÁMICO DE PRODUCTOS PARA SELECT2 (AJAX)
       ============================================================== */
    public static function ctrBuscarProductosAjax() {
        if(isset($_POST["buscarProductoSelect"])) {
            $busqueda = $_POST["buscarProductoSelect"];
            
            // Usamos LIKE para buscar tanto por nombre como por código de barras
            $stmt = Conexion::conectar()->prepare("SELECT id, codigo_barras, nombre, costo_usdt FROM productos WHERE nombre LIKE :busqueda OR codigo_barras LIKE :busqueda LIMIT 20");
            $stmt->bindValue(":busqueda", "%$busqueda%", PDO::PARAM_STR);
            $stmt->execute();
            
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Devolvemos el resultado en formato JSON para que JavaScript lo lea
            echo json_encode($productos);
            exit();
        }
    }
}