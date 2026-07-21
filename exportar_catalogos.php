<?php
session_start();
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    exit("Acceso denegado.");
}

require_once "config/conexion.php";
$conexion = Conexion::conectar();
$modulo = isset($_GET["modulo"]) ? $_GET["modulo"] : "";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Pragma: no-cache");
header("Expires: 0");
echo '<meta charset="utf-8"><table border="1">';

switch ($modulo) {
    
    // ==========================================
    // 1. INVENTARIO DE PRODUCTOS
    // ==========================================
    case 'productos':
        header("Content-Disposition: attachment; filename=Inventario_Productos_" . date('Ymd') . ".xls");
        
        echo '<tr><th colspan="6" style="background-color:#2c3e50; color:white; font-size:16px;">INVENTARIO GENERAL DE PRODUCTOS</th></tr>';
        echo '<tr style="background-color:#ecf0f1;">
                <th>Código Barras</th>
                <th>Descripción</th>
                <th>Stock</th>
                <th>Costo (USD)</th>
                <th>Precio Venta (USD)</th>
                <th>Estado</th>
              </tr>';
        
        $stmt = $conexion->prepare("SELECT codigo_barras, nombre, stock, costo_usdt, margen_ganancia, estado FROM productos ORDER BY nombre ASC");
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($productos as $p) {
            $precioVenta = $p["costo_usdt"] + ($p["costo_usdt"] * ($p["margen_ganancia"] / 100));
            $estado = ($p["estado"] == 1) ? 'Activo' : 'Inactivo';
            echo '<tr>
                    <td style="mso-number-format:\'@\';">' . $p["codigo_barras"] . '</td>
                    <td>' . utf8_decode($p["nombre"]) . '</td>
                    <td>' . $p["stock"] . '</td>
                    <td>' . str_replace('.', ',', round($p["costo_usdt"], 2)) . '</td>
                    <td>' . str_replace('.', ',', round($precioVenta, 2)) . '</td>
                    <td>' . $estado . '</td>
                  </tr>';
        }
        break;

    // ==========================================
    // 2. DIRECTORIO DE CLIENTES
    // ==========================================
    case 'clientes':
        header("Content-Disposition: attachment; filename=Directorio_Clientes_" . date('Ymd') . ".xls");
        
        echo '<tr><th colspan="5" style="background-color:#2c3e50; color:white; font-size:16px;">DIRECTORIO DE CLIENTES</th></tr>';
        echo '<tr style="background-color:#ecf0f1;">
                <th>Documento (C.I/RIF)</th>
                <th>Nombre / Razón Social</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Dirección</th>
              </tr>';
        
        $stmt = $conexion->prepare("SELECT documento, nombre, telefono, email, direccion FROM clientes ORDER BY nombre ASC");
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($clientes as $c) {
            echo '<tr>
                    <td style="mso-number-format:\'@\';">' . $c["documento"] . '</td>
                    <td>' . utf8_decode($c["nombre"]) . '</td>
                    <td style="mso-number-format:\'@\';">' . $c["telefono"] . '</td>
                    <td>' . $c["email"] . '</td>
                    <td>' . utf8_decode($c["direccion"]) . '</td>
                  </tr>';
        }
        break;

    // ==========================================
    // 3. DIRECTORIO DE PROVEEDORES
    // ==========================================
    case 'proveedores':
        header("Content-Disposition: attachment; filename=Directorio_Proveedores_" . date('Ymd') . ".xls");
        
        echo '<tr><th colspan="5" style="background-color:#2c3e50; color:white; font-size:16px;">DIRECTORIO DE PROVEEDORES</th></tr>';
        echo '<tr style="background-color:#ecf0f1;">
                <th>Documento (RIF)</th>
                <th>Razón Social</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Dirección</th>
              </tr>';
        
        // Ajustado a la columna real 'razon_social' de tu tabla proveedores
        $stmt = $conexion->prepare("SELECT documento, razon_social, telefono, email, direccion FROM proveedores ORDER BY razon_social ASC");
        $stmt->execute();
        $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($proveedores as $p) {
            echo '<tr>
                    <td style="mso-number-format:\'@\';">' . $p["documento"] . '</td>
                    <td>' . utf8_decode($p["razon_social"]) . '</td>
                    <td style="mso-number-format:\'@\';">' . $p["telefono"] . '</td>
                    <td>' . $p["email"] . '</td>
                    <td>' . utf8_decode($p["direccion"]) . '</td>
                  </tr>';
        }
        break;

    // ==========================================
    // 4. CUENTAS POR PAGAR (CxP - Usando la tabla real cuentas_por_pagar)
    // ==========================================
    case 'cxp':
        header("Content-Disposition: attachment; filename=Cuentas_Por_Pagar_" . date('Ymd') . ".xls");
        
        echo '<tr><th colspan="6" style="background-color:#c0392b; color:white; font-size:16px;">ESTADO DE CUENTAS POR PAGAR (CxP)</th></tr>';
        echo '<tr style="background-color:#ecf0f1;">
                <th>ID Registro</th>
                <th>Proveedor</th>
                <th>Deuda Total (USD)</th>
                <th>Saldo Restante (USD)</th>
                <th>Fecha Vencimiento</th>
                <th>Estado</th>
              </tr>';
        
        // Consultamos directamente tu tabla cuentas_por_pagar vinculada con proveedores usando razon_social
        $stmt = $conexion->prepare("SELECT cp.id, p.razon_social, cp.total_deuda_usdt, cp.saldo_restante_usdt, cp.fecha_vencimiento, cp.estado 
                                    FROM cuentas_por_pagar cp 
                                    INNER JOIN proveedores p ON cp.proveedor_id = p.id 
                                    WHERE cp.saldo_restante_usdt > 0 
                                    ORDER BY cp.fecha_vencimiento ASC");
        $stmt->execute();
        $cxp = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cxp as $c) {
            echo '<tr>
                    <td style="mso-number-format:\'@\';">CXP-' . $c["id"] . '</td>
                    <td>' . utf8_decode($c["razon_social"]) . '</td>
                    <td>' . str_replace('.', ',', round($c["total_deuda_usdt"], 2)) . '</td>
                    <td style="color:#c0392b; font-weight:bold;">' . str_replace('.', ',', round($c["saldo_restante_usdt"], 2)) . '</td>
                    <td>' . date('d/m/Y', strtotime($c["fecha_vencimiento"])) . '</td>
                    <td>' . $c["estado"] . '</td>
                  </tr>';
        }
        break;

    // ==========================================
    // 5. CUENTAS POR COBRAR (CxC - Créditos de Clientes)
    // ==========================================
    case 'cxc':
        header("Content-Disposition: attachment; filename=Gestion_Credito_Clientes_" . date('Ymd') . ".xls");
        
        echo '<tr><th colspan="5" style="background-color:#2980b9; color:white; font-size:16px;">GESTIÓN DE CRÉDITO Y PAGOS PENDIENTES (CxC)</th></tr>';
        echo '<tr style="background-color:#ecf0f1;">
                <th>Nro. Factura</th>
                <th>Cliente</th>
                <th>Fecha de Venta</th>
                <th>Total Factura (USD)</th>
                <th>Estado de Venta</th>
              </tr>';
        
        $stmt = $conexion->prepare("SELECT v.numero_factura, c.nombre as cliente_nombre, v.fecha_venta, v.total_usdt, v.estado 
                                    FROM ventas v 
                                    INNER JOIN clientes c ON v.cliente_id = c.id 
                                    WHERE v.estado = 'Credito' 
                                    ORDER BY v.fecha_venta ASC");
        $stmt->execute();
        $cxc = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cxc as $credito) {
            echo '<tr>
                    <td style="mso-number-format:\'@\';">' . $credito["numero_factura"] . '</td>
                    <td>' . utf8_decode($credito["cliente_nombre"]) . '</td>
                    <td>' . date('d/m/Y H:i', strtotime($credito["fecha_venta"])) . '</td>
                    <td>' . str_replace('.', ',', round($credito["total_usdt"], 2)) . '</td>
                    <td style="color:#2980b9; font-weight:bold;">Crédito Pendiente</td>
                  </tr>';
        }
        break;

    default:
        echo '<tr><td>Módulo no especificado o inválido.</td></tr>';
        break;
}

echo '</table>';
?>