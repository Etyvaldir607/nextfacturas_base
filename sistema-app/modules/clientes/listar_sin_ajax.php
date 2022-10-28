<?php

// Obtiene los clientes

$clientes = $db->query('SELECT a.cliente, a.imagen, a.nit, a.id_cliente, a.estado, a.telefono, a.direccion, a.tipo,  count(a.cliente) as nro_visitas , CONCAT(e.nombres, e.paterno) as empleado,r.nombre,max(b.fecha_egreso) as fecha_reciente
    FROM inv_clientes a 
    LEFT OUTER JOIN inv_egresos b ON a.cliente = b.nombre_cliente and b.fecha_egreso = (SELECT MAX(e.fecha_egreso) FROM inv_egresos e where e.cliente_id= a.id_cliente) 
    LEFT OUTER JOIN sys_empleados e ON b.empleado_id = e.id_empleado
    LEFT OUTER JOIN gps_rutas r ON r.empleado_id = e.id_empleado')
    ->group_by('a.cliente, a.nit')
    ->order_by('cliente asc, nit asc')
    ->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_imprimir = in_array('imprimir', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_modificar = in_array('editar', $permisos);

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Clientes</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_imprimir) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para ver el reporte hacer clic en el siguiente botón: </div>
		</div>
        <div class="col-xs-12 col-sm-4 text-right">
            <a href="?/clientes/imprimir" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs"> Imprimir</span></a>
            <a href="?/clientes/crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs"> Crear cliente</span></a>
            <a href="?/clientes/crear_tipo" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs"> Crear tipo</span></a>
        </div>
	</div>
	<hr>
	<?php } ?>
	<?php if ($clientes) { ?>
	<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
		<thead>
			<tr class="active">
				<th class="text-nowrap">#</th>
                <th class="text-nowrap">Imagen</th>
                <th class="text-nowrap">Código cliente</th>
                <th class="text-nowrap">Cliente</th>
                <th class="text-nowrap">NIT/CI</th>
                <th class="text-nowrap">Telefono</th>
                <th class="text-nowrap">Dirección</th>
                <th class="text-nowrap">Tipo</th>
               
				<th class="text-nowrap">Visitas</th>
                 <th class="text-nowrap">Ruta</th>
                <th class="text-nowrap">Vendedor</th>
            <?php if ($permiso_modificar || $permiso_eliminar) : ?>
                    <th class="text-nowrap">Opciones</th>
            <?php endif ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Imagen</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Cliente</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Código cliente</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">NIT/CI</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Telefono</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Dirección</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Tipo</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Visitas</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Ruta</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Vendedor</th>
            <?php if ($permiso_modificar || $permiso_eliminar) : ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Opciones</th>
            <?php endif ?>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($clientes as $nro => $cliente) { ?>
			<tr>
				<th class="text-nowrap"><?= $nro + 1; ?></th>
                <td class="text-nowrap text-middle text-center">
                    <img src="<?= ($cliente['imagen'] == '') ? imgs . '/image.jpg' : files . '/tiendas/' . $cliente['imagen']; ?>"  class="img-rounded cursor-pointer" data-toggle="modal" data-target="#modal_mostrar" data-modal-size="modal-md" data-modal-title="Imagen" width="75" height="75">
                </td>
				<td class="text-nowrap"><?= escape($cliente['id_cliente']); ?></td>
				<td class="text-nowrap"><?= escape($cliente['cliente']); ?></td>
                <td class="text-nowrap"><?= escape($cliente['nit']); ?></td>
                <td class="text-nowrap"><?= escape($cliente['telefono']); ?></td>
                <td class="text-nowrap"><?= escape($cliente['direccion']); ?></td>
                <td class="text-nowrap"><?= escape($cliente['tipo']); ?></td>
				<td class="text-nowrap"><?= escape($cliente['nro_visitas']); ?></td>
                <td class="text-nowrap"><?= escape($cliente['empleado']); ?></td>
                <td class="text-nowrap"><?= escape($cliente['ruta']); ?></td>

                <?php if ($permiso_modificar || $permiso_eliminar) : ?>
				<td class="text-nowrap">
                    <?php if ($permiso_modificar) : ?>
                        <a href="?/clientes/editar/<?= $cliente['id_cliente']; ?>" data-toggle="tooltip" data-title="Modificar cliente"><span class="glyphicon glyphicon-edit"></span></a>
                    <?php endif ?>
                    <?php if ($permiso_eliminar) : ?>
                        <a href="?/clientes/eliminar/<?= $cliente['id_cliente']; ?>" data-toggle="tooltip" data-title="Eliminar cliente" data-eliminar="true"><span class="glyphicon glyphicon-trash"></span></a>
                    <?php endif ?>
				</td>
                <?php endif ?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>No existen clientes registrados en la base de datos.</p>
	</div>
	<?php } ?>
    <div id="modal_mostrar" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content loader-wrapper">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"></h4>
                </div>
                <div class="modal-body">
                    <img src="" class="img-responsive img-rounded" data-modal-image="">
                </div>
                <div id="loader_mostrar" class="loader-wrapper-backdrop">
                    <span class="loader"></span>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>
<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>
<script>
$(function () {
	<?php if ($permiso_imprimir) { ?>
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'p':
					e.preventDefault();
					window.location = '?/clientes/imprimir';
				break;
			}
		}
	});
	<?php } ?>
	
	<?php if ($clientes) { ?>
	var table = $('#table').DataFilter({
		filter: true,
		name: 'clientes',
		reports: 'excel|word|pdf|html'
	});
	<?php } ?>
});
var $modal_mostrar = $('#modal_mostrar'), $loader_mostrar = $('#loader_mostrar'), size, title, image;

$modal_mostrar.on('hidden.bs.modal', function () {
    $loader_mostrar.show();
    $modal_mostrar.find('.modal-dialog').attr('class', 'modal-dialog');
    $modal_mostrar.find('.modal-title').text('');
}).on('show.bs.modal', function (e) {
    size = $(e.relatedTarget).attr('data-modal-size');
    title = $(e.relatedTarget).attr('data-modal-title');
    image = $(e.relatedTarget).attr('src');
    size = (size) ? 'modal-dialog ' + size : 'modal-dialog';
    title = (title) ? title : 'Imagen';
    $modal_mostrar.find('.modal-dialog').attr('class', size);
    $modal_mostrar.find('.modal-title').text(title);
    $modal_mostrar.find('[data-modal-image]').attr('src', image);
}).on('shown.bs.modal', function () {
    $loader_mostrar.hide();
});

</script>
<?php require_once show_template('footer-advanced'); ?>