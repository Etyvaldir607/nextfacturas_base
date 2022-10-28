<?php
$id_proforma = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el producto
$egreso = $db->select('*')->from('inv_proformas')->where('id_proforma',$id_proforma)->fetch_first();
$detalles = $db->query("SELECT a.*, b.*, a.unidad_id AS unidad_det, GROUP_CONCAT(c.unidad_id, '*',d.unidad, '*', c.otro_precio SEPARATOR '|') AS prec
	FROM inv_proformas_detalles a
	LEFT JOIN inv_productos b ON a.producto_id = b.id_producto
	LEFT JOIN inv_asignaciones c ON b.id_producto = c.producto_id
	LEFT JOIN inv_unidades d ON c.unidad_id = id_unidad WHERE a.proforma_id = '$id_proforma' GROUP BY a.id_detalle")->fetch();

// Obtiene el almacen principal
$almacen = $db->from('inv_almacenes')->fetch_first();
//echo json_encode($almacen);
//exit();
$id_almacen = ($almacen) ? $almacen['id_almacen'] : 0;
$prioridades = $db->select('*')->from('inv_prioridades_ventas')->fetch();
// Verifica si existe el almacen
if ($id_almacen != 0) {
    // Obtiene los productos
    $productos = $db->query("SELECT p.id_producto, p.asignacion_rol, p.descuento ,p.promocion,
                            z.id_asignacion, z.unidad_id, z.unidade, z.cantidad2,
                            p.descripcion,p.imagen,p.codigo,p.nombre_factura as nombre,p.nombre_factura,p.cantidad_minima,p.precio_actual,
                            IFNULL(e.cantidad_ingresos, 0) AS cantidad_ingresos, IFNULL(s.cantidad_egresos, 0) AS cantidad_egresos,
                            u.unidad, u.sigla, c.categoria, e.vencimiento, e.lote,e.id_detalle, '' as id_detalle_productos
                            FROM inv_productos p
                            LEFT JOIN (
                                SELECT d.producto_id, SUM(d.cantidad) AS cantidad_ingresos, d.vencimiento, d.lote, d.id_detalle
                                FROM inv_ingresos_detalles d
                                LEFT JOIN inv_ingresos i ON i.id_ingreso = d.ingreso_id
                                WHERE i.almacen_id = '$id_almacen'
                                GROUP BY d.producto_id, d.vencimiento, d.lote
                            ) AS e ON e.producto_id = p.id_producto
                            LEFT JOIN (
                                SELECT d.producto_id, SUM(d.cantidad) AS cantidad_egresos, d.lote
                                FROM inv_egresos_detalles d
                                LEFT JOIN inv_egresos e ON e.id_egreso = d.egreso_id
                                WHERE e.almacen_id = '$id_almacen'
                                GROUP BY d.producto_id,d.vencimiento, d.lote
                            ) AS s ON s.producto_id = p.id_producto AND s.lote = e.lote

                            LEFT JOIN inv_unidades u ON u.id_unidad = p.unidad_id
                        LEFT JOIN inv_categorias c ON c.id_categoria = p.categoria_id
                        LEFT JOIN (
                        SELECT w.producto_id,
                            GROUP_CONCAT(w.id_asignacion SEPARATOR '|') AS id_asignacion,
                            GROUP_CONCAT(w.unidad_id SEPARATOR '|') AS unidad_id,
                            GROUP_CONCAT(w.cantidad_unidad,')',w.unidad,':',w.otro_precio SEPARATOR '&') AS unidade,
                            GROUP_CONCAT(w.cantidad_unidad SEPARATOR '*') AS cantidad2
                        FROM (
                            SELECT q.*,u.*
                            FROM inv_asignaciones q
                            LEFT JOIN inv_unidades u ON q.unidad_id = u.id_unidad
                            ORDER BY u.unidad DESC
                        ) w
                        GROUP BY w.producto_id
                        ) z ON p.id_producto = z.producto_id")->fetch();
} else {
    $productos = null;
}
// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene la fecha de hoy
$hoy = date('Y-m-d');

// Obtiene los clientes
//$clientes = $db->select('nombre_cliente, nit_ci, count(nombre_cliente) as nro_visitas, sum(monto_total) as total_ventas')->from('inv_proformas')->group_by('nombre_cliente, nit_ci')->order_by('nombre_cliente asc, nit_ci asc')->fetch();
$clientes = $db->query("SELECT DISTINCT a.nombre_cliente, a.nit_ci from inv_proformas a LEFT JOIN inv_clientes b ON a.nit_ci = b.nit UNION
            select DISTINCT b.cliente as nombre_cliente, b.nit as nit_ci from inv_proformas a RIGHT JOIN inv_clientes b ON a.nit_ci = b.nit
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

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <span class="glyphicon glyphicon-option-vertical"></span>
                        <strong>Proformas</strong>
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="col-sm-8 hidden-xs">

                    </div>
                    <div class="col-sm-4 hidden-xs  text-right">
                        <div class="form-check form-check-inline">
                            <label class="form-check-label" for="inlineCheckbox1">Busqueda de Productos</label>
                            <input class="form-check-input" type="checkbox" id="inlineCheckbox1" onchange='sidenav()' checked>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="row" id='ContenedorF'>
    <?php if ($almacen) { ?>
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <span class="glyphicon glyphicon-list"></span>
                        <strong>Datos de la proforma</strong>
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
                    <form method="post" action="?/operaciones/proformas_guardar" class="form-horizontal">
                        <div class="form-group">
                            <label for="cliente" class="col-sm-4 control-label">Buscar:</label>
                            <div class="col-sm-8">
                                <select name="cliente" id="cliente" class="form-control text-uppercase" data-validation="letternumber" data-validation-allowing="-+./&() " data-validation-optional="true">
                                    <option value="">Buscar</option>
                                    <?php foreach ($clientes as $cliente) { ?>
                                        <option value="<?= escape($cliente['nit_ci']) . '|' . escape($cliente['nombre_cliente']); ?>"><?= escape($cliente['nit_ci']) . ' &mdash; ' . escape($cliente['nombre_cliente']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nit_ci" class="col-sm-4 control-label">NIT / CI:</label>
                            <div class="col-sm-8">
                                <input type="hidden" name="id_proforma" value="<?= $egreso['id_proforma']; ?>"/>
                                <input type="text" readonly value="<?= $egreso['nit_ci'] ?>" name="nit_ci" id="nit_ci" class="form-control text-uppercase" autocomplete="off" data-validation="required number">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nombre_cliente" class="col-sm-4 control-label">Señor(es):</label>
                            <div class="col-sm-8">
                                <input type="text" readonly value="<?= $egreso['nombre_cliente'] ?>" name="nombre_cliente" id="nombre_cliente" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-+./&() " data-validation-length="max100">
                            </div>
                        </div>
                        <div class="form-group hidden">
                            <label for="adelanto" class="col-md-4 control-label">Adelanto:</label>
                            <div class="col-md-8">
                                <input type="hidden" value="0" name="adelanto" id="adelanto" class="form-control" data-validation="required number" data-validation-allowing="float">
                            </div>
                        </div>
                        <div class="form-group hidden">
                            <label for="telefono_cliente" class="col-sm-4 control-label">Teléfono:</label>
                            <div class="col-sm-8">
                                <input type="text" value="0" name="telefono_cliente" id="telefono_cliente" class="form-control text-uppercase" autocomplete="off" data-validation="required" data-validation-length="max100">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="atencion" class="col-sm-4 control-label">Ubicación:</label>
                            <div class="col-sm-8">
                                <input type="text" value="<?= $egreso['coordenadas'] ?>" name="atencion" id="atencion" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-length="max100">
                            </div>
                        </div>
                        <div class="form-group hidden">
                            <label for="direccion" class="col-sm-4 control-label">Dirección:</label>
                            <div class="col-sm-8">
                                <textarea name="direccion" id="direccion" class="form-control text-uppercase" rows="2" autocomplete="off" data-validation="letternumber" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-optional="true"></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="observacion" class="col-sm-4 control-label">Observación:</label>
                            <div class="col-sm-8">
                                <select name="observacion" id="observacion" class="form-control text-uppercase" data-validation="letternumber" data-validation-allowing="-+./&() " data-validation-optional="true">
                                    <?php foreach ($prioridades as $prioridad) {
                                        if($egreso['observacion']==$prioridad['prioridad']){?>
                                            <option value="<?= escape($prioridad['prioridad']); ?>" selected><?= escape($prioridad['prioridad']); ?></option>
                                    <?php }else{?>
                                            <option value="<?= escape($prioridad['prioridad']); ?>"><?= escape($prioridad['prioridad']); ?></option>
                                    <?php } }?>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive margin-none">
                            <table id="ventas" class="table table-bordered table-condensed table-striped table-hover table-xs margin-none">
                                <thead>
                                <tr class="active">
                                    <th class="text-nowrap">#</th>
                                    <th class="text-nowrap">Código</th>
                                    <th class="text-nowrap">Nombre</th>
                                    <th class="text-nowrap">Cantidad</th>
                                    <th class="text-nowrap">Unidad</th>
                                    <th class="text-nowrap">Precio</th>
                                    <!-- <th class="text-nowrap">Descuento</th> -->
                                    <th class="text-nowrap">Importe</th>
                                    <th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
                                </tr>
                                </thead>
                                <tfoot>
                                <tr class="active">
                                    <th class="text-nowrap text-right" colspan="6">Importe total <?= escape($moneda); ?></th>
                                    <th class="text-nowrap text-right" data-subtotal="">0.00</th>
                                    <th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
                                </tr>
                                </tfoot>
                                <tbody>
                                <?php foreach($detalles as $key => $detalle){?>
                                    <tr class="active" data-producto="<?= $detalle['id_producto'] .'_'. $detalle['lote']; ?>">
                                        <td class="text-nowrap text-middle"><b><?= $key +1 ?></b></td>
                                        <td class="text-nowrap text-middle"><input type="text" value="<?= $detalle['id_producto']; ?>" name="productos[]" class="translate input-xs" tabindex="-1" data-validation="required number" data-validation-error-msg="Debe ser número"><?= $detalle['codigo'] ?></td>
                                        <td class="text-middle"><?= $detalle['nombre_factura']?><br>Lote: <?= $detalle['lote']?><br>Venc: <?= $detalle['vencimiento']?>
                                            <input type="hidden" value="<?= $detalle['nombre_factura']?>" name="nombres[]" class="form-control input-xs" data-validation="required">
                                            <input type="hidden" value="Lote: <?= $detalle['lote']?>" name="lote[]" class="form-control input-xs" data-validation="required">
                                            <input type="hidden" value="Venc: <?= $detalle['vencimiento']?>" name="vencimiento[]" class="form-control input-xs" data-validation="required">
                                        </td>
                                        <td class="text-middle"><input type="text" value="<?= $detalle['cantidad']/cantidad_unidad($db,$detalle['producto_id'],$detalle['unidad_det']) ?>" name="cantidades[]"  class="form-control text-right input-xs" maxlength="10" autocomplete="off" data-cantidad="" data-validation="required number" onkeyup="calcular_importe('<?= $detalle['id_producto'] .'_'. $detalle['lote']; ?>')"></td>

                                        <?php if(false){
                                    //                                        if($detalle['prec']){?>
                                            <td class="text-middle">
                                                <select name="unidad[]" id="unidad[]" data-xxx="true" class="form-control input-xs" onchange="agre()">
                                                    <?php $aparte = explode('|',$detalle['prec']);
                                                    foreach($aparte as $parte){
                                                        $part = explode('*',$parte);?>
                                                    <option value="<?= $part[1] ?>" data-xyyz="" data-yyy="<?= $part[2] ?>" data-yyz="<?= $part[0] ?>" ><?= $part[1] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </td>
                                        <?php }else{ ?>
                                            <td class="text-middle"><input type="text" value="<?= nombre_unidad($db,$detalle['unidad_det']); ?>" name="unidad[]" class="form-control input-xs text-right" autocomplete="off" data-unidad="<?= $detalle['unidad_det'] ?>" readonly data-validation-error-msg="Debe ser un número decimal positivo"></td>
                                        <?php } ?>
                                        <td class="text-middle"><input type="text" value="<?= $detalle['precio'] ?>" name="precios[]" class="form-control input-xs text-right" autocomplete="off" data-precio="<?= $detalle['precio'] ?>"  data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(<?= $detalle['producto_id'] ?>)"></td>
                                        <!-- <td class="text-middle"><input type="text" value="0" name="descuentos[]" class="form-control input-xs text-right" maxlength="2" autocomplete="off" data-descuento="0" data-validation="required number" data-validation-allowing="range[0;50]" data-validation-error-msg="Debe ser un número positivo entre 0 y 50" onkeyup="descontar_precio('<?= $detalle['id_producto'] .'_'. $detalle['lote']; ?>')"></td> -->
                                        <td class="text-nowrap text-middle text-right" data-importe="<?= ($detalle['cantidad']/cantidad_unidad($db,$detalle['producto_id'],$detalle['unidad_det']))*$detalle['precio'] ?>"><?= ($detalle['cantidad']/cantidad_unidad($db,$detalle['producto_id'],$detalle['unidad_det']))*$detalle['precio'] ?></td>
                                        <td class="text-nowrap text-middle text-center">
                                            <button type="button" class="btn btn-xs btn-danger" data-toggle="tooltip" data-title="Eliminar producto" tabindex="-1" onclick="eliminar_producto('<?= $detalle['id_producto'] .'_'. $detalle['lote']; ?>')"><span class="glyphicon glyphicon-trash"></span></button>
                                        </td>
                                    </tr>
                                <?php } ?>
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
							<th class="text-nowrap">Tipo</th>
							<th class="text-nowrap">Stock</th>
							<th class="text-nowrap">Precio</th>
							<th class="text-nowrap"><i class="glyphicon glyphicon-cog"></i></th>
							<th class="text-nowrap hidden"></th>
							<th class="text-nowrap hidden"></th>
						</tr>
						</thead>
						<tbody>
							<?php foreach ($productos as $nro => $producto) {
								$otro_precio=$db->query("SELECT *
								FROM inv_asignaciones a
								LEFT JOIN inv_unidades b ON a.unidad_id=b.id_unidad
								WHERE a.producto_id='{$producto['id_producto']}'")->fetch();
								// echo json_encode($otro_precio);
								if ((escape($producto['cantidad_ingresos'] - $producto['cantidad_egresos'])) > 0) {
							?>
								<tr data-busqueda="<?= $producto['id_producto'] .'_'. $producto['lote']; ?>">
									<td class="text-nowrap text-middle text-center width-none">
										<img src="<?= ($producto['imagen'] == '') ? imgs . '/image.jpg' : files . '/productos/' . $producto['imagen']; ?>" class="img-rounded cursor-pointer" data-toggle="modal" data-target="#modal_mostrar" data-modal-size="modal-md" data-modal-title="Imagen" width="75" height="75">
									</td>
									<td class="text-nowrap text-middle" data-codigo="<?= $producto['id_producto'] .'_'. $producto['lote']; ?>" data-codigo2=""><?= escape($producto['codigo']); ?></td>
									<td class="text-middle">
										<em><?= escape($producto['nombre']); ?></em>
										<br>
										<span class="vencimientoView" data-vencimientoview="">Venc: <?= escape($producto['vencimiento']); ?></span>
										<br>
										<span class="loteView" data-loteview="">Lote: <?= escape($producto['lote']); ?></span>
										<span class="vencimiento hidden" data-vencimiento="<?= $producto['id_producto'] .'_'. $producto['lote']; ?>">Venc: <?= escape($producto['vencimiento']); ?></span>
										<span class="lote hidden" data-lote="<?= $producto['id_producto'] .'_'. $producto['lote']; ?>">Lote: <?= escape($producto['lote']); ?></span>
										<span class="nombre_producto hidden" data-nombre="<?= $producto['id_producto'] .'_'. $producto['lote']; ?>"><?= escape($producto['nombre']); ?></span>
									</td>
									<td class="text-nowrap text-middle"><?= escape($producto['categoria']); ?></td>
									<td class="text-nowrap text-middle text-right" data-stock="<?= $producto['id_producto'] .'_'. $producto['lote']; ?>"><?= escape($producto['cantidad_ingresos'] - $producto['cantidad_egresos']); ?></td>
									<td class="text-middle text-right" data-valor="<?= $producto['id_producto'] .'_'. $producto['lote']; ?>" data-asignacion="" style="font-weight: bold; font-size: 0.8em;">
									<!-- *(1)CAJA:30.00 
									*(10)BOLSA:1000.00 -->
										<?php
											// if($producto['asignacion_rol']!=''):
											// 	$asignacion_rol=explode(',',$producto['asignacion_rol']);
											// 	if(in_array($id_rol,$asignacion_rol)):
													echo '*(1)' .escape($producto['unidad']).': <b>'.escape($producto['precio_actual']).'</b>';
											// 	endif;
											// else:
												// echo '*(1)'.escape($producto['unidad']).': <b>'.escape($producto['precio_actual']).'</b>';
											// endif;
											foreach ($otro_precio as $otro){
												// echo json_encode($otro['unidad']);
										?>
											<br />*(<?= escape($producto['cantidad2']) . ')' .escape($otro['unidad'] . ': '); ?><b><?= escape($otro['otro_precio']); ?></b>
										<?php } ?>
									</td>
									<td class="text-nowrap text-middle text-center width-none">
										<button type="button" class="btn btn-warning" data-vender="<?= $producto['id_producto'] .'_'. $producto['lote']; ?>" onclick="vender(this);" title="Vender">
										<span class="glyphicon glyphicon-shopping-cart"></span>
										<!--Vender-->
										</button>
										<button type="button" class="btn btn-default" data-actualizar="<?= $producto['id_producto'] .'_'. $producto['lote']; ?>" onclick="actualizar(this, <?= $id_almacen ?>);calcular_descuento()" title="Actualizar">
										<span class="glyphicon glyphicon-refresh"></span>
										<!--Actualizar-->
										</button>
									</td>
									<td class="hidden" data-cant="<?= $producto['id_producto'] .'_'. $producto['lote']; ?>"><?= escape($producto['cantidad2']); ?></td>
									<td class="hidden" data-stock2="<?= $producto['id_producto'] .'_'. $producto['lote']; ?>"><?= escape($producto['cantidad_ingresos'] - $producto['cantidad_egresos']); ?></td>
								</tr>



							<?php }  // fin if
						} // fin foreach ?>
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

        // $('[data-vender]').on('click', function () {
        //     adicionar_producto($.trim($(this).attr('data-vender')));
        // });

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

    function vender(elemento) {
        var $elemento = $(elemento),
            vender;
        vender = $elemento.attr('data-vender');
        adicionar_producto(vender);
    }

    function adicionar_producto(id_producto) {
        var $ventas = $('#ventas tbody');
        var $producto = $ventas.find('[data-producto=' + id_producto + ']');

        var $cantidad = $producto.find('[data-cantidad]');
        var numero = $ventas.find('[data-producto]').size() + 1;
        var codigo = $.trim($('[data-codigo=' + id_producto + ']').text());

        var nombre = $.trim($('[data-nombre=' + id_producto + ']').text());
        var lote = $.trim($('[data-lote=' + id_producto + ']').text());
        var vencimiento = $.trim($('[data-vencimiento=' + id_producto + ']').text());

        var cantidad2 = $.trim($('[data-cant=' + id_producto + ']').text());
        var stock = $.trim($('[data-stock=' + id_producto + ']').text());
        var valor = $.trim($('[data-valor=' + id_producto + ']').text());
        var asignaciones = $.trim($('[data-valor=' + id_producto + ']').attr("data-asignacion"));

        var plantilla = '';
        var cantidad;
        var $modcant = $('#modcant');

        var posicion = valor.indexOf(':');
        var porciones = valor.split('*');

        cantidad2 = '1*' + cantidad2;
        z = 1;
        var porci2 = cantidad2.split('*');
        //console.log(porci2);
        if ($producto.size()) {
            cantidad = $.trim($cantidad.val());
            cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
            cantidad = (cantidad < 9999999) ? cantidad + 1 : cantidad;
            $cantidad.val(cantidad).trigger('blur');
        } else {

            V_producto_simple=id_producto.split("_");
            id_producto_simple=V_producto_simple[0];

            plantilla = '<tr class="active" data-producto="' + id_producto + '">' +
                '<td class="text-nowrap text-middle"><b>' + numero + '</b></td>' +
                '<td class="text-nowrap text-middle"><input type="text" value="' + id_producto_simple + '" name="productos[]" class="translate input-xs" tabindex="-1" data-validation="required number" data-validation-error-msg="Debe ser número">' + codigo + '</td>' +
                '<td class="text-middle">'+nombre+'<br>'+lote+'<br>'+vencimiento+'';

            plantilla += '<input type="hidden" value=\'' + nombre + '\' name="nombres[]" class="form-control input-xs" data-validation="required">';
            plantilla += '<input type="hidden" value=\'' + lote + '\' name="lote[]" class="form-control input-xs" data-validation="required">';
            plantilla += '<input type="hidden" value=\'' + vencimiento + '\' name="vencimiento[]" class="form-control input-xs" data-validation="required">';
            plantilla += '</td>' +
                '<td class="text-middle"><input type="text" value="1" name="cantidades[]"  class="form-control text-right input-xs" maxlength="10" autocomplete="off" data-cantidad="" data-validation="required number" data-validation-allowing="range[1;' + stock + ']" data-validation-error-msg="Debe ser un número positivo entre 1 y ' + stock + '" onkeyup="calcular_importe(\'' + id_producto + '\')"></td>';

            //si tiene mas de una unidad
            if (porciones.length > 2) {
                plantilla = plantilla + '<td class="text-middle"><select name="unidad[]" id="unidad[]" data-unidad="" data-xxx="true" class="form-control input-xs" style="padding-left:0;" >';
                aparte = porciones[1].split(':');
                asignation = asignaciones.split('|');

                for (var ic = 1; ic < porciones.length; ic++) {
                    parte = porciones[ic].split(':');
                    oparte = parte[0].split(')');

                    plantilla = plantilla + '<option value="' + oparte[1] + '" data-pr="'+ id_producto +'" data-xyyz="' + stock + '" data-yyy="' + parte[1] + '" data-yyz="' + porci2[ic - 1] + '" data-asig="' + asignation[ic - 1] + '" >' + oparte[1] + ' ('+parte[1]+')</option>';
                }
                plantilla = plantilla + '</select></td>' +
                    '<td class="text-middle">' +

                    '<input type="text" value="' + $.trim(aparte[1]) + '" readonly name="precios[]" class="form-control input-xs text-right" autocomplete="off" data-precio="' + aparte[1] + '"   data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(\'' + id_producto + '\')">' +
                    '<input type="text" value="' + $.trim(aparte[1]) + '" readonly name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off" data-pre="' + aparte[1] + '"   data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(\'' + id_producto + '\')">' +

                    '<input type="hidden" value="' + asignation[0] + '" readonly name="asignaciones[]" class="form-control input-xs text-right" autocomplete="off" data-asignacion="' + asignation[0] + '"   data-validation-error-msg="Debe ser un número decimal positivo">' +

                    '</td>';
            } else {
                asignation=asignaciones.split('|');
                sincant = porciones[1].split(')');

                parte = sincant[1].split(':');
                plantilla = plantilla + '<td class="text-middle">' +
                    '<input type="text" value="' + parte[0] + '" data-xyyz="' + stock + '" name="unidad[]" class="form-control input-xs text-right" autocomplete="off" data-unidad="' + parte[0] + '" readonly data-validation-error-msg="Debe ser un número decimal positivo"></td>' +
                    '<td class="text-middle" data-xyyz="' + stock + '" >' +

                    '<input type="text" readonly value="' + parte[1] + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off"  data-precio="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(\'' + id_producto + '\')">' +
                    '<input type="text" readonly value="' + parte[1] + '" name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off"  data-pre="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(\'' + id_producto + '\')">' +

                    '<input type="hidden" value="' + asignation[0] + '" readonly name="asignaciones[]" class="form-control input-xs text-right" autocomplete="off" data-asignacion="' + asignation[0] + '"   data-validation-error-msg="Debe ser un número decimal positivo">' +
                    '</td>';
            }

            plantilla = plantilla + // '<td class="text-middle"><input type="text" value="0" name="descuentos[]" class="form-control input-xs text-right" maxlength="10" autocomplete="off" data-descuento="0" data-validation="required number" data-validation-allowing="float,range[-100.00;100.00],negative" data-validation-error-msg="Debe ser un número entre -100.00 y 100.00" onkeyup="descontar_precio(\'' + id_producto + '\')">' +
                '<td class="text-nowrap text-middle text-right" data-importe="">0.00</td>' +
                '<td class="text-nowrap text-middle text-center">' +
                '<button type="button" class="btn btn-xs btn-danger" tabindex="-1" onclick="eliminar_producto(\'' + id_producto + '\')"><span class="glyphicon glyphicon-trash"></span></button>' +
                '</td>' +
                '</tr>';

            $ventas.append(plantilla);

            $ventas.find('[data-cantidad], [data-precio], [data-descuento]').on('click', function() {
                $(this).select();
            });

            $ventas.find('[data-xxx]').on('change', function() {
                var v = $(this).find('option:selected').attr('data-yyy');
                var pr = $(this).find('option:selected').attr('data-pr');
                var st = $(this).find('option:selected').attr('data-xyyz');
                $(this).parent().parent().find('[data-precio]').val(v);
                $(this).parent().parent().find('[data-precio]').attr('data-precio',v);

                var z = $(this).find('option:selected').attr('data-yyz');
                var x = $.trim($('[data-stock2=' + pr + ']').text());
                var ze = Math.trunc(x / z);
                var zt = Math.trunc(st / z);
                $.trim($('[data-stock=' + pr + ']').text(ze));
                $(this).parent().parent().find('[data-cantidad]').attr('data-validation-allowing', 'range[1;' + zt + ']');
                $(this).parent().parent().find('[data-cantidad]').attr('data-validation-error-msg', 'Debe ser un número positivo entre 1 y ' + zt);
                calcular_importe(id_producto);
            });

            $ventas.find('[title]').tooltip({
                container: 'body',
                trigger: 'hover'
            });

            $.validate({
                form: '#formulario',
                modules: 'basic',
                onSuccess: function() {
                    guardar_nota();
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

    function sidenav(){
		let contenedor=document.getElementById('ContenedorF');
		if(contenedor.children[0].classList.contains('col-md-6')){
			contenedor.children[0].classList.remove('col-md-6');
			contenedor.children[0].classList.add('col-md-12');
			contenedor.children[1].classList.add('hidden');
		}
		else{
			contenedor.children[0].classList.remove('col-md-12');
			contenedor.children[0].classList.add('col-md-6');
			contenedor.children[1].classList.remove('hidden');
		}
	}
    </script>
<?php require_once show_template('footer-empty'); ?>