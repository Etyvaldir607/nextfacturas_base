<?php
    // Obtiene el id de la Nota de remision
    $id_nota = (sizeof($params) > 0) ? $params[0] : 0;

    // Obtiene el producto
    $egreso = $db->select('*')
                    ->from('inv_egresos')
                    ->where('id_egreso',$id_nota)
                    ->fetch_first();
    // echo json_encode($egreso); die();
    $detalles = $db->query("SELECT a.*, b.*,e.categoria, a.unidad_id AS unidad_det, d.unidad, SUM(a.cantidad)as cantidad
                            FROM inv_egresos_detalles a
                            LEFT JOIN inv_productos b ON a.producto_id = b.id_producto
                            LEFT JOIN inv_categorias e ON b.categoria_id = e.id_categoria
                            LEFT JOIN inv_unidades d ON b.unidad_id = d.id_unidad
                            WHERE a.egreso_id = '$id_nota'
                            GROUP BY a.vencimiento, a.lote, a.producto_id
                            ")->fetch();
                            
    // echo json_encode($detalles); die();
    // Obtiene el almacen principal
    // $almacen = $db->from('inv_almacenes')->fetch_first();
    $id_almacen = ($egreso['almacen_id']) ? $egreso['almacen_id'] : 0;

    //Obtiene los productos
    $productos = $db->query("SELECT p.id_producto, p.promocion, z.id_asignacion, z.unidad_id, z.unidade, z.cantidad2, p.descripcion, p.imagen, p.codigo, p.nombre, p.nombre_factura, p.cantidad_minima, p.precio_actual, IFNULL(e.cantidad_ingresos, 0) AS cantidad_ingresos, IFNULL(s.cantidad_egresos, 0) AS cantidad_egresos, u.unidad, u.sigla, c.categoria
                            FROM inv_productos p
                            LEFT JOIN (SELECT d.producto_id, SUM(d.cantidad) AS cantidad_ingresos
                                FROM inv_ingresos_detalles d
                                LEFT JOIN inv_ingresos i ON i.id_ingreso = d.ingreso_id
                                WHERE i.almacen_id = '$id_almacen' 
                                GROUP BY d.producto_id) AS e ON e.producto_id = p.id_producto
                            LEFT JOIN (SELECT d.producto_id, SUM(d.cantidad) AS cantidad_egresos
                                FROM inv_proformas_detalles d LEFT JOIN inv_proformas e ON e.id_proforma = d.proforma_id
                                WHERE e.almacen_id = '$id_almacen' 
                                GROUP BY d.producto_id) AS s ON s.producto_id = p.id_producto
                            LEFT JOIN inv_unidades u ON u.id_unidad = p.unidad_id LEFT JOIN inv_categorias c ON c.id_categoria = p.categoria_id
                            LEFT JOIN (SELECT w.producto_id, GROUP_CONCAT(w.id_asignacion SEPARATOR '|') AS id_asignacion, GROUP_CONCAT(w.unidad_id SEPARATOR '|') AS unidad_id, GROUP_CONCAT(w.cantidad_unidad,')',w.unidad,':',w.otro_precio SEPARATOR '&') AS unidade, GROUP_CONCAT(w.cantidad_unidad SEPARATOR '*') AS cantidad2
                            FROM (  SELECT *
                                    FROM inv_asignaciones q
                                    LEFT JOIN inv_unidades u ON q.unidad_id = u.id_unidad
                                    ORDER BY u.unidad DESC) w 
                                    GROUP BY w.producto_id ) z ON p.id_producto = z.producto_id
                            ")->fetch();

    // Obtiene la moneda oficial
    $moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
    $moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

    // Obtiene los clientes
    $clientes = $db->query("select DISTINCT a.nombre_cliente, a.nit_ci 
                            from inv_proformas a 
                            LEFT JOIN inv_clientes b ON a.nit_ci = b.nit 
                            
                            UNION
    
                            select DISTINCT b.cliente as nombre_cliente, b.nit as nit_ci 
                            from inv_proformas a 
                            RIGHT JOIN inv_clientes b ON a.nit_ci = b.nit
                            ORDER BY nombre_cliente asc, nit_ci asc
                            ")->fetch();
    
    // EGRESOS DEL CLIENTE
    $ids = array();
    $id_cliente = $egreso["cliente_id"];

    $compras = $db->query("SELECT e.id_egreso, e.fecha_egreso, e.hora_egreso, e.nro_factura, e.plan_de_pagos, ed.cantidad, 
                                  (ed.cantidad * ed.precio) as total, p.nombre_factura as producto, a.almacen, ed.lote, e.tipo, e.preventa
                            FROM inv_egresos e
                            LEFT JOIN inv_egresos_detalles ed ON ed.egreso_id = e.id_egreso
                            LEFT JOIN inv_productos p ON ed.producto_id = p.id_producto
                            LEFT JOIN inv_almacenes a ON e.almacen_id = a.id_almacen
                            WHERE e.cliente_id = '$id_cliente'
                            ORDER BY e.fecha_egreso DESC LIMIT 50")->fetch();
    foreach ($compras as $key => $compra) {
        if (in_array($compra['id_egreso'], $ids)) {
        } else {
            array_push($ids, $compra['id_egreso']);
        }
    }
    $pagos = $db->query("SELECT p.id_pago, p.movimiento_id, p.tipo, SUM(IF(d.estado = 1 , d.monto, 0)) as pagado, SUM(IF(d.estado = 0 , d.monto, 0)) as saldo, 
                                e.monto_total, e.descripcion as egreso, e.fecha_egreso, e.hora_egreso, e.nro_nota, id_egreso
                        FROM inv_egresos e
                        LEFT JOIN inv_pagos p ON p.movimiento_id = e.id_egreso
                        left JOIN inv_pagos_detalles d ON p.id_pago = d.pago_id 
                        WHERE p.tipo='Egreso' AND ((e.preventa IS NULL AND e.tipo!='preventa') OR e.preventa='habilitado')
                        AND p.movimiento_id IN (".implode(',', $ids).")
                        GROUP BY p.id_pago")->fetch();
                        
    // echo json_encode($pagos);

    // Obtiene los permisos
    $permisos = explode(',', permits);
    // Almacena los permisos en variables
    $permiso_mostrar = in_array('mostrar', $permisos);

?>

<?php require_once show_template('header-advanced'); ?>

    <div class="panel-heading">
        <h3 class="panel-title">
            <span class="glyphicon glyphicon-option-vertical"></span>
            <strong>Devoluciones</strong>
        </h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-8 hidden-xs">
                <div class="text-label">Para volver al listado hacer clic en el siguiente bot??n: </div>
            </div>
            <div class="col-xs-12 col-sm-4 text-right">
                <a href="?/notas/mostrar" class="btn btn-primary"><span class="glyphicon glyphicon-arrow-left"></span><span class="hidden-xs"> Listar</span></a>
            </div>
        </div>
        <hr>
        <div class="row">
            <?php if ($id_almacen) { ?>
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <span class="glyphicon glyphicon-list"></span>
                                <strong>Datos de la nota de venta</strong>
                            </h3>
                        </div>
                        <div class="panel-body">
                            <?php if (isset($_SESSION[temporary])) { ?>
                                <div class="alert alert-<?= ($_SESSION[temporary]['alert'])?$_SESSION[temporary]['alert']:$_SESSION[temporary]['type']; ?>">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <strong><?= $_SESSION[temporary]['title']; ?></strong>
                                    <p><?= ($_SESSION[temporary]['message'])?$_SESSION[temporary]['message']:$_SESSION[temporary]['content']; ?></p>
                                </div>
                                <?php unset($_SESSION[temporary]); ?>
                            <?php } ?>
                            <form method="post" action="?/notas/notas_devolucion_guardar" class="form-horizontal">
                                <div class="form-group">
                                    <label for="nit_ci" class="col-sm-4 control-label">NIT / CI:</label>
                                    <div class="col-sm-8">
                                        <input type="hidden" name="id_egreso" value="<?= $egreso['id_egreso']; ?>"/>
                                        <input class="hidden" name="id_cliente" value="<?= $egreso['cliente_id']; ?>"/>
                                        <input type="text" readonly value="<?= $egreso['nit_ci'] ?>" name="nit_ci" id="nit_ci" class="form-control text-uppercase" autocomplete="off" data-validation="required number">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="nombre_cliente" class="col-sm-4 control-label">Se??or(es):</label>
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
                                    <label for="direccion" class="col-sm-4 control-label">Descripci??n:</label>
                                    <div class="col-sm-8">
                                        <textarea name="descripcion" id="descripcion" class="form-control text-uppercase" rows="2" autocomplete="off" data-validation="letternumber" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-optional="true"></textarea>
                                    </div>
                                </div>

                                <div class="form-group hidden">
                                    <div class="col-sm-8 col-sm-offset-4">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="radio" name="tipo" value="Devolucion" checked> Devoluci??n
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline mt-3">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="radio" name="tipo" value="Reposicion" > Reposici??n
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive margin-none">
                                    <table id="ventas" class="table table-bordered table-condensed table-striped table-hover table-xs margin-none">
                                        <thead>
                                        <tr class="active">
                                            <th class="text-nowrap">#</th>
                                            <th class="text-nowrap">C??digo</th>
                                            <th class="text-nowrap">Nombre</th>
                                            <th class="text-nowrap">Lote</th>
                                            <th class="text-nowrap">Vencimiento</th>
                                            <th class="text-nowrap">Cantidad</th>
                                            <th class="text-nowrap">Unidad</th>
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
                                        <tbody class="text-sm">
                                        </tbody>
                                    </table>
                                </div>
                                <div class="form-group">
                                    <div class="col-xs-12">
                                        <input type="text" name="almacen_id" value="<?= $egreso['almacen_id']; ?>" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="El almac??n no esta definido">
                                        <input type="text" name="nro_registros" value="0" class="translate" tabindex="-1" data-ventas="" data-validation="required number" data-validation-allowing="range[1;20]" data-validation-error-msg="El n??mero de productos a vender debe ser mayor a cero y menor a 20">
                                        <input type="text" name="monto_total" value="0" class="translate" tabindex="-1" data-total="" data-validation="required number" data-validation-allowing="range[0.01;1000000.00],float" data-validation-error-msg="El monto total de la venta debe ser mayor a cero y menor a 1000000.00">
                                    </div>
                                </div>
                                <hr>
                                <div class="card card-info">
                                    <table class="table table-bordered table-hover table-responsive table-sm" id="tabla_deudas" style="max-width: 100% !important;">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Nro. Nota</th>
                                                <th>Monto total</th>
                                                <th>A cuenta</th>
                                                <th>Pendiente</th>
                                                <th>Tipo</th>
                                                <th>Seleccionar</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-sm">
                                            <?php 
                                            foreach ($pagos as $key => $pago) {
                                                if($pago['monto_total'] - $pago['pagado'] > 0){ ?>
                                                    <tr>
                                                        <td><?= date_decode($pago['fecha_egreso'], $_institution['formato']) . ' <br><small>' . $pago['hora_egreso'] ?></small></td>
                                                        <td><?= $pago['nro_nota'] ?></td>
                                                        <td class="text-right"><?= number_format(($pago['monto_total']), 2, ',', '.') ?></td>
                                                        <td class="text-right"><?= number_format(($pago['pagado']), 2, ',', '.') ?></td>
                                                        <td class="text-right danger"><?= number_format( ($pago['monto_total']-$pago['pagado']), 2, ',', '.') ?></td>
                                                        <td><?= ucwords(str_replace("Venta de productos con ", "", $pago['egreso'])) ?></td>
                                                        <td><label class="radio-inline"><input type="radio" name="para_pagar[]" id="para_pagar<?= $pago['id_egreso'] ?>" value="<?= $pago['id_egreso'] ?>">Pagar</label></td>
                                                        
                                                    </tr>
                                                <?php  
                                                }
                                            }   
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <hr>
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
                                <strong>B??squeda de productos</strong>
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
                                        <th class="text-nowrap">C??digo</th>
                                        <th class="text-nowrap">Nombre</th>
                                        <th class="text-nowrap">Lote</th>
                                        <th class="text-nowrap">Vencimiento</th>
                                        <th class="text-nowrap">Tipo</th>
                                        <th class="text-nowrap">Stock</th>
                                        <th class="text-nowrap">Costo</th>
                                        <th class="text-nowrap"><i class="glyphicon glyphicon-cog"></i></th>
                                        <th class="hidden">Costo</th>
                                        <th class="hidden"><i class="glyphicon glyphicon-cog"></i></th>
                                    </tr>
                                    </thead>
                                    <tbody class="text-sm">
                                    <?php foreach ($detalles as $nro => $detalle) {
                                        $lote = loteProducto($db, $detalle['id_producto'], $id_almacen);
                                        $otro_precio = $db->select('max(costo) as costo')
                                                          ->from('inv_ingresos_detalles')
                                                          ->where('lote =', $detalle['lote'])
                                                          ->where('vencimiento =', $detalle['vencimiento'])
                                                          ->where('producto_id =', $detalle['id_producto'])
                                                          ->fetch_first();
                                        ?>
                                        <tr>
                                            <td class="text-nowrap text-middle">
                                                <img src="<?= ($detalle['imagen'] == '') ? imgs . '/image.jpg' : files . '/productos/' . $detalle['imagen']; ?>" width="75" height="75">
                                                <span class="hidden" data-idproducto="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['id_producto']); ?></span>
                                            </td>
                                            <td class="text-nowrap text-middle" data-codigo="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['codigo']); ?></td>
                                            <td class="text-middle">
                                                <span><?= escape($detalle['nombre_factura']); ?></span>
                                                <span class="hidden" data-nombre="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['nombre_factura']); ?></span>
                                            </td>
                                            <td class="text-middle">
                                                <span><?= escape($detalle['lote']); ?></span>
                                                <span class="hidden" data-lote="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['lote']); ?></span>
                                            </td>
                                            <td class="text-nowrap text-middle">
                                                <span><?= escape($detalle['vencimiento']); ?></span>
                                                <span class="hidden" data-vencimiento="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['vencimiento']); ?></span>
                                            </td>
                                            <td class="text-nowrap text-middle"><?= escape($detalle['categoria']); ?></td>
                                            <td class="text-nowrap text-middle text-right" data-stock="<?= $detalle['id_detalle']; ?>"><?= escape($detalle['cantidad']); ?></td>
                                            <td class="text-nowrap text-middle text-right" data-valor="<?= $detalle['id_detalle']; ?>">
                                                <?php echo '*(1)' . $detalle['unidad'].': ' . $detalle['precio']; ?>
                                                <!-- <?php $aux = '*1'; ?>
                                                *<?= escape('(1)'.$detalle['unidad'].': '); ?><b><?= escape($detalle['precio']); ?></b>
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
                            <h3 class="panel-title"><i class="glyphicon glyphicon-home"></i> Devolucion notas de venta</h3>
                        </div>
                        <div class="panel-body">
                            <div class="alert alert-danger">
                                <strong>Advertencia!</strong>
                                <p>Usted no puede realizar esta operaci??n, verifique que todo este en orden tomando en cuenta las siguientes sugerencias:</p>
                                <ul>
                                    <li>No existe el almac??n seleccionado de la cual se puedan tomar los productos necesarios para la venta, debe fijar un almac??n.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>


<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/selectize.min.js"></script>
<script src="<?= js; ?>/bootstrap-notify.min.js"></script>
<script>
    $(function () {
        table = $('#productos').DataTable({
            info: false,
            lengthMenu: [[25, 50, 100, 500, -1], [25, 50, 100, 500, 'Todos']],
            order: []
        });

        calcular_total();
        var table;
        var $cliente = $('#cliente');
        var $nit_ci = $('#nit_ci');
        var $nombre_cliente = $('#nombre_cliente');

        $('[data-vender]').on('click', function () {
            adicionar_producto($.trim($(this).attr('data-vender')));
        });

        $.validate({
            modules: 'basic'
        });

    });

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
            '<td class="text-nowrap text-middle"><input type="text" value="' + idproducto + '" name="productos[]" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="Debe ser n??mero">' + codigo + '</td>' +
            '<td class="text-middle"><input type="text" value="' + nombre + '" name="nombres[]" class="translate" tabindex="-1" data-validation="required">' + nombre + '</td>' +
            '<td class="text-middle"><input type="text" value="' + lote + '" name="lotes[]" class="translate" tabindex="-1" data-validation="required">' + lote + '</td>' +
            '<td class="text-middle"><input type="text" value="' + vencimiento + '" name="vencimiento[]" class="translate" tabindex="-1" data-validation="required">' + vencimiento + '</td>' +
            '<td class="text-middle"><input type="text" value="1" name="cantidades[]" class="form-control input-xs text-right" maxlength="7" autocomplete="off" data-cantidad="" data-validation="required number" data-validation-allowing="range[1;' + stock + ']" data-validation-error-msg="Debe ser un n??mero positivo entre 1 y ' + stock + '" onkeyup="calcular_importe(' + id_producto + ')"></td>';
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
                '<td class="text-middle"><input type="text" value="' + aparte[1] + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off" data-precio="' + aparte[1] + '"  readonly  data-validation-error-msg="Debe ser un n??mero decimal positivo" onkeyup="calcular_importe(' + id_producto + ')"></td>';
            }
            else{
                sincant = porciones[1].split(')');
                console.log(sincant);
                parte = sincant[1].split(':');
                plantilla = plantilla + '<td class="text-middle"><input type="text" value="' + parte[0] + '" name="unidad[]" class="form-control input-xs text-right" autocomplete="off" data-unidad="' + parte[0] + '" readonly data-validation-error-msg="Debe ser un n??mero decimal positivo"></td>'+
                '<td class="text-middle"><input type="text" value="' + parte[1] + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off" data-precio="' + parte[1] + '"  data-validation-error-msg="Debe ser un n??mero decimal positivo" onkeyup="calcular_importe(' + id_producto + ')"></td>';
            }
            plantilla = plantilla +'<td class="hidden text-middle"><input type="text" value="0" name="descuentos[]" class="form-control input-xs text-right" maxlength="2" autocomplete="off" data-descuento="0" data-validation="required number" data-validation-allowing="range[0;50]" data-validation-error-msg="Debe ser un n??mero positivo entre 0 y 50" onkeyup="descontar_precio(' + id_producto + ')"></td>' +
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
                $(this).parent().parent().find('[data-cantidad]').attr('data-validation-error-msg','Debe ser un n??mero positivo entre 1 y ' + zt );
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

    function eliminar_producto(id_producto) {
        bootbox.confirm('Est?? seguro que desea eliminar el producto?', function (result) {
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
</script>
<?php require_once show_template('footer-advanced'); ?>