<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<b>Ajustes sobre la fecha</b>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para visualizar la información hacer clic en el siguiente botón:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/configuraciones/preferencias" class="btn btn-primary">
				<span class="glyphicon glyphicon-search"></span>
				<span>Mostrar</span>
			</a>
		</div>
	</div>
	<hr>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<form method="post" action="?/configuraciones/preferencias_guardar" class="form-horizontal">
				<div class="form-group">
					<label for="formato" class="col-md-4 control-label">Formato para las fechas:</label>
					<div class="col-md-8">
						<select name="formato" id="formato" class="form-control" data-validation="required">
							<option value="">Seleccionar</option>
							<option value="Y-m-d" <?= ($_institution['formato'] == 'Y-m-d') ? 'selected' : ''; ?>>yyyy-mm-dd</option>
							<option value="Y/m/d" <?= ($_institution['formato'] == 'Y/m/d') ? 'selected' : ''; ?>>yyyy/mm/dd</option>
							<option value="d-m-Y" <?= ($_institution['formato'] == 'd-m-Y') ? 'selected' : ''; ?>>dd-mm-yyyy</option>
							<option value="d/m/Y" <?= ($_institution['formato'] == 'd/m/Y') ? 'selected' : ''; ?>>dd/mm/yyyy</option>
						</select>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-8 col-md-offset-4">
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
<script>
$(function () {
	$.validate();
	
	$('.form-control:first').select();
});
</script>
<?php require_once show_template('footer-advanced'); ?>