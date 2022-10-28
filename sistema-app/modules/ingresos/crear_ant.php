<?php

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

$gestion = date('Y');
$gestion_base = date('Y-m-d');
//$gestion_base = ($gestion - 16) . date('-m-d');
$gestion_limite = ($gestion + 16) . date('-m-d');

// Obtiene el almacen principal
$almacen = $db->from('inv_almacenes')->where('principal', 'S')->fetch_first();
$id_almacen = ($almacen) ? $almacen['id_almacen'] : 0;

// Verifica si existe el almacen
if ($id_almacen != 0) {
    // Obtiene los productos
    $productos = $db->query("select p.id_producto, p.codigo, p.nombre, p.descripcion, p.promocion, p.nombre_factura, p.cantidad_minima, p.precio_actual, ifnull(e.cantidad_ingresos, 0) as cantidad_ingresos, ifnull(s.cantidad_egresos, 0) as cantidad_egresos, u.unidad, u.sigla, c.categoria from inv_productos p left join (select d.producto_id, sum(d.cantidad) as cantidad_ingresos from inv_ingresos_detalles d left join inv_ingresos i on i.id_ingreso = d.ingreso_id where i.almacen_id = $id_almacen group by d.producto_id ) as e on e.producto_id = p.id_producto left join (select d.producto_id, sum(d.cantidad) as cantidad_egresos from inv_egresos_detalles d left join inv_egresos e on e.id_egreso = d.egreso_id where e.almacen_id = $id_almacen group by d.producto_id ) as s on s.producto_id = p.id_producto left join inv_unidades u on u.id_unidad = p.unidad_id left join inv_categorias c on c.id_categoria = p.categoria_id")->fetch();
} else {
    $productos = null;
}

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene el modelo almacenes
$almacenes = $db->from('inv_almacenes')->order_by('almacen')->fetch();

// Obtiene los proveedores
$proveedores = $db->select('id_proveedor, proveedor as nombre_proveedor')->from('inv_proveedores')->group_by('proveedor')->order_by('proveedor asc')->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_listar = in_array('listar', $permisos);

?>
<?php require_once show_template('header-empty'); ?>
    <style>
        .table-xs tbody {
            font-size: 12px;
        }
        .input-xs {
            height: 22px;
            padding: 1px 5px;
            font-size: 12px;
            line-height: 1.5;
            border-radius: 3px;
        }
    </style>
    <div class="row" data-formato="<?= strtoupper($formato_textual); ?>" data-mascara="<?= $formato_numeral; ?>" data-gestion="<?= date_decode($gestion_base, $_institution['formato']); ?>">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <span class="glyphicon glyphicon-list"></span>
                        <strong>Datos del ingreso</strong>
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="alert alert-info">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <strong>Advertencia!</strong>
                        <ul>
                            <li>Para un mejor control del ingreso de productos se recomienda escribir una pequeña descripción acerca de la compra.</li>
                            <li>La moneda con la que se esta trabajando es <?= escape($moneda); ?>.</li>
                            <li>Los stocks que se muestra en la búsqueda de productos son del almacén principal.</li>
                        </ul>
                    </div>
                    <form method="post" action="?/ingresos/guardar" id="formulario" class="form-horizontal">
                        <div class="form-group">
                            <label for="almacen" class="col-md-4 control-label">Almacén:</label>
                            <div class="col-md-8">
                                <select name="almacen_id" id="almacen" class="form-control" data-validation="required number">
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($almacenes as $elemento) { ?>
                                        <option value="<?= $elemento['id_almacen']; ?>"><?= escape($elemento['almacen'] . (($elemento['principal'] == 'S') ? ' (principal)' : '')); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="proveedor" class="col-sm-4 control-label">Proveedor:</label>
                            <div class="col-sm-8">
                                <select name="nombre_proveedor" id="proveedor" class="form-control" data-validation="required letternumber length" data-validation-allowing="-.#() " data-validation-length="max100">
                                    <option value="">Buscar</option>
                                    <?php foreach ($proveedores as $elemento) { ?>
                                        <option value="<?= escape($elemento['nombre_proveedor']); ?>"><?= escape($elemento['nombre_proveedor']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="descripcion" class="col-sm-4 control-label">Descripción:</label>
                            <div class="col-sm-8">
                                <textarea name="descripcion" id="descripcion" class="form-control" autocomplete="off" data-validation="letternumber" data-validation-allowing="+-/.,:;#º()\n " data-validation-optional="true"></textarea>
                            </div>
                        </div>
                        <div class=" margin-none">
                            <table id="compras" class="table table-bordered table-condensed table-striped table-hover table-xs margin-none">
                                <thead>
                                <tr class="active">
                                    <th class="text-nowrap">Código</th>
                                    <th class="text-nowrap">Nombre</th>
                                    <th class="text-nowrap">F. vencimiento</th>
                                    <!--<th class="text-nowrap">Nro. DUI</th>-->
                                    <th class="text-nowrap">Contenedor</th>
                                    <th class="text-nowrap">Factura</th>
                                    <th class="text-nowrap">Cantidad</th>
                                    <th class="text-nowrap">Costo</th>
                                    <th class="text-nowrap">Importe</th>
                                    <th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
                                </tr>
                                </thead>
                                <tfoot>
                                <tr class="active">
                                    <th class="text-nowrap text-right" colspan="7">Importe total <?= escape($moneda); ?></th>
                                    <th class="text-nowrap text-right" data-subtotal="">0.00</th>
                                    <th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
                                </tr>
                                </tfoot>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <input type="text" name="nro_registros" value="0" class="translate" tabindex="-1" data-compras="" data-validation="required number" data-validation-allowing="range[1;50]" data-validation-error-msg="Debe existir como mínimo 1 producto y como máximo 50 productos">
                                <input type="text" name="monto_total" value="0" class="translate" tabindex="-1" data-total="" data-validation="required number" data-validation-allowing="range[0.01;1000000.00],float" data-validation-error-msg="El costo total de la compra debe ser mayor a cero y menor a 1000000.00">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-6 text-left">
                                <label for="almacen" class="col-md-5 control-label">Almacén transitorio:</label>
                                <div class="col-md-7 right">
                                    <div class="input-group">
                                  <span class="input-group-addon">
                                    <input type="checkbox" name="reserva" aria-label="...">
                                  </span>
                                        <input type="text" name="des_reserva" placeholder="Motivo" class="form-control" aria-label="...">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6 text-right">
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
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <span class="glyphicon glyphicon-search"></span>
                        <strong>Búsqueda de productos</strong>
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-xs-12 text-right">
                            <a href="?/ingresos/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span> Listado de ingresos</span></a>
                        </div>
                    </div>
                    <hr>
                    <?php if ($productos) { ?>
                        <table id="productos" class="table table-bordered table-condensed table-striped table-hover table-xs">
                            <thead>
                            <tr class="active">
                                <th class="text-nowrap">Código</th>
                                <th class="text-nowrap">Nombre</th>
                                <th class="text-nowrap">Descripción</th>
                                <!--<th class="text-nowrap">Color</th>-->
                                <th class="text-nowrap">Tipo</th>
                                <th class="text-nowrap">Stock</th>
                                <th class="text-nowrap">Precio</th>
                                <th class="text-nowrap"><i class="glyphicon glyphicon-cog"></i></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($productos as $nro => $producto) { ?>
                                <tr class="<?php if($producto['promocion']=='si'){echo 'warning';}?>" >
                                    <td class="text-nowrap" data-codigo="<?= $producto['id_producto']; ?>"><?= escape($producto['codigo']); ?></td>
                                    <td data-nombre="<?= $producto['id_producto']; ?>"><?= escape($producto['nombre']); ?></td>
                                    <td class="text-nowrap"><?= escape($producto['descripcion']); ?></td>
                                    <!--<td class="text-nowrap"><?= escape($producto['color']); ?></td>-->
                                    <td class="text-nowrap"><?= escape($producto['categoria']); ?></td>
                                    <td class="text-nowrap text-right"><?= escape($producto['cantidad_ingresos'] - $producto['cantidad_egresos']); ?></td>
                                    <td class="text-nowrap text-right"><?= escape($producto['precio_actual']); ?></td>
                                    <td class="text-nowrap">
                                        <button type="button" class="btn btn-xs btn-primary" data-comprar="<?= $producto['id_producto']; ?>" data-toggle="tooltip" data-title="Comprar"><span class="glyphicon glyphicon-share-alt"></span></button>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    <?php } else { ?>
                        <div class="alert alert-danger">
                            <strong>Advertencia!</strong>
                            <p>No existen productos registrados en la base de datos.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= js; ?>/jquery.form-validator.min.js"></script>
    <script src="<?= js; ?>/jquery.form-validator.es.js"></script>
    <script src="<?= js; ?>/jquery.dataTables.min.js"></script>
    <script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
    <script src="<?= js; ?>/selectize.min.js"></script>
    <script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>
    <script>
        $(function () {
            $('[data-comprar]').on('click', function () {
                adicionar_producto($.trim($(this).attr('data-comprar')));
            });

            $('#productos').dataTable({
                info: false,
                lengthMenu: [[25, 50, 100, 500, -1], [25, 50, 100, 500, 'Todos']],
                order: []
            });

            $('#productos_wrapper .dataTables_paginate').parent().attr('class', 'col-sm-12 text-right');

            $('#proveedor').selectize({
                persist: false,
                createOnBlur: true,
                create: true,
                onInitialize: function () {
                    $('#proveedor').css({
                        display: 'block',
                        left: '-10000px',
                        opacity: '0',
                        position: 'absolute',
                        top: '-10000px'
                    });
                },
                onChange: function () {
                    $('#proveedor').trigger('blur');
                },
                onBlur: function () {
                    $('#proveedor').trigger('blur');
                }
            });

            $('#almacen').selectize({
                persist: false,
                onInitialize: function () {
                    $('#almacen').css({
                        display: 'block',
                        left: '-10000px',
                        opacity: '0',
                        position: 'absolute',
                        top: '-10000px'
                    });
                },
                onChange: function () {
                    $('#almacen').trigger('blur');
                },
                onBlur: function () {
                    $('#almacen').trigger('blur');
                }
            });

            $(':reset').on('click', function () {
                $('#proveedor')[0].selectize.clear();
                $('#almacen')[0].selectize.clear();
            });

            $.validate({
                modules: 'basic'
            });

            $('#formulario').on('reset', function () {
                $('#compras tbody').find('[data-importe]').text('0.00');
                calcular_total();
            });

            $('#formulario :reset').trigger('click');
        });

        function adicionar_producto(id_producto) {
            var $producto = $('[data-producto=' + id_producto + ']');
            var $cantidad = $producto.find('[data-cantidad]');
            var $compras = $('#compras tbody');
            var codigo = $.trim($('[data-codigo=' + id_producto + ']').text());
            var nombre = $.trim($('[data-nombre=' + id_producto + ']').text());
            var plantilla = '';
            var cantidad;
            var formato = $('[data-formato]').attr('data-formato');

            if ($producto.size()) {
                cantidad = $.trim($cantidad.val());
                cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
                cantidad = (cantidad < 9999999) ? cantidad + 1: cantidad;
                $cantidad.val(cantidad).trigger('blur');
            } else {
                plantilla = '<tr class="active" data-producto="' + id_producto + '">' +
                '<td class="text-nowrap"><input type="text" value="' + id_producto + '" name="productos[]" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="Debe ser número">' + codigo + '</td>' +
                '<td>' + nombre + '</td>' +
                '<td><div class="row"><div class="col-xs-12"><input type="text" name="fechas[]" value="<?= date('Y/m/d'); ?>" class="form-control input-xs text-right" data-fecha="" ></div></div></td>' +
                //'<td><input type="text" value="" name="duis[]" class="form-control input-xs text-right" maxlength="7" autocomplete="off" data-dui=""  data-validation-error-msg="Debe ser número entero positivo" ></td>' +
                '<td><input type="text" value="" name="contenedores[]" class="form-control input-xs text-right" maxlength="7" autocomplete="off" data-contenedor=""  data-validation-error-msg="Debe ser número entero positivo" ></td>' +
                '<td><input type="text" value="" name="facturas[]" class="form-control input-xs text-right" maxlength="7" autocomplete="off" data-contenedor=""  data-validation-error-msg="Debe ser número entero positivo" ></td>' +
                '<td><input type="text" value="1" name="cantidades[]" class="form-control input-xs text-right" maxlength="7" autocomplete="off" data-cantidad="" data-validation="required number" data-validation-error-msg="Debe ser número entero positivo" onkeyup="calcular_importe(' + id_producto + ')"></td>' +
                '<td><input type="text" value="0.00" name="costos[]" class="form-control input-xs text-right" autocomplete="off" data-costo="" data-validation="required number" data-validation-allowing="range[0.01;1000000.00],float" data-validation-error-msg="Debe ser número decimal positivo" onkeyup="calcular_importe(' + id_producto + ')" onblur="redondear_importe(' + id_producto + ')"></td>' +
                '<td class="text-nowrap text-right" data-importe="">0.00</td>' +
                '<td class="text-nowrap text-center">' +
                '<button type="button" class="btn btn-xs btn-danger" data-toggle="tooltip" data-title="Eliminar producto" tabindex="-1" onclick="eliminar_producto(' + id_producto + ')"><span class="glyphicon glyphicon-remove"></span></button>' +
                '</td>' +
                '</tr>';

                $compras.append(plantilla);

                $compras.find('[data-cantidad], [data-costo]').on('click', function () {
                    $(this).select();
                });

                $compras.find('[data-fecha]').datetimepicker({
                    format: formato
                });

                $compras.find('[title]').tooltip({
                    container: 'body',
                    trigger: 'hover'
                });

                $.validate({
                    modules: 'basic'
                });
            }

            calcular_importe(id_producto);
        }

        function eliminar_producto(id_producto) {
            bootbox.confirm('Está seguro que desea eliminar el producto?', function (result) {
                if(result){
                    $('[data-producto=' + id_producto + ']').remove();
                    calcular_total();
                }
            });
        }

        function redondear_importe(id_producto) {
            var $producto = $('[data-producto=' + id_producto + ']');
            var $costo = $producto.find('[data-costo]');
            var costo;

            costo = $.trim($costo.val());
            costo = ($.isNumeric(costo)) ? parseFloat(costo).toFixed(2) : costo;
            $costo.val(costo);

            calcular_importe(id_producto);
        }

        function calcular_importe(id_producto) {
            var $producto = $('[data-producto=' + id_producto + ']');
            var $cantidad = $producto.find('[data-cantidad]');
            var $costo = $producto.find('[data-costo]');
            var $importe = $producto.find('[data-importe]');
            var cantidad, costo, importe;

            cantidad = $.trim($cantidad.val());
            cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
            costo = $.trim($costo.val());
            costo = ($.isNumeric(costo)) ? parseFloat(costo) : 0.00;
            importe = cantidad * costo;
            importe = importe.toFixed(2);
            $importe.text(importe);

            calcular_total();
        }

        function calcular_total() {
            var $compras = $('#compras tbody');
            var $total = $('[data-subtotal]:first');
            var $importes = $compras.find('[data-importe]');
            var importe, total = 0;

            $importes.each(function (i) {
                importe = $.trim($(this).text());
                importe = parseFloat(importe);
                total = total + importe;
            });

            $total.text(total.toFixed(2));
            $('[data-compras]:first').val($importes.size()).trigger('blur');
            $('[data-total]:first').val(total.toFixed(2)).trigger('blur');
        }
    </script>
<?php require_once show_template('footer-empty'); ?>