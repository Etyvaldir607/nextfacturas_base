<?php require_once show_template('header-advanced'); ?>
<link href="<?= css; ?>/jquery.Jcrop.min.css" rel="stylesheet">
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Perfil de usuario</strong>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para cambiar los datos de tu perfil hacer clic en editar.</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<a href="?/<?= home; ?>/perfil_editar" class="btn btn-info"><i class="glyphicon glyphicon-edit"></i><span class="hidden-xs"> Editar perfil</span></a>
		</div>
	</div>
	<hr>
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
				<img src="<?= ($_user['avatar'] == '') ? imgs . '/avatar.jpg' : profiles . '/' . $_user['avatar']; ?>" class="img-responsive img-rounded img-lightbox margin-bottom">
			</center>
			<div class="btn-group btn-group-justified margin-bottom" role="group">
				<div class="btn-group" role="group">
					<button type="button" class="btn btn-success" data-capturar="true" data-toggle="tooltip" data-title="Capturar avatar"><i class="glyphicon glyphicon-camera"></i></button>
				</div>
				<div class="btn-group" role="group">
					<button type="button" class="btn btn-info" data-subir="true" data-toggle="tooltip" data-title="Subir avatar"><i class="glyphicon glyphicon-picture"></i></button>
				</div>
			</div>
		</div>
		<div class="col-sm-6 col-md-7">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<span class="panel-title"><i class="glyphicon glyphicon-user"></i> Datos de usuario</span>
				</div>
				<div class="panel-body">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-sm-3 control-label">Usuario:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?= escape($_user['username']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Correo:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?= escape($_user['email']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Rol:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?= escape($_user['rol']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Estado:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?= ($_user['active'] == 1) ? 'Activado' : 'Bloqueado'; ?></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Modal capturar avatar -->
<div id="modal_capturar" class="modal fade">
	<div class="modal-dialog modal-lg">
		<form method="post" action="?/<?= home; ?>/perfil_capturar" id="form_capturar" class="modal-content">
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
<!-- End capturar avatar -->

<!-- Modal subir avatar -->
<div id="modal_subir" class="modal fade">
	<div class="modal-dialog">
		<form method="post" action="?/<?= home; ?>/perfil_subir" enctype="multipart/form-data" class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Subir avatar</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
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
<!-- End subir avatar -->

<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/jquery.resize.min.js"></script>
<script src="<?= js; ?>/jquery.Jcrop.min.js"></script>
<script>
$(function () {
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
	
	var $modal_capturar = $('#modal_capturar');

	$('[data-capturar]').on('click', function (e) {
		e.preventDefault();
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
		var estado, lienzo, avatar_x, avatar_y, avatar_w, avatar_h;
		estado = $('#img_capturar').find('img').attr('data-verificar');
		avatar_x = $.trim($('#avatar_x_capturar').val());
		avatar_y = $.trim($('#avatar_y_capturar').val());
		avatar_w = $.trim($('#avatar_w_capturar').val());
		avatar_h = $.trim($('#avatar_h_capturar').val());
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
			$('#alert_capturar').html('<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Advertencia!</strong><p>Debe capturar un avatar para guardar los cambios.</p></div>');
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
		$modal_capturar.modal('hide');
	});

	var llenar_coordenadas_capturar = function (x, y, w, h) {
		$('#alert_capturar').html('');
		$('#avatar_x_capturar').val(x);
		$('#avatar_y_capturar').val(y);
		$('#avatar_w_capturar').val(w);
		$('#avatar_h_capturar').val(h);
	}
});
</script>
<?php require_once show_template('footer-advanced'); ?>