<?php
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}
?>

<!-- =======================================
 ESCUDO ANTI-F5 (Evita el reenvío de formulario)
======================================== -->
<script>
    if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
    }
</script>

<div class="content-wrapper">
    <section class="content-header mb-3">
        <div class="container-fluid px-0">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold"><i class="fas fa-hand-holding-usd text-success me-2"></i> Ingresos Extras</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end bg-transparent p-0 m-0">
                        <li class="breadcrumb-item"><a href="index.php?ruta=dashboard">Inicio</a></li>
                        <li class="breadcrumb-item active">Ingresos</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="card-reditus p-4 shadow-sm border-top border-success border-3">
            <div class="card-header">
                <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarIngreso">
                    <i class="fas fa-plus mb-1"></i> Registrar Ingreso
                </button>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped dt-responsive tablaIngresos" width="100%">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:10px">#</th>
                            <th>Concepto</th>
                            <th>Monto</th>
                            <th>Moneda</th>
                            <th>Método de Pago</th>
                            <th>Referencia</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ingresos = IngresosControlador::ctrMostrarIngresos(null, null);
                        foreach ($ingresos as $key => $value) {
                            
                            $estado = ($value["estado"] == 1) ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Anulado</span>';
                            $simbolo = ($value["moneda"] == "USD") ? '$' : 'Bs';

                            // Lógica corregida para los botones (sin romper el echo)
                            $botones = '';
                            if($value["estado"] == 1){
                                $botones = '<button class="btn btn-danger btn-sm btnAnularIngreso" idIngreso="'.$value["id"].'" title="Anular Ingreso"><i class="fas fa-times"></i></button>';
                            } else {
                                $botones = '<button class="btn btn-secondary btn-sm" disabled title="Ingreso ya anulado"><i class="fas fa-times"></i></button>';
                            }

                            echo '<tr>
                                    <td>'.($key+1).'</td>
                                    <td class="text-uppercase">'.$value["concepto"].'</td>
                                    <td class="fw-bold">'.$simbolo.' '.number_format($value["monto"], 2).'</td>
                                    <td>'.$value["moneda"].'</td>
                                    <td>'.$value["metodo_pago"].'</td>
                                    <td>'.$value["referencia"].'</td>
                                    <td>'.date("d/m/Y", strtotime($value["fecha"])).'</td>
                                    <td>'.$estado.'</td>
                                    <td>
                                        <div class="btn-group">
                                            '.$botones.'
                                        </div>
                                    </td>
                                </tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- =======================================
MODAL AGREGAR INGRESO
====================================== -->
<div class="modal fade" id="modalAgregarIngreso" tabindex="-1" aria-labelledby="modalAgregarIngresoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form role="form" method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i> Registrar Nuevo Ingreso</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="box-body">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Concepto / Motivo</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-comment-dollar"></i></span>
                                <input type="text" class="form-control" name="nuevoConceptoIngreso" placeholder="Ej: Aporte de gerencia para vuelto" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Moneda</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                    <select class="form-select" name="nuevaMonedaIngreso" required>
                                        <option value="USD">Dólares (USD)</option>
                                        <option value="BS">Bolívares (BS)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Monto</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                    <input type="number" step="0.01" min="0.01" class="form-control" name="nuevoMontoIngreso" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Método de Pago</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                                    <select class="form-select" name="nuevoMetodoIngreso" required>
                                        <option value="Efectivo">Efectivo</option>
                                        <option value="Zelle">Zelle</option>
                                        <option value="Pago Movil">Pago Móvil</option>
                                        <option value="Transferencia">Transferencia</option>
                                        <option value="Punto de Venta">Punto de Venta</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Referencia</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                    <input type="text" class="form-control" name="nuevaReferenciaIngreso" placeholder="Opcional">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-success">Guardar Ingreso</button>
                </div>
                
                <?php
                    // Disparamos el controlador al enviar el formulario
                    $crearIngreso = new IngresosControlador();
                    $crearIngreso -> ctrCrearIngreso();
                ?>
            </form>
        </div>
    </div>
</div>

<?php
    // Disparamos el controlador de anulación
    $anularIngreso = new IngresosControlador();
    $anularIngreso -> ctrAnularIngreso();
?>
