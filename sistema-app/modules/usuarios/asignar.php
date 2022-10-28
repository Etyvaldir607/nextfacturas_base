<?php

// Obtiene el id_user
$id_user = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el modelo users
$user = $db->from('sys_users')->where('id_user', $id_user)->fetch_first();

// Verifica si existe el usuario
if (!$user) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene los empleados
$empleados = $db->select('e.id_empleado, e.paterno, e.materno, e.nombres')->from('sys_empleados e')->join('sys_users u', 'u.persona_id = e.id_empleado', 'left')->where('isnull(u.id_user)', true)->fetch();

// Obtiene al actual empleado
$empleado = $db->select('id_empleado, paterno, materno, nombres')->from('sys_empleados')->where('id_empleado', $user['persona_id'])->fetch();

// Adicionamos al empleado a la lista de empleados disponibles
$empleados = array_merge($empleados, $empleado);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Crear usuario</strong>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para regresar al listado de usuario hacer clic en el siguiente botón:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/usuarios/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span> Listado</span></a>
		</div>
	</div>
	<hr>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<form method="POST" action="?/usuarios/actualizar" class="form-horizontal">
				<div class="form-group">
					<label class="col-md-3 control-label">Usuario:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($user['username']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Correo:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($user['email']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label for="persona_id" class="col-md-3 control-label">Empleado:</label>
					<div class="col-md-9">
						<input type="hidden" value="<?= $user['id_user']; ?>" name="id_user" data-validation="required number">
						<select name="persona_id" id="persona_id" class="form-control text-uppercase" data-validation="required" data-validation-allowing="-+./&()">
							<option value="">Seleccionar</option>
							<?php foreach ($empleados as $empleado) { ?>
								<?php if ($empleado['id_empleado'] == $user['persona_id']) { ?>
								<option value="<?= $empleado['id_empleado']; ?>" selected><?= escape($empleado['paterno'] . ' ' . $empleado['materno'] . ' ' . $empleado['nombres']); ?></option>
								<?php } else { ?>
								<option value="<?= $empleado['id_empleado']; ?>"><?= escape($empleado['paterno'] . ' ' . $empleado['materno'] . ' ' . $empleado['nombres']); ?></option>
								<?php } ?>
							<?php } ?>
						</select>
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
<script src="<?= js; ?>/selectize.min.js"></script>
<script>
$(function () {
	$.validate();

	$('#persona_id').selectize({
		maxOptions: 6,
		onInitialize: function () {
			$('#persona_id').css({
				display: 'block',
				left: '-10000px',
				opacity: '0',
				position: 'absolute',
				top: '-10000px'
			});
		},
		onChange: function (value) {
			$('#persona_id').trigger('blur');
		},
		onBlur: function () {
			$('#persona_id').trigger('blur');
		}
	});

	$(':reset').on('click', function () {
		$("#persona_id")[0].selectize.clear();
	});
});
</script>
<?php require_once show_template('footer-advanced'); ?>