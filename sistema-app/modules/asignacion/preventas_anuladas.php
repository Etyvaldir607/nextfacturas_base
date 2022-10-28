<?php

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

// Obtiene el rango de fechas
$gestion = date('Y');
// $gestion_base =  $gestion . '-01-01';
$gestion_base = date("d-m-Y", strtotime(date('Y-m-d') . "- 1 days"));
$gestion_limite = ($gestion + 16) . date('-m-d');

// Obtiene fecha inicial
$fecha_inicial = (isset($params[0])) ? $params[0] : $gestion_base;
$fecha_inicial = (is_date($fecha_inicial)) ? $fecha_inicial : $gestion_base;
$fecha_inicial = date_encode($fecha_inicial);

// Obtiene fecha final
$fecha_final = (isset($params[1])) ? $params[1] : $gestion_limite;
$fecha_final = (is_date($fecha_final)) ? $fecha_final : $gestion_limite;
$fecha_final = date_encode($fecha_final);

// Obtiene las asignaciones
// $asignaciones = $db->query("SELECT em.nombres, em.paterno, em.materno, em.id_empleado, em.fecha, em.hora, e.monto_total, SUM(e.monto_total) as total, COUNT(e.id_egreso) as registros
//                             FROM sys_empleados em
//                             LEFT JOIN inv_egresos e ON em.id_empleado = e.empleado_id
//                             WHERE e.estadoe = 2
//                             AND e.tipo = 'Preventa'
//                             AND e.preventa = 'anulado'
//                             AND e.fecha_egreso >= '$fecha_inicial'
//                             AND e.fecha_egreso <= '$fecha_final'
//                             GROUP BY e.empleado_id ORDER BY e.fecha_egreso DESC")->fetch();
$asignaciones = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, e.cargo')
                ->from('inv_egresos i')
                ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
                ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
                ->where('i.fecha_egreso >= ', $fecha_inicial)
                ->where('i.fecha_egreso <= ', $fecha_final)
                ->where('i.estadoe>',1)
                ->where_in('i.tipo', ['Preventa','No venta'])
                ->where_in('i.preventa', ['anulado', 'eliminado'])
                ->order_by('i.fecha_egreso desc, i.hora_egreso desc')
                ->fetch();

// echo $db->last_query();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_ver = in_array('preventas_ver', $permisos);
$permiso_cliente = in_array('preventas_asignar', $permisos);
$permiso_imprimir = in_array('asignacion_imprimir', $permisos);
$permiso_imprimir1 = in_array('asignacion_imprimir1', $permisos);
$permiso_imprimir2 = in_array('asignacion_imprimir2', $permisos);
$permiso_activar = in_array('asignacion_activar', $permisos);
$permiso_cambiar = true;

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-formato="<?= strtoupper($formato_textual); ?>" data-mascara="<?= $formato_numeral; ?>" data-gestion="<?= date_decode($gestion_base, $_institution['formato']); ?>">
    <h3 class="panel-title">
        <span class="glyphicon glyphicon-option-vertical"></span>
        <strong>Listar preventas anuladas</strong>
    </h3>
</div>
<div class="panel-body">
    <?php if ($permiso_cambiar) { ?>
        <div class="row">
            <div class="col-sm-8 hidden-xs">
                <div class="text-label">Para filtrar por fechas hacer clic en el siguiente botón: </div>
            </div>
            <div class="col-xs-12 col-sm-4 text-right">
                <button class="btn btn-default" data-cambiar="true">
                    <span class="glyphicon glyphicon-calendar"></span>
                    <span class="hidden-xs">Cambiar</span>
                </button>
                <a href="?/asignacion/preventas_listar" class="btn btn-primary" ><i class="glyphicon glyphicon-arrow-left"></i><span class="hidden-xs"> Listar</span></a>
            </div>
        </div>
        <hr>
    <?php } ?>
    <?php if (isset($_SESSION[temporary])) { ?>
        <div class="alert alert-<?= $_SESSION[temporary]['alert']; ?>">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?= $_SESSION[temporary]['title']; ?></strong>
            <p><?= $_SESSION[temporary]['message']; ?></p>
        </div>
        <?php unset($_SESSION[temporary]); ?>
    <?php } ?>

    <?php if ($asignaciones) { ?>
        <table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
            <thead>
                <tr class="active">
                    <th class="text-nowrap">#</th>
                    <th class="text-nowrap">Fecha</th>
                    <th class="text-nowrap">Cliente</th>
                    <th class="text-nowrap">NIT/CI</th>
                    <th class="text-nowrap">Nro. preventa</th>
                    <th class="text-nowrap">Monto total <?= escape($moneda); ?></th>
                    <th class="text-nowrap">Registros</th>
                    <th class="text-nowrap">Almacen</th>
                    <th class="text-nowrap">Tipo</th>
                    <th class="text-nowrap">Empleado</th>
                    <?php if ($permiso_ver || $permiso_eliminar) { ?>
                    <th class="text-nowrap">Opciones</th>
                    <?php } ?>
                </tr>
            </thead>
            <tfoot>
                <tr class="active">
                    <th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Fecha</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Cliente</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">NIT/CI</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Nro. preventa</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Monto total <?= escape($moneda); ?></th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Registros</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Almacen</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Tipo</th>
                    <th class="text-nowrap text-middle" data-datafilter-filter="true">Empleado</th>
                    <?php if ($permiso_ver || $permiso_eliminar) { ?>
                    <th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
                    <?php } ?>
                </tr>
            </tfoot>
            <tbody>
                <?php foreach ($asignaciones as $nro => $proforma) { ?>
                <tr>
                    <th class="text-nowrap"><?= $nro + 1; ?></th>
                    <?php
			        $fecha_hab=explode(" ",$proforma['fecha_habilitacion']);
                    ?>
					<td class="text-nowrap"><?= escape(date_decode($fecha_hab[0], $_institution['formato'])); ?> <small class="text-success"><?= escape($fecha_hab[1]); ?></small></td>
                    <td class="text-nowrap"><?= escape($proforma['nombre_cliente']); ?></td>
                    <td class="text-nowrap"><?= escape($proforma['nit_ci']); ?></td>
                    <td class="text-nowrap text-right"><?= escape($proforma['nro_nota']); ?></td>
                    <td class="text-nowrap text-right"><?= escape($proforma['monto_total']); ?></td>
                    <td class="text-nowrap text-right"><?= escape($proforma['nro_registros']); ?></td>
                    <td class="text-nowrap <?= escape(($proforma['principal'] == 'S') ? 'bg-info' : ''); ?>"><?= escape($proforma['almacen']); ?></td>

                    <td class="text-nowrap <?= escape(($proforma['preventa'] == 'anulado') ? 'bg-warning' : 'bg-danger'); ?>"><?= escape($proforma['preventa']); ?></td>

                    <td class="width-md"><?= escape($proforma['nombres'] . ' ' . $proforma['paterno'] . ' ' . $proforma['materno']); ?></td>
                    <?php if ($permiso_ver || $permiso_eliminar) { ?>
                    <td class="text-nowrap">
                        <?php if ($permiso_ver) { ?>
                        <a href="?/asignacion/preventas_ver/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Ver detalle de la proforma"><i class="glyphicon glyphicon-eye-open"></i></a>
                        <?php } ?>
                        <?php if ($permiso_ver && false) { ?>
                            <!--<a onclick="habilitar(<?= $proforma['id_egreso']; ?>)" data-toggle="tooltip" data-title="Habilitar" class="text-info"><i class="glyphicon glyphicon-check"></i></a>-->
                        <?php } ?>
                        <?php if ($permiso_eliminar) { ?>
                            <!--<a href="?/preventas/eliminar/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Eliminar proforma" data-eliminar="true"><span class="glyphicon glyphicon-trash"></span></a>-->
                        <?php } ?>
                    </td>
                    <?php } ?>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>

        <div class="alert alert-danger" role="alert">
            <strong>Advertencia!</strong>
            <p>No existen registros en la base de datos, prueba cambiando las fechas.</p>
        </div>

    <?php } ?>


</div>


<!-- modal para cambiar fechas  -->
<div id="modal_fecha" class="modal fade">
    <div class="modal-dialog">
        <form id="form_fecha" class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Cambiar fecha</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label for="inicial_fecha">Fecha inicial:</label>
                            <input type="text" name="inicial" value="<?= ($fecha_inicial != $gestion_base) ? date_decode($fecha_inicial, $_institution['formato']) : ''; ?>" id="inicial_fecha" class="form-control" autocomplete="off" data-validation="date" data-validation-format="<?= $formato_textual; ?>" data-validation-optional="true">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label for="final_fecha">Fecha final:</label>
                            <input type="text" name="final" value="<?= ($fecha_final != $gestion_limite) ? date_decode($fecha_final, $_institution['formato']) : ''; ?>" id="final_fecha" class="form-control" autocomplete="off" data-validation="date" data-validation-format="<?= $formato_textual; ?>" data-validation-optional="true">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-aceptar="true">
                    <span class="glyphicon glyphicon-ok"></span>
                    <span>Aceptar</span>
                </button>
                <button type="button" class="btn btn-default" data-cancelar="true">
                    <span class="glyphicon glyphicon-remove"></span>
                    <span>Cancelar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>
<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>

<script>
    $(function() {
        var formato = $('[data-formato]').attr('data-formato');
        var mascara = $('[data-mascara]').attr('data-mascara');
        var gestion = $('[data-gestion]').attr('data-gestion');
        var $inicial_fecha = $('#inicial_fecha');
        var $final_fecha = $('#final_fecha');

        $.validate({
            form: '#form_fecha',
            modules: 'date',
            onSuccess: function() {
                var inicial_fecha = $.trim($('#inicial_fecha').val());
                var final_fecha = $.trim($('#final_fecha').val());
                var vacio = gestion.replace(new RegExp('9', 'g'), '0');

                inicial_fecha = inicial_fecha.replace(new RegExp('\\.', 'g'), '-');
                inicial_fecha = inicial_fecha.replace(new RegExp('/', 'g'), '-');
                final_fecha = final_fecha.replace(new RegExp('\\.', 'g'), '-');
                final_fecha = final_fecha.replace(new RegExp('/', 'g'), '-');
                vacio = vacio.replace(new RegExp('\\.', 'g'), '-');
                vacio = vacio.replace(new RegExp('/', 'g'), '-');
                final_fecha = (final_fecha != '') ? ('/' + final_fecha) : '';
                inicial_fecha = (inicial_fecha != '') ? ('/' + inicial_fecha) : ((final_fecha != '') ? ('/' + vacio) : '');

                window.location = '?/asignacion/preventas_anuladas' + inicial_fecha + final_fecha;
            }
        });

        //$inicial_fecha.mask(mascara).datetimepicker({
        $inicial_fecha.datetimepicker({
            format: formato
        });

        //$final_fecha.mask(mascara).datetimepicker({
        $final_fecha.datetimepicker({
            format: formato
        });

        $inicial_fecha.on('dp.change', function(e) {
            $final_fecha.data('DateTimePicker').minDate(e.date);
        });

        $final_fecha.on('dp.change', function(e) {
            $inicial_fecha.data('DateTimePicker').maxDate(e.date);
        });

        var $form_fecha = $('#form_fecha');
        var $modal_fecha = $('#modal_fecha');

        $form_fecha.on('submit', function(e) {
            e.preventDefault();
        });

        $modal_fecha.on('show.bs.modal', function() {
            $form_fecha.trigger('reset');
        });

        $modal_fecha.on('shown.bs.modal', function() {
            $modal_fecha.find('[data-aceptar]').focus();
        });

        $modal_fecha.find('[data-cancelar]').on('click', function() {
            $modal_fecha.modal('hide');
        });

        $modal_fecha.find('[data-aceptar]').on('click', function() {
            $form_fecha.submit();
        });

        $('[data-cambiar]').on('click', function() {
            $('#modal_fecha').modal({
                backdrop: 'static'
            });
        });

        <?php if ($asignaciones) { ?>
            var table = $('#table').DataFilter({
                filter: false,
                name: 'anuladas',
                reports: 'excel|word|pdf|html'
            });
        <?php } ?>
    });

    function habilitar(id_venta){
        bootbox.confirm('Está seguro que desea habilitar la preventa? tenga en cuenta que esta acción no se podra rehacer.', function (result) {
                        if(result){
                            window.location = '?/asignacion/habilitar/' + id_venta;
                        }
                    });
    }

</script>


<?php require_once show_template('footer-advanced'); ?>