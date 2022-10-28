<?php
/*
$ingresos = $db->query('SELECT *
                        FROM inv_ingresos_detalles
                        LEFT JOIN inv_ingresos i ON id_ingreso=ingreso_id
                        ORDER BY producto_id, lote, i.almacen_id, id_ingreso 
                        ')->fetch();

foreach ($ingresos as $nro => $ingreso) { 
    
    $cantidadIngreso=$ingreso['cantidad'];
    
    echo "ingreso de producto: ".$cantidadIngreso;
    echo "<br>";
    
    $swEgreso=true;
    
    while($cantidadIngreso>0 && $swEgreso){
    
        echo $egresoQuery = 'SELECT *
                        FROM inv_egresos_detalles
                        LEFT JOIN inv_egresos e ON id_egreso=egreso_id
                        WHERE 
                            lote="'.$ingreso['lote'].'" AND producto_id="'.$ingreso['producto_id'].'" AND almacen_id="'.$ingreso['almacen_id'].'"
                            
                            AND 
                            (
                                (e.tipo = "Preventa" AND e.preventa = "habilitado")
                                OR
                                (e.tipo="No venta" AND e.estadoe = "4") 
                                OR 
                                (e.tipo NOT IN ("Preventa", "No venta") )
                            )
                            
                            AND ingresos_detalles_id=0 
                            
                        ORDER BY id_egreso, id_detalle';

        $egreso = $db->query($egresoQuery)->fetch_first();
        
        echo "<br>";
        
        if(isset($egreso)){
            
            echo "cantidad que egreso - Cantidad sobrante: ".$egreso['cantidad']." - ".$cantidadIngreso."<br>";
            
            if($egreso['cantidad']>$cantidadIngreso){
                $sobra=$egreso['cantidad']-$cantidadIngreso;
                
                $db->query('UPDATE inv_egresos_detalles SET ingresos_detalles_id="'.($ingreso['id_detalle']).'" WHERE id_detalle="'.$egreso['id_detalle'].'"')->execute();
                
                $db->query('UPDATE inv_egresos_detalles SET cantidad="'.($cantidadIngreso).'" WHERE id_detalle="'.$egreso['id_detalle'].'"')->execute();
    
                $data = array(
    			 	'precio'=>$egreso['precio'],
    			 	'unidad_id'=>$egreso['unidad_id'],
    			 	'cantidad'=>$sobra,
    			 	'descuento'=>$egreso['descuento'],
    			 	'producto_id'=>$egreso['producto_id'],
    			 	'egreso_id'=>$egreso['egreso_id'],
    			 	'promocion_id'=>$egreso['promocion_id'],
    			 	'asignacion_id'=>$egreso['asignacion_id'],
    			 	'lote'=>$egreso['lote'],
    			 	'vencimiento'=>$egreso['vencimiento'],
    			 	'detalle_ingreso_id'=>0,
    			 	'ingresos_detalles_id'=>0 
    			);
    			
    			$db->insert('inv_egresos_detalles', $data) ; 
    			
            }else{
                $db->query('UPDATE inv_egresos_detalles SET ingresos_detalles_id="'.($ingreso['id_detalle']).'" WHERE id_detalle="'.$egreso['id_detalle'].'"')->execute();
            }
            
            $cantidadIngreso=$cantidadIngreso-$egreso['cantidad'];
        }else{
            $swEgreso=false;
        }
    }
}
*/





// $ingresos = $db->query("SELECT SUM(precio*cantidad)as monto_sumado,monto_total, nro_nota, id_egreso
//                         FROM inv_egresos_detalles
//                         LEFT JOIN inv_egresos e ON id_egreso=egreso_id
                        
//                         where   e.tipo IN('Preventa', 'Venta', 'No venta')
// 				                AND (preventa is NULL OR preventa='habilitado')  
        			    
//                         GROUP BY egreso_ID
//                         ")->fetch();

// $sum=0;
// foreach ($ingresos as $nro => $ingreso) { 
    
//     if($cantidadIngreso=$ingreso['monto_sumado']!=$cantidadIngreso=$ingreso['monto_total']){
//         echo " + ".$cantidadIngreso=$ingreso['monto_sumado'];
//         echo " + ".$cantidadIngreso=$ingreso['monto_total'];
//         echo " + ".$cantidadIngreso=$ingreso['nro_nota'];
//         echo " <br> ";

//         $sum=$sum+$cantidadIngreso=$ingreso['monto_sumado']-$ingreso['monto_total'];

//         $datos = array(
// 			'monto_total' => $ingreso['monto_sumado']
// 		);
// 		$condicion = array('id_egreso' => $ingreso['id_egreso']);
// 		$db->where($condicion)->update('inv_egresos', $datos);
		
//     }
    
// }
// echo $sum;

// Obtiene las monedas
$monedas = $db->select('z.*')->from('inv_monedas z')->order_by('z.id_moneda')->fetch();

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
		<strong>Monedas</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if (($permiso_crear || $permiso_imprimir) && ($permiso_crear || $monedas)) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para agregar nuevas monedas hacer clic en el siguiente botón: </div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<?php if ($permiso_imprimir) { ?>
			<a href="?/monedas/imprimir" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_crear) { ?>
			<a href="?/monedas/crear" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i><span> Nuevo</span></a>
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
	<?php if ($monedas) { ?>
	<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
		<thead>
			<tr class="active">
				<th class="text-nowrap">#</th>
				<th class="text-nowrap">Moneda</th>
				<th class="text-nowrap">Sigla</th>
				<th class="text-nowrap">Oficial</th>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar) { ?>
				<th class="text-nowrap">Opciones</th>
				<?php } ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Moneda</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Sigla</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Oficial</th>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar) { ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
				<?php } ?>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($monedas as $nro => $moneda) { ?>
			<tr>
				<th class="text-nowrap"><?= $nro + 1; ?></th>
				<td class="text-nowrap"><?= escape($moneda['moneda']); ?></td>
				<td class="text-nowrap"><?= escape($moneda['sigla']); ?></td>
				<td class="text-nowrap"><?= (escape($moneda['oficial']) == 'S') ? 'Si' : 'No'; ?></td>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar) { ?>
				<td class="text-nowrap">
					<?php if ($permiso_ver) { ?>
					<a href="?/monedas/ver/<?= $moneda['id_moneda']; ?>" data-toggle="tooltip" data-title="Ver moneda"><i class="glyphicon glyphicon-search"></i></a>
					<?php } ?>
					<?php if ($permiso_editar) { ?>
					<a href="?/monedas/editar/<?= $moneda['id_moneda']; ?>" data-toggle="tooltip" data-title="Editar moneda"><i class="glyphicon glyphicon-edit"></i></a>
					<?php } ?>
					<?php if ($permiso_eliminar) { ?>
					<a href="?/monedas/eliminar/<?= $moneda['id_moneda']; ?>" data-toggle="tooltip" data-title="Eliminar moneda" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i></a>
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
		<p>No existen monedas registradas en la base de datos, para crear nuevas monedas hacer clic en el botón nuevo o presionar las teclas <kbd>alt + n</kbd>.</p>
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
		bootbox.confirm('Está seguro que desea eliminar la moneda?', function (result) {
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
					window.location = '?/monedas/crear';
				break;
			}
		}
	});
	<?php } ?>
	
	<?php if ($monedas) { ?>
	var table = $('#table').DataFilter({
		filter: false,
		name: 'monedas',
		reports: 'excel|word|pdf|html'
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>