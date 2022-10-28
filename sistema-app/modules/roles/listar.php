<?php
// Obtiene los roles
/*
$roles = $db->query("select z.id_proceso, detalle, 	fecha_proceso,	hora_proceso, usuario_id
                    from sys_procesos z
                    where   detalle LIKE '%Se modifico la asignacion cliente con identificador numero %' 
                            AND (fecha_proceso='2021-12-23' or fecha_proceso='2021-12-22')
                    order by z.id_proceso DESC
                    ")->fetch();


echo "<table class='table table-bordered table-condensed table-restructured table-striped table-hover'>";

echo "</td><td>";
    echo "<tr>";
    echo "<td>";
 	echo "id_asig";
    echo "</td><td>";
    echo "hora_proceso";
	echo "</td><td>";
    echo "usuario_id";
	echo "</td><td>";
    echo "egreso_id";
    echo "</td><td>";
	echo "distribuidor_id";
    echo "</td><td>";
	echo "empleado_id";
    echo "</td><td>";
	echo "estado";
    echo "</td><td>";
	echo "monto_total";
    echo "</td><td>";
	echo "plan_de_pagos";
    echo "</td><td>";
	echo "estadoe";
    echo "</td><td>";
// 	echo "descripcion_venta";
//     echo "</td><td>";
	echo "nro_nota";
    echo "</td><td>";
	echo "tipo";
    echo "</td><td>";
	echo "distribuir";
    echo "</td><td>";
	echo "id_pago";
    echo "</td><td>";
	echo "id_pago_detalle";
    echo "</td><td>";
	echo "monto";
    echo "</td><td>";
	echo "codigo";
    echo "</td><td>";
	echo "empleado_id";
	echo "</td><td>";
	echo "estado";
	echo "</td><td>";
	echo "persona";
	echo "</td>";
	echo "</tr>";
	

foreach ($roles as $nro => $rol) { 
	$vec=explode("Se modifico la asignacion cliente con identificador numero ",$rol['detalle']);		
	
	$qw = $db->query("SELECT 
    ac.id_asignacion_cliente, 	ac.egreso_id, 	ac.distribuidor_id, 	ac.fecha_asignacion, 	ac.fecha_entrega, 	ac.estado_pedido, 	ac.empleado_id, 	ac.estado, 
    e.monto_total, e.plan_de_pagos, e.estadoe, e.descripcion_venta, e.nro_nota, e.tipo, e.distribuir, p.id_pago, id_pago_detalle, 
    pd.monto, pd.codigo, pd.empleado_id as empleado_id_pago, pd.estado as estado_recibo, u.persona_id
    
    FROM inv_asignaciones_clientes ac 
    
    left join inv_egresos e on id_egreso = egreso_id 
    left join inv_pagos p on e.id_egreso = p.movimiento_id and p.tipo='Egreso' 
    left join inv_pagos_detalles pd on id_pago = pago_id 
    
    left join sys_users u ON id_user = pd.empleado_id 
    
    where id_asignacion_cliente='".$vec[1]."'")->fetch_first();
    
    if($qw["plan_de_pagos"]=="no"){
    
    	echo "<tr>";
    	echo "<td>";
    	echo $vec[1];
        echo "</td><td>";
        echo $rol["hora_proceso"];
        echo "</td><td>";
        echo $rol["usuario_id"];
    
        echo "</td><td>";
    // 	echo $qw["id_asignacion_cliente"];
    //     echo "</td><td>";
    	echo $qw["egreso_id"];
        echo "</td><td>";
    	echo $qw["distribuidor_id"];
        echo "</td><td>";
    // 	echo $qw["fecha_asignacion"];
    //     echo "</td><td>";
    // 	echo $qw["fecha_entrega"];
    //     echo "</td><td>";
    // 	echo $qw["estado_pedido"];
    //     echo "</td><td>";
    	echo $qw["empleado_id"];
        echo "</td><td>";
    	echo $qw["estado"];
        echo "</td><td>";
    	echo $qw["monto_total"];
        echo "</td><td>";
    	echo $qw["plan_de_pagos"];
        echo "</td><td>";
    	echo $qw["estadoe"];
    //     echo "</td><td>";
    // 	echo $qw["descripcion_venta"];
        echo "</td><td>";
    	echo $qw["nro_nota"];
        echo "</td><td>";
    	echo $qw["tipo"];
        echo "</td><td>";
    	echo $qw["distribuir"];
        echo "</td><td>";
    	echo $qw["id_pago"];
        echo "</td><td>";
    	echo $qw["id_pago_detalle"];
        echo "</td><td>";
    	echo $qw["monto"];
        echo "</td><td>";
    	echo $qw["codigo"];
        echo "</td><td>";
    	echo $qw["empleado_id_pago"];
    	echo "</td><td>";
    	echo $qw["estado_recibo"];
    	echo "</td><td>";
    	echo $qw["persona_id"];
    	echo "</td>";
    	echo "</tr>";
    
        echo $qw["id_pago_detalle"].",";
        
    }
} 
			

echo "</table>";

*/

// Obtiene los roles
$roles = $db->select('z.*')->from('sys_roles z')->order_by('z.id_rol')->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_editar = in_array('editar', $permisos);
$permiso_ver = in_array('ver', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Roles</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if (($permiso_crear || $permiso_imprimir) && ($permiso_crear || $roles)) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para agregar nuevos roles hacer clic en el siguiente botón: </div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<?php if ($permiso_imprimir) { ?>
			<a href="?/roles/imprimir" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_crear) { ?>
			<!-- ésto debe ser comentado -->
			<a href="?/roles/crear" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i><span> Nuevo</span></a> 
			<?php } ?>
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
	<?php if ($roles) { ?>
	<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
		<thead>
			<tr class="active">
				<th class="text-nowrap">#</th>
				<th class="text-nowrap">Rol</th>
				<th class="text-nowrap">Descripción</th>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar) { ?>
				<th class="text-nowrap">Opciones</th>
				<?php } ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Rol</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Descripción</th>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar) { ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
				<?php } ?>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($roles as $nro => $rol) { ?>
			<tr>
				<th class="text-nowrap"><?= $nro + 1; ?></th>
				<td class="text-nowrap"><?= escape($rol['rol']); ?></td>
				<td class="text-nowrap"><?= escape($rol['descripcion']); ?></td>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar) { ?>
				<td class="text-nowrap">
					<?php if ($permiso_ver) { ?>
					<a href="?/roles/ver/<?= $rol['id_rol']; ?>" data-toggle="tooltip" data-title="Ver rol"><i class="glyphicon glyphicon-search"></i></a>
					<?php } ?>
					<?php if ($permiso_editar && $rol['id_rol'] != 1) { ?>
					<!--<a href="?/roles/editar/<?= $rol['id_rol']; ?>" data-toggle="tooltip" data-title="Editar rol"><i class="glyphicon glyphicon-edit"></i></a>-->
					<?php } ?>
					<?php if ($permiso_eliminar  && $rol['id_rol'] != 1) { ?>
					<a href="?/roles/eliminar/<?= $rol['id_rol']; ?>" data-toggle="tooltip" data-title="Eliminar rol" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i></a>
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
		<p>No existen roles registrados en la base de datos, para crear nuevos roles hacer clic en el botón nuevo o presionar las teclas <kbd>alt + n</kbd>.</p>
	</div>
	<?php } ?>
</div>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>
<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>
<script>
$(function () {
	<?php if ($permiso_eliminar) { ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar el rol?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>
	
	<?php if ($permiso_crear) { ?>
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'n':
					e.preventDefault();
					window.location = '?/roles/crear';
				break;
			}
		}
	});
	<?php } ?>
	
	<?php if ($roles) { ?>
	var table = $('#table').DataFilter({
		filter: false,
		name: 'roles',
		reports: 'excel|word|pdf|html'
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>