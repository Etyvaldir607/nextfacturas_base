<?php

// Obtiene el id_producto
$id_producto = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el producto
$producto = $db->select('z.*, a.unidad, a.sigla, b.categoria, p.proveedor')
               ->from('inv_productos z')
               ->join('inv_unidades a', 'z.unidad_id = a.id_unidad', 'left')
               ->join('inv_categorias b', 'z.categoria_id = b.id_categoria', 'left')
               ->join('inv_proveedores p', 'z.proveedor_id = p.id_proveedor', 'left')
               ->where('z.id_producto', $id_producto)
               ->fetch_first();


// Verifica si existe el producto
if (!$producto) {
	// Error 404
	require_once not_found();
	exit;
}

$roles = explode(',',$producto['asignacion_rol']);

$keys = array_keys($roles);

$consulta = "SELECT group_CONCAT(rol)AS roles FROM sys_roles ";

if ($roles) {
	$consulta .= " WHERE ";
	foreach ($roles as $key => $value) {	
		$consulta .= " id_rol='" . $value . "' ";
		if ($key < end($keys)) {
			$consulta .= " OR ";
		}
	}
}

$roles_ = $db->query($consulta)->fetch_first()['roles'];

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? escape($moneda['sigla']) : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_editar = in_array('editar', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos);
$permiso_listar = in_array('listar', $permisos);
$permiso_subir = in_array('subir', $permisos);
$permiso_suprimir = in_array('suprimir', $permisos);
$permiso_saltar = in_array('saltar', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<link href="<?= css; ?>/jquery.guillotine.min.css" rel="stylesheet">
<style>
.table-display > .thead > .tr,
.table-display > .tbody > .tr,
.table-display > .tfoot > .tr {
	margin-bottom: 15px;
}
.table-display > .thead > .tr > .th,
.table-display > .tbody > .tr > .th,
.table-display > .tfoot > .tr > .th {
	font-weight: bold;
}
@media (min-width: 768px) {
	.table-display {
		display: table;
	}
	.table-display > .thead,
	.table-display > .tbody,
	.table-display > .tfoot {
		display: table-row-group;
	}
	.table-display > .thead > .tr,
	.table-display > .tbody > .tr,
	.table-display > .tfoot > .tr {
		display: table-row;
	}
	.table-display > .thead > .tr > .th,
	.table-display > .thead > .tr > .td,
	.table-display > .tbody > .tr > .th,
	.table-display > .tbody > .tr > .td,
	.table-display > .tfoot > .tr > .th,
	.table-display > .tfoot > .tr > .td {
		display: table-cell;
	}
	.table-display > .tbody > .tr > .td,
	.table-display > .tbody > .tr > .th,
	.table-display > .tfoot > .tr > .td,
	.table-display > .tfoot > .tr > .th,
	.table-display > .thead > .tr > .td,
	.table-display > .thead > .tr > .th {
		padding-bottom: 15px;
		vertical-align: top;
	}
	.table-display > .tbody > .tr > .td:first-child,
	.table-display > .tbody > .tr > .th:first-child,
	.table-display > .tfoot > .tr > .td:first-child,
	.table-display > .tfoot > .tr > .th:first-child,
	.table-display > .thead > .tr > .td:first-child,
	.table-display > .thead > .tr > .th:first-child {
		padding-right: 15px;
	}
}
</style>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Detalle del producto</strong>
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
			<a href="?/productos/crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
			<?php } ?>
			<?php if ($permiso_editar) { ?>
			<a href="?/productos/editar/<?= $producto['id_producto']; ?>" class="btn btn-warning"><i class="glyphicon glyphicon-edit"></i><span class="hidden-xs hidden-sm"> Editar</span></a>
			<?php } ?>
			<?php if ($permiso_eliminar) { ?>
			<a href="?/productos/eliminar/<?= $producto['id_producto']; ?>" class="btn btn-danger" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
			<?php } ?>
			<?php if ($permiso_imprimir) { ?>
			<a href="?/productos/imprimir/<?= $producto['id_producto']; ?>" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs hidden-sm"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/productos/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs hidden-sm <?= ($permiso_imprimir) ? 'hidden-md' : ''; ?>"> Listado</span></a>
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
		<div class="col-sm-3">
			<img src="<?= ($producto['imagen'] == '') ? imgs . '/image.jpg' : files . '/productos/' . $producto['imagen']; ?>" class="img-responsive thumbnail cursor-pointer" data-toggle="modal" data-target="#modal_mostrar" data-modal-size="modal-md" data-modal-title="Imagen">
			<?php if ($permiso_subir || $permiso_suprimir) { ?>
			<div class="list-group">
				<?php if ($permiso_subir) { ?>
				<a href="#" class="list-group-item text-ellipsis" data-toggle="modal" data-target="#modal_subir" data-backdrop="static" data-keyboard="false">
					<span class="glyphicon glyphicon-picture"></span>
					<span>Subir imagen</span>
				</a>
				<?php } ?>
				<?php if ($permiso_suprimir) { ?>
				<a href="?/productos/suprimir/<?= $id_producto; ?>" class="list-group-item text-ellipsis" data-suprimir="true">
					<span class="glyphicon glyphicon-eye-close"></span>
					<span>Eliminar imagen</span>
				</a>
				<?php } ?>
			</div>
			<?php } ?>
		</div>
		<div class="col-sm-6">
			<div class="well">
				<p class="lead">Información del producto</p>
				<hr>
				<div class="table-display" data-print-data="true">
					<div class="tbody">
						<div class="tr">
							<div class="th text-nowrap">Fecha de creación:</div>
							<div class="td">
								<span><?= date_decode($producto['fecha_registro'], $_institution['formato']); ?></span>
								<span class="text-primary"><?= escape($producto['hora_registro']); ?></span>
							</div>
						</div>
						<div class="tr">
							<div class="th text-nowrap">Código del producto:</div>
							<div class="td">
								<span><?= escape($producto['codigo']); ?></span>
							</div>
						</div>
						<div class="tr">
							<div class="th text-nowrap">Código de barras:</div>
							<div class="td">
								<span><?php if($producto['codigo_barras']){echo substr($producto['codigo_barras'],2);}else{echo '';}  ?></span>
							</div>
						</div>
						<div class="tr">
							<div class="th text-nowrap">Nombre generico:</div>
							<div class="td">
								<span><?= escape($producto['nombre']); ?></span>
							</div>
						</div>
						<div class="tr">
							<div class="th text-nowrap">Nombre comercial:</div>
							<div class="td">
								<span><?= escape($producto['nombre_factura']); ?></span>
							</div>
						</div>
                        <div class="tr">
                            <div class="th text-nowrap">Categoría:</div>
                            <div class="td">
                                <span><?= escape($producto['categoria']); ?></span>
                            </div>
                        </div>
                        <div class="tr">
                            <div class="th text-nowrap">Precio sugerido:</div>
                            <div class="td">
                                <span><?= escape($producto['precio_sugerido']); ?></span>
                            </div>
                        </div>
                        <div class="tr">
                            <div class="th text-nowrap">Proveedor:</div>
                            <div class="td">
                                <span><?= escape($producto['proveedor']); ?></span>
                            </div>
                        </div>

						<div class="tr">
							<div class="th text-nowrap">Cantidad mínima:</div>
							<div class="td">
								<span><?= escape($producto['cantidad_minima'] . ' ' . $producto['sigla']); ?></span>
							</div>
						</div>
						<div class="tr">
							<div class="th text-nowrap">Cantidad por mayor:</div>
							<div class="td">
								<span><?= escape($producto['cantidad_mayor'] . ' ' . $producto['sigla']); ?></span>
							</div>
						</div>
						<div class="tr">
							<div class="th text-nowrap">Unidad:</div>
							<div class="td">
								<span><?= escape($producto['unidad']); ?></span>
							</div>
						</div>
						<div class="tr hidden">
                            <div class="th text-nowrap">Roles permitidos:</div>
                            <div class="td">
                                <span><?= (roles_ != '') ? $roles_ : 'No asignado' ;  ?></span>
                            </div>
                        </div>
                        <div class="tr">
                            <div class="th text-nowrap">Ubicación:</div>
                            <div class="td">
                                <span><?= (trim($producto['ubicacion']) == '') ? 'No asignado' : str_replace("\n", "<br>", escape($producto['ubicacion'])); ?></span>
                            </div>
                        </div>
                        <div class="tr">
                            <div class="th text-nowrap">Descripción:</div>
                            <div class="td">
                                <span><?= (trim($producto['descripcion']) == '') ? 'No asignado' : str_replace("\n", "<br>", escape($producto['descripcion'])); ?></span>
                            </div>
                        </div>
                        <div class="tr">
                            <div class="th text-nowrap">Codigo sanitario:</div>
                            <div class="td">
                                <span><?= escape($producto['codigo_sanitario']); ?></span>
                            </div>
                        </div>
					</div>
				</div>
			</div>
			<?php if ($permiso_saltar) : ?>
			<div class="pager">
				<div class="previous">
					<a href="?/productos/saltar/antes/<?= $id_producto; ?>" class="btn btn-default" data-saltar="true">
						<span class="glyphicon glyphicon-menu-left"></span>
						<span class="hidden-sm">Anterior</span>
					</a>
				</div>
				<div class="next">
					<a href="?/productos/saltar/despues/<?= $id_producto; ?>" class="btn btn-default" data-saltar="true">
						<span class="hidden-sm">Siguiente</span>
						<span class="glyphicon glyphicon-menu-right"></span>
					</a>
				</div>
			</div>
			<?php endif ?>
		</div>
		<div class="col-sm-3">
			<?php if ($producto['codigo_barras'] != 'CB') : ?>
			<div class="thumbnail hidden" data-print-code="true">
				<img class="barcode img-responsive" jsbarcode-format="code128" jsbarcode-value="<?= substr($producto['codigo_barras'],2); ?>" jsbarcode-displayValue="true" jsbarcode-width="2" jsbarcode-height="64" jsbarcode-margin="0" jsbarcode-textMargin="-3" jsbarcode-fontSize="20" jsbarcode-lineColor="#333">
			</div>
			<div class="thumbnail">
				<svg class="barcode img-responsive" jsbarcode-format="code128" jsbarcode-value="<?= substr($producto['codigo_barras'],2); ?>" jsbarcode-displayValue="true" jsbarcode-width="2" jsbarcode-height="64" jsbarcode-margin="0" jsbarcode-textMargin="-3" jsbarcode-fontSize="20" jsbarcode-lineColor="#333"></svg>
			</div>
			<?php endif ?>
			<div class="well">
				<p class="lead margin-none">Precios de venta</p>
				<hr>
				<p class="lead margin-none text-left"><?= ($producto['precio_actual'])? 'Precio producto: ':''; ?><em class="lead margin-none text-righ text-info"><?= escape($producto['precio_actual'] . ' ' . $moneda); ?></em></p>
				<p class="lead margin-none "><?= ($producto['precio_contado'])? 'Precio contado: ':''; ?><em class="lead margin-none text-righ text-info"><?= escape($producto['precio_contado'] . ' ' . $moneda); ?></em></p>
				<p class="lead margin-none "><?= ($producto['precio_mayor'])? 'Precio por mayor: ':''; ?><em class="lead margin-none text-righ text-info"><?= escape($producto['precio_mayor'] . ' ' . $moneda); ?></em></p>
			</div>
			<?php $cod_barras = str_replace('CB', '', $producto['codigo_barras']); 
			if($cod_barras !='') { ?>
			<p class="lead text-danger">Impresión de codigos de barras</p>
			<form id="impresion" data-codigo="<?= substr($producto['codigo_barras'],2); ?>">
				<div class="input-group">
					<input type="text" class="form-control" placeholder="Cantidad a imprimir">
					<span class="input-group-btn">
						<button type="submit" class="btn btn-warning">
							<span class="glyphicon glyphicon-barcode"></span>
							<span>Imprimir</span>
						</button>
					</span>
				</div>
			</form>
			<?php } ?>

		</div>
	</div>
</div>

<!-- Modal subir inicio -->
<?php if ($permiso_subir) { ?>
<div id="modal_subir" class="modal fade" tabindex="-1">
	<div class="modal-dialog">
		<form method="post" action="?/productos/subir" enctype="multipart/form-data" id="form_subir" class="modal-content" autocomplete="off">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Subir imagen</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label for="imagen" class="control-label">Imagen:</label>
					<input type="file" name="imagen" id="imagen" class="form-control" data-validation="required mime size" data-validation-allowing="jpg, png" data-validation-max-size="2M">
					<input type="text" value="<?= $id_producto; ?>" name="id_producto" id="id_producto" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="El campo no es válido">
					<input type="text" value="" name="data" id="data" class="translate" tabindex="-1" data-validation="required" data-validation-error-msg="El campo no es válido">
				</div>
				<div class="row" data-guillotine-element="container">
					<div class="col-sm-7">
						<div class="thumbnail">
							<img id="image" src="">
						</div>
					</div>
					<div class="col-sm-5">
						<div class="list-group">
							<a href="#" class="list-group-item" data-guillotine-action="fit">
								<span class="glyphicon glyphicon-fullscreen"></span>
								<span>Tamaño completo</span>
							</a>
							<a href="#" class="list-group-item" data-guillotine-action="center">
								<span class="glyphicon glyphicon-align-center"></span>
								<span>Centrar imagen</span>
							</a>
							<a href="#" class="list-group-item" data-guillotine-action="zoomIn">
								<span class="glyphicon glyphicon-zoom-in"></span>
								<span>Aumentar tamaño</span>
							</a>
							<a href="#" class="list-group-item" data-guillotine-action="zoomOut">
								<span class="glyphicon glyphicon-zoom-out"></span>
								<span>Reducir tamaño</span>
							</a>
							<a href="#" class="list-group-item" data-guillotine-action="rotateLeft">
								<span class="glyphicon glyphicon-menu-left"></span>
								<span>Girar a izquierda</span>
							</a>
							<a href="#" class="list-group-item" data-guillotine-action="rotateRight">
								<span class="glyphicon glyphicon-menu-right"></span>
								<span>Girar a derecha</span>
							</a>
							<a href="#" class="list-group-item" data-guillotine-action="getData">
								<span class="glyphicon glyphicon-floppy-disk"></span>
								<span>Guardar cambios</span>
							</a>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default">
					<span class="glyphicon glyphicon-search"></span>
					<span>Visualizar</span>
				</button>
			</div>
		</form>
	</div>
</div>
<?php } ?>
<!-- Modal subir fin -->

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

<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/jquery.guillotine.min.js"></script>
<script src="<?= js; ?>/JsBarcode.all.min.js"></script>
<script>
$(function () {
	JsBarcode('.barcode').init();

	<?php if ($permiso_eliminar) { ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar el producto?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>

	<?php if ($permiso_subir) { ?>
	var $modal_subir = $('#modal_subir');
	var $image = $('#image');
	var $container = $('[data-guillotine-element="container"]');

	$.validate({
		form: '#form_subir',
		modules: 'file'
	});

	$modal_subir.on('hidden.bs.modal', function () {
		$(this).find('form').trigger('reset');
		$container.hide();
	}).on('show.bs.modal', function (e) {
		if ($('.modal:visible').size() != 0) { e.preventDefault(); }
	});

	$('#imagen').on('validation', function (e, valid) {
		if (valid) {
			var input = $(this).get(0);
			if (input.files && input.files[0]) {
				var reader = new FileReader();
				reader.onload = function (e) {
					$image.attr('src', e.target.result);
				}
				reader.readAsDataURL(input.files[0]);
			}
		} else {
			$container.hide();
		}
	}).on('change', function () {
		$container.hide();
	});

	$image.on('load', function () {
		$image.guillotine('remove');
		$image.guillotine({
			width: 650,
			height: 650
		});
		$image.guillotine('fit');
		$container.show();
	});

	$('[data-guillotine-action]').on('click', function (e) {
		e.preventDefault();
		var data, scale, action = $(this).attr('data-guillotine-action');
		if (action != 'getData') {
			if (action == 'zoomIn') {
				data = $image.guillotine('getData');
				scale = data.scale;
				if (scale <= 2) {
					$image.guillotine(action);
				}
			} else {
				$image.guillotine(action);
			}
		} else {
			data = $image.guillotine(action);
			data = JSON.stringify(data);
			$('#data').val(data);
			$modal_subir.modal('hide').find('form').submit();
		}
	});

	$modal_subir.trigger('hidden.bs.modal');
	<?php } ?>

	<?php if ($permiso_suprimir) { ?>
	$('[data-suprimir]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar la imagen del producto?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>

	<?php if ($permiso_saltar) : ?>
	$('[data-saltar]').on('click', function (e) {
		e.preventDefault();
		var href = $(this).attr('href');
		window.location = href;
	});
	<?php endif ?>

	$('#impresion').on('submit', function (e) {
		e.preventDefault();
		var codigo = $(this).attr('data-codigo'), cantidad = $.trim($(this).find(':text').val());
		
		if ($.isNumeric(cantidad) && cantidad > 0 && cantidad < 10000) {
			window.open('?/productos/generar/' + codigo + '/' + cantidad, '_blank');
		} else {
			$(this).find(':text').val('');
			bootbox.alert('La información enviada debe ser de tipo numérico (mayor que 0 y menor que 10000)');
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