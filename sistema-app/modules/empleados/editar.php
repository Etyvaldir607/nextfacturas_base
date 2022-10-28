<?php

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);
$Datos=$db->query('SELECT*FROM inv_departamentos')->fetch();

// Obtiene el id_empleado
$id_empleado = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el empleado
$empleado = $db->select('z.*')->from('sys_empleados z')->where('z.id_empleado', $id_empleado)->fetch_first();

// Verifica si existe el empleado
if (!$empleado) {
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
		<strong>Editar empleado</strong>
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
			<a href="?/empleados/crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
			<?php } ?>
			<?php if ($permiso_ver) { ?>
			<a href="?/empleados/ver/<?= $empleado['id_empleado']; ?>" class="btn btn-warning"><i class="glyphicon glyphicon-search"></i><span class="hidden-xs hidden-sm"> Ver</span></a>
			<?php } ?>
			<?php if ($permiso_eliminar) { ?>
			<a href="?/empleados/eliminar/<?= $empleado['id_empleado']; ?>" class="btn btn-danger" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/empleados/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs"> Listado</span></a>
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<form method="post" action="?/empleados/guardar" class="form-horizontal">
				<div class="form-group">
					<label for="nombres" class="col-md-3 control-label">Nombres:</label>
					<div class="col-md-9">
						<input type="hidden" value="<?= $empleado['id_empleado']; ?>" name="id_empleado" data-validation="required">
						<input type="text" value="<?= $empleado['nombres']; ?>" name="nombres" id="nombres" class="form-control" autocomplete="off" data-validation="required letternumber length" data-validation-allowing=" " data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="paterno" class="col-md-3 control-label">Apellido paterno:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $empleado['paterno']; ?>" name="paterno" id="paterno" class="form-control" autocomplete="off" data-validation="letternumber length" data-validation-allowing=" " data-validation-length="max100" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="materno" class="col-md-3 control-label">Apellido materno:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $empleado['materno']; ?>" name="materno" id="materno" class="form-control" autocomplete="off" data-validation="letternumber length" data-validation-allowing=" " data-validation-length="max100" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="genero" class="col-md-3 control-label">Género:</label>
					<div class="col-md-9">
						<div class="radio">
							<label>
								<input type="radio" name="genero" value="Masculino" <?= ($empleado['genero'] == 'Masculino') ? 'checked' : ''; ?>>
								<span>Masculino</span>
							</label>
						</div>
						<div class="radio">
							<label>
								<input type="radio" name="genero" value="Femenino" <?= ($empleado['genero'] == 'Femenino') ? 'checked' : ''; ?>>
								<span>Femenino</span>
							</label>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label for="fecha_nacimiento" class="col-md-3 control-label">Fecha de nacimiento:</label>
					<div class="col-md-9">
						<input type="text" value="<?= date_decode($empleado['fecha_nacimiento'], $_institution['formato']); ?>" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" autocomplete="off" data-validation="birthdate" data-validation-format="<?= $formato_textual; ?>" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="telefono" class="col-md-3 control-label">Teléfono:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $empleado['telefono']; ?>" name="telefono" id="telefono" class="form-control" autocomplete="off" data-validation="alphanumeric length" data-validation-allowing="-+,() " data-validation-length="max100" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group" style="display:none;">
					<label for="cargo" class="col-md-3 control-label">Empresa:</label>
					<div class="col-md-9">
						<select name="cargo" id="cargo" class="form-control">
                            <option value="1" <?php if($empleado['cargo']==1){echo 'selected';} ?> ><?= $_institution['empresa1'] ?></option>
                            <option value="2" <?php if($empleado['cargo']==2){echo 'selected';} ?> ><?= $_institution['empresa2'] ?></option>
                        </select>
					</div>
				</div>
				<div class="form-group">
					<label for="departamento" class="col-md-3 control-label">Departamento:</label>
					<div class="col-md-9">
					<select name='departamento' id='departamento' class='form-control'>
                        <?php
                            foreach($Datos as $Fila=>$Dato):
                        ?>
                            <option value='<?=$Dato['id_departamento']?>' <?php if($empleado['departamento_id']==$Dato['id_departamento']):echo 'selected';endif;?>><?=$Dato['departamento']?></option>
                        <?php
                            endforeach;
                        ?>
                        </select>
                    </div>
				</div>
				
				<div class="form-group">
					<label for="fecha_nacimiento" class="col-md-3 control-label">Fecha de ingreso:</label>
					<div class="col-md-9">
						<input type="text" value="<?= date_decode($empleado['fecha_ingreso'], $_institution['formato']); ?>" name="fecha_ingreso" id="fecha_ingreso" class="form-control" autocomplete="off" data-validation="required  birthdate" data-validation-format="<?= $formato_textual; ?>" data-validation-optional="true">
					</div>
				</div>

				<div class="form-group">
					<label for="codigo" class="col-md-3 control-label">C.I.:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $empleado['ci'] ?>" name="ci" id="ci" class="form-control" maxlength="10" autocomplete="off" data-validation="required letternumber length" data-validation-allowing=" " data-validation-length="max100">
					</div>
				</div>
				
				<div class="form-group">
					<label for="codigo" class="col-md-3 control-label">Código:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $empleado['codigo'] ?>" name="codigo" id="codigo" class="form-control" maxlength="10" autocomplete="off" data-validation="required letternumber length" data-validation-allowing=" " data-validation-length="max100">
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
<script src="<?= js; ?>/moment.min.js"></script>
<script src="<?= js; ?>/moment.es.js"></script>
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>
<script src="<?= js; ?>/jquery.maskedinput.min.js"></script>
<script src="<?= js; ?>/selectize.min.js"></script>
<script>
$(function () {
	$.validate({
		modules: 'basic,date'
	});

	$('#telefono').selectize({
		persist: false,
		createOnBlur: true,
		create: true,
		onInitialize: function () {
			$('#telefono').css({
				display: 'block',
				left: '-10000px',
				opacity: '0',
				position: 'absolute',
				top: '-10000px'
			});
		},
		onChange: function () {
			$('#telefono').trigger('blur');
		},
		onBlur: function () {
			$('#telefono').trigger('blur');
		}
	});

	$(':reset').on('click', function () {
		$('#telefono')[0].selectize.clear();
	});
	
	$('#fecha_nacimiento').mask('<?= $formato_numeral; ?>').datetimepicker({
		format: '<?= strtoupper($formato_textual); ?>'
	}).on('dp.change', function () {
		$(this).trigger('blur');
	});
	
	$('#fecha_ingreso').mask('<?= $formato_numeral; ?>').datetimepicker({
		format: '<?= strtoupper($formato_textual); ?>'
	}).on('dp.change', function () {
		$(this).trigger('blur');
	});
	
	$('.form-control:first').select();

	$('.form-control:first').select();
	
	<?php if ($permiso_eliminar) { ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar el empleado?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>