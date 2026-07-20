<?php
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}
?>
<div class="content-wrapper">
    <section class="content-header mb-3">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold"><i class="fas fa-history text-secondary me-2"></i> Historial de Cierres Z</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end bg-transparent p-0 m-0">
                        <li class="breadcrumb-item"><a href="index.php?ruta=dashboard">Inicio</a></li>
                        <li class="breadcrumb-item active">Historial Z</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="card shadow-sm border-top border-secondary border-3">
            <div class="card-body">
                <table class="table table-bordered table-striped dt-responsive tablaHistorialZ" width="100%">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:10px">Ticket</th>
                            <th>Fecha y Hora</th>
                            <th>Autorizador</th>
                            <th>Gastos Operativos</th>
                            <th>Ingresos Extras</th>
                            <th>Efectivo Físico ($)</th>
                            <th>Efectivo Físico (Bs)</th>
                            <th>Punto Venta (Bs)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $cierresZ = CierreZControlador::ctrMostrarCierresZ(null, null);
                        foreach ($cierresZ as $key => $value) {
                            echo '<tr>
                                    <td class="fw-bold">#'.str_pad($value["id"], 6, "0", STR_PAD_LEFT).'</td>
                                    <td>'.date("d/m/Y H:i", strtotime($value["fecha_cierre"])).'</td>
                                    <td><span class="badge bg-primary">@'.strtoupper($value["nombre_gerente"]).'</span></td>
                                    <td class="text-danger fw-bold">-$ '.number_format($value["gastos_totales"], 2).'</td>
                                    <td class="text-success fw-bold">+$ '.number_format($value["ingresos_extras"], 2).'</td>
                                    <td>$ '.number_format($value["efectivo_caja_usd"], 2).'</td>
                                    <td>Bs '.number_format($value["efectivo_caja_bs"], 2).'</td>
                                    <td>Bs '.number_format($value["punto_venta_bs"], 2).'</td>
                                    <td>
                                        <button class="btn btn-secondary btn-sm btnImprimirZ shadow-sm" idCierreZ="'.$value["id"].'">
                                            <i class="fas fa-print"></i>
                                        </button>
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