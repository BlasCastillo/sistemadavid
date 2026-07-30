<?php
require_once "modelo/Clientes.php";
require_once "modelo/Bitacora.php"; // Inyección Global

class ClientesControlador {

    public static function ctrMostrarClientes(?int $id = null, int $filtroEstado = 1) {
        if ($id != null) {
            return Clientes::buscarPorId($id);
        } else {
            return Clientes::leerTodos($filtroEstado);
        }
    }

    public static function ctrCrearCliente() {
        if (isset($_POST["nuevoDocCliente"]) && isset($_POST["nuevoNombreCliente"])) {
            if (preg_match('/^[a-zA-Z0-9-]+$/', $_POST["nuevoDocCliente"])) {
                
                $existe = Clientes::verificarDocumentoDuplicado($_POST["nuevoDocCliente"]);
                if ($existe) {
                    $status = ($existe->estado == 1) ? "activo" : "INACTIVO";
                    echo json_encode(["status" => "error", "mensaje" => "El documento ya pertenece a un cliente " . $status . "."]);
                    exit();
                }

                $cliente = new Clientes();
                $cliente->setDocumento($_POST["nuevoDocCliente"]);
                $cliente->setNombre($_POST["nuevoNombreCliente"]);
                $cliente->setTelefono($_POST["nuevoTelefonoCliente"] ?? null);
                $cliente->setEmail($_POST["nuevoEmailCliente"] ?? null);
                $cliente->setDireccion($_POST["nuevaDireccionCliente"] ?? null);

                if ($cliente->crear()) {
                    // BITÁCORA
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Directorio/Clientes", "Creación", "Registró al nuevo cliente: " . $_POST["nuevoNombreCliente"] . " (Doc: " . $_POST["nuevoDocCliente"] . ")");
                    
                    echo json_encode(["status" => "success", "mensaje" => "Cliente registrado con éxito."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error interno en el servidor."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "El documento contiene caracteres no permitidos."]);
            }
            exit();
        }
    }

    public static function ctrActualizarCliente() {
        if (isset($_POST["idClienteEditar"]) && isset($_POST["editarDocCliente"])) {
            if (preg_match('/^[a-zA-Z0-9-]+$/', $_POST["editarDocCliente"])) {
                
                $existe = Clientes::verificarDocumentoDuplicado($_POST["editarDocCliente"]);
                if ($existe && $existe->id != $_POST["idClienteEditar"]) {
                    echo json_encode(["status" => "error", "mensaje" => "Ya existe otro cliente con este mismo documento."]);
                    exit();
                }

                $cliente = new Clientes();
                $cliente->setId($_POST["idClienteEditar"]);
                $cliente->setDocumento($_POST["editarDocCliente"]);
                $cliente->setNombre($_POST["editarNombreCliente"]);
                $cliente->setTelefono($_POST["editarTelefonoCliente"] ?? null);
                $cliente->setEmail($_POST["editarEmailCliente"] ?? null);
                $cliente->setDireccion($_POST["editarDireccionCliente"] ?? null);

                if ($cliente->actualizar()) {
                    // BITÁCORA
                    Bitacora::registrarAccion($_SESSION["id_usuario"], "Directorio/Clientes", "Actualización", "Actualizó los datos del cliente Doc: " . $_POST["editarDocCliente"]);
                    
                    echo json_encode(["status" => "success", "mensaje" => "Datos del cliente actualizados."]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al actualizar."]);
                }
            } else {
                echo json_encode(["status" => "error", "mensaje" => "El documento contiene caracteres no permitidos."]);
            }
            exit();
        }
    }

    public static function ctrEliminarCliente() {
        if (isset($_POST["idClienteEliminar"])) {
            $cliente = new Clientes();
            $cliente->setId($_POST["idClienteEliminar"]);

            if ($cliente->desactivar()) {
                // BITÁCORA
                Bitacora::registrarAccion($_SESSION["id_usuario"], "Directorio/Clientes", "Desactivación", "Desactivó al cliente ID: " . $_POST["idClienteEliminar"]);
                
                echo json_encode(["status" => "success", "mensaje" => "Cliente desactivado correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al desactivar."]);
            }
            exit();
        }
    }

    public static function ctrActivarCliente() {
        if (isset($_POST["idClienteActivar"])) {
            $cliente = new Clientes();
            $cliente->setId($_POST["idClienteActivar"]);

            if ($cliente->activar()) {
                // BITÁCORA
                Bitacora::registrarAccion($_SESSION["id_usuario"], "Directorio/Clientes", "Reactivación", "Reactivó al cliente ID: " . $_POST["idClienteActivar"]);
                
                echo json_encode(["status" => "success", "mensaje" => "Cliente reactivado correctamente."]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Error al reactivar."]);
            }
            exit();
        }
    }
}
?>