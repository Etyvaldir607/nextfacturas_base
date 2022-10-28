<?php

// Obtiene el id_menu
$id_menu = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el menu
$menu = $db->select('z.*, a.menu AS antecesor')
->from('sys_menus z')
->join('sys_menus a', 'z.antecesor_id = a.id_menu', 'left')
->where('z.id_menu', $id_menu)
->fetch_first();

// Verifica si existe el menu
if (!$menu) {
	// Error 404
	require_once not_found();
	exit;
}

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title"><i class="glyphicon glyphicon-option-vertical"></i> Ver menú</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<a href="?/<?= tools; ?>/menus_crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
			<a href="?/<?= tools; ?>/menus_editar/<?= $menu['id_menu']; ?>" class="btn btn-warning"><i class="glyphicon glyphicon-edit"></i><span class="hidden-xs hidden-sm"> Editar</span></a>
			<a href="?/<?= tools; ?>/menus_eliminar/<?= $menu['id_menu']; ?>" class="btn btn-danger" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
			<a href="?/<?= tools; ?>/menus_listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs"> Listado</span></a>
		</div>
	</div>
	<hr>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<div class="form-horizontal">
				<div class="form-group">
					<label class="col-md-3 control-label">#:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($menu['id_menu']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Menú:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($menu['menu']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Ícono:</label>
					<div class="col-md-9">
						<p class="form-control-static"><i class="glyphicon glyphicon-<?= $menu['icono']; ?>"></i> <?= escape($menu['icono']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Ruta:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= ($menu['ruta'] == '') ? 'Ninguno' : escape($menu['ruta']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Antecesor:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= ($menu['antecesor'] == '') ? 'Ninguno' : escape($menu['antecesor']); ?></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
$(function () {
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar el menú?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
});
</script>
<?php require_once show_template('footer-advanced'); ?>