<?php
$id_venta = (isset($params[0])) ? $params[0] : 0;

// Obtiene la venta
$venta = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, v.nombres nombresv, v.paterno paternov, v.materno maternov, cl.direccion')
            ->from('inv_egresos i')
            ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
            ->join('inv_clientes cl', 'i.cliente_id = cl.id_cliente', 'left')
            ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
            ->join('sys_empleados v', 'i.vendedor_id = v.id_empleado', 'left')
            ->where('id_egreso', $id_venta)
            ->fetch_first();

// Verifica si existe el egreso
if (!$venta || $venta['preventa'] == 'anulado') {
	// Error return back with notiication warning
    set_notification('warning', 'Accion insatisfactoria', 'Por favor seleccione una preventa válida...');
    return redirect('?/asignacion/preventas_listar');
}

// Obtiene los detalles
$detalles = $db->select('d.*, p.codigo, p.nombre, p.nombre_factura')
                ->from('inv_egresos_detalles d')
                ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
                ->where('d.egreso_id', $id_venta)
                ->order_by('id_detalle asc')
                ->fetch();

// Obtiene los distribuidores
$distribuidores = $db->query("SELECT CONCAT(e.nombres, ' ', e.paterno, ' ', e.materno) as empleado, e.id_empleado
                            FROM sys_users u
                            LEFT JOIN sys_empleados e ON u.persona_id = e.id_empleado
                            WHERE u.rol_id = 4")->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

$permiso_listar = true;
// $permiso_editar = true;

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_editar = in_array('preventas_actualizar', $permisos);
$permiso_habilitar = in_array('preventas_habilitar', $permisos);
$permiso_habilitar2 = in_array('habilitar', $permisos);
$permiso_anular = in_array('anular', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-venta="<?= $id_venta; ?>" data-servidor="<?= ip_local . name_project . '/factura.php'; ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Detalles de la preventa</strong>
	</h3>
</div>
<div class="panel-body">
    <?php if ($permiso_listar) { ?>
        <div class="row">
            <div class="col-xs-12 col-sm-12 text-right">
				<?php if($permiso_anular && $venta['preventa'] != 'anulado') { ?>
                    <a onclick="anular(<?= $id_venta ?>)" data-toggle='tooltip' data-title='Anular preventa' class="btn btn-danger"><i class='glyphicon glyphicon-ban-circle'></i> Anular preventa</a>
                <?php } ?>
                <?php if($permiso_habilitar2 && $venta['preventa'] != 'habilitado') { ?>
                    <a onclick="habilitar(<?= $id_venta ?>)" data-toggle='tooltip' data-title='Habilitar preventa' class="btn btn-success"><i class='glyphicon glyphicon-thumbs-up'></i> Habilitar preventa</a>
                <?php } ?>
				<?php if($permiso_editar) { ?>
                    <a href="?/asignacion/preventas_editar/<?= $id_venta ?>" data-toggle='tooltip' data-title='Edita preventa' class="btn btn-warning"><i class='glyphicon glyphicon-edit'></i> Editar preventa</a>
                <?php } ?>

                <a href="?/asignacion/preventas_listar" class="btn btn-primary" ><i class="glyphicon glyphicon-arrow-left"></i><span class="hidden-xs"> Listar</span></a>
            </div>
        </div>
        <hr>
	<?php } ?>
	<?php if (isset($_SESSION[temporary])) { ?>
		<div class='alert alert-<?= ($_SESSION[temporary]['alert'])?$_SESSION[temporary]['alert']:$_SESSION[temporary]['type']; ?>'>
			<button type='button' class='close' data-dismiss='alert'>&times;</button>
			<strong><?= $_SESSION[temporary]['title']; ?></strong>
			<p><?= ($_SESSION[temporary]['message'])?$_SESSION[temporary]['message']:$_SESSION[temporary]['content']; ?></p>
		</div>
		<?php unset($_SESSION[temporary]); ?>
	<?php } ?>
    <div class="row">
        <div class="col-md-6">
            <?php if($permiso_habilitar2 && $venta['preventa'] != 'habilitado') { ?>
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="glyphicon glyphicon-user"></i> Asignación de la preventa</h3>
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-info" role="alert">
                            <strong>Información</strong>
                            <hr>
                            <p>Para poder asignar un <b>Distribuidor</b> a la preventa, primero es necesario que habilite la preventa. <br> 
                                Para habilitar la preventa Haga click en el botón <a onclick="habilitar(<?= $id_venta ?>)" data-toggle='tooltip' data-title='Habilitar preventa'> <i class='glyphicon glyphicon-thumbs-up'></i> Habilitar preventa</a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php } else { ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Información</strong> 
                    <hr>
                    <p>Usted no tiene los permisos para habilitar esta preventa para asignacion de distribuidor.</p>
                </div>
            <?php } ?>
			<?php if($permiso_anular && $venta['preventa'] != 'anulado') { ?>
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="glyphicon glyphicon-user"></i> Anulación de la preventa</h3>
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-danger" role="alert">
                            <strong>Información</strong>
                            <hr>
                            <p>
                                Para <b>Anular</b> la preventa Haga click en el botón <a onclick="anular(<?= $id_venta ?>)" data-toggle='tooltip' data-title='Anular preventa'> <i class='glyphicon glyphicon-ban-circle'></i> <b>Anular preventa</b></a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php } else { ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Información</strong> 
                    <hr>
                    <p>Usted no tiene los permisos para anular la preventa o la preventa ya fue anulada.</p>
                </div>
            <?php } ?>
        </div>
        <div class="col-md-6">
            <div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><i class="glyphicon glyphicon-log-out"></i> Información de la preventa</h3>
				</div>
				<div class="panel-body">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-3 control-label">Fecha y hora:</label>
							<div class="col-md-9">
								<?php
						        $fecha_hab=explode(" ",$venta['fecha_egreso']);
						        $hora_hab=explode(" ",$venta['hora_egreso']);
						        ?>
								<p class="form-control-static"><?= escape(date_decode($fecha_hab[0], $_institution['formato'])); ?> <small class="text-success"><?= escape($hora_hab[0]); ?></small></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Cliente:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nombre_cliente']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">NIT / CI:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nit_ci']); ?></p>
							</div>
						</div>
                        <div class="form-group">
							<label class="col-md-3 control-label">Direccion:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['direccion']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Tipo de egreso:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['tipo']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Observacion:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['descripcion_venta']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Monto total:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape(number_format($venta['monto_total'],2,',','.')); ?></p>
							</div>
						</div>
						<!--<div class="form-group">-->
						<!--	<label class="col-md-3 control-label">Número de registros:</label>-->
						<!--	<div class="col-md-9">-->
						<!--		<p class="form-control-static"><?= escape($venta['nro_registros']); ?></p>-->
						<!--	</div>-->
						<!--</div>-->
						<div class="form-group">
							<label class="col-md-3 control-label">Forma de Pago:</label>
							<div class="col-md-9">
								<p class="form-control-static"><u><?= ($venta['plan_de_pagos'] == 'si')?'Plan de Pagos':'Contado'; ?></u></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Tipo de entrega:</label>
							<div class="col-md-9">
								<p class="form-control-static"><u><?= ($venta['distribuir'] == 'S')?'Distribucion':'Entrega Inmediata'; ?></u></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Almacén:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['almacen']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Vendedor:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nombresv'] . ' ' . $venta['paternov'] . ' ' . $venta['maternov']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Operador:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nombres'] . ' ' . $venta['paterno'] . ' ' . $venta['materno']); ?></p>
							</div>
						</div>
					</div>
				</div>
			</div>
        </div>
        <div class="col-md-12">
            <div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><i class="glyphicon glyphicon-list"></i> Detalle de la preventa</h3>
				</div>
				<div class="panel-body">
					<?php if ($detalles) { ?>
					<div class="table-responsive">
						<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
							<thead>
								<tr class="active">
									<th class="text-nowrap">#</th>
									<th class="text-nowrap">Código</th>
									<th class="text-nowrap">Nombre</th>
                                    <th class="text-nowrap">Lote</th>
                                    <th class="text-nowrap">Vencimiento</th>
									<th class="text-nowrap">Cantidad</th>
									<th class="text-nowrap">Precio <?= escape($moneda); ?></th>
									<!--<th class="text-nowrap">Descuento (%)</th>-->
									<th class="text-nowrap">Importe <?= escape($moneda); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php $total = 0; ?>
								<?php foreach ($detalles as $nro => $detalle) { ?>
								<tr>
									<?php 
									$cantidad = escape($detalle['cantidad']); 
									$precio = escape($detalle['precio']);
                                    $pr = $db->select('*')
                                             ->from('inv_productos a')
                                             ->join('inv_unidades b', 'a.unidad_id = b.id_unidad')
                                             ->where('a.id_producto',$detalle['producto_id'])
                                             ->fetch_first();
                                    

                                     if($pr['unidad_id'] == $detalle['unidad_id']) {
                                        $unidad = $pr['unidad'];
                                    }else{
                                        $pr = $db->select('*')
                                                 ->from('inv_asignaciones a')
                                                 ->join('inv_unidades b', 'a.unidad_id = b.id_unidad')
                                                 ->where(array('a.producto_id'=>$detalle['producto_id'],'a.unidad_id'=>$detalle['unidad_id']))
                                                 ->fetch_first();
                                        //Validacion
										if($pr['cantidad_unidad'])
										{
											$unidad = $pr['unidad'];
                                       		$cantidad = $cantidad / $pr['cantidad_unidad'];
										}
                                    }

									$importe = $cantidad * $precio; 
									$total = $total + $importe;?>
									<th class="text-nowrap"><?= $nro + 1;?></th>
									<td class="text-nowrap"><?= escape($detalle['codigo']); ?></td>
									<td class="text-nowrap"><?= escape($detalle['nombre_factura']); ?></td>
                                    <td class="text-nowrap"><?= escape($detalle['lote']); ?></td>
                                    <td class="text-nowrap"><?= escape(date_decode($detalle['vencimiento'], $_institution['formato']) ); ?></td>
									<td class="text-nowrap text-right"><?= $cantidad.' '.$unidad; ?></td>
									<td class="text-nowrap text-right"><?= number_format($precio, 2, ',', '.'); ?></td>
									<!--<td class="text-nowrap text-right"><?php // number_format($detalle['descuento'], 2, ',', '.'); ?></td>-->
									<td class="text-nowrap text-right"><?= number_format($importe, 2, ',', '.'); ?></td>
								</tr>
								<?php } ?>
							</tbody>
							<tfoot>
								<tr class="active">
									<th class="text-nowrap text-right" colspan="7">Importe total <?= escape($moneda); ?></th>
									<th class="text-nowrap text-right"><?= number_format($total, 2, ',', '.'); ?></th>
								</tr>
							</tfoot>
						</table>
					</div>
					<?php } else { ?>
					<div class="alert alert-danger">
						<strong>Advertencia!</strong>
						<p>Esta venta no tiene detalle, es muy importante que todos los egresos cuenten con un detalle que especifique la operación realizada.</p>
					</div>
					<?php } ?>
				</div>
			</div>
        </div>
    </div>

</div>

<script src="<?= js; ?>/selectize.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script>
$(function() {
    $('#id_distribuidor').selectize({
        create: false,
        createOnBlur: false,
        maxOptions: 7,
        persist: false
    });

    $.validate({
        form: '#form_asignar',
        modules: 'basic'
    });

    $("#id_distribuidor").change(function(){
        if( $("#id_distribuidor").val() == "" ){
            $("#distro_group").addClass('has-error');
            $('#para_distro').removeClass('hidden');
        } else {
            $("#distro_group").addClass('has-success');
            $('#para_distro').addClass('hidden');
        }
    });

});

function enviar_form() {
    if( $("#id_distribuidor").val() == "" ){
        $("#id_distribuidor").addClass('error');
        $('#para_distro').removeClass('hidden');
    } else {
        $('#form_asignar').submit();
    }
}

function habilitar(id_venta){
	bootbox.confirm('Está seguro que desea habilitar la preventa? tenga en cuenta que esta acción no se podra rehacer.', function (result) {
                    if(result){
                        window.location = '?/asignacion/habilitar/' + id_venta;
                    }
                });
}

function anular(id_venta){
	bootbox.confirm('Está seguro que desea anular la preventa? tenga en cuenta que esta acción no se podra rehacer.', function (result) {
                    if(result){
                        window.location = '?/asignacion/anular/' + id_venta;
                    }
                });
}

</script>
<?php require_once show_template('footer-advanced'); ?>