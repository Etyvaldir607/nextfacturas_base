<?php

// Obtiene el modelo roles
$roles = $db->from('sys_roles')->order_by('rol', 'asc')->fetch();

//obtiene los almacenes
$almacenes = $db->from('inv_almacenes')->order_by('principal','desc')->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_listar = in_array('listar', $permisos);
$permiso_crear = in_array('crear', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Crear usuario</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_listar || $permiso_crear) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para regresar al listado de usuario hacer clic en el siguiente botón:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<?php if ($permiso_crear) { ?>
			    <a href="?/empleados/crear" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i><span> Nuevo empleado</span></a>
			<?php } ?>
			<?php if ($permiso_crear) { ?>
			    <a href="?/usuarios/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span> Listado</span></a>
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<form method="POST" action="?/usuarios/guardar" class="form-horizontal">
				<div class="form-group">
					<label for="active" class="col-md-3 control-label">Estado:</label>
					<div class="col-md-9">
						<div class="radio">
							<label>
								<input type="radio" name="active" id="active" value="1" checked>
								<span>Activado</span>
							</label>
						</div>
						<div class="radio">
							<label>
								<input type="radio" name="active" value="0">
								<span>Bloqueado</span>
							</label>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label for="username" class="col-md-3 control-label">Usuario:</label>
					<div class="col-md-9">
						<input type="hidden" value="0" name="id_user" data-validation="required">
						<input type="text" value="" name="username" id="username" class="form-control" autocomplete="off" data-validation="required alphanumeric server" data-validation-allowing="_" data-validation-url="?/usuarios/validar">
					</div>
				</div>
				<div class="form-group">
					<label for="password_confirmation" class="col-md-3 control-label">Contraseña:</label>
					<div class="col-md-9">
						<input type="password" value="" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="off" data-validation="required length strength" data-validation-length="5-50" data-validation-strength="2">
					</div>
				</div>
				<div class="form-group">
					<label for="password" class="col-md-3 control-label">Confirmar contraseña:</label>
					<div class="col-md-9">
						<input type="password" value="" name="password" id="password" class="form-control" autocomplete="off" data-validation="required confirmation">
					</div>
				</div>
				<div class="form-group">
					<label for="email" class="col-md-3 control-label">Correo:</label>
					<div class="col-md-9">
						<input type="text" value="" name="email" id="email" class="form-control" autocomplete="off" data-validation="email" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="rol_id" class="col-md-3 control-label">Rol:</label>
					<div class="col-md-9">
						<select name="rol_id" id="rol_id" class="form-control" data-validation="required">
							<option value="">Seleccionar</option>
							<?php foreach ($roles as $elemento) { ?>
							<option value="<?= $elemento['id_rol']; ?>"><?= escape($elemento['rol']); ?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				
				<!--<div class="form-group">-->
				<!--	<label for="almacen_id" class="col-md-3 control-label">Almacén:</label>-->
				<!--	<div class="col-md-9">-->
				<!--		<select name="almacen_id" id="almacen_id" class="form-control" data-validation="required">-->
				<!--			<option value="">Seleccionar</option>-->
				<!--			<?php foreach ($almacenes as $elemento) { ?>-->
				<!--			<option value="<?= $elemento['id_almacen']; ?>"><?= escape($elemento['almacen']); ?></option>-->
				<!--			<?php } ?>-->
				<!--		</select>-->
				<!--	</div>-->
				<!--</div>-->
				
				<!--<hr>-->
				<div class="form-group">
					<label for="almacen_id" class="col-md-3 control-label">Almacenes:</label>
					<div class="col-md-9">
						<?php foreach ($almacenes as $elemento) { ?>
						    <label><input type="checkbox" name="id_almacen[]" value="<?= $elemento['id_almacen'] ?>"> <?= $elemento['almacen']; ?></label><br>
						<?php } ?>
					</div>
				</div>

				<div class="form-group">
					<div class="col-md-9 col-md-offset-3">
						<button type="submit" class="btn btn-primary">
							<span class="glyphicon glyphicon-floppy-disk"></span>
							<span>Guardar</span>
						</button>
						<button type="reset" class="btn btn-default">
							<span class="glyphicon glyphicon-refresh"></span>
							<span>Restablecer</span>
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script>
$(function () {
	$.validate({
		modules: 'security'
	});

	$('.form-control:first').select();
});
</script>
<?php require_once show_template('footer-advanced'); ?>