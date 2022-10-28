<?php

// Obtiene los parametros
$id_egreso = (isset($params[0])) ? $params[0] : 0;
//$id_producto = (isset($params[1])) ? $params[1] : 0;
$det = $db->from('inv_egresos')->where('id_egreso',$id_egreso)->fetch_first();
 $nombre = $det['nombre_cliente'];
 $nit = $det['nit_ci'];
// Obtiene los movimientos
//$movimientos = $db->query("select m.*, ifnull(concat(e.nombres, ' ', e.paterno, ' ', e.materno), '') as empleado from (select i.id_ingreso as id_movimiento, d.id_detalle, i.fecha_ingreso as fecha_movimiento, i.hora_ingreso as hora_movimiento, i.descripcion, d.cantidad, d.costo as monto, 'i' as tipo, i.empleado_id, i.almacen_id from inv_ingresos_detalles d left join inv_ingresos i on d.ingreso_id = i.id_ingreso where d.producto_id = $id_producto union select e.id_egreso as id_movimiento, d.id_detalle, e.fecha_egreso as fecha_movimiento, e.hora_egreso as hora_movimiento, e.descripcion, d.cantidad, d.precio as monto, 'e' as tipo, e.empleado_id, e.almacen_id from inv_egresos_detalles d left join inv_egresos e on d.egreso_id = e.id_egreso where d.producto_id = $id_producto) m left join sys_empleados e on m.empleado_id = e.id_empleado where m.almacen_id = $id_almacen order by m.fecha_movimiento asc, m.hora_movimiento asc")->fetch();
// Obtener las transacciones
$cliente = $db->select('cliente_id,nombre_cliente, nit_ci, count(nombre_cliente) as nro_visitas, sum(monto_total) as total_ventas')
              ->from('inv_egresos')
              ->where(array('nombre_cliente' => $nombre,'nit_ci' => $nit))
              ->fetch_first();

$movimientos = $db->select('c.codigo, c.nombre, c.descripcion, d.categoria, SUM(b.cantidad) AS cant, COUNT(a.id_egreso) AS reg, SUM(b.precio*b.cantidad) AS prec')
				  ->from('inv_egresos a')
				  ->join('inv_egresos_detalles b','a.id_egreso = b.egreso_id')
				  ->join('inv_productos c','b.producto_id = c.id_producto')
				  ->join('inv_categorias d', 'c.categoria_id = d.id_categoria')
				  ->group_by('id_producto')
				  ->where(array('a.nombre_cliente' => $nombre, 'nit_ci' => $nit))
				  ->fetch();
//var_dump($movimientos);exit();
// echo json_encode($movimientos); die();

// Verifica si existen movimientos
if (!$movimientos) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene el almacen
$almacen = $db->from('inv_almacenes')->where('id_almacen', $id_almacen)->fetch_first();

// Obtiene el producto
$producto = $db->from('inv_productos')->where('id_producto', $id_producto)->fetch_first();

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Reporte clientes</strong>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para cambiar de almac®¶n o de producto hacer clic en el siguiente bot√≥n: </div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/clientes/reporte" class="btn btn-primary">
				<span class="glyphicon glyphicon-menu-left"></span>
				<span>Regresar</span>
			</a>
			<a href="?/clientes/imprimir_reporte/<?= $id_egreso; ?>" target="_blank" class="btn btn-default">
				<span class="glyphicon glyphicon-print"></span>
				<span>Imprimir</span>
			</a>
		</div>
	</div>
	<hr>
	<div class="row">
		<div class="col-sm-6">
			<div class="well">
				<h4 class="margin-none"><u>Cliente</u></h4>
				<dl class="margin-none">
					<dt>NIT / CI:</dt>
					<dd><?= escape($cliente['nit_ci']); ?></dd>
					<dt>Nombre:</dt>
					<dd><?= escape($cliente['nombre_cliente']); ?></dd>
					<dt></dt>
					<dd>
						<a href="?/precios/ver/<?= $id_producto; ?>" target="_blank"><?= escape(number_format($producto['precio_actual'],2,',','.')); ?></a>
					</dd>
				</dl>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="well">
				<h4 class="margin-none"><u>Registros</u></h4>
				<dl class="margin-none">
					<dt>Visitas:</dt>
					<dd><?= escape($cliente['nro_visitas']); ?></dd>
					<dt>Total:</dt>
					<dd><?= escape(number_format($cliente['total_ventas'],2,',','.')); ?></dd>
					<dt></dt>
					<dd></dd>
				</dl>
			</div>
		</div>
	</div>
	<?php if ($movimientos) { ?>
	<h3 class="text-center">PRODUCTOS</h3>
	<div class="table-responsive">
		<table class="table table-bordered table-condensed table-striped table-hover">
			<thead>
				<tr class="active">
					<th class="text-nowrap text-center text-middle" rowspan="2">#</th>
					<th class="text-nowrap text-center text-middle" rowspan="2">Codigo</th>
					<th class="text-nowrap text-center text-middle" rowspan="2">Nombre</th>
					<th class="text-nowrap text-center text-middle" colspan="2">Detalle</th>
					<th class="text-nowrap text-center text-middle" colspan="3">Saldos</th>
				</tr>
				<tr class="active">
					<th class="text-nowrap text-center text-middle">Tipo</th>
					<th class="text-nowrap text-center text-middle">descripci√≥n</th>
					<th class="text-nowrap text-center text-middle">Registros</th>
					<th class="text-nowrap text-center text-middle">Cantidad</th>
					<th class="text-nowrap text-center text-middle">Total</th>
				</tr>
			</thead>
			<tbody>
				<?php $saldo_cantidad = 0; ?>
				<?php $saldo_costo = 0; ?>
				<?php $ingresos = array(); ?>
				<?php foreach ($movimientos as $nro => $movimiento) { ?>
					<tr>
						<th class="text-nowrap"><?= $nro + 1; ?></th>
						<td class="text-nowrap"><?= escape($movimiento['codigo']); ?></td>
						<td class="text-nowrap"><?= escape($movimiento['nombre']); ?></td>
						<td class="text-nowrap text-right info"><?= escape($movimiento['categoria']); ?></td>
						<td class="text-nowrap text-right info"><strong><?= escape($movimiento['descripcion']); ?></strong></td>
						<td class="text-nowrap text-right success"><?= escape($movimiento['reg']) ?></td>
						<td class="text-nowrap text-right success"><?= escape(number_format($movimiento['cant'],2,',','.')) ?></td>
						<td class="text-nowrap text-right success"><?= escape(number_format($movimiento['prec'],2,',','.')) ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>El kardex valorado no puede mostrarse por que no existen movimientos registrados.</p>
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
/*$(function () {
	var table = $('#table').DataFilter({
		filter: true,
		name: 'reporte_de_existencias',
		reports: 'excel|word|pdf|html'
	});
});*/
</script>
<?php require_once show_template('footer-advanced'); ?>