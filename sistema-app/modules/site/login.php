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
		<link rel="stylesheet" href="<?= css; ?>/animate.min.css">
		<link rel="stylesheet" href="<?= themes . '/' . (($_institution['tema'] == '') ? 'bootstrap' : $_institution['tema']); ?>/style.min.css">
		<!--[if lt IE 9]>
			<script src="<?= js; ?>/html5shiv.min.js"></script>
			<script src="<?= js; ?>/respond.min.js"></script>
		<![endif]-->
		<link rel="icon" type="image/png" href="<?= project; ?>/favicon.png">
		<style>
		body {
			margin: 0;
		}
		.wrapper-login {
			display: table;
			left: 0;
			position: absolute;
			top: 0;
			width: 100%;
		}
		.content-login {
			display: table-cell;
			vertical-align: middle;
			padding: 15px 0;
		}
		.h-100 {
			height: 100%;
		}
		.font-weight-normal {
			font-weight: normal;
		}
		</style>
	</head>
	<body>
		<div class="wrapper-login">
			<div class="content-login">
				<div class="container-fluid">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
							<div class="panel panel-default margin-none">
								<div class="panel-heading">
									<h3 class="panel-title" data-header="true">Ingreso</h3>
								</div>
								<div class="panel-body">
									<form method="post" action="?/<?= site; ?>/auth" class="form-horizontal margin-none">
										<input type="hidden" name="locale" value="">
										<div class="form-group">
											<label for="username" class="col-sm-3 control-label font-weight-normal">Usuario:</label>
											<div class="col-sm-9">
												<input type="text" name="username" class="form-control" autocomplete="off" autofocus="autofocus" data-validation="required">
											</div>
										</div>
										<div class="form-group">
											<label for="password" class="col-sm-3 control-label font-weight-normal">Contraseña:</label>
											<div class="col-sm-9">
												<input type="password" name="password" class="form-control" autocomplete="off" data-validation="required">
											</div>
										</div>
										<div class="form-group">
											<div class="col-sm-offset-3 col-sm-9">
												<div class="checkbox">
													<label>
														<input type="checkbox" name="remember">
														<span>Recuérdame</span>
													</label>
												</div>
											</div>
										</div>
										<div class="form-group margin-none">
											<div class="col-sm-offset-3 col-sm-9">
												<button type="submit" class="btn btn-primary">Ingresar</button>
												<button type="reset" class="btn btn-default">Restablecer</button>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script src="<?= js; ?>/jquery.min.js"></script>
		<script src="<?= js; ?>/bootstrap.min.js"></script>
		<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
		<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
		<script src="<?= js; ?>/bootstrap-notify.min.js"></script>
		<script src="http://localhost:9000/<?= name_project; ?>/locale.js?v=<?= $version; ?>"></script>
		<script>
		$(function () {
			if (window.l || false) {
				$('input[name=locale]').val(window.l);
			}

			$.validate();

			<?php if (isset($_SESSION[temporary])) { ?>
			<?php unset($_SESSION[temporary]); ?>
			$.notify({
				message: 'La información enviada no coincide con los registros, asegúrese de introducir correctamente su nombre de usuario y contraseña.' 
			},{
				type: 'danger'
			});
			<?php } ?>
		});
		</script>
	</body>
</html>