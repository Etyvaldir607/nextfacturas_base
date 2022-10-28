<?php
// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

$queryx = "SELECT DISTINCT(i.nro_movimiento), i.*,c.codigo,c.cliente,c.direccion,a.almacen,a.principal,e.nombres,e.paterno,e.materno,e.cargo,
                                ac.estado_pedido, ac.estado as estado_a, ac.id_asignacion_cliente, CONCAT(em.nombres,' ',em.paterno,' ',em.materno) as distribuidor, i.plan_de_pagos, nombre_grupo
                        FROM inv_egresos i
                        LEFT JOIN inv_almacenes a ON i.almacen_id=a.id_almacen
                        LEFT JOIN sys_empleados e ON i.vendedor_id=e.id_empleado
                        LEFT JOIN sys_users AS u ON e.id_empleado=u.persona_id
                        LEFT JOIN inv_clientes c ON i.cliente_id = c.id_cliente
                        LEFT JOIN inv_asignaciones_clientes ac ON i.id_egreso = ac.egreso_id AND ac.estado = 'A'
                        LEFT JOIN sys_empleados em ON ac.distribuidor_id=em.id_empleado
                        
                        LEFT join inv_clientes_grupos cg on cg.id_cliente_grupo=i.codigo_vendedor
					    LEFT JOIN sys_supervisor ss ON cg.id_cliente_grupo=ss.cliente_grupo_id
                          
                        WHERE   i.tipo IN('Preventa', 'No venta')
        				        AND (preventa is NULL OR (preventa='habilitado' AND estadoe!=3))
        				        
                			    AND (
                			        (i.vendedor_id='".$_user['persona_id']."' AND '".$_user['rol_id']."' = 15)
                			        OR 
                			        (ss.user_ids='".$_user['id_user']."' AND '".$_user['rol_id']."' = 14)
                			        OR 
                			        '".$_user['rol_id']."' = 1
                			    )
        				ORDER BY i.fecha_egreso DESC, i.hora_egreso DESC
				";

$proformas = $db->query($queryx)->fetch();
				
				// WHERE (i.estadoe>2 OR i.estadoe<=4)				
				// AND i.tipo IN('Preventa', 'No venta')
				// AND (i.preventa != 'eliminado' AND i.preventa != 'anulado' OR i.preventa <=> NULL)
				

// echo json_encode($proformas); die();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_ver = in_array('ver', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_cambiar = true;

?>
<?php require_once show_template('header-advanced'); ?>
<style>
.table-xs tbody {
	font-size: 12px;
}
.width-sm {
	min-width: 150px;
}
.width-md {
	min-width: 200px;
}
.width-lg {
	min-width: 250px;
}
</style>
<div class="panel-heading" data-formato="<?= strtoupper($formato_textual); ?>" data-mascara="<?= $formato_numeral; ?>" data-gestion="<?= date_decode($gestion_base, $_institution['formato']); ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Preventas de venta personal</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_cambiar || $permiso_crear) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para realizar una preventa hacer clic en el siguiente botón: </div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<?php if ($permiso_cambiar) { ?>
			<!--<button class="btn btn-default" data-cambiar="true"><i class="glyphicon glyphicon-calendar"></i><span class="hidden-xs"> Cambiar</span></button>-->
			<?php } ?>
			<?php if ($permiso_crear) { ?>
			<a href="?/preventas/seleccionar_almacen" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span><span class="hidden-xs"> Nueva preventa</span></a>
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<?php if ($proformas) { ?>
	<table id="table" class="table table-bordered table-condensed table-striped table-hover table-xs">
		<thead>
			<tr class="active">
				<th class="text-nowrap">Nro. nota</th>
				<th class="text-nowrap">Fecha</th>
				<th class="text-nowrap">Cliente</th>
				<th class="text-nowrap">Direccion</th>
				<th class="text-nowrap">Monto total <?= escape($moneda); ?></th>
				<th class="text-nowrap">Registros</th>
				<th class="text-nowrap">Almacen</th>
				<th class="text-nowrap">Grupo</th>
				<th class="text-nowrap">Vendedor</th>
				<th class="text-nowrap">Estado</th>
				<?php if ($permiso_ver || $permiso_eliminar) { ?>
				<th class="text-nowrap">Opciones</th>
				<?php } ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap" data-datafilter-filter="true">Nro. nota</th>
				<th class="text-nowrap" data-datafilter-filter="true">Fecha</th>
				<th class="text-nowrap" data-datafilter-filter="true">Cliente</th>
				<th class="text-nowrap" data-datafilter-filter="true">Direccion</th>
				<th class="text-nowrap" data-datafilter-filter="true">Monto total <?= escape($moneda); ?></th>
				<th class="text-nowrap" data-datafilter-filter="true">Registros</th>
				<th class="text-nowrap" data-datafilter-filter="true">Almacen</th>
				<th class="text-nowrap" data-datafilter-filter="true">Grupo</th>
				<th class="text-nowrap" data-datafilter-filter="true">Vendedor</th>
				<th class="text-nowrap" data-datafilter-filter="true">Estado</th>
				<?php if ($permiso_ver || $permiso_eliminar) { ?>
				<th class="text-nowrap" data-datafilter-filter="false">Opciones</th>
				<?php } ?>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($proformas as $nro => $proforma) { ?>
			<?php 
    		    $id_motivo = $proforma['motivo_id'];
                $nombre_motivo = $db->query("   select * 
                                                from gps_noventa_motivos 
                                                where id_motivo = '$id_motivo'
                                            ")->fetch_first();
                $fecha_hab=explode(" ",$proforma['fecha_habilitacion']);
            ?>
			<tr>
				<td class="text-nowrap"><?= escape($proforma['nro_nota']); ?></td>
				<td class="text-nowrap"><?= escape(date_decode($fecha_hab[0], $_institution['formato'])); ?> <small class="text-success"><?= escape($fecha_hab[1]); ?></small></td>
				<td class=""><?= escape($proforma['cliente']); ?></td>
				<td class=""><?= escape($proforma['direccion']); ?></td>
				<td class="text-nowrap text-right"><?= escape(number_format($proforma['monto_total'],2,',','.')); ?></td>
				<td class="text-nowrap text-right"><?= escape($proforma['nro_registros']); ?></td>
				<td class="text-nowrap"><?= escape($proforma['almacen']); ?></td>
				<td class=""><?= escape($proforma['nombre_grupo']); ?></td>
				<td class=""><?= escape($proforma['nombres'] . ' ' . $proforma['paterno'] . ' ' . $proforma['materno']); ?></td>
				<td class="">
				
				<?php 
				// echo $proforma['preventa']." - - - ".$proforma['estado_pedido'];
				
                if ($proforma['preventa'] == NULL && $proforma['estado_pedido'] == NULL):
                    echo '<span style="color: red;">No esta habilitado</span>';
                
                elseif ($proforma['preventa'] == 'habilitado' && $proforma['estado_pedido'] == 'salida'):
                    echo 'Ya fue asignado al distribuidor ('.$proforma['distribuidor'].')';
                
                elseif ($proforma['preventa'] == 'habilitado' && $proforma['estado_pedido'] == 'entregado'):
                    echo '<span style="color: green;">Ya fue entregado</span>';
                
                elseif ($proforma['distribuir'] == 'S' && $proforma['preventa'] == 'habilitado' && $proforma['estado_pedido'] == NULL):
                    echo '<span style="color: blue;">Aun no fue asignado a un repartidor </span>';
                
                elseif ($proforma['distribuir'] == 'N' && $proforma['preventa'] == 'habilitado' && $proforma['estado_pedido'] == NULL):
                    echo '<span style="color: green;">En espera del cliente</span>';
                
                elseif ($proforma['preventa'] == NULL && $proforma['estado_pedido'] == 'reasignado') :
                    echo '<span style="color: blue;">Puede reasignar ('.$nombre_motivo['motivo'].')</span>';
                
                elseif ($proforma['preventa'] == 'habilitado' && $proforma['estado_pedido'] == 'reasignado') :
                    echo '<span style="color: blue;">Puede reasignar ('.$nombre_motivo['motivo'].')</span>';
                
                elseif ($proforma['preventa'] == 'habilitado' && $proforma['estado_pedido'] == 'sin_aprobacion') :
                    //echo '<span style="color: green;">Almacen preparando los productos</span>';
                    echo 'Ya fue asignado al distribuidor ('.$proforma['distribuidor'].')';
                
                elseif ($proforma['preventa'] == 'anulado') :
                    echo '<span style="color: red;">Anulado</span>';
                
                endif;
                ?></td>
				<?php if ($permiso_ver || $permiso_eliminar) { ?>
				<td class="text-nowrap">
					<?php if ($permiso_ver) { ?>
					<a href="?/preventas/ver/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Ver detalle de la preventa"><i class="glyphicon glyphicon-list-alt"></i></a>
					<?php } ?>
					<?php if ($permiso_eliminar && false) { ?>
					<a href="?/preventas/eliminar/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Eliminar preventa" data-eliminar="true"><span class="glyphicon glyphicon-trash"></span></a>
					<?php } ?>
				</td>
				<?php } ?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>No existen proformas registrados en la base de datos.</p>
	</div>
	<?php } ?>
</div>

<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/jquery.maskedinput.min.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>
<script src="<?= js; ?>/dataFiltersCustom.min.js"></script>
<script src="<?= js; ?>/moment.min.js"></script>
<script src="<?= js; ?>/moment.es.js"></script>
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>
<script>
$(function () {	
	<?php if ($permiso_crear) { ?>
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'n':
					e.preventDefault();
					window.location = '?/preventas/crear';
				break;
			}
		}
	});
	<?php } ?>

	<?php if ($permiso_eliminar) { ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar la proforma y todo su detalle?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>

	<?php if ($proformas) { ?>
	var table = $('#table').DataFilter({
		filter: true,
		name: 'Preventas personales',
		fechas: '',
        creacion: "Fecha y hora de Impresion: <?= date("d/m/Y H:i:s") ?>",
        total: 6,
		reports: 'excel|pdf'
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>