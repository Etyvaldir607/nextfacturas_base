<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<b>Ajustes sobre los reportes</b>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para editar los datos hacer clic en el siguiente botón:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/configuraciones/reportes_editar" class="btn btn-primary">
				<span class="glyphicon glyphicon-edit"></span>
				<span>Modificar</span>
			</a>
		</div>
	</div>
	<hr>
	<div class="row">
		<div class="col-sm-4 col-md-3">
			<img src="<?= ($_institution['imagen_encabezado'] == '') ? imgs . '/picture.jpg' : institucion . '/' . $_institution['imagen_encabezado']; ?>" class="img-responsive thumbnail cursor-pointer" data-toggle="modal" data-target="#modal_mostrar" data-modal-size="modal-lg" data-modal-title="Logotipo">
		</div>
		<div class="col-sm-8 col-md-9">
			<div class="well">
				<div class="table-display">
					<div class="tbody">
						<div class="tr">
							<div class="th text-nowrap">Texto para el pie de página:</div>
							<div class="td text-ellipsis"><?= escape($_institution['pie_pagina']); ?></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Modal mostrar inicio -->
<div id="modal_mostrar" class="modal fade" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content loader-wrapper">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title"></h4>
			</div>
			<div class="modal-body">
				<img src="" class="img-responsive img-rounded" data-modal-image="">
			</div>
			<div id="loader_mostrar" class="loader-wrapper-backdrop">
				<span class="loader"></span>
			</div>
		</div>
	</div>
</div>
<!-- Modal mostrar fin -->

<script>
$(function () {
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'u':
					e.preventDefault();
					window.location = '?/configuraciones/reportes_editar';
				break;
			}
		}
	});

	var $modal_mostrar = $('#modal_mostrar'), $loader_mostrar = $('#loader_mostrar'), size, title, image;

	$modal_mostrar.on('hidden.bs.modal', function () {
		$loader_mostrar.show();
		$modal_mostrar.find('.modal-dialog').attr('class', 'modal-dialog');
		$modal_mostrar.find('.modal-title').text('');
	}).on('show.bs.modal', function (e) {
		size = $(e.relatedTarget).attr('data-modal-size');
		title = $(e.relatedTarget).attr('data-modal-title');
		image = $(e.relatedTarget).attr('src');
		size = (size) ? 'modal-dialog ' + size : 'modal-dialog';
		title = (title) ? title : 'Imagen';
		$modal_mostrar.find('.modal-dialog').attr('class', size);
		$modal_mostrar.find('.modal-title').text(title);
		$modal_mostrar.find('[data-modal-image]').attr('src', image);
	}).on('shown.bs.modal', function () {
		$loader_mostrar.hide();
	});
});
</script>
<?php require_once show_template('footer-advanced'); ?>