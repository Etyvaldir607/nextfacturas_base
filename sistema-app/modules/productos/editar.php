<?php

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

// Obtiene el modelo unidades
$unidades = $db->from('inv_unidades')->order_by('unidad')->fetch();

// Obtiene el modelo categorias
$categorias = $db->from('inv_categorias')->order_by('categoria')->fetch();

// Obtiene el id_producto
$id_producto = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el producto
$producto =  $db->select('z.*, a.unidad as unidad, b.categoria as categoria')
                ->from('inv_productos z')
                ->join('inv_unidades a', 'z.unidad_id = a.id_unidad', 'left')
                ->join('inv_categorias b', 'z.categoria_id = b.id_categoria', 'left')
                ->where('z.id_producto', $id_producto)
                ->fetch_first();

// Verifica si existe el producto
if (!$producto) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_ver = in_array('ver', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_listar = in_array('listar', $permisos);

$Roles=$db->query("SELECT GROUP_CONCAT(rol)AS roles FROM sys_roles LIMIT 1")->fetch_first()['roles'];

$proveedores = $db->query(' SELECT a.id_proveedor, a.proveedor, a.nit, a.telefono, a.direccion,  count(a.proveedor) as nro_visitas, sum(b.monto_total) as total_compras 
                            FROM inv_proveedores a 
                            LEFT OUTER JOIN inv_ingresos b ON a.proveedor = b.nombre_proveedor')
                  ->group_by('a.proveedor')
                  ->order_by('proveedor asc, nit asc')
                  ->fetch();
//##obtener productos sin
require_once dirname(__DIR__) . '/siat/siat.php';
$actividades 		= siat_actividades();
$productos_sin 		= siat_obtener_productos();
$unidad_medida_siat = siat_tipos_unidades_medida(0, 0);

require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-formato="<?= strtoupper($formato_textual); ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Editar producto</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_crear || $permiso_ver || $permiso_eliminar || $permiso_listar) { ?>
		<div class="row">
			<div class="col-sm-7 col-md-6 hidden-xs">
				<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
			</div>
			<div class="col-xs-12 col-sm-5 col-md-6 text-right">
				<?php if ($permiso_crear) { ?>
					<a href="?/productos/crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
				<?php } ?>
				<?php if ($permiso_ver) { ?>
					<a href="?/productos/ver/<?= $producto['id_producto']; ?>" class="btn btn-warning"><i class="glyphicon glyphicon-search"></i><span class="hidden-xs hidden-sm"> Ver</span></a>
				<?php } ?>
				<?php if ($permiso_eliminar) { ?>
					<a href="?/productos/eliminar/<?= $producto['id_producto']; ?>" class="btn btn-danger" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
				<?php } ?>
				<?php if ($permiso_listar) { ?>
					<a href="?/productos/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs"> Listado</span></a>
				<?php } ?>
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
						<input type="hidden" value="<?= $producto['id_producto']; ?>" name="id_producto" data-validation="required">
						<input type="text" value="<?= $producto['codigo']; ?>" name="codigo" id="codigo" class="form-control" data-validation-allowing="-/.#º() " data-validation-length="max50">
					</div>
				</div>
				<div class="form-group">
					<label for="codigo_barras" class="col-md-3 control-label">Código de barras:</label>
					<div class="col-md-9">
						<div class="input-group">
							<input type="text" value="<?php if ($producto['codigo_barras'] != 'CB') {
															echo substr($producto['codigo_barras'], 2);
														} ?>" name="codigo_barras" id="codigo_barras" class="form-control" data-validation="alphanumeric length server" data-validation-allowing="-/.#º() " data-validation-length="max50" data-validation-url="?/productos/validar_barras" data-validation-optional="true">
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
						<input type="text" value="<?= escape($producto['nombre']); ?>" name="nombre" id="nombre" class="form-control" data-validation="required letternumber length" data-validation-allowing='-+/.,:;#&º"()¨ ' data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="nombre_factura" class="col-md-3 control-label">Nombre comercial:</label>
					<div class="col-md-9">
						<input type="text" value="<?= escape($producto['nombre_factura']); ?>" name="nombre_factura" id="nombre_factura" class="form-control" data-validation="required letternumber length" data-validation-allowing='-+/.,:;#&º"()¨ ' data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="categoria_id" class="col-md-3 control-label">Categoría:</label>
					<div class="col-md-9">
						<select name="categoria_id" id="categoria_id" class="form-control" data-validation="required">
							<option value="">Seleccionar</option>
							<?php foreach ($categorias as $elemento) { ?>
								<?php if ($elemento['id_categoria'] == $producto['categoria_id']) { ?>
									<option value="<?= $elemento['id_categoria']; ?>" selected><?= escape($elemento['categoria']); ?></option>
								<?php } else { ?>
									<option value="<?= $elemento['id_categoria']; ?>"><?= escape($elemento['categoria']); ?></option>
								<?php } ?>
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
						    	<?php if ($elemento['id_proveedor'] == $producto['proveedor_id']) { ?>
								<option value="<?= $elemento['id_proveedor']; ?>" selected><?= escape($elemento['proveedor']); ?></option>
								<?php } else { ?>
								<option value="<?= $elemento['id_proveedor']; ?>"><?= escape($elemento['proveedor']); ?></option>
							<?php } ?>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="form-group">
					<label for="cantidad_minima" class="col-md-3 control-label">Cantidad mínima:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $producto['cantidad_minima']; ?>" name="cantidad_minima" id="cantidad_minima" class="form-control" data-validation="required number">
					</div>
				</div>
				<div class="form-group">
					<label for="unidad_id" class="col-md-3 control-label">Unidad:</label>
					<div class="col-md-9">
						<select name="unidad_id" id="unidad_id" class="form-control" data-validation="required">
							<option value="">Seleccionar</option>
							<?php foreach ($unidades as $elemento) { ?>
								<?php if ($elemento['id_unidad'] == $producto['unidad_id']) { ?>
									<option value="<?= $elemento['id_unidad']; ?>" selected><?= escape($elemento['unidad']); ?></option>
								<?php } else { ?>
									<option value="<?= $elemento['id_unidad']; ?>"><?= escape($elemento['unidad']); ?></option>
								<?php } ?>
							<?php } ?>
						</select>
					</div>
				</div>
			    <div class="form-group">
					<label for="precio_actual" class="col-md-3 control-label">Precio del producto:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $producto['precio_actual']; ?>" name="precio_actual" id="precio_actual" class="form-control" data-validation="number" data-validation-allowing="float" data-validation-optional="true">
					</div>
				</div>
				<div class="form-group">
					<label for="precio_contado" class="col-md-3 control-label">Precio al contado:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $producto['precio_contado']; ?>" name="precio_contado" id="precio_contado" class="form-control" data-validation="number" data-validation-allowing="float" data-validation-optional="true">
					</div>
				</div>
				
				<div class="form-group">
					<label for="cantidad_minima" class="col-md-3 control-label">Cantidad por mayor:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $producto['cantidad_mayor']; ?>" name="cantidad_mayor" id="cantidad_mayor" class="form-control" data-validation="required number">
					</div>
				</div>
				<div class="form-group">
					<label for="precio_mayor" class="col-md-3 control-label">Precio por mayor:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $producto['precio_mayor']; ?>" name="precio_mayor" id="precio_mayor" class="form-control" data-validation="number" data-validation-allowing="float" data-validation-optional="true">
					</div>
				</div>
				<!--<div class="form-group hidden">-->
    <!--                <label for="precio_actual" class="col-md-3 control-label">Descuento al Contado:</label>-->
    <!--                <div class="col-md-9">-->
    <!--                    <input type="text" value="<?= $producto['descuento']; ?>" name="descuento_contado" id="descuento_contado" value='1' class="form-control" data-validation="number" data-validation-allowing="float" data-validation-optional="true" >-->
    <!--                </div>-->
				<!--</div>-->
				<!--<div class="form-group hidden">-->
    <!--                <label for="precio_contado" class="col-md-3 control-label">Precio al Contado:</label>-->
    <!--                <div class="col-md-9">-->
    <!--                    <input type="text" value="<?= $producto['precio_contado']; ?>" name="precio_contado" id="precio_contado" value='1' class="form-control" data-validation="number" data-validation-allowing="float" data-validation-optional="true" >-->
    <!--                </div>-->
				<!--</div>-->
				<!--<div class="form-group hidden">-->
				<!--	<label for="roles" class="col-md-3 control-label">Roles permitidos:</label>-->
				<!--	<div class="col-md-9">-->
				<!--		<input type='text' name='roles' id='roles' class='form-control demo-default' value='<?= $Roles ?>' data-validation='text' data-validation-optional='true'>-->
				<!--	</div>-->
				<!--</div>-->
				<div class="form-group">
					<label for="precio_actual" class="col-md-3 control-label">Precio sugerido(Clientes):</label>
					<div class="col-md-9">
						<input type="text" value="<?= $producto['precio_sugerido']; ?>" name="precio_sugerido" id="precio_sugerido" class="form-control" data-validation="number" data-validation-allowing="float" data-validation-optional="true">
					</div>
				</div>
				<!--<input type="hidden" value="<?= $producto['precio_actual']; ?>" name="precio_actual">-->
				<div class="form-group">
					<label for="ubicacion" class="col-md-3 control-label">Ubicación:</label>
					<div class="col-md-9">
						<textarea name="ubicacion" id="ubicacion" class="form-control" rows="3" data-validation="letternumber" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-optional="true"><?= escape($producto['ubicacion']); ?></textarea>
					</div>
				</div>
				<div class="form-group">
					<label for="descripcion" class="col-md-3 control-label">Descripción:</label>
					<div class="col-md-9">
						<textarea name="descripcion" id="descripcion" class="form-control" rows="3" data-validation="letternumber" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-optional="true"><?= escape($producto['descripcion']); ?></textarea>
					</div>
				</div>
				<div class="form-group">
					<label for="nombre" class="col-md-3 control-label">Codigo Sanitario:</label>
					<div class="col-md-9">
						<input type="text" value="<?= $producto['codigo_sanitario']; ?>" name="codigo_sanitario" id="codigo_sanitario" class="form-control" data-validation="letternumber length" data-validation-allowing='-+/.,:;#&º"()¨ ' data-validation-length="max100">
					</div>
				</div>
				
				<div class="form-group mb-3">
					<label class="col-md-3 control-label">Unidad de Medida SIAT</label>
					<div class="col-md-9">
						<select id="unidad_medida_siat" name="unidad_medida_siat" class="form-control form-select">
							<option value="">-- unidad medida siat --</option>
							<?php foreach($unidad_medida_siat->RespuestaListaParametricas->listaCodigos as $um): ?>
							<option value="<?php print $um->codigoClasificador ?>" 
								<?php print $producto['unidad_medida_siat'] == $um->codigoClasificador ? 'selected' : '' ?>>
								<?php print $um->descripcion; ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="form-group mb-3">
					<label class="col-md-3 control-label">Codigo Actividad</label>
					<div class="col-md-9">
						<select id="codigo_actividad" name="codigo_actividad" class="form-control form-select">
							<option value="">-- codigo actividad --</option>
							<?php foreach($actividades->RespuestaListaActividades->listaActividades as $a): ?>
							<option value="<?php print $a->codigoCaeb ?>" 
								<?php print $producto['codigo_actividad'] == $a->codigoCaeb ? 'selected' : '' ?>>
								<?php print $a->descripcion; ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="form-group mb-3">
					<label class="col-md-3 control-label">Codigo SIAT</label>
					<div class="col-md-9">
						<select id="codigo_sin" name="codigo_sin" class="form-control form-select">
							<option value="">-- codigo producto SIN --</option>
						</select>
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
<script>
	$(function() {
		var $fecha = $('#ven_fecha');

		var formato = $('[data-formato]').attr('data-formato');
		$fecha.datetimepicker({
			format: formato
		});

		$.validate({
			modules: 'basic'
		});

		$('.form-control:first').select();

		<?php if ($permiso_eliminar) { ?>
			$('[data-eliminar]').on('click', function(e) {
				e.preventDefault();
				var url = $(this).attr('href');
				bootbox.confirm('Está seguro que desea eliminar el producto?', function(result) {
					if (result) {
						window.location = url;
					}
				});
			});
		<?php } ?>
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
		var productos_sin = <?php print json_encode($productos_sin) ?>;
		var elCodigoSin = document.querySelector('#codigo_sin');
		function llenarProductosSin(codigoActividad)
		{
			elCodigoSin.innerHTML = '';
			for(var prod of productos_sin.RespuestaListaProductos.listaCodigos)
			{
				if( parseInt(prod.codigoActividad) != codigoActividad )
					continue;
				var op = document.createElement('option');
				op.value = prod.codigoProducto;
				op.innerHTML = prod.descripcionProducto;
				elCodigoSin.appendChild(op);
			}
		}
		document.querySelector('#codigo_actividad').addEventListener('change', function()
		{
			if( !this.value )
				return true;
			llenarProductosSin(parseInt(this.value));
		});
		llenarProductosSin(<?php print (int)$producto['codigo_actividad'] ?>);
		elCodigoSin.value = <?php print (int)$producto['codigo_sin'] ?>;
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
