<?php

// Obtiene el id_user
$id_user = $_SESSION[user]['id_user'];

// Obtiene el user
$user = $db->select('u.*, r.rol as rol')->from('sys_users u')->join('sys_roles r', 'u.rol_id = r.id_rol', 'left')->where('u.id_user', $id_user)->fetch_first();

// Verifica si existe el usuario
if (!$user) {
	// Error 404
	require_once not_found();
	exit;
}

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Datos de usuario</strong>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para regresar a tu perfil hacer clic en el botón:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<a href="?/<?= home; ?>/perfil_ver" class="btn btn-warning"><i class="glyphicon glyphicon-search"></i><span> Ver perfil</span></a>
		</div>
	</div>
	<hr>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<form method="post" action="?/<?= home; ?>/perfil_guardar" class="form-horizontal">
				<div class="form-group">
					<label for="username" class="col-md-3 control-label">Usuario:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $user['username']; ?>" name="username" id="username" class="form-control" autocomplete="off" data-validation="required alphanumeric" data-validation-allowing="_">
					</div>
				</div>
				<div class="form-group">
					<label for="password_confirmation" class="col-md-3 control-label">Contraseña:</label>
					<div class="col-md-9">
						<input type="password" value="" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="off" data-validation="length strength" data-validation-length="5-30" data-validation-strength="2" data-validation-optional="true">
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