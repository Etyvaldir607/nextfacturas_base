<?php

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);
$Datos=$db->query('SELECT * FROM inv_departamentos')->fetch();
// $val_codigo = $db->query("SELECT codigo FROM sys_empleados WHERE visible = 'S'")->fetch();
// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_listar = in_array('listar', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Crear empleado</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para regresar al listado de empleados hacer clic en el siguiente botón:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/empleados/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span> Listado</span></a>
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
						<input type="hidden" value="0" name="id_empleado" data-validation="required number">
						<input type="hidden" value="0" name="id_empleado" data-validation="required number">
						<input type="text" value="" name="nombres" id="nombres" class="form-control" autocomplete="off" data-validation="required letternumber length" data-validation-allowing=" " data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="paterno" class="col-md-3 control-label">Apellido paterno:</label>
					<div class="col-md-9">
						<input type="text" value="" name="paterno" id="paterno" class="form-control" autocomplete="off" data-validation="letternumber length" data-validation-allowing=" " data-validation-length="max100" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="materno" class="col-md-3 control-label">Apellido materno:</label>
					<div class="col-md-9">
						<input type="text" value="" name="materno" id="materno" class="form-control" autocomplete="off" data-validation="letternumber length" data-validation-allowing=" " data-validation-length="max100" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="genero" class="col-md-3 control-label">Género:</label>
					<div class="col-md-9">
						<div class="radio">
							<label>
								<input type="radio" name="genero" value="Masculino" checked>
								<span>Masculino</span>
							</label>
						</div>
						<div class="radio">
							<label>
								<input type="radio" name="genero" value="Femenino" >
								<span>Femenino</span>
							</label>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label for="fecha_nacimiento" class="col-md-3 control-label">Fecha de nacimiento:</label>
					<div class="col-md-9">
						<input type="text" value="" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" autocomplete="off" data-validation="required  birthdate" data-validation-format="<?= $formato_textual; ?>" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="telefono" class="col-md-3 control-label">Teléfono:</label>
					<div class="col-md-9">
						<input type="text" value="" name="telefono" id="telefono" class="form-control" autocomplete="off" data-validation="alphanumeric length" data-validation-allowing="-+,() " data-validation-length="max100" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="departamento" class="col-md-3 control-label">Departamento:</label>
					<div class="col-md-9">
					<select name='departamento' id='departamento' class='form-control'>
                        <?php
                            foreach($Datos as $Fila=>$Dato):
                        ?>
                            <option value='<?=$Dato['id_departamento']?>'><?=$Dato['departamento']?></option>
                        <?php
                            endforeach;
                        ?>
                        </select>
                    </div>
				</div>
				<!--<div class="form-group">-->
				<!--	<label for="cargo" class="col-md-3 control-label">Empresa:</label>-->
				<!--	<div class="col-md-9">-->
    <!--                    <select name="cargo" id="cargo" class="form-control">-->
    <!--                        <option value="1"><?= $_institution['empresa1']; ?></option>-->
    <!--                        <option value="2"><?= $_institution['empresa2']; ?></option>-->
    <!--                    </select>-->
    <!--                </div>-->
				<!--</div>-->
				<!--<div class="form-group">-->
				<!--	<label for="codigo" class="col-md-3 control-label">Código:</label>-->
				<!--	<div class="col-md-9">-->
				<!--		<input type="text" value="" name="codigo" id="codigo" class="form-control" maxlength="10" autocomplete="off" data-validation="required letternumber length" data-validation-allowing=" " data-validation-length="max100">-->
				<!--	</div>-->
				<!--</div>-->
				<div class="form-group">
					<label for="fecha_nacimiento" class="col-md-3 control-label">Fecha de ingreso:</label>
					<div class="col-md-9">
						<input type="text" value="" name="fecha_ingreso" id="fecha_ingreso" class="form-control" autocomplete="off" data-validation="required  birthdate" data-validation-format="<?= $formato_textual; ?>" data-validation-optional="true">
					</div>
				</div>

				<div class="form-group">
					<label for="codigo" class="col-md-3 control-label">C.I.:</label>
					<div class="col-md-9">
						<input type="text" value="" name="ci" id="ci" class="form-control" maxlength="10" autocomplete="off" data-validation="required letternumber length" data-validation-allowing=" " data-validation-length="max100">
					</div>
				</div>
				
				<div class="form-group">
					<label for="codigo" class="col-md-3 control-label">Código:</label>
					<div class="col-md-9">
						<input type="text" value="" name="codigo" id="codigo" class="form-control" maxlength="10" autocomplete="off" data-validation="required letternumber length" data-validation-allowing=" " data-validation-length="max100">
					</div>
				</div>
				
				
				
                

				
				
				
				<!--<div class="form-group">-->
    <!--				<label for="codigo" class="col-md-3 control-label">Código:</label>-->
    <!--    			<form id="validacion_codigo" data-codigo="<?= '0'; ?>">-->
    <!--    				<div class="input-group">-->
    <!--    					<input type="text" class="form-control">-->
    <!--    					<span class="input-group-btn">-->
        						<!--<button type="submit" class="btn btn-warning">-->
        						<!--	<span class="glyphicon glyphicon-barcode"></span>-->
        						<!--	<span>Verificar</span>-->
        						<!--</button>-->
    <!--    					</span>-->
    <!--    				</div>-->
    <!--    			</form>-->
    <!--			</div>-->
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
    
//     $('#validacion_codigo').on('submit', function (e) {
// 		e.preventDefault();
// 		var codigo = $(this).attr('data-codigo'), codigo_e = $.trim($(this).find(':text').val());
	    
// 	    <?php foreach($val_codigo as $nro => $codigo) {?>
//     		if (codigo_e == <?= escape($val_codigo['codigo']) ?>) {
//                 $(this).find(':text').val('');
//     			bootbox.alert('Otro empleado ya cuenta con el código ingresado');
//     		}
// 		<?php } ?>
// 	});

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
});
</script>
<?php require_once show_template('footer-advanced'); ?>