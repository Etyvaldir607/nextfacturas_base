<?php

// Obtiene los empleados
$empleados = $db->query("SELECT e.*,d.departamento
						FROM sys_empleados AS e
						LEFT JOIN inv_departamentos AS d ON e.departamento_id=d.id_departamento
						ORDER BY e.id_empleado")->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

$horarios = $db->from('rrhh_horario')->fetch();

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_editar = in_array('editar', $permisos);
$permiso_ver = in_array('ver', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos);

$permiso_asignar = in_array('asignar', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Empleados</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if (($permiso_crear || $permiso_imprimir) && ($permiso_crear || $empleados)) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para agregar nuevos empleados hacer clic en el siguiente botón: </div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<?php if ($permiso_imprimir) { ?>
			<a href="?/empleados/imprimir" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_crear) { ?>
			<a href="?/empleados/crear" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i><span> Nuevo</span></a>
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<?php if (isset($_SESSION[temporary])) { ?>
	<div class="alert alert-<?= $_SESSION[temporary]['alert']; ?>">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong><?= $_SESSION[temporary]['title']; ?></strong>
		<p><?= $_SESSION[temporary]['message']; ?></p>
	</div>
	<?php unset($_SESSION[temporary]); ?>
	<?php } ?>
	<?php if ($empleados) { ?>
	<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
		<thead>
			<tr class="active">
				<th class="text-nowrap">#</th>
				<th class="text-nowrap">Nombres</th>
				<th class="text-nowrap">Apellido paterno</th>
				<th class="text-nowrap">Apellido materno</th>
				<th class="text-nowrap">Código</th>
				<th class="text-nowrap">Departamento</th>
				<th class="text-nowrap">Género</th>
				<th class="text-nowrap">Fecha de nacimiento</th>
				<th class="text-nowrap">Teléfono</th>
                <th class="text-nowrap">Empresa</th>
				<th class="text-nowrap">Cargo</th>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar) { ?>
				<th class="text-nowrap">Opciones</th>
				<?php } ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Nombres</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Apellido paterno</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Apellido materno</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Código</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Departamento</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Género</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Fecha de nacimiento</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Teléfono</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Empresa</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Cargo</th>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar) { ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
				<?php } ?>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($empleados as $nro => $empleado) { ?>
			<tr>
				<th class="text-nowrap"><?= $nro + 1; ?></th>
				<td class="text-nowrap"><?= escape($empleado['nombres']); ?></td>
				<td class="text-nowrap"><?= escape($empleado['paterno']); ?></td>
				<td class="text-nowrap"><?= escape($empleado['materno']); ?></td>
				<td class="text-nowrap"><?= escape($empleado['codigo']); ?></td>
				<td class="text-nowrap"><?= escape($empleado['departamento']); ?></td>
				<td class="text-nowrap"><?= escape($empleado['genero']); ?></td>
				<td class="text-nowrap"><?= date_decode(escape($empleado['fecha_nacimiento']), $_institution['formato']); ?></td>
				<td class="text-nowrap">
					<?php $telefono = explode(',', escape($empleado['telefono'])); ?>
					<?php foreach ($telefono as $elemento) { ?>
						<span class="label label-success"><?= $elemento; ?></span>
					<?php } ?>
				</td>
                <td class="text-nowrap"><?php if($empleado['cargo']==1){echo $_institution['empresa1'];}else{echo $_institution['empresa2'];} ?></td>
				<td class="text-nowrap"></td>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar) { ?>
				<td class="text-nowrap">
					<?php if ($permiso_ver) { ?>
					<a href="?/empleados/ver/<?= $empleado['id_empleado']; ?>" data-toggle="tooltip" data-title="Ver empleado"><i class="glyphicon glyphicon-search"></i></a>
					<?php } ?>
					<?php if ($permiso_editar) { ?>
					<a href="?/empleados/editar/<?= $empleado['id_empleado']; ?>" data-toggle="tooltip" data-title="Editar empleado"><i class="glyphicon glyphicon-edit"></i></a>
					<?php } ?>
					<?php if ($permiso_eliminar) { ?>
					<a href="?/empleados/eliminar/<?= $empleado['id_empleado']; ?>" data-toggle="tooltip" data-title="Eliminar empleado" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i></a>
					<?php } ?>
					<?php if ($permiso_asignar) : ?>
					<a href="?/empleados/asignar/<?= $empleado['id_empleado']; ?>" data-toggle="tooltip" title="Asignar horario" data-asignar="true"><span class="glyphicon glyphicon-time"></span></a>
					<?php endif ?>
				</td>
				<?php } ?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>No existen empleados registrados en la base de datos, para crear nuevos empleados hacer clic en el botón nuevo o presionar las teclas <kbd>alt + n</kbd>.</p>
	</div>
	<?php } ?>
</div>

<!-- Modal asignar inicio -->
<?php if ($permiso_asignar) : ?>
<div id="modal_asignar" class="modal fade" tabindex="-1">
	<div class="modal-dialog">
		<form method="post" action="?/empleados/asignar" id="form_asignar" class="modal-content loader-wrapper" autocomplete="off">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Asignar horarios</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label for="horario_id" class="control-label">Turnos disponibles:</label>
					<?php
					$Ids='';
					foreach($horarios as $nro => $horario):
						$Ids.=$horario['id_horario'].',';
						$Jornadas=$db->query("SELECT * FROM rrhh_jornada WHERE horario_id='{$horario['id_horario']}' AND estado=1")->fetch();
					?>
					<div class="checkbox">
						<label>
							<input type="checkbox" id='Check<?=$horario['id_horario']?>' onchange="validador(<?=$horario['id_horario']?>,this.checked)" value="<?= $horario['id_horario']; ?>" name="horario_id[]" data-validation="checkbox_group" data-validation-qty="min1" data-validation-error-msg="Debe seleccionar al menos 1 turno">
						<?php
						$Aux='';
						foreach($Jornadas as $Fila=>$Jornada):
							$Aux.=$Jornada['entrada'].'-'.$Jornada['salida'].' ';
						?>
								<samp class="text-primary"><b><?= substr($Jornada['entrada'], 0, -3); ?></b></samp>
								<span class="text-muted">&mdash;</span>
								<samp class="text-primary"><b><?= substr($Jornada['salida'], 0, -3); ?></b></samp>
								<span class="text-muted">&nbsp;</span>
						<?php
						endforeach;
						?>
							<input type='hidden' id='Horario<?=$horario['id_horario']?>' value='<?=$Aux?>'>
							<span class="text-muted" data-toggle="tooltip" data-title="<?= $horario['horario']; ?>" title="" data-placement="right">
								[<?= str_replace(',', ' - ', $horario['dias']); ?>]
								<input type='hidden' id='Dias<?=$horario['id_horario']?>' value='<?=$horario['dias']?>'>
							</span>
						</label>
					</div>
					<?php
					endforeach;
					?>
					<input type="hidden" id='Ids' value='<?=$Ids?>'>
				</div>
			</div>
			<div class="modal-footer">
				<button type="submit" class="btn btn-primary">
					<span class="glyphicon glyphicon-floppy-disk"></span>
					<span>Guardar</span>
				</button>
				<button type="reset" class="btn btn-default">
					<span class="glyphicon glyphicon-refresh"></span>
					<span>Restablecer</span>
				</button>
			</div>
			<div id="loader_asignar" class="loader-wrapper-backdrop hidden">
				<span class="loader"></span>
			</div>
		</form>
	</div>
</div>
<?php endif ?>
<!-- Modal asignar fin -->

<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>
<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>
<script src='<?= js; ?>/jquery.form-validator.min.js'></script>
<script src='<?= js; ?>/jquery.form-validator.es.js'></script>
<script src="<?= js; ?>/jquery.maskedinput.min.js"></script>
<script>
$(function () {
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
	
	<?php if ($permiso_crear) { ?>
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'n':
					e.preventDefault();
					window.location = '?/empleados/crear';
				break;
			}
		}
	});
	<?php } ?>
	
	<?php if ($empleados) { ?>
	var table = $('#table').DataFilter({
		filter: false,
		name: 'empleados',
		reports: 'excel|word|pdf|html'
	});
	<?php } ?>






	<?php if ($permiso_asignar) : ?>
		var $modal_asignar = $('#modal_asignar'), $form_asignar = $('#form_asignar'), $loader_asignar = $('#loader_asignar'), $fecha_asignacion_asignar = $('#fecha_asignacion_asignar');

		$.validate({
			form: '#form_asignar',
			modules: 'basic',
			onSuccess: function () {
				$loader_asignar.removeClass('hidden');
			}
		});

		$fecha_asignacion_asignar.mask('<?= $formato_numeral; ?>');

		$('[data-asignar]').on('click', function (e) {
			e.preventDefault();
			var href = $(this).attr('href');
			$form_asignar.attr('action', href);
			$modal_asignar.modal({
				backdrop: 'static',
				keyboard: false
			});
		});

		$('[data-grupo-asignar]').on('click', function (e) {
			e.preventDefault();
			var id_empleado = $(this).attr('data-grupo-asignar');
			var href = $(this).attr('href') + '/' + id_empleado;
			if (id_empleado != 'true') {
				$form_asignar.attr('action', href);
				$modal_asignar.modal({
					backdrop: 'static',
					keyboard: false
				});
			} else {
				bootbox.alert('Para continuar con el proceso debe seleccionar al menos un empleado.');
			}
		});

		$modal_asignar.on('hidden.bs.modal', function () {
			$form_asignar.trigger('reset');
		}).on('show.bs.modal', function (e) {
			if ($('.modal:visible').size() != 0) { e.preventDefault(); }
		}).on('shown.bs.modal', function () {
			$form_asignar.find('.form-control:nth(1)').focus();
		});
	<?php endif ?>


});
function validador(id,estado){
	let Ids=document.getElementById('Ids').value.slice(0,-1);
	Ids=Ids.split(',');

	let Dia=document.getElementById('Dias'+id).value.split(',');
	let Horario=document.getElementById('Horario'+id).value.trim().split(' ');

	let Sw=false;
	for(let i=0;i<Ids.length;++i){
		let Check=document.getElementById('Check'+Ids[i]).checked;
		if(Ids[i]!=id && Check){
			let Dias=document.getElementById('Dias'+Ids[i]).value.split(',');
			let Horarios=document.getElementById('Horario'+Ids[i]).value.trim().split(' ');
			for(let j=0;j<Dia.length;++j){
				if(Dias.includes(Dia[j])){
					for(let x=0;x<Horarios.length;++x){
						let Horario1=Horarios[x].split('-');
						for(let y=0;y<Horario.length;++y){
							let Horario2=Horario[y].split('-');
							if(	verificarHora(Horario1[0],Horario1[1],Horario2[0]) ||
								verificarHora(Horario1[0],Horario1[1],Horario2[1]) ||
								verificarHora(Horario2[0],Horario2[1],Horario1[0]) ||
								verificarHora(Horario2[0],Horario2[1],Horario1[1])
							   )//Verificar
								Sw=true;
						}
					}
				}
			}
		}
	}
	if(Sw)
		document.getElementById('Check'+id).checked=false;
}
function verificarHora(dateInicial,dateFinal,dateActual){
	dateInicial=new Date('7/10/2013 '+dateInicial+':00');
	dateFinal=new Date('7/10/2013 '+dateFinal+':00');
	dateActual=new Date('7/10/2013 '+dateActual+':00');
	dateInicial=dateInicial.getTime();
	dateFinal=dateFinal.getTime();
	dateActual=dateActual.getTime();
	return dateActual>=dateInicial && dateActual<=dateFinal;
}
</script>
<?php require_once show_template('footer-advanced'); ?>