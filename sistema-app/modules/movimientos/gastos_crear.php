<?php

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_listar = in_array('gastos_listar', $permisos);

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene el numero de comprobante
$comprobante = $db->query("select ifnull(max(nro_comprobante), 0) + 1 as nro_comprobante from caj_movimientos where tipo = 'g'")->fetch_first();
$nro_comprobante = $comprobante['nro_comprobante'];

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Crear nuevo gasto</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para regresar al listado de gastos hacer clic en el siguiente botón:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/movimientos/gastos_listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span> Listado de gastos</span></a>
		</div>
	</div>
	<hr>
	<?php } ?>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<form method="post" action="?/movimientos/gastos_guardar" class="form-horizontal" autocomplete="off">
				<div class="form-group">
					<label for="nro_comprobante" class="col-md-3 control-label">Número de comprobante:</label>
					<div class="col-md-9">
						<input type="hidden" value="0" name="id_movimiento" data-validation="required">
						<input type="hidden" value="<?= date($_institution['formato']); ?>" name="fecha_movimiento" data-validation="required">
						<input type="hidden" value="<?= date('H:i:s'); ?>" name="hora_movimiento" data-validation="required">
						<input type="text" value="<?= $nro_comprobante; ?>" name="nro_comprobante" id="nro_comprobante" class="form-control" data-validation="required number">
					</div>
				</div>
				<div class="form-group">
					<label for="concepto" class="col-md-3 control-label">Por concepto de:</label>
					<div class="col-md-9">
						<textarea name="concepto" id="concepto" class="form-control" data-validation="required letternumber" data-validation-allowing="+-/.,:;#()\n " autofocus="autofocus"></textarea>
					</div>
				</div>
				<div class="form-group">
					<label for="monto" class="col-md-3 control-label">Monto <?= $moneda; ?>:</label>
					<div class="col-md-9">
						<input type="text" value="" name="monto" id="monto" class="form-control" data-validation="required number" data-validation-allowing="float">
					</div>
				</div>
				<div class="form-group">
					<label for="observacion" class="col-md-3 control-label">Observación:</label>
					<div class="col-md-9">
						<textarea name="observacion" id="observacion" class="form-control" data-validation="letternumber" data-validation-allowing="+-/.,:;#()\n " data-validation-optional="true"></textarea>
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
<script>
$(function () {
	$.validate({
		modules: 'basic'
	});
});
</script>
<?php require_once show_template('footer-advanced'); ?>