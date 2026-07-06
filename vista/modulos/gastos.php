<?php
$estadoFiltro = isset($_GET["estado"]) ? intval($_GET["estado"]) : 1;
$gastos = GastosControlador::ctrMostrarGastos(null, $estadoFiltro);
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-wallet text-primary me-2"></i> <?php echo $estadoFiltro == 1 ? "Control de Gastos Operativos" : "Egresos Anulados"; ?></h1>
        <div>
            <?php if($estadoFiltro == 1): ?>
                <a href="index.php?ruta=gastos&estado=0" class="btn btn-outline-secondary fw-bold me-2"><i class="fas fa-eye-slash me-1"></i> Ver Anulados</a>
            <?php else: ?>
                <a href="index.php?ruta=gastos" class="btn btn-outline-success fw-bold me-2"><i class="fas fa-eye me-1"></i> Ver Activos</a>
            <?php endif; ?>
            <a href="index.php?ruta=gastos-crear" class="btn btn-primary fw-bold shadow-sm"><i class="fas fa-plus me-1"></i> Registrar Gasto</a>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Fecha</th>
                            <th>Concepto / Descripción</th>
                            <th>Tipo de Gasto</th>
                            <th>Monto</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gastos as $g): ?>
                            <tr>
                                <td><?php echo $g->getId(); ?></td>
                                <td class="fw-bold"><?php echo date('d/m/Y', strtotime($g->getFecha())); ?></td>
                                <td class="text-dark"><?php echo htmlspecialchars($g->getConcepto()); ?></td>
                                <td>
                                    <span class="badge <?php echo $g->getTipo() == 'Fijo' ? 'bg-indigo text-white' : 'bg-teal text-white'; ?>">
                                        <?php echo $g->getTipo(); ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-danger">-$ <?php echo number_format($g->getMonto(), 2); ?></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <?php if($estadoFiltro == 1): ?>
                                            <a href="index.php?ruta=gastos-editar&idGasto=<?php echo $g->getId(); ?>" class="btn btn-sm btn-warning text-dark" title="Editar"><i class="fas fa-edit"></i></a>
                                            <button class="btn btn-sm btn-danger btnAnularGasto" idGasto="<?php echo $g->getId(); ?>" conceptoGasto="<?php echo $g->getConcepto(); ?>" title="Anular"><i class="fas fa-ban"></i></button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success btnReactivarGasto" idGasto="<?php echo $g->getId(); ?>" conceptoGasto="<?php echo $g->getConcepto(); ?>"><i class="fas fa-undo"></i> Restaurar</button>
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