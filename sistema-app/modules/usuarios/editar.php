<?php

// Obtiene el id_user
$id_user = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el modelo roles
$roles = $db->from('sys_roles')->order_by('rol', 'asc')->fetch();

//obtiene los almacenes
$almacenes = $db->from('inv_almacenes')->order_by('principal','desc')->fetch();

// Obtiene el usuario
$user = $db->select('u.*, r.rol as rol')->from('sys_users u')->join('sys_roles r', 'u.rol_id = r.id_rol', 'left')->where('u.id_user', $id_user)->fetch_first();

// Verifica si existe el usuario
if (!$user) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_ver = in_array('ver', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_listar = in_array('listar', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Editar usuario</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_crear || $permiso_ver || $permiso_eliminar || $permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<?php if ($permiso_crear) { ?>
			<a href="?/usuarios/crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
			<?php } ?>
			<?php if ($permiso_ver) { ?>
			<a href="?/usuarios/ver/<?= $user['id_user']; ?>" class="btn btn-warning"><i class="glyphicon glyphicon-search"></i><span class="hidden-xs hidden-sm"> Ver</span></a>
			<?php } ?>
			<?php if ($permiso_eliminar) { ?>
			<a href="?/usuarios/eliminar/<?= $user['id_user']; ?>" class="btn btn-danger" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/usuarios/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs"> Listado</span></a>
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
								<input type="radio" name="active" id="active" value="1" <?= ($user['active'] == '1') ? 'checked' : ''; ?>>
								<span>Activado</span>
							</label>
						</div>
						<div class="radio">
							<label>
								<input type="radio" name="active" value="0" <?= ($user['active'] == '0') ? 'checked' : ''; ?>>
								<span>Bloqueado</span>
							</label>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label for="username" class="col-md-3 control-label">Usuario:</label>
					<div class="col-md-9">
						<input type="hidden" value="<?= $user['id_user']; ?>" name="id_user" data-validation="required">
						<input type="text" value="<?= $user['username']; ?>" name="username" id="username" class="form-control" autocomplete="off" data-validation="required alphanumeric" data-validation-allowing="_">
					</div>
				</div>
				<div class="form-group">
					<label for="password_confirmation" class="col-md-3 control-label">Contraseña:</label>
					<div class="col-md-9">
						<input type="password" value="" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="off" data-validation="length strength" data-validation-length="5-50" data-validation-strength="2" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="password" class="col-md-3 control-label">Confirmar contraseña:</label>
					<div class="col-md-9">
						<input type="password" value="" name="password" id="password" class="form-control" autocomplete="off" data-validation="confirmation">
					</div>
				</div>
				<div class="form-group">
					<label for="email" class="col-md-3 control-label">Correo:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $user['email']; ?>" name="email" id="email" class="form-control" autocomplete="off" data-validation="email" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="rol_id" class="col-md-3 control-label">Rol:</label>
					<div class="col-md-9">
						<select name="rol_id" id="rol_id" class="form-control" data-validation="required">
							<option value="">Seleccionar</option>
							<?php foreach ($roles as $elemento) { ?>
								<?php if ($elemento['id_rol'] == $user['rol_id']) { ?>
								<option value="<?= $elemento['id_rol']; ?>" selected><?= escape($elemento['rol']); ?></option>
								<?php } else { ?>
								<option value="<?= $elemento['id_rol']; ?>"><?= escape($elemento['rol']); ?></option>
								<?php } ?>
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
				<!--				<?php if ($elemento['id_almacen'] == $user['almacen_id']) { ?>-->
				<!--				<option value="<?= $elemento['id_almacen']; ?>" selected><?= escape($elemento['almacen']); ?></option>-->
				<!--				<?php } else { ?>-->
				<!--				<option value="<?= $elemento['id_almacen']; ?>"><?= escape($elemento['almacen']); ?></option>-->
				<!--				<?php } ?>-->
				<!--			<?php } ?>-->
				<!--		</select>-->
				<!--	</div>-->
				<!--</div>-->
				
				<div class="form-group">
					<label for="almacen_id" class="col-md-3 control-label">Almacenes:</label>
					<div class="col-md-9">
						<?php $almacenes = $db->query("SELECT * 
                                                            FROM inv_almacenes a
                                                            LEFT JOIN inv_users_almacenes as ua ON ua.almacen_id = a.id_almacen and user_id = '$id_user'
                                                            ORDER BY a.id_almacen ASC")->fetch();
						foreach ($almacenes as $elemento) { 
						    if($elemento['id_user_almacen']== ''){?>
						        <label><input type="checkbox" name="id_almacen[]" value="<?= $elemento['id_almacen'] ?>"> <?= $elemento['almacen']; ?></label><br>
				            <?php }else{ ?>
				                <label><input type="checkbox" name="id_almacen[]" value="<?= $elemento['id_almacen'] ?>" checked> <?= $elemento['almacen']; ?></label><br>
				            <?php } ?>
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
	
	<?php if ($permiso_eliminar) { ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar el usuario?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>