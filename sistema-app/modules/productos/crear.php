<?php

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

// Obtiene el modelo unidades
$unidades = $db->from('inv_unidades')->order_by('unidad')->fetch();

// Obtiene el modelo categorias
$categorias = $db->from('inv_categorias')->order_by('categoria')->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_listar = in_array('listar', $permisos);

$Roles=$db->query("SELECT GROUP_CONCAT(rol)AS roles FROM sys_roles LIMIT 1")->fetch_first()['roles'];

$proveedores = $db->query('SELECT a.id_proveedor, a.proveedor, a.nit, a.telefono, a.direccion,  count(a.proveedor) as nro_visitas, sum(b.monto_total) as total_compras FROM inv_proveedores a LEFT OUTER JOIN inv_ingresos b ON a.proveedor = b.nombre_proveedor')->group_by('a.proveedor')->order_by('proveedor asc, nit asc')->fetch();


require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-formato="<?= strtoupper($formato_textual); ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Crear producto</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_listar) { ?>
		<div class="row">
			<div class="col-sm-8 hidden-xs">
				<div class="text-label">Para regresar al listado de productos hacer clic en el siguiente botón:</div>
			</div>
			<div class="col-xs-12 col-sm-4 text-right">
				<a href="?/productos/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span> Listado</span></a>
			</div>
		</div>
		<hr>
	<?php } ?>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<form method="post" action="?/productos/guardar" class="form-horizontal" autocomplete="off">
				<div class="form-group">
					<label for="codigo" class="col-md-3 control-label">Código:</label>
					<div class="col-md-9">
						<input type="hidden" value="0" name="id_producto" data-validation="required">
						<input type="text" value="" name="codigo" id="codigo" class="form-control" data-validation="server" data-validation-url="?/productos/validar"   data-validation-allowing="-/.#º() " data-validation-length="max50" >
					</div>
				</div>

				<div class="form-group">
					<label for="codigo_barras" class="col-md-3 control-label">Código de barras:</label>
					<div class="col-md-9">
						<div class="input-group">
							<input type="text" value="" name="codigo_barras" id="codigo_barras" class="form-control" data-validation="alphanumeric length server" data-validation-length="max50" data-validation-url="?/productos/validar_barras" data-validation-optional="true">
							<span class="input-group-btn">
								<button type="button" id="generar_crear" class="btn btn-default">
									<span class="glyphicon glyphicon-barcode"></span>
									<span class="hidden-xs">Generar</span>
								</button>
							</span>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label for="nombre" class="col-md-3 control-label">Nombre generico:</label>
					<div class="col-md-9">
						<input type="text" value="" name="nombre" id="nombre" class="form-control" data-validation="required letternumber length" data-validation-allowing='-+/.,:;#&º"()¨ ' data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="nombre_factura" class="col-md-3 control-label">Nombre comercial:</label>
					<div class="col-md-9">
						<input type="text" value="" name="nombre_factura" id="nombre_factura" class="form-control" data-validation="required letternumber length" data-validation-allowing='-+/.,:;#&º"()¨ ' data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="categoria_id" class="col-md-3 control-label">Categoría:</label>
					<div class="col-md-9">
						<select name="categoria_id" id="categoria_id" class="form-control" data-validation="required">
							<option value="">Seleccionar</option>
							<?php foreach ($categorias as $elemento) { ?>
								<option value="<?= $elemento['id_categoria']; ?>"><?= escape($elemento['categoria']); ?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="form-group">
					<label for="categoria_id" class="col-md-3 control-label">Proveedor:</label>
					<div class="col-md-9">
						<select name="proveedor_id" id="proveedor_id" class="form-control" data-validation="required">
							<option value="">Seleccionar</option>
							<?php foreach ($proveedores as $elemento) { ?>
								<option value="<?= $elemento['id_proveedor']; ?>"><?= escape($elemento['proveedor']); ?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="form-group">
					<label for="cantidad_minima" class="col-md-3 control-label">Cantidad mínima:</label>
					<div class="col-md-9">
						<input type="text" value="10" name="cantidad_minima" id="cantidad_minima" class="form-control" data-validation="required number">
					</div>
				</div>
				<div class="form-group">
					<label for="unidad_id" class="col-md-3 control-label">Unidad:</label>
					<div class="col-md-9">
						<select name="unidad_id" id="unidad_id" class="form-control" data-validation="required">
							<option value="">Seleccionar</option>
							<?php foreach ($unidades as $elemento) { ?>
								<option value="<?= $elemento['id_unidad']; ?>"><?= escape($elemento['unidad']); ?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="form-group">
					<label for="precio_actual" class="col-md-3 control-label">Precio del producto:</label>
					<div class="col-md-9">
						<input type="text" value="" name="precio_actual" id="precio_actual" class="form-control" data-validation="number" data-validation-allowing="float" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="precio_contado" class="col-md-3 control-label">Precio al contado:</label>
					<div class="col-md-9">
						<input type="text" value="" name="precio_contado" id="precio_contado" class="form-control" data-validation="number" data-validation-allowing="float" data-validation-optional="true">
					</div>
				</div>
				
				<!--Josema::adicon precios-->
				<div class="form-group">
					<div class="">
						<label for="precio_mayor" class="col-md-3 control-label">Precio por mayor:</label>
						<div class="col-md-3">
							<input type="text" value="" name="precio_mayor" id="precio_mayor" class="form-control" data-validation="number" data-validation-allowing="float" data-validation-optional="true">
						</div>
					</div>
					<div class="">
						<label for="cantidad_minima" class="col-md-2 control-label">Cantidad por mayor:</label>
						<div class="col-md-3">
							<input type="text" value="100" name="cantidad_mayor" id="cantidad_mayor" class="form-control" data-validation="required number">
						</div>
					</div>
				</div>
				<!--Josema::adicon precios-->
				
				<!--<div class="form-group">-->
				<!--	<label for="precio_actual" class="col-md-3 control-label">Descuento al Contado:</label>-->
				<!--	<div class="col-md-9">-->
				<!--		<input type="text" value="" name="descuento_contado" id="descuento_contado" value='1' class="form-control" data-validation="number" data-validation-allowing="float" data-validation-optional="true">-->
				<!--	</div>-->
				<!--</div>-->
				<div class="form-group">
					<label for="precio_actual" class="col-md-3 control-label">Precio sugerido(Clientes):</label>
					<div class="col-md-9">
						<input type="text" value="" name="precio_sugerido" id="precio_sugerido" class="form-control" data-validation="number" data-validation-allowing="float" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group hidden">
					<label for="roles" class="col-md-3 control-label">Roles permitidos:</label>
					<div class="col-md-9">
						<input type='text' name='roles' id='roles' class='form-control demo-default' value='<?= $Roles ?>' data-validation='text' data-validation-optional='true'>
					</div>
				</div>
				<div class="form-group">
					<label for="ubicacion" class="col-md-3 control-label">Ubicación:</label>
					<div class="col-md-9">
						<textarea name="ubicacion" id="ubicacion" class="form-control" rows="3" data-validation="letternumber" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-optional="true"></textarea>
					</div>
				</div>
				<div class="form-group">
					<label for="descripcion" class="col-md-3 control-label">Descripción:</label>
					<div class="col-md-9">
						<textarea name="descripcion" id="descripcion" class="form-control" rows="3" data-validation="letternumber" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-optional="true"></textarea>
					</div>
				</div>
				<div class="form-group">
					<label for="nombre" class="col-md-3 control-label">Codigo Sanitario:</label>
					<div class="col-md-9">
						<input type="text" value="" name="codigo_sanitario" id="codigo_sanitario" class="form-control" >
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
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>
<script src="<?= js; ?>/selectize.min.js"></script>
<script type="text/javascript">
	$(function() {
		var $fecha = $('#ven_fecha');

		var formato = $('[data-formato]').attr('data-formato');
		$fecha.datetimepicker({
			format: formato
		});


		$.validate({
			modules: 'basic,security'
		});

		$('#nombre').on('keyup', function() {
			$('#nombre_factura').val($.trim($(this).val()));
		});

		$('.form-control:first').select();

		$('#roles').selectize({
			plugins: [],
			delimiter: ',',
			persist: false,
			create: function(input) {
				return {
					value: input,
					text: input
				}
			}
		});
        // Josema:: agregando validacion para los precios
        $('#precio_actual').keyup(function(){
			valor = $('#precio_actual').val();
			$('#precio_mayor').attr('max', valor);
            //console.log(valor);
		});
		$('#precio_mayor').keyup(function(){
			valor = $('#precio_mayor').val();
			maxi = $('#precio_actual').val();
			if ( valor > maxi ) {
				$('#precio_mayor').attr('data-validation-allowing', 'range[1;' + maxi + ']');
				$('#precio_mayor').attr('data-validation-error-msg', 'Debe ser menor o igual al precio unitario: ' + maxi);
				// console.log($('#precio_mayor').max());
			}
		});
        // Josema:: agregando validacion para los precios
	});

	var $generar_crear = $('#generar_crear');
	var $codigo_crear = $('#codigo_barras');
	$generar_crear.on('click', function() {

		$.ajax({
			type: 'post',
			dataType: 'json',
			url: '?/productos/generarbc'
		}).done(function(objeto) {
			$codigo_crear.val(objeto.codigo);
			$codigo_crear.trigger('blur');
		}).fail(function() {
			$codigo_crear.val('no se puede');
			$codigo_crear.trigger('blur');
		});
	});
</script>
<?php require_once show_template('footer-advanced'); ?>