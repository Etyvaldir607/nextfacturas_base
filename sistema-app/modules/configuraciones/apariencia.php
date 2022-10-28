<?php

// Obtiene los temas instalados
$temas = get_directories(project . '/themes');

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<b>Apariencia</b>
	</h3>
</div>
<div class="panel-body">
	<form method="post" action="?/configuraciones/apariencia_guardar">
		<div class="row">
			<div class="col-sm-8 hidden-xs">
				<div class="text-label">Para cambiar de apariencia seleccione un tema y hacer clic en el siguiente botón:</div>
			</div>
			<div class="col-xs-12 col-sm-4 text-right">
				<button type="submit" class="btn btn-primary">
					<span class="glyphicon glyphicon-refresh"></span>
					<span>Actualizar</span>
				</button>
			</div>
		</div>
		<hr>
		<div class="row">
			<?php foreach ($temas as $tema) { ?>
			<label class="col-xs-6 col-sm-4 col-md-3 col-lg-2 text-radio">
				<img src="<?= project . '/themes/' . $tema . '/preview.jpg' ?>" class="img-responsive img-thumbnail margin-bottom">
				<p class="margin-bottom">
					<input type="radio" name="tema" value="<?= $tema ?>" <?= ($_institution['tema'] == $tema) ? 'checked' : ''; ?>> <?= $tema; ?>
				</p>
			</label>
			<?php } ?>
		</div>
	</form>
</div>
<?php require_once show_template('footer-advanced'); ?>