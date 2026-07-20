<?php
class IngresosControlador {

    /* ==============================================================
       1. MOSTRAR INGRESOS EXTRAORDINARIOS
       ============================================================== */
    public static function ctrMostrarIngresos($item, $valor) {
        $respuesta = Ingresos::mdlMostrarIngresos($item, $valor);
        return $respuesta;
    }

    /* ==============================================================
       2. CREAR INGRESO MANUAL (DESDE EL MODAL)
       ============================================================== */
    public static function ctrCrearIngreso() {
        if(isset($_POST["nuevoConceptoIngreso"])) {
            
            // Validación básica anti-inyección para el concepto
            if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ \-\/.]+$/', $_POST["nuevoConceptoIngreso"])) {

                $datos = [
                    "concepto" => $_POST["nuevoConceptoIngreso"],
                    "monto" => $_POST["nuevoMontoIngreso"],
                    "moneda" => $_POST["nuevaMonedaIngreso"],
                    "metodo_pago" => $_POST["nuevoMetodoIngreso"],
                    "referencia" => !empty($_POST["nuevaReferenciaIngreso"]) ? $_POST["nuevaReferenciaIngreso"] : null
                ];

                $respuesta = Ingresos::mdlCrearIngreso($datos);

                if($respuesta == "ok") {
                    echo '<script>
                        Swal.fire({
                            icon: "success",
                            title: "¡El ingreso ha sido guardado correctamente!",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then(function(result){
                            if(result.value){
                                window.location = "index.php?ruta=ingresos";
                            }
                        });
                    </script>';
                }
            } else {
                echo '<script>
                    Swal.fire({
                        icon: "error",
                        title: "¡El concepto no puede ir vacío o llevar caracteres no permitidos!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then(function(result){
                        if(result.value){
                            window.location = "index.php?ruta=ingresos";
                        }
                    });
                </script>';
            }
        }
    }

    /* ==============================================================
       3. ANULAR INGRESO
       ============================================================== */
    public static function ctrAnularIngreso() {
        
        // Verificamos si la orden viene en la URL
        if(isset($_GET["idIngresoAnular"])) {
            
            $id = $_GET["idIngresoAnular"];
            $respuesta = Ingresos::mdlAnularIngreso($id);

            if($respuesta == "ok") {
                echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "¡El ingreso ha sido anulado correctamente!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then(function(result){
                        if(result.value){
                            window.location = "index.php?ruta=ingresos";
                        }
                    });
                </script>';
            }
        }
    }
}