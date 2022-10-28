<?php

// Obtiene los clientes
$clientes = $db->from('cli_clientes')->order_by('id_cliente')->fetch();

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Lista de registrados</strong>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para agregar nuevas categorías hacer clic en el siguiente botón: </div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
		</div>
	</div>
	<hr>
	<?php if ($clientes) { ?>
	<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
		<thead>
			<tr class="active">
				<th class="text-nowrap">#</th>
				<th class="text-nowrap">Cliente</th>
				<th class="text-nowrap">CI</th>
				<th class="text-nowrap">Teléfono</th>
				<th class="text-nowrap">Correo electrónico</th>
				<th class="text-nowrap">Institución</th>
				<th class="text-nowrap">Fecha graduación</th>
				<th class="text-nowrap">Descripción</th>
				<th class="text-nowrap">Opciones</th>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Cliente</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">CI</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Teléfono</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Correo electrónico</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Institución</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Fecha graduación</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Descripción</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($clientes as $nro => $cliente) { ?>
			<tr>
				<th class="text-nowrap"><?= $nro + 1; ?></th>
				<td class="text-nowrap"><?= escape($cliente['cliente']); ?></td>
				<td class="text-nowrap"><?= escape($cliente['ci']); ?></td>
				<td class="text-nowrap"><?= escape($cliente['telefono']); ?></td>
				<td class="text-nowrap"><?= escape($cliente['correo']); ?></td>
				<td class="text-nowrap"><?= escape($cliente['institucion']); ?></td>
				<td class="text-nowrap"><?= escape($cliente['fecha_graduacion']); ?></td>
				<td class="text-nowrap"><?= escape($cliente['descripcion']); ?></td>
				<td class="text-nowrap">
					<a href="http://localhost:9000/ticket/index.php?c=<?= $cliente['codigo']; ?>&d=<?= $cliente['descripcion']; ?>" class="btn btn-primary btn-xs" target="_blank">IMPRIMIR TICKET</a>
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>No existen categorías registradas en la base de datos, para crear nuevas categorías hacer clic en el botón nuevo o presionar las teclas <kbd>alt + n</kbd>.</p>
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
	<?php if ($clientes) { ?>
	var table = $('#table').DataFilter({
		filter: false,
		name: 'categorias',
		reports: 'excel|word|pdf|html'
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>