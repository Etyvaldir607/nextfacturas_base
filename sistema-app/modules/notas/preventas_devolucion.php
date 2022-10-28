<?php
$id_egreso = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el producto
$egreso = $db->select('*')->from('inv_egresos')->where('id_egreso',$id_egreso)->fetch_first();

if ($egreso['estadoe'] != '3') {
    $_SESSION[temporary] = array(
        'alert' => 'danger',
        'title' => 'No se puede hacer la devolucion!',
        'message' => 'Esta preventa aun no fue entregada, el cliente no puede hacer devoluciones hasta despues de haber recibido los productos.'
    );
    redirect('?/notas/mostrar');
}


$detalles = $db->query("SELECT a.*, b.*,e.categoria, a.unidad_id AS unidad_det, d.unidad
	FROM inv_egresos_detalles a
	LEFT JOIN inv_productos b ON a.producto_id = b.id_producto
	LEFT JOIN inv_categorias e ON b.categoria_id = e.id_categoria
	LEFT JOIN inv_unidades d ON b.unidad_id = d.id_unidad WHERE a.egreso_id = '$id_egreso' GROUP BY a.id_detalle")->fetch();

// echo json_encode($detalles); die();


// Obtiene el almacen principal
$almacen = $db->from('inv_almacenes')->fetch_first();
//echo json_encode($almacen);
//exit();
$id_almacen = ($almacen) ? $almacen['id_almacen'] : 0;
$prioridades = $db->select('*')->from('inv_prioridades_ventas')->fetch();
// Verifica si existe el almacen
if ($id_almacen != 0) {
    // Obtiene los productos
//    $productos = $db->query("select p.id_producto, p.descripcion, p.imagen, p.codigo, p.nombre, p.nombre_factura, p.cantidad_minima, p.precio_actual, p.unidad_id, ifnull(e.cantidad_ingresos, 0) as cantidad_ingresos, ifnull(s.cantidad_egresos, 0) as cantidad_egresos, IFNULL(e.costo, 0) AS costo_ingresos, u.unidad, u.sigla, c.categoria from inv_productos p left join (select d.producto_id, d.costo, sum(d.cantidad) as cantidad_ingresos from inv_ingresos_detalles d left join inv_ingresos i on i.id_ingreso = d.ingreso_id where i.almacen_id = $id_almacen  group by d.producto_id ) as e on e.producto_id = p.id_producto left join (select d.producto_id, sum(d.cantidad) as cantidad_egresos from inv_egresos_detalles d left join inv_egresos e on e.id_egreso = d.egreso_id where e.almacen_id = $id_almacen group by d.producto_id ) as s on s.producto_id = p.id_producto left join inv_unidades u on u.id_unidad = p.unidad_id left join inv_categorias c on c.id_categoria = p.categoria_id where p.grupo = ''")->fetch();
    $productos = $db->query("SELECT p.id_producto, p.promocion, z.id_asignacion, z.unidad_id, z.unidade, z.cantidad2, p.descripcion, p.imagen, p.codigo, p.nombre, p.nombre_factura, p.cantidad_minima, p.precio_actual, IFNULL(e.cantidad_ingresos, 0) AS cantidad_ingresos, IFNULL(s.cantidad_egresos, 0) AS cantidad_egresos, u.unidad, u.sigla, c.categoria
					FROM inv_productos p
					LEFT JOIN (SELECT d.producto_id, SUM(d.cantidad) AS cantidad_ingresos
						   FROM inv_ingresos_detalles d
						   LEFT JOIN inv_ingresos i ON i.id_ingreso = d.ingreso_id
						   WHERE i.almacen_id = '$id_almacen' GROUP BY d.producto_id) AS e ON e.producto_id = p.id_producto
					LEFT JOIN (SELECT d.producto_id, SUM(d.cantidad) AS cantidad_egresos
						   FROM inv_egresos_detalles d LEFT JOIN inv_egresos e ON e.id_egreso = d.egreso_id
						   WHERE e.almacen_id = '$id_almacen' GROUP BY d.producto_id) AS s ON s.producto_id = p.id_producto
					LEFT JOIN inv_unidades u ON u.id_unidad = p.unidad_id LEFT JOIN inv_categorias c ON c.id_categoria = p.categoria_id
					LEFT JOIN (SELECT w.producto_id, GROUP_CONCAT(w.id_asignacion SEPARATOR '|') AS id_asignacion, GROUP_CONCAT(w.unidad_id SEPARATOR '|') AS unidad_id, GROUP_CONCAT(w.cantidad_unidad,')',w.unidad,':',w.otro_precio SEPARATOR '&') AS unidade, GROUP_CONCAT(w.cantidad_unidad SEPARATOR '*') AS cantidad2
					   FROM (SELECT *
							FROM inv_asignaciones q
								  LEFT JOIN inv_unidades u ON q.unidad_id = u.id_unidad
										 ORDER BY u.unidad DESC) w GROUP BY w.producto_id ) z ON p.id_producto = z.producto_id")->fetch();
} else {
    $productos = null;
}
// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene la fecha de hoy
$hoy = date('Y-m-d');

// Obtiene los clientes
//$clientes = $db->select('nombre_cliente, nit_ci, count(nombre_cliente) as nro_visitas, sum(monto_total) as total_ventas')->from('inv_egresos')->group_by('nombre_cliente, nit_ci')->order_by('nombre_cliente asc, nit_ci asc')->fetch();
$clientes = $db->query("select DISTINCT a.nombre_cliente, a.nit_ci from inv_egresos a LEFT JOIN inv_clientes b ON a.nit_ci = b.nit UNION
select DISTINCT b.cliente as nombre_cliente, b.nit as nit_ci from inv_egresos a RIGHT JOIN inv_clientes b ON a.nit_ci = b.nit
ORDER BY nombre_cliente asc, nit_ci asc")->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_mostrar = in_array('mostrar', $permisos);

// Obtiene el modelo unidades
$unidades = $db->from('inv_unidades')->order_by('unidad')->fetch();

// Obtiene el modelo categorias
$categorias = $db->from('inv_categorias')->order_by('categoria')->fetch();

?>
<?php require_once show_template('header-advanced'); ?>
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
        .position-left-bottom {
            bottom: 0;
            left: 0;
            position: fixed;
            z-index: 1030;
        }
        .margin-all {
            margin: 15px;
        }
        .display-table {
            display: table;
        }
        .display-cell {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
        }
        .btn-circle {
            border-radius: 50%;
            height: 75px;
            width: 75px;
        }
    </style>

    <div class="panel-heading">
        <h3 class="panel-title">
            <span class="glyphicon glyphicon-option-vertical"></span>
            <strong>Devoluciones</strong>
        </h3>
    </div>

    <div class="panel-body">
        <div class="row">
            <div class="col-sm-8 hidden-xs">
                <div class="text-label">Para volver al listado hacer clic en el siguiente botón: </div>
            </div>
            <div class="col-xs-12 col-sm-4 text-right">
                <a href="?/notas/mostrar" class="btn btn-primary"><span class="glyphicon glyphicon-arrow-left"></span><span class="hidden-xs"> Listar</span></a>
            </div>
        </div>
        <hr>
        <div class="row">
        <?php if ($almacen) { ?>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <span class="glyphicon glyphicon-list"></span>
                            <strong>Datos de la preventa</strong>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <?php if (isset($_SESSION[temporary])) { ?>
                            <div class="alert alert-<?= $_SESSION[temporary]['alert']; ?>">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <strong><?= $_SESSION[temporary]['title']; ?></strong>
                                <p><?= $_SESSION[temporary]['message']; ?></p>
                            </div>
                            <?php unset($_SESSION[temporary]); ?>
                        <?php } ?>
                        <form method="post" action="?/notas/guardar_devolucion" class="form-horizontal">
                            <div class="form-group">
                                <label for="nit_ci" class="col-sm-4 control-label">NIT / CI:</label>
                                <div class="col-sm-8">
                                    <input type="hidden" name="id_egreso" value="<?= $egreso['id_egreso']; ?>"/>
                                    <input type="hidden" name="id_cliente" value="<?= $egreso['cliente_id']; ?>"/>
                                    <input type="text" readonly value="<?= $egreso['nit_ci'] ?>" name="nit_ci" id="nit_ci" class="form-control text-uppercase" autocomplete="off" data-validation="required number">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="nombre_cliente" class="col-sm-4 control-label">Señor(es):</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly value="<?= $egreso['nombre_cliente'] ?>" name="nombre_cliente" id="nombre_cliente" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-+./&() " data-validation-length="max100">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="nor_factura" class="col-sm-4 control-label">Nro factura:</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly value="<?= $egreso['nro_factura'] ?>" name="nro_factura" id="nor_factura" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-+./&() " data-validation-length="max100">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="motivo" class="col-sm-4 control-label">Motivo:</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly value="DEVOLUCION" name="motivo" id="motivo" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-+./&() " data-validation-length="max100">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="direccion" class="col-sm-4 control-label">Descripción:</label>
                                <div class="col-sm-8">
                                    <textarea name="descripcion" id="descripcion" class="form-control text-uppercase" rows="2" autocomplete="off" data-validation="letternumber" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-optional="true"></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-8 col-sm-offset-4">
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="tipo" value="Devolucion"> Devolución
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline mt-3">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="tipo" value="Reposicion" checked> Reposición
                                        </label>
                                    </div>
                                </div>
                            </div>


                            <div class="table-responsive margin-none">
                                <table id="ventas" class="table table-bordered table-condensed table-striped table-hover table-xs margin-none">
                                    <thead>
                                    <tr class="active">
                                        <th class="text-nowrap">#</th>
                                        <th class="text-nowrap">Código</th>
                                        <th class="text-nowrap" width="15%">Nombre</th>
                                        <th class="text-nowrap">Lote</th>
                                        <th class="text-nowrap">Vencimiento</th>
                                        <th class="text-nowrap" width="13">Cantidad</th>
                                        <th class="text-nowrap" width="15%">Unidad</th>
                                        <th class="text-nowrap">Precio</th>
                                        <th class="text-nowrap">Importe</th>
                                        <th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
                                    </tr>
                                    </thead>
                                    <tfoot>
                                    <tr class="active">
                                        <th class="text-nowrap text-right" colspan="8">Importe total <?= escape($moneda); ?></th>
                                        <th class="text-nowrap text-right" data-subtotal="">0.00</th>
                                        <th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
                                    </tr>
                                    </tfoot>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-12">
                                    <input type="text" name="almacen_id" value="<?= $almacen['id_almacen']; ?>" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="El almacén no esta definido">
                                    <input type="text" name="nro_registros" value="0" class="translate" tabindex="-1" data-ventas="" data-validation="required number" data-validation-allowing="range[1;20]" data-validation-error-msg="El número de productos a vender debe ser mayor a cero y menor a 20">
                                    <input type="text" name="monto_total" value="0" class="translate" tabindex="-1" data-total="" data-validation="required number" data-validation-allowing="range[0.01;1000000.00],float" data-validation-error-msg="El monto total de la venta debe ser mayor a cero y menor a 1000000.00">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-12 text-right">
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
                        <?php if ($permiso_mostrar) { ?>
                            <div class="row">
                                <div class="col-xs-12 text-right">
                                    <a href="?/manuales/mostrar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span> Ventas personales</span></a>
                                </div>
                            </div>
                            <hr>
                        <?php } ?>
                        <?php if ($productos) { ?>
                            <table id="productos" class="table table-bordered table-condensed table-striped table-hover table-xs">
                                <thead>
                                <tr class="active">
                                    <th class="text-nowrap">Imagen</th>
                                    <th class="text-nowrap">Código</th>
                                    <th class="text-nowrap">Nombre</th>
                                    <th class="text-nowrap">Lote</th>
                                    <th class="text-nowrap" width="10%">Vencimiento</th>
                                    <th class="text-nowrap">Tipo</th>
                                    <th class="text-nowrap">Stock</th>
                                    <th class="text-nowrap">Costo</th>
                                    <th class="text-nowrap"><i class="glyphicon glyphicon-cog"></i></th>
                                    <th class="hidden">Costo</th>
                                    <th class="hidden"><i class="glyphicon glyphicon-cog"></i></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($detalles as $nro => $detalle) {
                                    $lote = loteProducto($db, $detalle['id_producto'], $id_almacen);
                                    $otro_precio = $db->select('max(costo) as costo')->from('inv_ingresos_detalles')->where('lote =', $detalle['lote'])->where('vencimiento =', $detalle['vencimiento'])->where('producto_id =', $detalle['id_producto'])->fetch_first();
                                    // $otro_precio = $db->select('*')->from('inv_asignaciones a')->join('inv_unidades b','a.unidad_id=b.id_unidad')->where('a.producto_id',$detalle['id_producto'])->fetch();
                                    ?>
                                    <tr>
                                        <td class="text-nowrap text-middle">
                                            <img src="<?= ($detalle['imagen'] == '') ? imgs . '/image.jpg' : files . '/productos/' . $detalle['imagen']; ?>" width="75" height="75">
                                            <span class="hidden" data-idproducto="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['id_producto']); ?></span>
                                        </td>
                                        <td class="text-nowrap text-middle" data-codigo="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['codigo']); ?></td>
                                        <td class="text-middle">
                                            <span><?= escape($detalle['nombre']); ?></span>
                                            <span class="hidden" data-nombre="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['nombre_factura']); ?></span>
                                        </td>
                                        <td class="text-middle">
                                            <span><?= escape($detalle['lote']); ?></span>
                                            <span class="hidden" data-lote="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['lote']); ?></span>
                                        </td>
                                        <td class="text-nowrap text-middle">
                                            <span><?= escape('Vencimiento: '.$detalle['vencimiento']); ?></span>
                                            <span class="hidden" data-vencimiento="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['vencimiento']); ?></span>
                                        </td>
                                        <td class="text-nowrap text-middle"><?= escape($detalle['categoria']); ?></td>
                                        <td class="text-nowrap text-middle text-right" data-stock="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['cantidad']); ?></td>
                                        <td class="text-nowrap text-middle text-right" data-valor="<?= $detalle['id_detalle']; ?>">
                                            <?php echo '*(1)' . $detalle['unidad'].': ' . $otro_precio['costo']; ?>
                                            <!-- <?php $aux = '*1'; ?>
                                            *<?= escape('(1)'.$detalle['unidad'].': '); ?><b><?= escape($lote['costo']); ?></b>
                                            <?php foreach($otro_precio as $otro){ $aux = $aux.'*'.$otro['cantidad_unidad']?>
                                                <br/>*<?= escape('('.$otro['cantidad_unidad'].')'.$otro['unidad'].': '); ?><b><?= escape($otro['cantidad_unidad']*$lote['costo']); ?></b>
                                            <?php } ?> -->
                                        </td>
                                        <td class="text-nowrap text-middle">
                                            <button type="button" class="btn btn-xs btn-primary" data-vender="<?= $detalle['id_detalle']; ?>" data-toggle="tooltip" data-title="Vender"><span class="glyphicon glyphicon-share-alt"></span></button>
                                            <!-- <button type="button" class="btn btn-xs btn-success" data-actualizar="<?= $detalle['id_detalle']; ?>" data-toggle="tooltip" data-title="Actualizar stock y precio del producto"><span class="glyphicon glyphicon-refresh"></span></button> -->
                                        </td>
                                        <td class="hidden" data-cant="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['cantidad']); ?></td>
                                        <td class="hidden" data-stock2="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['cantidad']); ?></td>
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
        <?php } else { ?>
            <div class="col-xs-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="glyphicon glyphicon-home"></i> Ventas manuales</h3>
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-danger">
                            <strong>Advertencia!</strong>
                            <p>Usted no puede realizar esta operación, verifique que todo este en orden tomando en cuenta las siguientes sugerencias:</p>
                            <ul>
                                <li>No existe el almacén principal de la cual se puedan tomar los productos necesarios para la venta, debe fijar un almacén principal.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        </div>
    </div>
    
    <h2 class="btn-primary position-left-bottom display-table btn-circle margin-all display-table" data-toggle="tooltip" data-title="Esto es una venta manual" data-placement="right"><i class="glyphicon glyphicon-edit display-cell"></i></h2>
    <script src="<?= js; ?>/jquery.form-validator.min.js"></script>
    <script src="<?= js; ?>/jquery.form-validator.es.js"></script>
    <script src="<?= js; ?>/jquery.dataTables.min.js"></script>
    <script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
    <script src="<?= js; ?>/selectize.min.js"></script>
    <script src="<?= js; ?>/bootstrap-notify.min.js"></script>
    <script>
    $(function () {
        calcular_total();
        var table;
        var $cliente = $('#cliente');
        var $nit_ci = $('#nit_ci');
        var $nombre_cliente = $('#nombre_cliente');

        $('[data-vender]').on('click', function () {
            adicionar_producto($.trim($(this).attr('data-vender')));
        });

        $('[data-actualizar]').on('click', function () {
            var id_producto = $.trim($(this).attr('data-actualizar'));

            $('#loader').fadeIn(100);

            $.ajax({
                type: 'post',
                dataType: 'json',
                url: '?/manuales/actualizar',
                data: {
                    id_producto: id_producto
                }
            }).done(function (producto) {
                if (producto) {
                    var precio = parseFloat(producto.precio).toFixed(2);
                    var stock = parseInt(producto.stock);
                    var cell;

                    cell = table.cell($('[data-valor=' + producto.id_producto + ']'));
                    cell.data(precio);
                    cell = table.cell($('[data-stock=' + producto.id_producto + ']'));
                    cell.data(stock);
                    table.draw();

                    var $producto = $('[data-producto=' + producto.id_producto + ']');
                    var $cantidad = $producto.find('[data-cantidad]');
                    var $precio = $producto.find('[data-precio]');

                    if ($producto.size()) {
                        $cantidad.attr('data-validation-allowing', 'range[1;' + stock + ']');
                        $cantidad.attr('data-validation-error-msg', 'Debe ser un número positivo entre 1 y ' + stock);
                        $precio.val(precio);
                        $precio.attr('data-precio', precio);
                        descontar_precio(producto.id_producto);
                    }

                    $.notify({
                        title: '<strong>Actualización satisfactoria!</strong>',
                        message: '<div>El stock y el precio del producto se actualizaron correctamente.</div>'
                    }, {
                        type: 'success'
                    });
                } else {
                    $.notify({
                        title: '<strong>Advertencia!</strong>',
                        message: '<div>Ocurrió un problema, no existe almacén principal.</div>'
                    }, {
                        type: 'danger'
                    });
                }
            }).fail(function () {
                $.notify({
                    title: '<strong>Advertencia!</strong>',
                    message: '<div>Ocurrió un problema y no se pudo actualizar el stock ni el precio del producto.</div>'
                }, {
                    type: 'danger'
                });
            }).always(function () {
                $('#loader').fadeOut(100);
            });
        });

        table = $('#productos').DataTable({
            info: false,
            lengthMenu: [[25, 50, 100, 500, -1], [25, 50, 100, 500, 'Todos']],
            order: []
        });

        $('#productos_wrapper .dataTables_paginate').parent().attr('class', 'col-sm-12 text-right');

        $cliente.selectize({
            persist: false,
            createOnBlur: true,
            create: true,
            onInitialize: function () {
                $cliente.css({
                    display: 'block',
                    left: '-10000px',
                    opacity: '0',
                    position: 'absolute',
                    top: '-10000px'
                });
            },
            onChange: function () {
                $cliente.trigger('blur');
            },
            onBlur: function () {
                $cliente.trigger('blur');
            }
        }).on('change', function (e) {
            var valor = $(this).val();
            valor = valor.split('|');
            $(this)[0].selectize.clear();
            if (valor.length != 1) {
                $nit_ci.prop('readonly', true);
                $nombre_cliente.prop('readonly', true);
                $nit_ci.val(valor[0]);
                $nombre_cliente.val(valor[1]);
            } else {
                $nit_ci.prop('readonly', false);
                $nombre_cliente.prop('readonly', false);
                if (es_nit(valor[0])) {
                    $nit_ci.val(valor[0]);
                    $nombre_cliente.val('').focus();
                } else {
                    $nombre_cliente.val(valor[0]);
                    $nit_ci.val('').focus();
                }
            }
        });

        $.validate({
            modules: 'basic'
        });

        $('form:first').on('reset', function () {
            $('#ventas tbody').empty();
            $nit_ci.prop('readonly', false);
            $nombre_cliente.prop('readonly', false);
            calcular_total();
        });
    });

    function es_nit(texto) {
        var numeros = '0123456789';
        for(i = 0; i < texto.length; i++){
            if (numeros.indexOf(texto.charAt(i), 0) != -1){
                return true;
            }
        }
        return false;
    }

    function asig(val){

    }
    function desp(elemento) {
        $(elemento).next('tr').toggle();
    }

    function adicionar_producto(id_producto) {
        var $ventas = $('#ventas tbody');
        var $producto = $ventas.find('[data-producto=' + id_producto + ']');
        var $cantidad = $producto.find('[data-cantidad]');
        var numero = $ventas.find('[data-producto]').size() + 1;
        var codigo = $.trim($('[data-codigo=' + id_producto + ']').text());
        var idproducto = $.trim($('[data-idproducto=' + id_producto + ']').text());
        var nombre = $.trim($('[data-nombre=' + id_producto + ']').text());
        var lote = $.trim($('[data-lote=' + id_producto + ']').text());
        var vencimiento = $.trim($('[data-vencimiento=' + id_producto + ']').text());
        var cantidad2 = $.trim($('[data-cant=' + id_producto + ']').text());
        var stock = $.trim($('[data-stock=' + id_producto + ']').text());
        var valor = $.trim($('[data-valor=' + id_producto + ']').text());
        var plantilla = '';
        var cantidad;
        var $modcant = $('#modcant');
        //console.log(aa);
        var posicion = valor.indexOf(':');
        var porciones = valor.split('*');

        cantidad2 = '1*' + cantidad2;
        z=1;
        if ($producto.size()) {
            cantidad = $.trim($cantidad.val());
            cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
            cantidad = (cantidad < 9999999) ? cantidad + 1: cantidad;
            $cantidad.val(cantidad).trigger('blur');
        } else {
            plantilla = '<tr class="active" data-producto="' + id_producto + '">' +
            '<td class="text-nowrap text-middle">' + numero + '</td>' +
            '<td class="text-nowrap text-middle"><input type="text" value="' + idproducto + '" name="productos[]" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="Debe ser número">' + codigo + '</td>' +
            '<td class="text-middle"><input type="text" value="' + nombre + '" name="nombres[]" class="translate" tabindex="-1" data-validation="required">' + nombre + '</td>' +
            '<td class="text-middle"><input type="text" value="' + lote + '" name="lotes[]" class="translate" tabindex="-1" data-validation="required">' + lote + '</td>' +
            '<td class="text-middle"><input type="text" value="' + vencimiento + '" name="vencimiento[]" class="translate" tabindex="-1" data-validation="required">' + vencimiento + '</td>' +
            '<td class="text-middle"><input type="text" value="1" name="cantidades[]" class="form-control input-xs text-right" maxlength="7" autocomplete="off" data-cantidad="" data-validation="required number" data-validation-allowing="range[1;' + stock + ']" data-validation-error-msg="Debe ser un número positivo entre 1 y ' + stock + '" onkeyup="calcular_importe(' + id_producto + ')"></td>';
            if(porciones.length>2){
                plantilla = plantilla+'<td class="text-middle"><select name="unidad[]" id="unidad[]" data-xxx="true" class="form-control input-xs" >';
                aparte = porciones[1].split(':');
                for(var ic=1;ic<porciones.length;ic++){
                    parte = porciones[ic].split(':');
                    parte2 = parte[0].split(')');
                    parte3 = parte2[0].split('(');
                    console.log(parte2);
                    plantilla = plantilla+'<option value="' +parte2[1]+ '" data-xyyz="' +stock+ '" data-yyy="' +parte[1]+ '" data-yyz="' +parte3[1]+ '" >' +parte2[1]+ '</option>';
                }
                plantilla = plantilla+'</select></td>'+
                '<td class="text-middle"><input type="text" value="' + aparte[1] + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off" data-precio="' + aparte[1] + '"  readonly  data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(' + id_producto + ')"></td>';
            }
            else{
                sincant = porciones[1].split(')');
                console.log(sincant);
                parte = sincant[1].split(':');
                plantilla = plantilla + '<td class="text-middle"><input type="text" value="' + parte[0] + '" name="unidad[]" class="form-control input-xs text-right" autocomplete="off" data-unidad="' + parte[0] + '" readonly data-validation-error-msg="Debe ser un número decimal positivo"></td>'+
                '<td class="text-middle"><input type="text" value="' + parte[1] + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off" data-precio="' + parte[1] + '"  data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(' + id_producto + ')"></td>';
            }
            plantilla = plantilla +'<td class="hidden text-middle"><input type="text" value="0" name="descuentos[]" class="form-control input-xs text-right" maxlength="2" autocomplete="off" data-descuento="0" data-validation="required number" data-validation-allowing="range[0;50]" data-validation-error-msg="Debe ser un número positivo entre 0 y 50" onkeyup="descontar_precio(' + id_producto + ')"></td>' +
            '<td class="text-nowrap text-middle text-right" data-importe="">0.00</td>' +
            '<td class="text-nowrap text-middle text-center">' +
            '<button type="button" class="btn btn-xs btn-danger" data-toggle="tooltip" data-title="Eliminar producto" tabindex="-1" onclick="eliminar_producto(' + id_producto + ')"><span class="glyphicon glyphicon-remove"></span></button>' +
            '</td>' +
            '</tr>';

            $ventas.append(plantilla);

            $ventas.find('[data-cantidad], [data-precio], [data-descuento]').on('click', function () {
                $(this).select();
            });

            $ventas.find('[data-xxx]').on('change', function () {
                var v = $(this).find('option:selected').attr('data-yyy');

                var st = $(this).find('option:selected').attr('data-xyyz');

                $(this).parent().parent().find('[data-precio]').val(v);
                $(this).parent().parent().find('[data-precio]').attr(v);
                $(this).parent().parent().find('[data-precio]').attr(v);
                var z = $(this).find('option:selected').attr('data-yyz');
                var x = $.trim($('[data-stock2=' + id_producto + ']').text());
                var ze = Math.trunc(x / z);
                var zt = Math.trunc(st / z);
                $.trim($('[data-stock=' + id_producto + ']').text(ze));
                $(this).parent().parent().find('[data-cantidad]').attr('data-validation-allowing','range[1;' + zt + ']');
                $(this).parent().parent().find('[data-cantidad]').attr('data-validation-error-msg','Debe ser un número positivo entre 1 y ' + zt );
                //console.log($(this).parent().parent().find('[data-cantidad]').attr('data-validation-allowing'));
                calcular_importe(id_producto);
            });

            $ventas.find('[title]').tooltip({
                container: 'body',
                trigger: 'hover'
            });

            $.validate({
                form: '#formulario',
                modules: 'basic',
                onSuccess: function () {
                    guardar_factura();
                }
            });
        }

        calcular_importe(id_producto);
    }

    function eliminar_producto(id_producto) {
        bootbox.confirm('Está seguro que desea eliminar el producto?', function (result) {
            if(result){
                $('[data-producto=' + id_producto + ']').remove();
                renumerar_productos();
                calcular_total();
            }
        });
    }

    function renumerar_productos() {
        var $ventas = $('#ventas tbody');
        var $productos = $ventas.find('[data-producto]');
        $productos.each(function (i) {
            $(this).find('td:first').text(i + 1);
        });
    }

    function descontar_precio(id_producto) {
        var $producto = $('[data-producto=' + id_producto + ']');
        var $precio = $producto.find('[data-precio]').val();
        console.log($precio);
        var $descuento = $producto.find('[data-descuento]');
        var precio, descuento;

        precio = $.trim($precio);
        precio = ($.isNumeric(precio)) ? parseFloat(precio) : 0;
        descuento = $.trim($descuento.val());
        descuento = ($.isNumeric(descuento)) ? parseInt(descuento) : 0;
        precio = precio - (precio * descuento / 100);
        $producto.find('[data-precio]').val(precio.toFixed(2));

        calcular_importe(id_producto);
    }

    function calcular_importe(id_producto) {
        var $producto = $('[data-producto=' + id_producto + ']');
        var $cantidad = $producto.find('[data-cantidad]');
        var $precio = $producto.find('[data-precio]');
        var $descuento = $producto.find('[data-descuento]');
        var $importe = $producto.find('[data-importe]');
        var cantidad, precio, importe, fijo;

        fijo = $descuento.attr('data-descuento');
        fijo = ($.isNumeric(fijo)) ? parseInt(fijo) : 0;
        cantidad = $.trim($cantidad.val());
        cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
        precio = $.trim($precio.val());
        precio = ($.isNumeric(precio)) ? parseFloat(precio) : 0.00;
        descuento = $.trim($descuento.val());
        descuento = ($.isNumeric(descuento)) ? parseInt(descuento) : 0;
        importe = cantidad * precio;
        importe = importe.toFixed(2);
        $importe.text(importe);

        calcular_total();
    }

    function calcular_total() {
        var $ventas = $('#ventas tbody');
        var $total = $('[data-subtotal]:first');
        var $importes = $ventas.find('[data-importe]');
        var importe, total = 0;

        $importes.each(function (i) {
            importe = $.trim($(this).text());
            importe = parseFloat(importe);
            total = total + importe;
        });

        $total.text(total.toFixed(2));
        $('[data-ventas]:first').val($importes.size()).trigger('blur');
        $('[data-total]:first').val(total.toFixed(3)).trigger('blur');
    }
    </script>
<?php require_once show_template('footer-advanced'); ?>