<?php

// Obtiene los formatos para la fecha

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_listar = in_array('listar', $permisos);
$permiso_cambiar = in_array('cambiar_vendedor', $permisos);

$grupos = $db->select("g.*, CONCAT(e.codigo ,' - ', e.nombres, ' ', e.paterno, ' ', e.materno) as vendedor")
			->from('inv_clientes_grupos g')
			->join('sys_empleados e', 'g.vendedor_id = e.id_empleado', 'left')
			->fetch();

			// echo $db->last_query();

$vendedores = $db->query("SELECT CONCAT(e.codigo ,' - ', e.nombres, ' ', e.paterno, ' ', e.materno) as vendedor, e.id_empleado, u.id_user, r.rol
							FROM sys_empleados e
							LEFT JOIN sys_users u ON e.id_empleado = u.persona_id
							LEFT JOIN sys_roles r ON r.id_rol = u.rol_id
							WHERE u.active = 1
							AND e.id_empleado NOT IN (SELECT vendedor_id FROM inv_clientes_grupos )
							AND (r.rol = 'Vendedor' OR r.rol = 'vendedor')")->fetch();

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Codigo de Vendedor </strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_listar) { ?>
	<!--<div class="row">-->
		<!--<div class="col-sm-8 hidden-xs">-->
		<!--	<div class="text-label">Para regresar al listado de clientes hacer clic en el siguiente botón:</div>-->
		<!--</div>-->
		<!--<div class="col-xs-12 col-sm-4 text-right">-->
			<!--<a href="?/clientes/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span> Listado</span></a>-->
		<!--</div>-->
	<!--</div>-->
	<hr>
	<?php } ?>

	<?php if (isset($_SESSION[temporary])) { ?>
		<div class='alert alert-<?= ($_SESSION[temporary]['alert'])?$_SESSION[temporary]['alert']:$_SESSION[temporary]['type']; ?>'>
			<button type='button' class='close' data-dismiss='alert'>&times;</button>
			<strong><?= $_SESSION[temporary]['title']; ?></strong>
			<p><?= ($_SESSION[temporary]['message'])?$_SESSION[temporary]['message']:$_SESSION[temporary]['content']; ?></p>
		</div>
		<?php unset($_SESSION[temporary]); ?>
	<?php } ?>
	<div class="row">
		<div class="col-sm-6">
			<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
				<thead>
					<tr class="active">
						<th class="text-nowrap">#</th>
						<th class="">Codigo de vendedor</th>
						<th class="text-nowrap">Vendedor</th>
						<th class="text-nowrap">Credito</th>
						<!--<th class="text-nowrap">Permiso</th>-->
						<th class="text-nowrap">Estado</th>
	                    <th class="text-nowrap">Opciones</th>
					</tr>
				</thead>
				<tfoot>
					<tr class="active">
						<th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Codigo de vendedor</th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Vendedor</th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Credito</th>
						<!--<th class="text-nowrap text-middle" data-datafilter-filter="true">Permiso</th>-->
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Estado</th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Opciones</th>
					</tr>
				</tfoot>
				<tbody>
					<?php foreach ($grupos as $nro => $grupo) { ?>
					<tr>
						<th class="text-nowrap"><?= $nro + 1; ?></th>
						<td class="text-nowrap"><?= escape($grupo['nombre_grupo']); ?></td>
						<td class=""><?= escape($grupo['vendedor']); ?></td>
						<td class="text-nowrap"><?= escape($grupo['credito_grupo']); ?></td>
						<!--<td class="text-nowrap"><?//= escape($grupo['permiso_grupo']); ?></td>-->
						<td class="text-nowrap">
						<?php
							if($grupo['estado_grupo']=='1')
							{
								echo 'ACTIVO'; 
							} 
							 else {
							 	echo 'NO ACTIVO';
							}
						?>
						</td>
		                <td class="text-nowrap">
	                        <?php //<a href="?/clientes/eliminar_grupo/<?= $grupo['id_cliente_grupo']; ? >" data-toggle="tooltip" data-title="Eliminar codigo" data-eliminar="true"><span class="glyphicon glyphicon-trash"></span></a> ?>
							
							<?php if($permiso_cambiar) { ?>
								<a onclick="vendedor(<?= $grupo['id_cliente_grupo']; ?>)" data-toggle="tooltip" data-title="Cambiar vendedor" data-eliminar="true"><span class="glyphicon glyphicon-refresh"></span></a>
							<?php } ?>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
	
		</div>
		<div class="col-sm-6">
			<form method="post" action="?/clientes/guardar_grupo" class="form-horizontal" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="grupo" class="col-md-3 control-label">Codigo de vendedor:</label>
                    <div class="col-md-9">
                        <!--<input type="hidden" value="0" name="id_grupo" data-validation="required number">-->
                        <input type="text" value="" name="grupo" id="grupo" class="form-control" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="- " data-validation-length="max100">
					</div>
                </div>
                <div class="form-group">
                    <label for="descuento" class="col-md-3 control-label">Vendedor:</label>
                    <div class="col-md-9">
						<select class="form-control" name="vendedor" id="vendedor" data-validation="required">
                    		<option value="">Seleccionar...</option>
							<?php foreach($vendedores as $vendedor) { ?>
								<option value="<?= $vendedor['id_empleado'] ?>"><?= $vendedor['vendedor'] ?></option>
							<?php } ?>
                    	</select>
	                  	<!-- <input type="text" value="" name="descuento" id="descuento" class="form-control" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="- " data-validation-length="max100"> -->
					</div>
                </div>
                <div class="form-group">
                    <label for="credito" class="col-md-3 control-label">Credito:</label>
                    <div class="col-md-9">
                    	<!--<input type="text" value="" name="credito" id="credito" class="form-control" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="- " data-validation-length="max100">-->

                    	<select class="form-control" name="credito" id="credito">
                    		<option value="si">SI</option>
                    		<option value="no">NO</option>
                    	</select>

                    </div>
                </div>
                 <!--<div class="form-group">
                    <label for="grupo" class="col-md-3 control-label">Permiso:</label>
                    <div class="col-md-9">
                  		<input type="text" value="" name="permiso" id="permiso" class="form-control" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="- " data-validation-length="max100">
                    </div>
                </div>-->
                 <div class="form-group">
                    <label for="estado" class="col-md-3 control-label">Estado:</label>
                    <div class="col-md-9">
                    	<!--<input type="text" value="" name="estado" id="estado" class="form-control" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="- " data-validation-length="max100">-->
                    	<select class="form-control" name="estado" id="estado">
                    		<option value="1">ACTIVO</option>
                    		<option value="0">NO ACTIVO</option>
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
<script src="<?= js; ?>/moment.min.js"></script>
<script src="<?= js; ?>/moment.es.js"></script>
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>
<script src="<?= js; ?>/jquery.maskedinput.min.js"></script>
<script src="<?= js; ?>/selectize.min.js"></script>
<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>

    <!--<script src="<?= js; ?>/jquery.dataTables.min.js"></script>-->
    <!--<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>-->
    <!--<script src="<?= js; ?>/jquery.form-validator.min.js"></script>-->
    <!--<script src="<?= js; ?>/jquery.form-validator.es.js"></script>-->
    <!--<script src="<?= js; ?>/jquery.maskedinput.min.js"></script>-->
    <!--<script src="<?= js; ?>/jquery.base64.js"></script>-->
    <!--<script src="<?= js; ?>/pdfmake.min.js"></script>-->
    <!--<script src="<?= js; ?>/vfs_fonts.js"></script>-->
    <!--<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>-->
    <!--<script src="<?= js; ?>/moment.min.js"></script>-->
    <!--<script src="<?= js; ?>/moment.es.js"></script>-->
    <!--<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>-->
    <!--<script src="<?= js; ?>/leaflet.js"></script>-->
    <!--<script src="<?= js; ?>/leaflet-routing-machine.js"></script>-->
    <!--<script src="<?= js; ?>/Leaflet.Icon.Glyph.js"></script>-->

<script>
$(function () {
	$.validate({
		modules: 'basic,date,file'
	});

	var table = $('#table').DataFilter({
		filter: true,
		name: 'clientes',
		reports: 'excel|word|pdf|html'
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
	
	$('.form-control:first').select();
});


//Funcion para obtener latitud y longitud
function mostrarUbicacion(position) {
    var latitud = position.coords.latitude; //Obtener latitud
    var longitud = position.coords.longitude; //Obtener longitud
    var div = document.getElementById("atencion");
    $('#atencion').val(latitud+', '+longitud)
    //innerHTML = "<br>Latitud: " + latitud + "<br>Longitud: " + longitud; //Imprime latitud y longitud
    //console.log(latitud);
}

function Excepciones(error) {
    switch (error.code) {
        case error.PERMISSION_DENIED:
            alert('Activa permisos de geolocalizacion');
            break;
        case error.POSITION_UNAVAILABLE:
            alert('Activa localizacion por GPS o Redes .');
            break;
        default:
            alert('ERROR: ' + error.code);
    }
}

function vendedor(id_grupo) {
	<?php
	if(!isset($vendedores[0])){
	?>
	    alert("no existen vendedores");
	<?php
	}else{
	?>
	    bootbox.prompt({
    		title: "Está seguro de no vender esta asignación? no podrá rehacer esta acción.",
    		inputType: 'select',
    		inputOptions: [ <?php foreach ($vendedores as $item) { ?> {
    								text: '<?= $item['vendedor'] ?>',
    								value: '<?= $item['id_empleado'] ?>'
    							},
    						<?php } ?>
    		],
    		callback: function (result) {
    			if(result){
    				// window.location = '?/asignacion/preventas_noventa/' + id_asignacion + '/' + result;
    				// alert('La asignacion es: ' + id_asignacion +' El motivo es: ' + result);
    				cambiar_vendedor(id_grupo, result);
    			}
    		}
    	});
	<?php
	}
	?>
}

function cambiar_vendedor(a, b){
	$.ajax({
		type: 'post',
		dataType: 'json',
		url: '?/clientes/cambiar_vendedor',
		data: {
			id_grupo: a,
			id_vendedor: b
		}
	}).done(function (respuesta) {
		if (respuesta.estado === 's') {
			$.notify({
				message: 'Accion satisfactoria! la operacion se registró correctamente.'
			}, {
				type: 'success'
			});
			setTimeout("location.reload(true);", 3000);
		}else{
			$.notify({
				message: respuesta.estado
			}, {
				type: 'danger'
			});
			// setTimeout("location.reload(true);", 3000);
		}
	}).fail(function () {
		$.notify({
			message: 'La operación fue interrumpida por un fallo.'
		}, {
			type: 'danger'
		});
	});
}

</script>
<?php require_once show_template('footer-advanced'); ?>