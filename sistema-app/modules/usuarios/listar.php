<?php

// Obtiene los usuarios
$users = $db->select("u.*, r.rol, ifnull(e.paterno, '') as paterno, ifnull(e.materno, '') as materno, ifnull(e.nombres, '') as nombres, t.model")
			->from('sys_users u')
			->join('sys_roles r', 'u.rol_id = r.id_rol', 'left')
			->join('sys_empleados e', 'u.persona_id = e.id_empleado', 'left')
			->join('sys_token t', 't.user_id = u.id_user', 'left')
			->where('active', '1')
			->order_by('username')
			->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_editar = in_array('editar', $permisos);
$permiso_ver = in_array('ver', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos);
$permiso_activar = in_array('activar', $permisos);
$permiso_asignar = in_array('asignar', $permisos);
$permiso_capturar = in_array('capturar', $permisos);
$permiso_anulados = in_array('listar_anulados', $permisos);
$permiso_dispositivo = in_array('eliminar_dispositivo', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<link rel="stylesheet" href="<?= css; ?>/dataTables.bootstrap.min.css">
<link rel="stylesheet" href="<?= css; ?>/jquery.Jcrop.min.css">
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Usuarios</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if (($permiso_crear || $permiso_imprimir) && ($permiso_crear || $users)) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para agregar nuevos usuarios hacer clic en el siguiente botón: </div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<?php if ($permiso_anulados) { ?>
			<a href="?/usuarios/listar_anulados" class="btn btn-danger"><i class="glyphicon glyphicon-list"></i><span> Listar Bloqueados</span></a>
			<?php } ?>
			<?php if ($permiso_imprimir) { ?>
			<a href="?/usuarios/imprimir" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_crear) { ?>
			<a href="?/usuarios/crear" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i><span> Nuevo</span></a>
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
	<?php if ($users) { ?>
	<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
		<thead>
			<tr class="active">
				<th class="text-nowrap">#</th>
				<th class="text-nowrap">Avatar</th>
				<th class="text-nowrap">Usuario</th>
				<th class="text-nowrap">Correo</th>
				<th class="text-nowrap">Rol</th>
				<th class="text-nowrap">Empleado</th>
				<th class="text-nowrap">Celular</th>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar || $permiso_activar || $permiso_asignar || $permiso_capturar) { ?>
				<th class="text-nowrap">Opciones</th>
				<?php } ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Avatar</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Usuario</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Correo</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Rol</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Empleado</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Celular</th>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar || $permiso_activar || $permiso_asignar || $permiso_capturar) { ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
				<?php } ?>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($users as $nro => $user) { ?>
			<tr>
				<th class="text-nowrap"><?= $nro + 1; ?></th>
				<td class=""><img src="<?= ($user['avatar'] == '') ? imgs . '/avatar.jpg' : profiles . '/' . $user['avatar']; ?>" class="img-circle" data-toggle="lightbox" data-lightbox-image="<?= ($user['avatar'] == '') ? imgs . '/avatar.jpg' : profiles . '/' . $user['avatar']; ?>" width="30" height="30"></td>
				<td class=""><?= escape($user['username']); ?></td>
				<td class=""><?= escape($user['email']); ?></td>
				<td class=""><?= escape($user['rol']); ?></td>
				<td class=""><?= escape($user['paterno'] . ' ' . $user['materno'] . ' ' . $user['nombres']); ?></td>
				<td class=""><?= escape($user['model']); ?></td>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar || $permiso_activar || $permiso_asignar || $permiso_capturar) { ?>
				<td class="text-nowrap">
					<?php if ($permiso_activar && $user['id_user'] != 1) { ?>
						<?php if ($user['active'] == 1) { ?>
						<a href="?/usuarios/activar/<?= $user['id_user']; ?>" class="text-success" data-toggle="tooltip" data-title="Bloquear usuario" data-activar="true"><i class="glyphicon glyphicon-check"></i></a>
						<?php } else { ?>
						<a href="?/usuarios/activar/<?= $user['id_user']; ?>" class="text-danger" data-toggle="tooltip" data-title="Desbloquear usuario" data-activar="true"><i class="glyphicon glyphicon-unchecked"></i></a>
						<?php } ?>
					<?php } ?>
					<?php if ($permiso_ver) { ?>
					<a href="?/usuarios/ver/<?= $user['id_user']; ?>" data-toggle="tooltip" data-title="Ver usuario"><i class="glyphicon glyphicon-search"></i></a>
					<?php } ?>
					
					<a href="?/usuarios/editar/<?= $user['id_user']; ?>" data-toggle="tooltip" data-title="Editar usuario"><i class="glyphicon glyphicon-edit"></i></a>
					<?php //} 
					    $id_user = $user['id_user'];
    					$aux = $db->query("SELECT u.id_user, emp.id_empleado, e.id_egreso, i.id_ingreso, d.id_asignacion, p.id_proceso
                                            FROM sys_users as u
                                            LEFT JOIN sys_empleados as emp ON u.persona_id = emp.id_empleado
                                            LEFT JOIN inv_egresos as e ON e.empleado_id = emp.id_empleado
                                            LEFT JOIN inv_ingresos as i ON i.empleado_id = emp.id_empleado
                                            LEFT JOIN gps_asigna_distribucion as d ON d.distribuidor_id = emp.id_empleado
                                            LEFT JOIN sys_procesos as p ON u.persona_id = p.usuario_id
                                            WHERE u.id_user = '$id_user'
                                            LIMIT 1")->fetch_first();
                        $permiso_caso_no_movimientos = ($aux['id_egreso'] != '' || $aux['id_ingreso'] != '' || $aux['id_asignacion'] != '' || $aux['id_empleado'] != '' || $aux['id_proceso'] != '') ? false : true;
					?>
					<?php if ($permiso_eliminar && $user['id_user'] != 1) { ?>
    					<?php if ($permiso_caso_no_movimientos) { ?>
    					    <a href="?/usuarios/eliminar/<?= $user['id_user']; ?>" data-toggle="tooltip" data-title="Eliminar usuario" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i></a>
    					<?php } ?>
					<?php } ?>
					<?php if ($permiso_asignar) { ?>
						<a href="?/usuarios/listar" data-toggle="tooltip" data-title="Asignar / cambiar empleado" data-asignar="<?= $user['id_user']; ?>"><i class="glyphicon glyphicon-user"></i></a>
					<?php } ?>
					<?php if ($permiso_capturar) { ?>
					<a href="#" data-toggle="tooltip" data-title="Tomar avatar" data-capturar="<?= $user['id_user']; ?>"><i class="glyphicon glyphicon-camera"></i></a>
					<?php } ?>
					<?php if ($permiso_dispositivo && $user['model']!="") { ?>
					<a href="?/usuarios/eliminar_dispositivo/<?= $user['id_user']; ?>" data-toggle="tooltip" data-celular="<?= $user['id_user']; ?>" data-title="Eliminar celular"><i class="glyphicon glyphicon-phone"></i></a>
					<?php } ?>
				</td>
				<?php } ?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>No existen usuarios registrados en la base de datos, para crear nuevos usuarios hacer clic en el botón nuevo o presionar las teclas <kbd>alt + n</kbd>.</p>
	</div>
	<?php } ?>
</div>

<!-- Modal capturar avatar -->
<?php if ($permiso_capturar) { ?>
<div id="modal_capturar" class="modal fade">
	<div class="modal-dialog modal-lg">
		<form method="POST" action="?/usuarios/capturar" id="form_capturar" class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Capturar avatar</h4>
			</div>
			<div class="modal-body">
				<div id="alert_capturar"></div>
				<div class="row">
					<div class="col-sm-6">
						<div class="panel panel-success">
							<div class="panel-heading">
								<h3 class="panel-title"><i class="glyphicon glyphicon-film"></i> Cámara</h3>
							</div>
							<div class="panel-body">
								<div class="embed-responsive embed-responsive-4by3">
									<video id="video_capturar" class="embed-responsive-item" autoplay></video>
								</div>
							</div>
						</div>
					</div>
					<div class="col-sm-6">
						<div class="panel panel-success">
							<div class="panel-heading">
								<h3 class="panel-title"><i class="glyphicon glyphicon-picture"></i> Avatar</h3>
							</div>
							<div class="panel-body">
								<canvas id="canvas_capturar" class="hidden"></canvas>
								<div id="img_capturar">
									<img src="<?= imgs; ?>/picture.jpg" class="img-responsive" data-verificar="false">
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<div class="row">
					<div class="col-xs-4 text-left">
						<a href="#" id="tomar_capturar" class="btn btn-success"><i class="glyphicon glyphicon-camera"></i><span class="hidden-xs"> Capturar</span></a>
					</div>
					<div class="col-xs-8 text-right">
						<input type="hidden" value="" name="id_user" id="id_user_capturar">
						<input type="hidden" value="" name="avatar" id="avatar_capturar">
						<input type="hidden" value="" name="avatar_x" id="avatar_x_capturar">
						<input type="hidden" value="" name="avatar_y" id="avatar_y_capturar">
						<input type="hidden" value="" name="avatar_w" id="avatar_w_capturar">
						<input type="hidden" value="" name="avatar_h" id="avatar_h_capturar">
						<a href="#" id="cancelar_capturar" class="btn btn-default"><span class="glyphicon glyphicon-remove"></span><span class="hidden-xs"> Cancelar</span></a>
						<button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-ok"></i><span class="hidden-xs"> Guardar</span></button>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<?php } ?>
<!-- End capturar avatar -->

<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>
<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>
<script src="<?= js; ?>/jquery.resize.min.js"></script>
<script src="<?= js; ?>/jquery.Jcrop.min.js"></script>
<script>
$(function () {
	<?php if ($permiso_crear) { ?>
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'n':
					e.preventDefault();
					window.location = '?/usuarios/crear';
				break;
			}
		}
	});
	<?php } ?>

	<?php if ($permiso_activar) { ?>
	$('[data-activar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea cambiar el estado del usuario?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>
	
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

	<?php if ($permiso_eliminar) { ?>
	$('[data-celular]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que eliminar el celular?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>

	<?php if ($permiso_asignar) { ?>
	$('[data-asignar]').on('click', function (e) {
		e.preventDefault();
		var id_user = $(this).attr('data-asignar');
		bootbox.confirm('Desea asignar a este usuario un empleado y/o empleado ya registrado?', function (result) {
			if(result){
				window.location = '?/usuarios/asignar/' + id_user;
			}
		});
	});
	<?php } ?>

	<?php if ($permiso_capturar) { ?>
	var $modal_capturar = $('#modal_capturar');

	$('[data-capturar]').on('click', function (e) {
		e.preventDefault();
		var id_user = $(this).attr('data-capturar');
		$('#id_user_capturar').val(id_user);
		$modal_capturar.modal({
			backdrop: 'static'
		});
	});

	$('#img_capturar').resize(function () {
		var estado = $(this).find('img').attr('data-verificar');
		if (estado == 'true') {
			$(this).html('<img src="<?= imgs; ?>/picture.jpg" class="img-responsive" data-verificar="false">');
		}
	});

	$('#form_capturar').on('submit', function (e) {
		var estado, id_user, lienzo, avatar_x, avatar_y, avatar_w, avatar_h;
		estado = $('#img_capturar').find('img').attr('data-verificar');
		id_user = $.trim($('#id_user_capturar').val());
		avatar_x = $.trim($('#avatar_x_capturar').val());
		avatar_y = $.trim($('#avatar_y_capturar').val());
		avatar_w = $.trim($('#avatar_w_capturar').val());
		avatar_h = $.trim($('#avatar_h_capturar').val());
		if (id_user != '') {
			if (estado == 'true') {
				if (avatar_x != '' && avatar_y != '' && avatar_w != '' && avatar_h != '') {
					lienzo = $('#img_capturar').find('img').attr('src');
					$('#avatar_capturar').val(lienzo);
					//$modal_capturar.modal('hide');
				} else {
					e.preventDefault();
					$('#alert_capturar').html('<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Advertencia!</strong><p>Debe definir el tamaño de la avatar, para ello debe arrastrar un marco en la avatar.</p></div>');
				}
			} else {
				e.preventDefault();
				$('#alert_capturar').html('<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Advertencia!</strong><p>Debe capturar una avatar para guardar los cambios.</p></div>');
			}
		} else {
			e.preventDefault();
			$('#alert_capturar').html('<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Advertencia!</strong><p>No se identificó a ningún usuario.</p></div>');
		}
	});

	$modal_capturar.on('show.bs.modal', function (e) {
		llenar_coordenadas_capturar('', '', '', '');
		if (Modernizr.getusermedia && Modernizr.canvas) {
			window.datos = {
				'video': null,
				'url': null
			},
			navigator.getUserMedia = Modernizr.prefixed('getUserMedia', navigator);
			navigator.getUserMedia({
				'audio': false,
				'video': true
			},
			function(video) {
				datos.video = video;
				datos.url = window.URL.createObjectURL(video);
				$('#video_capturar').attr('src', datos.url);
			},
			function() {
				$modal_capturar.modal('hide');
				bootbox.alert('No se puede tener acceso a la cámara.');
			});
		} else {
			bootbox.alert('Su navegador no soporta esta acción!');
		}
	});

	$modal_capturar.on('shown.bs.modal', function (e) {
		$('#tomar_capturar').focus();
	});

	$modal_capturar.on('hide.bs.modal', function (e) {
		var captura, contexto;
		captura = $('#canvas_capturar');
		contexto = captura[0].getContext('2d');
		contexto.clearRect(0, 0, captura.width(), captura.height());
		if (datos.video) {
			//datos.video.stop();
			//window.URL.revokeObjectURL(datos.url);
			window.location.reload();
		}
	});

	$('#tomar_capturar').on('click', function (e) {
		e.preventDefault();
		llenar_coordenadas_capturar('', '', '', '');
		var camara, captura, lienzo, contexto, ancho, alto, data;
		camara = $('#video_capturar');
		captura = $('#canvas_capturar');
		lienzo = $('#img_capturar');
		ancho = camara.width();
		alto = camara.height();
		captura.attr({
			'width': ancho,
			'height': alto
		});
		contexto = captura[0].getContext('2d');
		contexto.drawImage(camara[0], 0, 0, ancho, alto);
		data = captura[0].toDataURL('image/jpeg', 1);
		lienzo.html('<img class="img-responsive" data-verificar="true">');
		lienzo.find('img').attr('src', data);
		lienzo.find('img').Jcrop({
			aspectRatio: 1,
			onChange: function (data) {
				llenar_coordenadas_capturar('', '', '', '');
			},
			onSelect: function (data) {
				llenar_coordenadas_capturar(data.x, data.y, data.w, data.h);
			}
		});
	});

	$('#cancelar_capturar').on('click', function (e) {
		e.preventDefault();
		$('#id_user_capturar').val('');
		$modal_capturar.modal('hide');
	});

	var llenar_coordenadas_capturar = function (x, y, w, h) {
		$('#alert_capturar').html('');
		$('#avatar_x_capturar').val(x);
		$('#avatar_y_capturar').val(y);
		$('#avatar_w_capturar').val(w);
		$('#avatar_h_capturar').val(h);
	}
	<?php } ?>
	
	<?php if ($users) { ?>
	var table = $('#table').DataFilter({
		filter: false,
		name: 'usuarios',
		reports: 'excel|word|pdf|html'
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>