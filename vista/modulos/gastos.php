<?php
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$gastos = GastosControlador::ctrMostrarGastos(null, $estadoFiltro);
?>

<div class="container-fluid py-4">
    <div class="module-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-wallet me-2 text-danger"></i> <?php echo $estadoFiltro == 1 ? "Control de Gastos Operativos" : "Egresos Anulados"; ?></h4>
            <small class="text-muted d-block mt-1">Seguimiento de todos los egresos y gastos operativos del negocio.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=gastos&estado=0" class="btn btn-outline-secondary"><i class="fas fa-eye-slash me-1"></i> Ver Anulados</a>
            <?php else: ?>
                <a href="index.php?ruta=gastos" class="btn btn-outline-secondary"><i class="fas fa-eye me-1"></i> Ver Activos</a>
            <?php endif; ?>
            <a href="index.php?ruta=gastos-crear" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Registrar Gasto</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Concepto / Descripción</th>
                            <th class="text-center">Tipo de Gasto</th>
                            <th class="text-end">Monto</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gastos as $g): ?>
                            <tr>
                                <td class="text-muted"><?php echo $g->getId(); ?></td>
                                <td class="fw-semibold"><?php echo date('d/m/Y', strtotime($g->getFecha())); ?></td>
                                <td><?php echo htmlspecialchars($g->getConcepto()); ?></td>
                                <td class="text-center">
                                    <span class="badge <?php echo $g->getTipo() == 'Fijo' ? 'badge-info' : 'badge-warning'; ?>">
                                        <?php echo $g->getTipo(); ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-danger">-$ <?php echo number_format($g->getMonto(), 2); ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=gastos-editar&idGasto=<?php echo $g->getId(); ?>" class="btn-action text-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                            <button class="btn-action text-danger btnAnularGasto" idGasto="<?php echo $g->getId(); ?>" conceptoGasto="<?php echo $g->getConcepto(); ?>" title="Anular"><i class="fas fa-ban"></i></button>
                                        <?php else: ?>
                                            <button class="btn-action text-success btnReactivarGasto" idGasto="<?php echo $g->getId(); ?>" conceptoGasto="<?php echo $g->getConcepto(); ?>" title="Restaurar"><i class="fas fa-undo"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>