<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<b>Información de la empresa</b>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para editar la información hacer clic en el siguiente botón:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/configuraciones/institucion_editar" class="btn btn-primary">
				<span class="glyphicon glyphicon-edit"></span>
				<span>Modificar</span>
			</a>
		</div>
	</div>
	<hr>
	<div class="alert alert-warning">Los datos mostrados a continuación deben ser propios de su empresa, ya que con esta información serán generados todos los documentos del sistema.</div>
	<div class="well">
		<div class="table-display">
			<div class="tbody">
				<div class="tr">
					<div class="th text-nowrap">Nombre de la empresa:</div>
					<div class="td text-ellipsis"><?= escape($_institution['nombre']); ?></div>
				</div>
				<div class="tr">
					<div class="th text-nowrap">Información de la empresa:</div>
					<div class="td text-ellipsis"><?= escape($_institution['lema']); ?></div>
				</div>
				<div class="tr">
					<div class="th text-nowrap">Actividad económica:</div>
					<div class="td text-ellipsis"><?= escape($_institution['razon_social']); ?></div>
				</div>
				<div class="tr">
					<div class="th text-nowrap">NIT de la empresa:</div>
					<div class="td text-ellipsis"><?= escape($_institution['nit']); ?></div>
				</div>
				<div class="tr">
					<div class="th text-nowrap">Propietario:</div>
					<div class="td text-ellipsis"><?= escape($_institution['propietario']); ?></div>
				</div>
				<div class="tr">
					<div class="th text-nowrap">Dirección de la empresa:</div>
					<div class="td text-ellipsis"><?= escape($_institution['direccion']); ?></div>
				</div>
				<div class="tr">
					<div class="th text-nowrap">Correo electrónico:</div>
					<div class="td text-ellipsis"><?= escape($_institution['correo']); ?></div>
				</div>
                <div class="tr">
                    <div class="th text-nowrap">Teléfono:</div>
                    <div class="td text-ellipsis"><?= ($_institution['telefono'] == '') ? 'No asignado' : str_replace(',', ' / ', escape($_institution['telefono'])); ?></div>
                </div>
                <div class="tr">
                    <div class="th text-nowrap">Empresa :</div>
                    <div class="td text-ellipsis"><?= ($_institution['empresa1'] == '') ? 'No asignado' : str_replace(',', ' / ', escape($_institution['empresa1'])); ?></div>
                </div>
			</div>
		</div>
	</div>
</div>
<script>
$(function () {
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'u':
					e.preventDefault();
					window.location = '?/configuraciones/institucion_editar';
				break;
			}
		}
	});
});
</script>
<?php require_once show_template('footer-advanced'); ?>