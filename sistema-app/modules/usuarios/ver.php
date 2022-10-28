<?php

// Obtiene el id_user
$id_user = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el user
// $user = $db->select("u.*, r.rol, ifnull(e.paterno, '') as paterno, ifnull(e.materno, '') as materno, ifnull(e.nombres, '') as nombres")
//             ->from('sys_users u')
//             ->join('sys_roles r', 'u.rol_id = r.id_rol', 'left')
//             ->join('sys_empleados e', 'u.persona_id = e.id_empleado', 'left')
//             ->where('u.id_user', $id_user)
//             ->fetch_first();
$user = $db->query("SELECT u.*, r.rol, emp.*, u.id_user, emp.id_empleado, e.id_egreso, i.id_ingreso, d.id_asignacion, p.id_proceso
                    FROM sys_users as u
                    LEFT JOIN sys_empleados as emp ON u.persona_id = emp.id_empleado
                    LEFT JOIN sys_roles as r ON r.id_rol = u.rol_id
                    LEFT JOIN inv_egresos as e ON e.empleado_id = emp.id_empleado
                    LEFT JOIN inv_ingresos as i ON i.empleado_id = emp.id_empleado
                    LEFT JOIN gps_asigna_distribucion as d ON d.distribuidor_id = emp.id_empleado
                    LEFT JOIN sys_procesos as p ON u.persona_id = p.usuario_id
                    WHERE u.id_user = '$id_user'
                    LIMIT 1")->fetch_first();
$permiso_caso_no_movimientos = ($user['id_egreso'] != '' || $user['id_ingreso'] != '' || $user['id_asignacion'] != '' || $user['id_empleado'] != '' || $user['id_proceso'] != '') ? false : true;
$us_no_asignado_empleado = $user['id_empleado'] == '' ? false : true; 

// Verifica si existe el user
if (!$user) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_editar = in_array('editar', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos);
$permiso_listar = in_array('listar', $permisos);
$permiso_capturar = in_array('capturar', $permisos);
$permiso_subir = in_array('subir', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<link href="<?= css; ?>/jquery.Jcrop.min.css" rel="stylesheet">
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Ver usuario</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_crear || $permiso_editar || $permiso_eliminar || $permiso_imprimir || $permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<?php if ($permiso_crear) { ?>
			<a href="?/usuarios/crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
			<?php } ?>
			<?php if ($permiso_editar) { ?>
			<a href="?/usuarios/editar/<?= $user['id_user']; ?>" class="btn btn-warning"><i class="glyphicon glyphicon-edit"></i><span class="hidden-xs hidden-sm"> Editar</span></a>
			<?php } ?>
			<?php if ($permiso_eliminar) { ?>
    			<?php if ($permiso_caso_no_movimientos) { ?>
    			    <a href="?/usuarios/eliminar/<?= $user['id_user']; ?>" class="btn btn-danger" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
    			<?php } ?>
			<?php } ?>
			<?php if ($permiso_imprimir) { ?>
			<a href="?/usuarios/imprimir/<?= $user['id_user']; ?>" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs hidden-sm"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/usuarios/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs hidden-sm <?= ($permiso_imprimir) ? 'hidden-md' : ''; ?>"> Listado</span></a>
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
	<div class="row">
		<div class="col-sm-4 col-sm-offset-1 col-md-3 col-md-offset-1">
			<center>
				<img src="<?= ($user['avatar'] == '') ? imgs . '/avatar.jpg' : profiles . '/' . $user['avatar']; ?>" class="img-responsive img-rounded img-lightbox margin-bottom">
			</center>
			<div class="btn-group btn-group-justified margin-bottom" role="group">
				<?php if ($permiso_capturar) { ?>
				<div class="btn-group" role="group">
					<button type="button" class="btn btn-success" data-capturar="<?= $id_user; ?>" data-toggle="tooltip" data-title="Capturar avatar"><i class="glyphicon glyphicon-camera"></i></button>
				</div>
				<?php } ?>
				<?php if ($permiso_subir) { ?>
				<div class="btn-group" role="group">
					<button type="button" class="btn btn-info" data-subir="true" data-toggle="tooltip" data-title="Subir avatar"><i class="glyphicon glyphicon-picture"></i></button>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="col-sm-6 col-md-7">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<span class="panel-title"><i class="glyphicon glyphicon-user"></i> Datos del usuario</span>
				</div>
				<div class="panel-body">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-sm-3 control-label">Usuario:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?= escape($user['username']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Correo:</label>
							<div class="col-sm-9">
							    <p class="form-control-static"><?= ($user['email'] != '') ? $user['email'] : 'No registrado'; ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Rol:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?= escape($user['rol']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Estado:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?= ($user['active'] == 1) ? 'Activado' : 'Bloqueado'; ?></p>
							</div>
						</div>
						<!--<hr>-->
						<div class="form-group">
							<label class="col-sm-3 control-label">Almacenes:</label>
							<div class="col-sm-9">
							    <?php $almacenes = $db->query("SELECT ua.*, a.almacen
                                                                FROM inv_users_almacenes ua
                                                                LEFT JOIN inv_almacenes a ON a.id_almacen = ua.almacen_id
                                                                WHERE user_id = $id_user
                                                                ORDER BY almacen_id ASC")->fetch();
                                if($almacenes!=null){
        							foreach($almacenes as $nro => $almacen){ ?>
    								    <p class="form-control-static"><?= $almacen['almacen']; ?></p>
    								<?php } ?>
								<?php }else{ ?>
								    <p class="form-control-static">El usuario no tiene asignado almacenes</p>
								<?php } ?>
							</div>
						</div>
						<!--<div class="form-group">-->
						<!--	<label class="col-sm-3 control-label">Empleado:</label>-->
						<!--	<div class="col-sm-9">-->
						<!--		<p class="form-control-static"><?= escape($user['nombres'] . ' ' . $user['paterno'] . ' ' . $user['materno']); ?></p>-->
						<!--	</div>-->
						<!--</div>-->
					</div>
				</div>
			</div>
		</div>
		<?php if($us_no_asignado_empleado){?>
		<div class="col-sm-6 col-md-7">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<span class="panel-title"><i class="glyphicon glyphicon-user"></i> Datos del empleado</span>
				</div>
				<div class="panel-body">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-sm-3 control-label">Apellido paterno:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?= escape($user['paterno']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Apellido materno:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?= escape($user['materno']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Nombres:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?= escape($user['nombres']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Codigo:</label>
							<div class="col-sm-9">
							    <p class="form-control-static"><?= $user['codigo']; ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Genero:</label>
							<div class="col-sm-9">
							    <p class="form-control-static"><?= $user['genero']; ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Telefono:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?= ($user['telefono'] != '') ? $user['telefono'] : 'No registrado'; ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Fecha de nacimiento:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?= ($user['fecha_nacimiento'] != '0000-00-00') ? $user['fecha_nacimiento'] : 'No registrado'; ?></p>
							</div>
						</div>
						<!--<div class="form-group">-->
						<!--	<label class="col-sm-3 control-label">Departamento:</label>-->
						<!--	<div class="col-sm-9">-->
						<!--	    <p class="form-control-static"><?= $user['departamento_id']; ?></p>-->
						<!--	</div>-->
						<!--</div>-->
					</div>
				</div>
			</div>
		</div>
		<?php }else{ ?>
		    <div class="col-sm-6 col-md-7">
			    <div class="panel panel-primary">
			        <div class="panel-heading">
    					<span class="panel-title"><i class="glyphicon glyphicon-user"></i> Datos del empleado</span>
    				</div>
    				<p class="text-center control-label">El usuario no se encuentra asignado a un empleado</p>
                </div>
		    </div>
		<?php } ?>
	</div>
</div>

<!-- Modal subir avatar -->
<?php if ($permiso_subir) { ?>
<div id="modal_subir" class="modal fade">
	<div class="modal-dialog">
		<form method="POST" action="?/usuarios/subir" enctype="multipart/form-data" class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Subir avatar</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<input type="hidden" value="<?= $id_user; ?>" name="id_user" data-validation="required">
					<input type="file" name="avatar" class="form-control" data-validation="required mime size" data-validation-allowing="jpg, png" data-validation-max-size="512kb">
				</div>
			</div>
			<div class="modal-footer">
				<div class="row">
					<div class="col-xs-12 text-right">
						<a href="#" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span><span class="hidden-xs"> Cancelar</span></a>
						<button type="submit" id="enviar_subir" class="btn btn-primary"><i class="glyphicon glyphicon-ok"></i><span class="hidden-xs"> Guardar</span></button>
						<button type="reset" id="borrar_subir" class="hidden"></button>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<?php } ?>
<!-- End subir avatar -->

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

<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/jquery.resize.min.js"></script>
<script src="<?= js; ?>/jquery.Jcrop.min.js"></script>
<script>
$(function () {
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

	<?php if ($permiso_subir) { ?>
	$.validate({
		modules: 'file'
	});

	var $modal_subir = $('#modal_subir');

	$('[data-subir]').on('click', function (e) {
		e.preventDefault();
		$modal_subir.modal({
			backdrop: 'static'
		});
	});

	$modal_subir.on('show.bs.modal', function (e) {
		$('#borrar_subir').trigger('click');
	});

	$modal_subir.on('shown.bs.modal', function (e) {
		$('#enviar_subir').focus();
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
					$('#alert_capturar').html('<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Advertencia!</strong><p>Debe definir el tamaño del avatar, para ello debe arrastrar un marco en el avatar.</p></div>');
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
});
</script>
<?php require_once show_template('footer-advanced'); ?>