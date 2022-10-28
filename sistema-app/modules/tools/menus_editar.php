<?php

// Obtiene el modelo menus
$menus = $db->from('sys_menus')->fetch();

// Obtiene el id_menu
$id_menu = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el menu
$menu = $db->select('z.*, a.menu AS antecesor')->from('sys_menus z')->join('sys_menus a', 'z.antecesor_id = a.id_menu', 'left')->where('z.id_menu', $id_menu)->fetch_first();

// Verifica si existe el menu
if (!$menu) {
	// Error 404
	require_once not_found();
	exit;
}

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Editar menú</strong>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<a href="?/<?= tools; ?>/menus_crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
			<a href="?/<?= tools; ?>/menus_ver/<?= $menu['id_menu']; ?>" class="btn btn-warning"><i class="glyphicon glyphicon-search"></i><span class="hidden-xs hidden-sm"> Ver</span></a>
			<a href="?/<?= tools; ?>/menus_eliminar/<?= $menu['id_menu']; ?>" class="btn btn-danger" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
			<a href="?/<?= tools; ?>/menus_listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs"> Listado</span></a>
		</div>
	</div>
	<hr>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<form method="post" action="?/<?= tools; ?>/menus_guardar" class="form-horizontal">
				<div class="form-group">
					<label for="menu" class="col-md-3 control-label">Menú:</label>
					<div class="col-md-9">
						<input type="hidden" value="<?= $menu['id_menu']; ?>" name="id_menu" data-validation="required">
						<input type="text" value="<?= $menu['menu']; ?>" name="menu" id="menu" class="form-control" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-/ " data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="icono" class="col-md-3 control-label">Ícono:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $menu['icono']; ?>" name="icono" id="icono" class="form-control" autocomplete="off" data-validation="required alphanumeric length" data-validation-allowing="-" data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="ruta" class="col-md-3 control-label">Ruta:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $menu['ruta']; ?>" name="ruta" id="ruta" class="form-control" placeholder="?/nombre_modulo/nombre_archivo" autocomplete="off" data-validation="custom length" data-validation-regexp="^\?(\/[a-z0-9-_]+){2,}$" data-validation-length="max200" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="antecesor_id" class="col-md-3 control-label">Antecesor:</label>
					<div class="col-md-9">
						<select name="antecesor_id" id="antecesor_id" class="form-control" data-validation="number" data-validation-optional="true">
							<option value="">Seleccionar</option>
							<?php foreach ($menus as $elemento) { ?>
								<?php if ($elemento['id_menu'] == $menu['antecesor_id']) { ?>
								<option value="<?= $elemento['id_menu']; ?>" selected><?= escape($elemento['id_menu']); ?> &mdash; <?= escape($elemento['menu']); ?></option>
								<?php } else { ?>
								<option value="<?= $elemento['id_menu']; ?>"><?= escape($elemento['id_menu']); ?> &mdash; <?= escape($elemento['menu']); ?></option>
								<?php } ?>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-9 col-md-offset-3">
						<button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i><span> Guardar</span></button>
						<button type="reset" class="btn btn-default"><i class="glyphicon glyphicon-refresh"></i><span class="hidden-xs"> Limpiar</span></button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/selectize.min.js"></script>
<script>
$(function () {
	$.validate({
		modules: 'basic'
	});

	$('#antecesor_id').selectize({
		maxOptions: 6,
		onInitialize: function () {
			$('#antecesor_id').css({
				display: 'block',
				left: '-10000px',
				opacity: '0',
				position: 'absolute',
				top: '-10000px'
			});
		},
		onChange: function (value) {
			$('#antecesor_id').trigger('blur');
		},
		onBlur: function () {
			$('#antecesor_id').trigger('blur');
		}
	});

	$(':reset').on('click', function () {
		$('#antecesor_id')[0].selectize.clear();
	});
	
	$('.form-control:first').select();

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