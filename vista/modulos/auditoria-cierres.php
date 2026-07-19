<?php
// Validar inicio de sesión
if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}

// 1. RESTRICCIÓN DE ACCESO (Basado en tu menú)
$permisos = $_SESSION["permisos"] ?? [];
$modoDios = ($_SESSION["rol_id"] == 1) || in_array("all", $permisos);

if (!$modoDios && !in_array("auditar_cierres", $permisos)) {
    echo '<script>
        Swal.fire({
            icon: "error",
            title: "Acceso Denegado",
            text: "No tienes los permisos necesarios para auditar cajas.",
            showConfirmButton: true,
            confirmButtonText: "Cerrar"
        }).then(function(result){
            if (result.value) {
                window.location = "index.php?ruta=dashboard";
            }
        });
    </script>';
    exit;
}

// 2. SOLICITAMOS LOS DATOS AL CONTROLADOR
$cierres = CierresControlador::ctrMostrarCierres(null, null);
?>

<div class="content-wrapper">
    <section class="content-header mb-3">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 fw-bold"><i class="fas fa-balance-scale text-warning me-2"></i> Auditoría de Cierres (Reportes X)</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end bg-transparent p-0 m-0">
                        <li class="breadcrumb-item"><a href="index.php?ruta=dashboard">Inicio</a></li>
                        <li class="breadcrumb-item active">Auditoría Cierres</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="card shadow-sm border-top border-warning border-3">
            <div class="card-body">
                
                <table class="table table-bordered table-striped dt-responsive tabla-auditoria w-100">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 10px">N°</th>
                            <th>Cajero</th>
                            <th>Fecha Apertura</th>
                            <th>Fecha Cierre</th>
                            <th>Estado Caja</th>
                            <th>Autorizado Por</th>
                            <th style="width: 80px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cierres as $cierre): ?>
                            <tr>
                                <td class="fw-bold"><?php echo str_pad($cierre["id"], 6, "0", STR_PAD_LEFT); ?></td>
                                <td><span class="badge bg-primary fs-6">@<?php echo $cierre["cajero_nombre"]; ?></span></td>
                                <td><?php echo date("d/m/Y h:i A", strtotime($cierre["fecha_apertura"])); ?></td>
                                <td><?php echo date("d/m/Y h:i A", strtotime($cierre["fecha_cierre"])); ?></td>
                                
                                <td class="text-center">
                                    <?php if ($cierre["estado"] == "Cuadrado"): ?>
                                        <span class="badge bg-success py-2 px-3"><i class="fas fa-check-circle me-1"></i> Cuadrado</span>
                                    <?php elseif ($cierre["estado"] == "Ajustado"): ?>
                                        <span class="badge bg-info text-dark py-2 px-3"><i class="fas fa-check-double me-1"></i> Ajustado</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger py-2 px-3"><i class="fas fa-exclamation-triangle me-1"></i> Descuadre</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php 
                                        if (!empty($cierre["autorizador_nombre"])) {
                                            echo '<span class="text-muted fw-bold"><i class="fas fa-user-shield me-1"></i> ' . $cierre["autorizador_nombre"] . '</span>';
                                        } else {
                                            echo '<span class="text-muted fst-italic">N/A</span>';
                                        }
                                    ?>
                                </td>

                                <td>
                                    <!-- Botón directo para reimprimir el Reporte X -->
                                    <div class="btn-group">
                                        <a href="ticket-cierre.php?idCierre=<?php echo $cierre["id"]; ?>" target="_blank" class="btn btn-info btn-sm border-0 shadow-sm" title="Reimprimir Reporte X">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <!-- Aquí luego agregaremos el botón para ver el detalle de las facturas (Lupa) -->
                                        <button class="btn btn-warning btn-sm border-0 shadow-sm btnVerDetalleCierre" idCierre="<?php echo $cierre["id"]; ?>" title="Ver Desglose de Caja">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </section>
</div>

<!-- =======================================================
     MODAL: DESGLOSE DEL CIERRE DE CAJA
======================================================== -->
<div class="modal fade" id="modalDetalleCierre" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fas fa-search-dollar me-2"></i> Desglose de Arqueo #<span id="detCierreID"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <!-- Aquí se inyectará el contenido vía AJAX -->
                <div id="cuerpoDetalleCierre" class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x text-warning"></i>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

