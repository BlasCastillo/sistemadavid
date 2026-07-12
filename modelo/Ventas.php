<?php
require_once "config/conexion.php";

class Ventas {

    /* ==============================================================
       1. GESTIÓN DEL CARRITO TEMPORAL Y SUSPENSIONES
       ============================================================== */
       
    // Agregar un producto al carrito (Estado Activo: identificador_cliente = NULL)
    public static function agregarTemporal(int $usuario_id, int $producto_id, int $cantidad, float $precio_venta, float $descuento = 0) {
        $stmt = Conexion::conectar()->prepare("INSERT INTO ventas_temporales (usuario_id, producto_id, cantidad, precio_venta_usdt, descuento_aplicado) VALUES (:usuario_id, :producto_id, :cantidad, :precio_venta, :descuento)");
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(":producto_id", $producto_id, PDO::PARAM_INT);
        $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
        $stmt->bindParam(":precio_venta", $precio_venta, PDO::PARAM_STR);
        $stmt->bindParam(":descuento", $descuento, PDO::PARAM_STR);
        return $stmt->execute();
    }

    // Leer el carrito (Si identificador es nulo, trae el activo. Si tiene cédula, trae el suspendido)
    public static function leerTemporales(int $usuario_id, $identificador_cliente = null) {
        $sql = "SELECT t.*, p.nombre as producto_nombre, p.codigo_barras, p.stock 
                FROM ventas_temporales t 
                INNER JOIN productos p ON t.producto_id = p.id 
                WHERE t.usuario_id = :usuario_id";
        
        if ($identificador_cliente == null) {
            $sql .= " AND t.identificador_cliente IS NULL";
        } else {
            $sql .= " AND t.identificador_cliente = :identificador";
        }

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        
        if ($identificador_cliente != null) {
            $stmt->bindParam(":identificador", $identificador_cliente, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function eliminarTemporal(int $id) {
        $stmt = Conexion::conectar()->prepare("DELETE FROM ventas_temporales WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Actualizar cantidad manualmente
    public static function actualizarCantidad(int $id_temporal, int $nueva_cantidad) {
        $stmt = Conexion::conectar()->prepare("UPDATE ventas_temporales SET cantidad = :cantidad WHERE id = :id");
        $stmt->bindParam(":cantidad", $nueva_cantidad, PDO::PARAM_INT);
        $stmt->bindParam(":id", $id_temporal, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Verificar si un producto ya está en el carrito activo para sumar cantidad en vez de duplicar fila
    public static function verificarProductoActivo(int $usuario_id, int $producto_id) {
        $stmt = Conexion::conectar()->prepare("SELECT id, cantidad FROM ventas_temporales WHERE usuario_id = :usuario_id AND producto_id = :producto_id AND identificador_cliente IS NULL");
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(":producto_id", $producto_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Suspender Factura (Asigna la cédula a los items nulos del usuario)
    public static function suspenderCarrito(int $usuario_id, string $cedula) {
        $stmt = Conexion::conectar()->prepare("UPDATE ventas_temporales SET identificador_cliente = :cedula WHERE usuario_id = :usuario_id AND identificador_cliente IS NULL");
        $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Recuperar Factura (Quita la cédula y los vuelve Activos)
    public static function recuperarCarrito(int $usuario_id, string $cedula) {
        $conexion = Conexion::conectar();
        // Primero, por seguridad, vaciamos el carrito activo actual si lo hubiera
        $stmtLimpiar = $conexion->prepare("DELETE FROM ventas_temporales WHERE usuario_id = :usuario_id AND identificador_cliente IS NULL");
        $stmtLimpiar->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmtLimpiar->execute();

        // Ahora activamos el suspendido
        $stmt = $conexion->prepare("UPDATE ventas_temporales SET identificador_cliente = NULL WHERE usuario_id = :usuario_id AND identificador_cliente = :cedula");
        $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Obtener lista de facturas suspendidas de un usuario
    public static function listarSuspendidas(int $usuario_id) {
        $stmt = Conexion::conectar()->prepare("SELECT identificador_cliente, SUM(precio_venta_usdt * cantidad) as total, COUNT(id) as items FROM ventas_temporales WHERE usuario_id = :usuario_id AND identificador_cliente IS NOT NULL GROUP BY identificador_cliente");
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /* ==============================================================
       2. CREACIÓN EXPRESS DE CLIENTES
       ============================================================== */
    public static function crearClienteExpress(string $documento, string $nombre, string $telefono, string $email, string $direccion) {
        $conexion = Conexion::conectar();
        
        // Verificamos si ya existe
        $stmtCheck = $conexion->prepare("SELECT id FROM clientes WHERE documento = :documento");
        $stmtCheck->bindParam(":documento", $documento, PDO::PARAM_STR);
        $stmtCheck->execute();
        
        // Forzamos explícitamente a que lo traiga como OBJETO para evitar el error stdClass
        $existe = $stmtCheck->fetch(PDO::FETCH_OBJ);

        // CORRECCIÓN APLICADA: Ahora usamos la flecha -> para leer el objeto
        if ($existe) { return $existe->id; }

        // Si no existe, lo creamos
        $stmt = $conexion->prepare("INSERT INTO clientes (documento, nombre, telefono, email, direccion, estado) VALUES (:documento, :nombre, :telefono, :email, :direccion, 1)");
        $stmt->bindParam(":documento", $documento, PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->bindParam(":telefono", $telefono, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $direccion, PDO::PARAM_STR);
        
        if ($stmt->execute()) { return $conexion->lastInsertId(); }
        return false;
    }

    /* ==============================================================
       3. PROCESAMIENTO DE LA VENTA (TRANSACCIÓN ACID MULTIPAGO)
       ============================================================== */
    public static function procesarVentaFinal($datosCabecera, $datosDetalle, $datosPagos) {
        $conexion = Conexion::conectar();
        
        try {
            $conexion->beginTransaction();

            // A. Guardar Cabecera
            $stmt = $conexion->prepare("INSERT INTO ventas (usuario_id, cliente_id, numero_factura, tasa_bcv, total_usdt, total_bs, estado, fecha_venta) VALUES (:usuario_id, :cliente_id, :numero_factura, :tasa_bcv, :total_usdt, :total_bs, :estado, NOW())");
            $stmt->bindParam(":usuario_id", $datosCabecera["usuario_id"], PDO::PARAM_INT);
            $stmt->bindParam(":cliente_id", $datosCabecera["cliente_id"], PDO::PARAM_INT);
            $stmt->bindParam(":numero_factura", $datosCabecera["numero_factura"], PDO::PARAM_STR);
            $stmt->bindParam(":tasa_bcv", $datosCabecera["tasa_bcv"], PDO::PARAM_STR);
            $stmt->bindParam(":total_usdt", $datosCabecera["total_usdt"], PDO::PARAM_STR);
            $stmt->bindParam(":total_bs", $datosCabecera["total_bs"], PDO::PARAM_STR);
            $stmt->bindParam(":estado", $datosCabecera["estado"], PDO::PARAM_STR);
            $stmt->execute();
            
            $venta_id = $conexion->lastInsertId();

            // B. Procesar Detalles y Descontar Inventario
            foreach ($datosDetalle as $item) {
                $stmtDetalle = $conexion->prepare("INSERT INTO ventas_detalle (venta_id, producto_id, cantidad, precio_unitario_usdt, descuento_usdt) VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario, :descuento)");
                $stmtDetalle->bindParam(":venta_id", $venta_id, PDO::PARAM_INT);
                $stmtDetalle->bindParam(":producto_id", $item->producto_id, PDO::PARAM_INT);
                $stmtDetalle->bindParam(":cantidad", $item->cantidad, PDO::PARAM_INT);
                $stmtDetalle->bindParam(":precio_unitario", $item->precio_venta_usdt, PDO::PARAM_STR);
                $stmtDetalle->bindParam(":descuento", $item->descuento_aplicado, PDO::PARAM_STR);
                $stmtDetalle->execute();

                // Restar del stock general
                $stmtStock = $conexion->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :producto_id");
                $stmtStock->bindParam(":cantidad", $item->cantidad, PDO::PARAM_INT);
                $stmtStock->bindParam(":producto_id", $item->producto_id, PDO::PARAM_INT);
                $stmtStock->execute();
            }

            // C. Procesar Matriz de Multipagos
            foreach ($datosPagos as $pago) {
                $stmtPago = $conexion->prepare("INSERT INTO ventas_pagos (venta_id, metodo_pago, moneda, monto_pagado, referencia) VALUES (:venta_id, :metodo_pago, :moneda, :monto_pagado, :referencia)");
                $stmtPago->bindParam(":venta_id", $venta_id, PDO::PARAM_INT);
                $stmtPago->bindParam(":metodo_pago", $pago["metodo"], PDO::PARAM_STR);
                $stmtPago->bindParam(":moneda", $pago["moneda"], PDO::PARAM_STR);
                $stmtPago->bindParam(":monto_pagado", $pago["monto"], PDO::PARAM_STR);
                $stmtPago->bindParam(":referencia", $pago["referencia"], PDO::PARAM_STR);
                $stmtPago->execute();
            }

            // D. Vaciar Carrito Activo
            $stmtVaciar = $conexion->prepare("DELETE FROM ventas_temporales WHERE usuario_id = :usuario_id AND identificador_cliente IS NULL");
            $stmtVaciar->bindParam(":usuario_id", $datosCabecera["usuario_id"], PDO::PARAM_INT);
            $stmtVaciar->execute();

            $conexion->commit();
            return $venta_id; // Retornamos el ID para generar el PDF inmediatamente

        } catch (Exception $e) {
            $conexion->rollBack();
            return false;
        }
    }

    /* ==============================================================
       4. UTILIDAD: GENERACIÓN DE CARPETAS PARA PDF
       ============================================================== */
    public static function crearRutaFacturaPDF(string $nombreCajero): string {
        $fechaHoy = date("Y-m-d");
        // Reemplazamos espacios por guiones bajos para evitar problemas en URLs o Windows
        $cajeroLimpio = str_replace(' ', '_', $nombreCajero); 
        
        $rutaBase = "Facturacion/" . $fechaHoy . "/" . $cajeroLimpio . "/";

        // Si la carpeta no existe, PHP la crea con permisos de escritura/lectura
        if (!file_exists($rutaBase)) {
            mkdir($rutaBase, 0777, true);
        }

        return $rutaBase;
    }

    /* ==============================================================
       5. LECTURA DE DATOS PARA EL TICKET PDF
       ============================================================== */
    public static function leerVentaCabecera(int $id_venta) {
        $stmt = Conexion::conectar()->prepare("SELECT v.*, c.documento as cliente_doc, c.nombre as cliente_nombre, c.direccion as cliente_direccion, u.usuario as cajero_nombre FROM ventas v INNER JOIN clientes c ON v.cliente_id = c.id INNER JOIN usuarios u ON v.usuario_id = u.id WHERE v.id = :id");
        $stmt->bindParam(":id", $id_venta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public static function leerVentaDetalle(int $id_venta) {
        $stmt = Conexion::conectar()->prepare("SELECT d.*, p.codigo_barras, p.nombre FROM ventas_detalle d INNER JOIN productos p ON d.producto_id = p.id WHERE d.venta_id = :id");
        $stmt->bindParam(":id", $id_venta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function leerVentaPagos(int $id_venta) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM ventas_pagos WHERE venta_id = :id");
        $stmt->bindParam(":id", $id_venta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
?>