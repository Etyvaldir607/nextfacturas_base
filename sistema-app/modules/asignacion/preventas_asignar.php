<?php

$id_venta = (isset($params[0])) ? $params[0] : 0;

// Obtiene la venta
$venta = $db->select('i.*, a.id_almacen, a.almacen, a.principal, e.nombres, e.paterno, e.materno, v.nombres nombresv, v.paterno paternov, v.materno maternov, cl.direccion, cl.cliente')
            ->from('inv_egresos i')
            ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
            ->join('inv_clientes cl', 'i.cliente_id = cl.id_cliente', 'left')
            ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
            ->join('sys_empleados v', 'i.vendedor_id = v.id_empleado', 'left')
            ->where('id_egreso', $id_venta)
            ->fetch_first();

// Verifica si existe el egreso
if (!$venta) {
	// Error return back with notiication warning
    set_notification('warning', 'Accion insatisfactoria', 'Por favor seleccione una preventa válida...');
    return redirect('?/asignacion/preventas_listar');
}

if($venta['preventa'] != 'habilitado'){
	set_notification('warning', 'Accion insatisfactoria', 'Por favor habilite la preventa antes de asignar un distribuidor...');
    return redirect('?/asignacion/preventas_habilitar/'.$id_venta);
}

// Obtiene los detalles
$detalles = $db->query('select d.*, SUM(d.cantidad) as cantidad, d.unidad_id as unidad_otra, p.codigo, p.nombre, p.nombre_factura
                        from inv_egresos_detalles d
                        left join inv_productos p ON d.producto_id = p.id_producto
                        where d.egreso_id="'.$id_venta.'"
                        group by precio, producto_id, lote, vencimiento
                        order by id_detalle asc')
                ->fetch();

// Obtiene los distribuidores
$distribuidores = $db->query("SELECT CONCAT(e.nombres, ' ', e.paterno, ' ', e.materno) as empleado, e.id_empleado
                              FROM sys_users u
                              LEFT JOIN sys_empleados e ON u.persona_id = e.id_empleado
                              LEFT JOIN inv_users_almacenes ON user_id=id_user
                              WHERE u.rol_id = 4 
                                    AND almacen_id='".$venta['id_almacen']."'
                            ")->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

$permiso_listar = true;

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
            <div class="col-sm-8 hidden-xs">
                <div class="text-label">Para realizar una acción hacer clic en los siguientes botones: </div>
            </div>
            <div class="col-xs-12 col-sm-4 text-right">

                <a href="?/asignacion/preventas_listar" class="btn btn-primary" ><i class="glyphicon glyphicon-arrow-left"></i><span class="hidden-xs"> Listar</span></a>
            </div>
        </div>
        <hr>
	<?php } ?>
	<?php if (isset($_SESSION[temporary])) { ?>
		<div class="alert alert-<?= (isset($_SESSION[temporary]['alert'])) ? $_SESSION[temporary]['alert'] : $_SESSION[temporary]['type']; ?>">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			<strong><?= $_SESSION[temporary]['title']; ?></strong>
			<p><?= (isset($_SESSION[temporary]['message'])) ? $_SESSION[temporary]['message'] : $_SESSION[temporary]['content']; ?></p>
		</div>
		<?php unset($_SESSION[temporary]); ?>
	<?php } ?>

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><i class="glyphicon glyphicon-user"></i> Asignación de la preventa</h3>
				</div>
                <div class="panel-body">
                    <div class="form-horizontal">
                        <form action="?/asignacion/preventas_guardar" id="form_asignar" method="POST">
                            <input type="hidden" name="id_venta" value="<?= $id_venta ?>">
                            <div class="form-group">
                                <label class="col-md-3 control-label hidden">Nro. de movimiento:</label>
                                <div class="col-md-9">
                                    <input type="text" class="form-control hidden" value="<?= $venta['nro_movimiento'] ?>" name="nro_movimiento" id="nro_movimiento" readonly>
                                </div>
                            </div>
                            <div class="form-group" id="distro_group">
                                <label class="col-md-3 control-label">Distribuidor:</label>
                                <div class="col-md-9">
                                    <select class="form-control text-uppercase " name="id_distribuidor" id="id_distribuidor" data-validation="required number" onchange="setDistribuidor();"
                                            readonly data-validation-allowing="range[1;999999]">
                                        <option value="" selected disabled>Seleccione...</option>
                                        <?php 
                                        foreach($distribuidores as $distro) { 
                                            $distrix1 = $db->query('  SELECT *
                                                                            from      inv_asignaciones_clientes ac
                                                                            left join sys_empleados e ON distribuidor_id=id_empleado 
                                                                            where   ac.distribuidor_id="'.$distro['id_empleado'].'"
                                                                                    AND 
                                                                                    (
                                                                                        e.fecha="'.date('Y-m-d').'"
                                                                                        OR 
                                                                                        fecha_hora_salida IN (  SELECT  MAX(fecha_hora_salida)as fecha_hora_salida
                                                                                                                FROM    inv_asignaciones_clientes ac  
                                                                                                                WHERE   distribuidor_id="'.$distro['id_empleado'].'" 
                                                                                                                        AND fecha_hora_liquidacion="0000-00-00 00:00:00" 
                                                                                                                        AND fecha_hora_salida!="0000-00-00 00:00:00" 
                                                                                                            )
                                                                                    )
                                                                        ')->fetch_first();
                                            if($distrix1){ ?>
                                                <option value="-1"><?= $distro['empleado']." (El repartidor no esta disponible)" ?></option>
                                            <?php }else{ ?>
                                                <option value="<?= $distro['id_empleado'] ?>"><?= $distro['empleado'] ?></option>
                                            <?php } ?>
                                        <?php } ?>
                                    </select>
                                    <span class="help-block form-error text-danger hidden" id="para_distro">El campo es requerido</span>
                                    
                                    <div>
                                    <input type="text" value="0" id="ix_distrib" data-validation="required number" data-validation-allowing="range[1;999999],int" data-validation-error-msg="No se puede asignar al distribuidor" style="opacity:0;">
                                    </div>
                                    
                                </div>
                            </div>
                            <div class="form-group hidden">
                                <label class="col-md-3 control-label">Fecha entrega:</label>
                                <div class="col-md-9">
                                    <input type="date" class="form-control " min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" name="fecha_entrega" id="fecha_entrega" data-validation="required" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label hidden">Empleado:</label>
                                <div class="col-md-9">
                                    <input  type="text" class="form-control text-uppercase hidden" value="<?= $_user['nombres'] . ' ' . $_user['paterno'] . ' ' . $_user['materno'] ?>" 
                                            name="empleado" id="empleado">
                                </div>
                            </div>
                            
                            <hr>
                            <div class="form-group">
                                <div class="col-xs-12 text-right">
                                    <button type="button" onclick="enviar_form()" class="btn btn-success">Guardar asignacion</button>
                                    <button type="reset" class="btn btn-default">Restablecer</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><i class="glyphicon glyphicon-log-out"></i> Información de la preventa</h3>
				</div>
				<div class="panel-body">
					<div class="form-horizontal">
						
						<?php
						$fecha_habilitacion=explode(" ",$venta['fecha_habilitacion']);
                        $Aux=escape(date_decode($fecha_habilitacion[0], $_institution['formato']));
                        $Aux=$Aux." <small class='text-success'>".$fecha_habilitacion[1]."</small>";
                        ?>
						
						<div class="form-group">
							<label class="col-md-3 control-label">Fecha y hora:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= $Aux ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Cliente:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['cliente']); ?></p>
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
							<label class="col-md-3 control-label">Número de nota:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nro_nota']); ?></p>
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
								<p class="form-control-static"><?= escape(number_format($venta['monto_total'], 2, ',', '.') ); ?></p>
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
                                    $pr = $db->select('*')->from('inv_productos a')->join('inv_unidades b', 'a.unidad_id = b.id_unidad')->where('a.id_producto',$detalle['producto_id'])->fetch_first();
                                    
                                    if($pr['unidad_id'] == $detalle['unidad_id']) {
                                        $unidad = $pr['unidad'];
                                    }else{
                                        $pr = $db->select('*')->from('inv_asignaciones a')->join('inv_unidades b', 'a.unidad_id = b.id_unidad')->where(array('a.producto_id'=>$detalle['producto_id'],'a.unidad_id'=>$detalle['unidad_id']))->fetch_first();
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
                                    <td class="text-nowrap"><?= escape(date_decode($detalle['vencimiento'], $_institution['formato'])); ?></td>
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

function setDistribuidor(){
    id_distribuidor=$('#id_distribuidor').val();
    if(id_distribuidor==-1){
        alert("No se puede asignar al distribuidor");
    }
    $('#ix_distrib').val(id_distribuidor);
}
</script>
<?php require_once show_template('footer-advanced'); ?>