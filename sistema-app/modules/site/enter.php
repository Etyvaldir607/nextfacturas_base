<?php

// Importa la configuracion para el manejo de la base de datos
require_once config . '/database.php';

// Obtiene datos de la empresa $_institution = palabra reservada
$_institution = $db->from('sys_instituciones')->fetch_first();

// Generamos un random para evitar el cacheado del script
$version = rand(0, 1000000);

?>
<!doctype html>
<html lang="es">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
		<meta name="mobile-web-app-capable" content="yes">
		<title><?= $_institution['nombre']; ?></title>
		<link rel="stylesheet" href="<?= css; ?>/bootstrap.min.css">
		<link rel="stylesheet" href="<?= css; ?>/bootstrap-structured.min.css">
		<link rel="stylesheet" href="<?= themes . '/' . (($_institution['tema'] == '') ? 'bootstrap' : $_institution['tema']); ?>/style.min.css">
		<!--[if lt IE 9]>
			<script src="<?= js; ?>/html5shiv.min.js"></script>
			<script src="<?= js; ?>/respond.min.js"></script>
		<![endif]-->
		<link rel="icon" type="image/png" href="<?= project; ?>/favicon.png">
		<style>
		body {
			margin: 15px auto;
		}
		</style>
	</head>
	<body>
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-6 col-sm-offset-3 col-md-4 col-md-offset-4">
					<div class="row margin-bottom">
						<div class="col-xs-6 col-xs-offset-3">
							<img src="<?= imgs . '/logo-color.png'; ?>" class="img-responsive">
						</div>
					</div>
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title" data-header="true">
								<span class="glyphicon glyphicon-lock"></span>
								<strong>Ingresar</strong>
							</h3>
						</div>
						<div class="panel-body">
							<?php if (isset($_SESSION[temporary])) { ?>
							<?php unset($_SESSION[temporary]); ?>
							<div class="alert alert-warning">
								<button type="button" class="close" data-dismiss="alert">&times;</button>
								<strong>Advertencia!</strong>
								<div>La información enviada no coincide con los registros, asegurese de escribir correctamente sus datos.</div>
							</div>
							<?php } ?>
							<form method="post" action="?/<?= site; ?>/auth" class="margin-none">
								<input type="hidden" name="locale" value="">
								<div class="form-group">
									<input type="text" name="username" class="form-control" placeholder="Nombre de usuario" autocomplete="off" autofocus="autofocus" data-validation="required">
								</div>
								<div class="form-group">
									<input type="password" name="password" class="form-control" placeholder="Contraseña" autocomplete="off" data-validation="required">
								</div>
								<div class="form-group">
									<div class="checkbox">
										<label>
											<input type="checkbox" name="remember">
											<span>Recuérdame</span>
										</label>
									</div>
								</div>
								<button type="submit" class="btn btn-primary">
									<span class="glyphicon glyphicon-share-alt"></span>
									<span>Ingresar</span>
								</button>
								<button type="reset" class="btn btn-default">
									<span class="glyphicon glyphicon-refresh"></span>
									<span>Restablecer</span>
								</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script src="<?= js; ?>/jquery.min.js"></script>
		<script src="<?= js; ?>/bootstrap.min.js"></script>
		<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
		<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
		<script src="http://localhost:9000/<?= name_project; ?>/locale.js?v=<?= $version; ?>"></script>
		<script>
		$(function () {
			if (window.l || false) {
				$('input[name=locale]').val(window.l);
			}

			$.validate();
		});
		</script>
	</body>
</html>