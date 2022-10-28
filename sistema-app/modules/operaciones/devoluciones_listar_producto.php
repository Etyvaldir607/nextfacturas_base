<?php

    // Obtiene los formatos para la fecha
    $formato_textual = get_date_textual($_institution['formato']);
    $formato_numeral = get_date_numeral($_institution['formato']);
    // Obtiene el rango de fechas
    $gestion = date('Y');
    $gestion_base = date('Y-m-d');
    $gestion_limite = ($gestion + 1) . date('-m-d');

    // Obtiene fecha inicial
    $fecha_inicial = (isset($params[0])) ? $params[0] : $gestion_base;
    $fecha_inicial = (is_date($fecha_inicial)) ? $fecha_inicial : $gestion_base;
    $fecha_inicial = date_encode($fecha_inicial);

    // Obtiene fecha final
    $fecha_final = (isset($params[1])) ? $params[1] : $gestion_limite;
    $fecha_final = (is_date($fecha_final)) ? $fecha_final : $gestion_limite;
    $fecha_final = date_encode($fecha_final);


    // echo "Aqui el codigo de el listado de devoluciones por producto";

    $devoluciones = $db->query("SELECT m.*,IFNULL(concat(e.nombres,' ',e.paterno,' ',e.materno),'')AS empleado, p.nombre, a.almacen, a.principal
                            FROM(
                                SELECT i.nro_movimiento, i.id_ingreso AS id_movimiento,i.id_ingreso, d.id_detalle, i.fecha_ingreso AS fecha_movimiento, i.hora_ingreso AS hora_movimiento, i.descripcion, d.cantidad, d.costo AS monto, 'i' AS tipo, i.empleado_id, i.almacen_id, i.tipo AS modo, d.producto_id, d.lote
                                FROM inv_ingresos_detalles d
                                LEFT JOIN  inv_ingresos i ON d.ingreso_id = i.id_ingreso
                                WHERE i.tipo = 'Devolucion'
                            ) m
                            LEFT JOIN sys_empleados e ON m.empleado_id = e.id_empleado
                            LEFT JOIN inv_productos p ON m.producto_id = p.id_producto
                            LEFT JOIN inv_almacenes a ON m.almacen_id = a.id_almacen
                            WHERE m.fecha_movimiento >= '" .$fecha_inicial . "'
                            AND m.fecha_movimiento <= '" .$fecha_final . "'
                            ORDER BY m.fecha_movimiento ASC, m.hora_movimiento ASC")->fetch();

// ->where('i.fecha_ingreso >= ', $fecha_inicial)
// ->where('i.fecha_ingreso <= ', $fecha_final)

    // foreach ($devoluciones as $key => $devol) {
    //     if ($devol['modo'] == 'Devolucion') {
    //         $e_array = $db->select('id_egreso')->from('inv_egresos')->where('ingreso_id', $devol['id_ingreso'])->fetch();
    //         $ides = [];
    //         $cant_egre = 0;
    //         $cant_ingre = 0;
    //         foreach ($e_array as $key => $ea) {
    //             array_push($ides,  $ea['id_egreso']);
    //         }
    //         $d_ingreso = $db->select('SUM(cantidad) as cantidad, id_detalle')->from('inv_ingresos_detalles')->where('ingreso_id', $devol['id_ingreso'])->group_by('id_detalle')->fetch();
    //             foreach ($d_ingreso as $key => $di){
    //                 $cant_ingre = $cant_ingre + $di['cantidad'];
    //                 $d_egreso = $db->select('SUM(cantidad) as cantidad')->from('inv_egresos_detalles')->where('detalle_ingreso_id', $di['id_detalle'])->fetch_first();
    //                 $cant_egre = $cant_egre + $d_egreso['cantidad'];
    //             };
    //     }
    // }


    // echo json_encode($devoluciones); die();
    // Almacena los permisos en variables
    $permiso_cambiar = true;

?>

<?php require_once show_template('header-advanced'); ?>
<style>
    .p-3 {
        padding: 0.7em !important;
    }
</style>

<div class="panel-heading" data-formato="<?= strtoupper($formato_textual); ?>" data-mascara="<?= $formato_numeral; ?>" data-gestion="<?= date_decode($gestion_base, $_institution['formato']); ?>">
    <h3 class="panel-title">
        <span class="glyphicon glyphicon-option-vertical"></span>
        <strong>Listado de devoluciones por <u>Producto</u></strong>
    </h3>
</div>
<div class="panel-body">
    <div class="row">
        <div class="col-sm-8 hidden-xs">
            <div class="text-label">Para realizar una nota de remisión hacer clic en el siguiente botón: </div>
        </div>
        <div class="col-xs-12 col-sm-4 text-right">
            <button class="btn btn-default" data-cambiar="true"><i class="glyphicon glyphicon-calendar"></i><span class="hidden-xs"> Cambiar</span></button>
            <!-- <a href="?/operaciones/notas_imprimir/87" class="btn btn-primary" target="_blank" data-imprimir="true"><span class="glyphicon glyphicon-arrow-left"></span><span class="hidden-xs"> Listar</span></a> -->
        </div>
    </div>
    <hr>
    <div class="row">
        <?php if(count($devoluciones) > 0) { ?>
            <div class="p-3">
                <table id="table" class="table table-bordered table-condensed table-striped table-hover table-xs">
                    <thead>
                        <tr class="active">
                            <th class="text-nowrap">#</th>
                            <th class="text-nowrap">Fecha</th>
                            <th class="text-nowrap">Nro. movimiento</th>
                            <th class="text-nowrap">Producto</th>
                            <th class="text-nowrap">Lote</th>
                            <th class="text-nowrap">Cantidad</th>
                            <th class="text-nowrap">Costo</th>
                            <th class="text-nowrap">Total</th>
                            <th class="text-nowrap">Almacen</th>
                            <th class="text-nowrap">Empleado</th>
                            <th class="text-nowrap">Observacion</th>
                            <th class="text-nowrap">Opciones</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="active">
                            <th class="text-nowrap tect-middle" data-datafilter-filter="false">#</th>
                            <th class="text-nowrap tect-middle" data-datafilter-filter="true">Fecha</th>
                            <th class="text-nowrap tect-middle" data-datafilter-filter="true">Nro. movimiento</th>
                            <th class="text-nowrap tect-middle" data-datafilter-filter="true">Producto</th>
                            <th class="text-nowrap tect-middle" data-datafilter-filter="false">Lote</th>
                            <th class="text-nowrap tect-middle" data-datafilter-filter="true">Cantidad</th>
                            <th class="text-nowrap tect-middle" data-datafilter-filter="true">Costo</th>
                            <th class="text-nowrap tect-middle" data-datafilter-filter="true">Total</th>
                            <th class="text-nowrap tect-middle" data-datafilter-filter="true">Almacen</th>
                            <th class="text-nowrap tect-middle" data-datafilter-filter="true">Empleado</th>
                            <th class="text-nowrap tect-middle" data-datafilter-filter="false">Observacion</th>
                            <th class="text-nowrap tect-middle" data-datafilter-filter="false">Opciones</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php foreach ($devoluciones as $key => $devol) { ?>
                            <tr>
                                <td class="text-nowrap"><?= $key+1; ?></td>
                                <td class="text-nowrap"><?= escape(date_decode($devol['fecha_movimiento'],$_institution['formato'])); ?> <small class="text-success"><?= $devol['hora_movimiento']; ?></small></td>
                                <td class="text-nowrap"><?= $devol['nro_movimiento']; ?></td>
                                <td class="text-nowrap"><?= $devol['nombre']; ?></td>
                                <td class="text-nowrap"><?= strtoupper($devol['lote']); ?></td>
                                <td class="text-nowrap bg-danger text-right"><b><?= $devol['cantidad']; ?></b></td>
                                <td class="text-nowrap text-right"><?= number_format($devol['monto'],2,'.',','); ?></td>
                                <td class="text-nowrap text-right"><?= number_format($devol['cantidad'] * $devol['monto'] , 2,'.',','); ?></td>
                                <td class="text-nowrap <?= ($devol['principal'] == 'S') ? 'bg-info': ''; ?>"><?= $devol['almacen']; ?></td>
                                <td class="text-nowrap"><?= $devol['empleado']; ?></td>
                                <td class="text-nowrap"><?= $devol['descripcion']; ?></td>
                                <td class="text-nowrap">
                                    <a href="?/ingresos/ver/<?= $devol['id_movimiento'] ?>" target='_blank' data-toggle='tooltip' data-title='Ver detalle' >
                                        <span class='glyphicon glyphicon-eye-open'></span>
                                    </a>
                                    <?php
                                        if ($devol['modo'] == 'Devolucion') {
                                            $e_array = $db->select('id_egreso')->from('inv_egresos')->where('ingreso_id', $devol['id_ingreso'])->fetch();
                                            $ides = [];
                                            $cant_egre = 0;
                                            $cant_ingre = 0;
                                            foreach ($e_array as $key => $ea) {
                                                array_push($ides,  $ea['id_egreso']);
                                            }
                                            $d_ingreso = $db->select('SUM(cantidad) as cantidad, id_detalle, producto_id')->from('inv_ingresos_detalles')->where('ingreso_id', $devol['id_ingreso'])->where('producto_id', $devol['producto_id'])->group_by('id_detalle')->fetch();
                                                foreach ($d_ingreso as $key => $di){
                                                    $cant_ingre = $cant_ingre + $di['cantidad'];
                                                    $d_egreso = $db->select('SUM(cantidad) as cantidad')->from('inv_egresos_detalles')->where('detalle_ingreso_id', $di['id_detalle'])->where('producto_id', $di['producto_id'])->fetch_first();
                                                    $cant_egre = $cant_egre + $d_egreso['cantidad'];
                                                };
                                            if ($cant_egre < $cant_ingre) {
                                                echo "<span class='glyphicon glyphicon-bell text-danger' data-toggle='tooltip' data-title='Se debe reponer productos'></span>";
                                            }

                                        }

                                        // if ($devol['modo'] == 'Devolucion') {
                                        //     if ($cant_egre < $cant_ingre) {
                                        //         echo "<span class='glyphicon glyphicon-bell text-danger' data-toggle='tooltip' data-title='Se debe reponer productos'></span>";
                                        //     }
                                        // }
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="alert alert-danger p-3">
                <strong>Advertencia!</strong>
                <p>No existen devoluciones registradas en la base de datos.</p>
            </div>
        <?php } ?>
    </div>
</div>


<!-- Inicio modal fecha -->
<?php if ($permiso_cambiar) { ?>
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
<?php } ?>
<!-- Fin modal fecha -->


<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>

<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/moment.min.js"></script>
<script src="<?= js; ?>/moment.es.js"></script>

<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/jquery.maskedinput.min.js"></script>
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>
<script>
    $(function () {
        var table = $('#table').DataFilter({
            filter: true,
            name: 'Devoluciones',
            reports: 'excel|word|pdf|html'
        });
    });

    <?php if ($permiso_cambiar) { ?>
        var formato = $('[data-formato]').attr('data-formato');
        var mascara = $('[data-mascara]').attr('data-mascara');
        var gestion = $('[data-gestion]').attr('data-gestion');
        var $inicial_fecha = $('#inicial_fecha');
        var $final_fecha = $('#final_fecha');

        $.validate({
            form: '#form_fecha',
            modules: 'date',
            onSuccess: function () {
                var inicial_fecha = $.trim($('#inicial_fecha').val());
                var final_fecha = $.trim($('#final_fecha').val());
                var vacio = gestion.replace(new RegExp('9', 'g'), '0');

                inicial_fecha = inicial_fecha.replace(new RegExp('\\.', 'g'), '-');
                inicial_fecha = inicial_fecha.replace(new RegExp('/', 'g'), '-');
                final_fecha = final_fecha.replace(new RegExp('\\.', 'g'), '-');
                final_fecha = final_fecha.replace(new RegExp('/', 'g'), '-');
                vacio = vacio.replace(new RegExp('\\.', 'g'), '-');
                vacio = vacio.replace(new RegExp('/', 'g'), '-');
                final_fecha = (final_fecha != '') ? ('/' + final_fecha ) : '';
                inicial_fecha = (inicial_fecha != '') ? ('/' + inicial_fecha) : ((final_fecha != '') ? ('/' + vacio) : '');

                window.location = '?/operaciones/devoluciones_listar_producto' + inicial_fecha + final_fecha;
            }
        });

        $inicial_fecha.datetimepicker({
            format: formato
        });

        $final_fecha.datetimepicker({
            format: formato
        });

        $inicial_fecha.on('dp.change', function (e) {
            $final_fecha.data('DateTimePicker').minDate(e.date);
        });

        $final_fecha.on('dp.change', function (e) {
            $inicial_fecha.data('DateTimePicker').maxDate(e.date);
        });

        var $form_fecha = $('#form_fecha');
        var $modal_fecha = $('#modal_fecha');

        $form_fecha.on('submit', function (e) {
            e.preventDefault();
        });

        $modal_fecha.on('show.bs.modal', function () {
            $form_fecha.trigger('reset');
        });

        $modal_fecha.on('shown.bs.modal', function () {
            $modal_fecha.find('[data-aceptar]').focus();
        });

        $modal_fecha.find('[data-cancelar]').on('click', function () {
            $modal_fecha.modal('hide');
        });

        $modal_fecha.find('[data-aceptar]').on('click', function () {
            $form_fecha.submit();
        });

        $('[data-cambiar]').on('click', function () {
            $('#modal_fecha').modal({
                backdrop: 'static'
            });
        });
	<?php } ?>

</script>
<?php require_once show_template('footer-advanced');
