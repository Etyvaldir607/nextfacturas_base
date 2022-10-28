<?php
$id_egreso = (sizeof($params) > 0) ? $params[0] : 0;
$entrega = (sizeof($params) > 1) ? $params[1] : 0;

// Obtiene el producto
$egreso = $db->select('*')
             ->from('inv_egresos')
             ->join('inv_clientes','id_cliente=cliente_id','inner')
             ->where('id_egreso',$id_egreso)
             ->fetch_first();
             
$detalles = $db->query("SELECT a.*, SUM(a.cantidad)as cantidad_producto, b.*, a.unidad_id AS unidad_det, GROUP_CONCAT(c.unidad_id, '*',d.unidad, '*', c.otro_precio SEPARATOR '|') AS prec, e.categoria
                    	FROM inv_egresos_detalles a
                    	LEFT JOIN inv_productos b ON a.producto_id = b.id_producto
                    	LEFT JOIN inv_asignaciones c ON b.id_producto = c.producto_id
                    	LEFT JOIN inv_unidades d ON c.unidad_id = id_unidad
                        LEFT JOIN inv_categorias e ON e.id_categoria = b.categoria_id
                        WHERE a.egreso_id = '$id_egreso' 
                        GROUP BY a.producto_id, lote, vencimiento, a.precio
                    ")->fetch();
                    //    GROUP BY a.id_detalle
                    
// echo json_encode($detalles); die();
$pagos = $db->from('inv_pagos')
            ->where('movimiento_id', $egreso['id_egreso'])
            ->where('tipo', 'Egreso')
            ->fetch_first();
            
$detpagos =  $db->from('inv_pagos_detalles')
                ->where('pago_id', $pagos['id_pago'])
                ->order_by('id_pago_detalle', 'asc')
                ->fetch();
                
// echo json_encode($detpagos); DIE();

$id_almacen = $egreso["almacen_id"];
$prioridades = $db->select('*')->from('inv_prioridades_ventas')->fetch();

// Verifica si existe el almacen
if ($id_almacen != 0) {
    // Obtiene los productos
    //    $productos = $db->query("select p.id_producto, p.descripcion, p.imagen, p.codigo, p.nombre, p.nombre_factura, p.cantidad_minima, p.precio_actual, p.unidad_id, ifnull(e.cantidad_ingresos, 0) as cantidad_ingresos, ifnull(s.cantidad_egresos, 0) as cantidad_egresos, IFNULL(e.costo, 0) AS costo_ingresos, u.unidad, u.sigla, c.categoria from inv_productos p left join (select d.producto_id, d.costo, sum(d.cantidad) as cantidad_ingresos from inv_ingresos_detalles d left join inv_ingresos i on i.id_ingreso = d.ingreso_id where i.almacen_id = $id_almacen  group by d.producto_id ) as e on e.producto_id = p.id_producto left join (select d.producto_id, sum(d.cantidad) as cantidad_egresos from inv_egresos_detalles d left join inv_egresos e on e.id_egreso = d.egreso_id where e.almacen_id = $id_almacen group by d.producto_id ) as s on s.producto_id = p.id_producto left join inv_unidades u on u.id_unidad = p.unidad_id left join inv_categorias c on c.id_categoria = p.categoria_id where p.grupo = ''")->fetch();
    $productos = $db->query("SELECT p.id_producto, p.asignacion_rol, p.descuento ,p.promocion,
                                    z.id_asignacion, z.unidad_id, z.unidade, z.cantidad2,
                                    p.descripcion,p.imagen,p.codigo,p.nombre_factura as nombre,p.nombre_factura,p.cantidad_minima,p.precio_actual,
                                    IFNULL(dx.cantidad, 0) AS cantidad,
                                    u.unidad, u.sigla, c.categoria, dx.vencimiento, dx.lote,dx.id_detalle, '' as id_detalle_productos, 
                                    SUM(dx.cantidad)as cantidad_producto
                            
                            FROM inv_productos p
                            
                            INNER JOIN inv_egresos_detalles dx ON p.id_producto=dx.producto_id
                            INNER JOIN inv_egresos ex ON ex.id_egreso=dx.egreso_id
                            
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
                            )z ON p.id_producto = z.producto_id
                        
                            WHERE dx.egreso_id = '$id_egreso' 
                        
                            GROUP BY dx.producto_id, dx.lote, dx.vencimiento
                        
                            ")->fetch();

// echo json_encode($productos); die();
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
$clientes = $db->query("select DISTINCT a.nombre_cliente, a.nit_ci 
                        from inv_egresos a 
                        LEFT JOIN inv_clientes b ON a.nit_ci = b.nit 
                        
                        UNION
                        
                        select DISTINCT b.cliente as nombre_cliente, b.nit as nit_ci 
                        from inv_egresos a 
                        RIGHT JOIN inv_clientes b ON a.nit_ci = b.nit
                        ORDER BY nombre_cliente asc, nit_ci asc
                      ")->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_mostrar = in_array('mostrar', $permisos);
$permiso_asignar = in_array('preventas_asignar', $permisos);

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
                        <strong>Editar preventa</strong>
                    </h3>
                </div>
                <!--<div class="panel-body">-->
                <!--    <div class="col-sm-8 hidden-xs">-->

                <!--    </div>-->
                <!--    <div class="col-sm-4 hidden-xs  text-right">-->
                <!--        <div class="form-check form-check-inline">-->
                <!--            <label class="form-check-label" for="inlineCheckbox1">Busqueda de Productos</label>-->
                <!--            <input class="form-check-input" type="checkbox" id="inlineCheckbox1" onchange='sidenav()' checked>-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->
            </div>
        </div>
    </div>
    <div class="row" id='ContenedorF'>
    <?php if ($egreso) { ?>
        <div class="col-md-12">
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
                    <form method="post" action="?/asignacion/preventa_distribucion_guardar" class="form-horizontal">
                        <input type="hidden" name="modo_egreso" value="preventa"/>
                        <input type="hidden" name="entrega" value="<?= $entrega ?>"/>
                        <input type="hidden" name="atras" value="<?= back(); ?>"/>
                        
                        <div class="form-group">
                            <!--<label for="cliente" class="col-sm-4 control-label">Buscar:</label>-->
                            <!--<div class="col-sm-8">-->
                                <!--select name="cliente" id="cliente" class="form-control text-uppercase" data-validation="letternumber" data-validation-allowing="-+./&() " data-validation-optional="true">
                                    <option value="">Buscar</option>
                                    <?php //foreach ($clientes as $cliente) { ?>
                                        <option value="<?= escape($cliente['nit_ci']) . '|' . escape($cliente['nombre_cliente']); ?>"><?= escape($cliente['nit_ci']) . ' &mdash; ' . escape($cliente['nombre_cliente']); ?></option>
                                    <?php //} ?>
                                </select-->
                            <!--</div>-->
                        </div>
                        <div class="form-group">
                            <label for="nit_ci" class="col-sm-4 control-label">Nro Nota:</label>
                            <div class="col-sm-8">
                                <label for="nro_nota" class="col-sm-4 control-label" style="text-align:left;"><?= $egreso['nro_nota'] ?></label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nit_ci" class="col-sm-4 control-label">NIT / CI:</label>
                            <div class="col-sm-8">
                                <input type="hidden" name="id_egreso" value="<?= $egreso['id_egreso']; ?>"/>
                                <input type="hidden" readonly value="<?= $egreso['nit_ci'] ?>" name="nit_ci" id="nit_ci" class="form-control text-uppercase" autocomplete="off" data-validation="required number">
                                <input type="hidden" readonly value="<?= $egreso['cliente_id'] ?>" name="cliente_id" id="cliente_id" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-+./&() " data-validation-length="max100">
    
                                <label for="nit_ci" class="col-sm-4 control-label" style="text-align:left;"><?= $egreso['nit_ci'] ?></label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nombre_cliente" class="col-sm-4 control-label">Señor(es):</label>
                            <div class="col-sm-8">
                                <input type="hidden" readonly value="<?= $egreso['cliente'] ?>" name="nombre_cliente" id="nombre_cliente" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-+./&() " data-validation-length="max100">
                                <label for="nro_nota" class="col-sm-4 control-label" style="text-align:left;"><?= $egreso['cliente'] ?></label>
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
                        <!-- <div class="form-group">
                            <label for="atencion" class="col-sm-4 control-label">Ubicación:</label>
                            <div class="col-sm-8">
                                <input type="text" value="<?php // $egreso['coordenadas'] ?>" name="atencion" id="atencion" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-length="max100">
                            </div>
                        </div> -->
                        <div class="form-group hidden">
                            <label for="direccion" class="col-sm-4 control-label">Dirección:</label>
                            <div class="col-sm-8">
                                <textarea name="direccion" id="direccion" class="form-control text-uppercase" rows="2" autocomplete="off" data-validation="letternumber" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-optional="true"></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="observacion" class="col-sm-4 control-label">Observación:</label>
                            <div class="col-sm-8">
                                <textarea name="observacion" id="observacion" class="form-control text-uppercase" rows="2" autocomplete="off" data-validation="letternumber" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-optional="true"><?= $egreso['descripcion_venta'] ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="prioridad" class="col-sm-4 control-label">Prioridad:</label>
                            <div class="col-sm-8">
                                <div class="row" style="padding-right: 0px !important;">
                                    <div class="col-sm-4">
                                        <select name="prioridad" id="prioridad" class="form-control text-uppercase" data-validation="required" data-validation-allowing="-+./&()">
                                            <option value="">Buscar</option>
                                            <?php foreach ($prioridades as $prioridad) { ?>
                                                <option value="<?= escape($prioridad['prioridad']); ?>" <?= ($prioridad['prioridad'] == $egreso['observacion']) ? 'selected' : '' ?> ><?= escape($prioridad['prioridad']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-8">
                                        <label for="forma_pago" class="col-sm-4 control-label" style="padding-top: 0px !important;">Forma de Pago:</label>
                                        <div class="col-sm-8">
                                            <select name="forma_pago" id="forma_pago" class="form-control" data-validation="required number" onchange="set_plan_pagos()">
                                                <option value="1" <?= ($egreso['plan_de_pagos'] == 'no') ? 'selected':'' ?> >Contado</option>
                                                <option value="2" <?= ($egreso['plan_de_pagos'] == 'si') ? 'selected':'' ?> >Plan de Pagos</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
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
                                <?php foreach($detalles as $key => $detalle){

                                    $det_ingreso_x = "  SELECT sum(lote_cantidad) cantidad_lote
                                                        from inv_ingresos_detalles 
                                                        inner join inv_ingresos i ON id_ingreso = ingreso_id
                                                        WHERE   producto_id='".$detalle['producto_id']."' 
                                                                AND LOTE='".$detalle['lote']."' 
                                                                AND vencimiento='".$detalle['vencimiento']."' 
                                                                AND i.almacen_id='".$egreso['almacen_id']."' 
                                                        ";
                                    $det_ingreso = $db->query($det_ingreso_x)->fetch_first();
                                    
                                    $promociones_x = "  SELECT *
                                                        from inv_promocion_precios 
                                                        WHERE   producto_id='".$detalle['producto_id']."' 
                                                                AND LOTE='".$detalle['lote']."' 
                                                                AND vencimiento='".$detalle['vencimiento']."' 
                                                        ";
                                    $promociones = $db->query($promociones_x)->fetch_first();
                                    
                                    $detalle_id_producto = $detalle['id_producto'] .'_'. str_replace(' ', '-',$detalle['lote'])."_".$key; 
                                    
                                    ?>

                                    <tr class="active" data-producto="<?= $detalle_id_producto ?>">
                                        <td class="text-nowrap text-middle"><b><?= $key +1 ?></b></td>
                                        
                                        <td class="text-nowrap text-middle">
                                            <input type="text" value="<?= $detalle['id_producto']; ?>" name="productos[]" class="translate input-xs" tabindex="-1" data-validation="required number" data-validation-error-msg="Debe ser número"><?= $detalle['codigo'] ?>
                                        </td>
                                        
                                        <td class="text-middle">
                                            <?= $detalle['nombre_factura']?><br>Lote: <?= $detalle['lote']?><br>Venc: <?= $detalle['vencimiento']?>
                                            <input type="hidden" value="<?= $detalle['id_detalle']?>" name="detalle[]" class="form-control input-xs" data-validation="required">
                                            <input type="hidden" value="<?= $detalle['nombre_factura']?>" name="nombres[]" class="form-control input-xs" data-validation="required">
                                            <input type="hidden" value="<?= $detalle['lote']?>" name="lote[]" class="form-control input-xs" data-validation="required">
                                            <input type="hidden" value="<?= $detalle['vencimiento']?>" name="vencimiento[]" class="form-control input-xs" data-validation="required">
                                        </td>
                                        
                                        <td class="text-middle"><input type="text" value="<?= $detalle['cantidad_producto'] ?>" name="cantidades[]"  class="form-control text-right input-xs" maxlength="10" autocomplete="off" 
                                            data-cantidad="" data-validation="required number" 
                                            data-validation-allowing="range[1;<?= $detalle['cantidad_producto'] ?>]" data-validation-error-msg="Debe ser un número positivo entre 1 y <?= $detalle['cantidad_producto'] ?>" 
                                            onkeyup="calcular_importe('<?= $detalle_id_producto ?>')"></td>

                                        <?php if(false){ ?>
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
                                        <td class="text-middle">
                                            <input type="text" value="<?= $detalle['precio'] ?>" name="precios[]" class="form-control input-xs text-right" autocomplete="off" data-precio="<?= $detalle['precio'] ?>"  data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe('<?= $detalle_id_producto ?>',1)" <?php if(!$promociones){ ?> readonly="readonly" <?php } ?>>
                                            <input type="hidden" value="<?= $detalle['precio'] ?>" name="precio_hidden[]" class="form-control input-xs text-right" autocomplete="off" data-precio-hidden="<?= $detalle['precio'] ?>"  data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe('<?= $detalle_id_producto ?>',1)" <?php if(!$promociones){ ?> readonly="readonly" <?php } ?>>
                                        </td>
                                        
                                        <td class="hidden"><input type="text" value="0" name="descuentos[]" class="form-control input-xs text-right" maxlength="2" autocomplete="off" data-descuento="0" data-validation="required number" data-validation-allowing="range[0;50]" data-validation-error-msg="Debe ser un número positivo entre 0 y 50" onkeyup="descontar_precio(<?= $detalle['producto_id'] ?>)"></td>
                                        
                                        <td class="text-nowrap text-middle text-right" data-importe="<?= $detalle['cantidad_producto']*$detalle['precio'] ?>"><?= $detalle['cantidad_producto']*$detalle['precio'] ?></td>
                                        
                                        <td class="text-nowrap text-middle text-center">
                                            <button type="button" class="btn btn-xs btn-danger" data-toggle="tooltip" data-title="Eliminar producto" tabindex="-1" onclick="eliminar_producto('<?= $detalle_id_producto ?>')"><span class="glyphicon glyphicon-trash"></span></button>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <input type="text" name="almacen_id" value="<?= $egreso["almacen_id"]; ?>" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="El almacén no esta definido">
                                <input type="text" name="nro_registros" value="0" class="translate" tabindex="-1" data-ventas="" data-validation="required number" data-validation-allowing="range[1;20]" data-validation-error-msg="El número de productos a vender debe ser mayor a cero y menor a 20">
                                <input type="text" name="monto_total" value="0" class="translate" tabindex="-1" data-total="" data-validation="required number" data-validation-allowing="range[0.01;1000000.00],float" data-validation-error-msg="El monto total de la venta debe ser mayor a cero y menor a 1000000.00">
                            </div>
                        </div>


                        <!-- para plan de pagos -->
						<div id="plan_de_pagos" style="display: <?= ($egreso['plan_de_pagos'] == 'no') ? 'none':'block' ?>;">
							<div class="form-group">
								<label for="almacen" class="col-md-4 control-label">Nro Cuotas:</label>
								<div class="col-md-8">
									<input type="text" value="<?= count($detpagos); ?>" id="nro_cuentas" name="nro_cuentas" class="form-control text-right" autocomplete="off" data-cuentas="" data-validation="required number" data-validation-allowing="range[1;360],int" data-validation-error-msg="Debe ser número entero positivo" onkeyup="set_cuotas()">
								</div>
							</div>

							<table id="cuentasporpagar" class="table table-bordered table-condensed table-striped table-hover table-xs margin-none">
								<thead>
									<tr class="active">
										<th class="text-nowrap text-center col-xs-4">Detalle</th>
										<th class="text-nowrap text-center col-xs-4">Fecha de Pago</th>
										<th class="text-nowrap text-center col-xs-4">Monto</th>
									</tr>
								</thead>
								<tbody>
									<?php for ($i = 1; $i <= 36; $i++) { ?>
										<tr class="active cuotaclass">
											<?php //if ($i == 1) { ?>
												<!--<td class="text-nowrap" valign="center">-->
												<!--	<div data-cuota="<?= $i ?>" data-cuota2="<?= $i ?>" class="cuota_div">Pago Inicial:</div>-->
												<!--</td>-->
											<?php //} else { ?>
												<td class="text-nowrap" valign="center">
													<div data-cuota="<?= $i ?>" data-cuota2="<?= $i ?>" class="cuota_div">Cuota <?= $i ?>:</div>
												</td>
											<?php //} ?>

											<td>
												<div data-cuota="<?= $i ?>" class="cuota_div">
													<div class="col-sm-12">
														<input  id="inicial_fecha_<?= $i ?>" name="fecha[]" value="<?= (count($detpagos) >= $i) ? date('d-m-Y', strtotime($detpagos[$i-1]['fecha']) ) : date('d-m-Y') ?>" min="<?= date('d-m-Y') ?>" class="form-control input-sm" autocomplete="off" <?php if ($i == 1) { ?> data-validation="required date" <?php } ?> data-validation-format="DD-MM-YYYY" data-validation-min-date="<?= date('d-m-Y'); ?>" onchange="javascript:change_date(<?= $i ?>);" onblur="javascript:change_date(<?= $i ?>);" <?php if ($i > 1) { ?> disabled="disabled" <?php } ?>>
													</div>
												</div>
											</td>
											<td>
												<div data-cuota="<?= $i ?>" class="cuota_div"><input type="text" value="<?= (count($detpagos) >= $i) ? number_format($detpagos[$i-1]['monto'] ,2) : '0.00' ?>" name="cuota[]" class="form-control input-sm text-right monto_cuota" maxlength="7" autocomplete="off" data-montocuota="0" data-validation-allowing="range[0.01;1000000.00],float" data-validation-error-msg="Debe ser número decimal positivo" onchange="javascript:calcular_cuota('<?= $i ?>');"></div>
											</td>
										</tr>
									<?php } ?>
								</tbody>
								<tfoot>
									<tr class="active">
										<th class="text-nowrap text-center" colspan="2">Importe total <?= escape($moneda); ?></th>
										<th class="text-nowrap text-right" data-totalcuota=""><?= (count($detpagos) > 0) ? $egreso["monto_total"] : '0.00' ?></th>
									</tr>
								</tfoot>
							</table>
							<br>
						</div>

                        <?php if ($entrega == 1) { ?>
                            <div class="form-group">
                                <div class="col-xs-12 text-right">
                                    <button type="submit" class="btn btn-success">
                                        <span class="glyphicon glyphicon-floppy-disk"></span>
                                        <span>Guardar y Entregar</span>
                                    </button>
                                    
                                    <?php if ($entrega == 1) { ?>
                                        <a href="<?= back(); ?>" class="btn btn-primary"><i class="glyphicon glyphicon-arrow-left"></i><span> Cancelar Edicion</span></a>
                                    <?php } else {?>
                                        <a href="?/asignacion/preventas_asignar/<?= $id_egreso ?>" class="btn btn-primary"><i class="glyphicon glyphicon-arrow-left"></i><span> Cancelar Edicion</span></a>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } else { ?>
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
                        <?php } ?>

                        
                    </form>
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
    <script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>

    <script>
    $(function () {
        //sidenav();
        calcular_total();
        set_cuotas();
        set_plan_pagos();

        var table;
        var $cliente = $('#cliente');
        var $nit_ci = $('#nit_ci');
        var $nombre_cliente = $('#nombre_cliente');

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

    // function calcular_importe(id_producto) {
    //     var $producto = $('[data-producto=' + id_producto + ']');
    //     var $cantidad = $producto.find('[data-cantidad]');
    //     var $precio = $producto.find('[data-precio]');
    //     var $descuento = $producto.find('[data-descuento]');
    //     var $importe = $producto.find('[data-importe]');
    //     var cantidad, precio, importe, fijo;

    //     fijo = $descuento.attr('data-descuento');
    //     fijo = ($.isNumeric(fijo)) ? parseInt(fijo) : 0;
    //     cantidad = $.trim($cantidad.val());
    //     cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
    //     precio = $.trim($precio.val());
    //     precio = ($.isNumeric(precio)) ? parseFloat(precio) : 0.00;
    //     descuento = $.trim($descuento.val());
    //     descuento = ($.isNumeric(descuento)) ? parseInt(descuento) : 0;
    //     importe = cantidad * precio;
    //     importe = importe.toFixed(2);
    //     $importe.text(importe);

    //     calcular_total();
    // }
    function calcular_importe(id_producto, precio_externo) {
		// console.log(id_producto);
		var $producto = $('[data-producto=' + id_producto + ']');
		var $cantidad = $producto.find('[data-cantidad]');
		var $precio = $producto.find('[data-precio]');
		var $descuento = $producto.find('[data-descuento]');
        var $importe = $producto.find('[data-importe]');

		// Josema:: add
		var cantidad, precio, importe, fijo, descuento;

		fijo = $descuento.attr('data-descuento');
		fijo = ($.isNumeric(fijo)) ? parseFloat(fijo) : 0;
		cantidad = $.trim($cantidad.val());
		cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
		precio = $.trim($precio.val());
		precio = ($.isNumeric(precio)) ? parseFloat(precio) : 0.00;
		descuento = $.trim($descuento.val());
		descuento = ($.isNumeric(descuento)) ? parseFloat(descuento) : 0;



		var $auxiliarP, $auxiliarC, ant_pre;
		ant_pre = $producto.find('[data-pre]').val();
		unidad = $producto.find('[data-unidad]').val();
		// console.log(unidad);


		V_producto_simple=id_producto.split("_");
		id_producto_simple=V_producto_simple[0];

		forma_pago = $('#forma_pago').val();

		var parameter = {
			'id_producto' : id_producto_simple,
			'unidad' : unidad,
			'cantidad' : cantidad,
			'forma_pago': forma_pago
		};
		// console.log(parameter);
		
		if(precio_externo==1 || precio==0){
		    $auxiliarP = precio;
			$auxiliarC = cantidad;
			importe = (cantidad * $auxiliarP) - descuento;
			importe = importe.toFixed(2);
			$importe.text(importe);

			calcular_total();
		}else{
    		$.ajax({
    			url: "?/productos/precio",
    			type: "POST",
    			data: parameter,
    			success: function( data ){
    				$auxiliarP = data['precio_mayor'];
    				$auxiliarC = data['cantidad'];
    				// Asignamos el nuevo precio
    				$producto.find('[data-precio]').val($auxiliarP);
    				importe = (cantidad * $auxiliarP) - descuento;
    				importe = importe.toFixed(2);
    				$importe.text(importe);
    
    				calcular_total();
    			}
    		});
		}
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
        set_cuotas();
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

    $("#forma_pago").on('change', function() {
		var $ventas = $('#ventas tbody');
		var $productos = $ventas.find('[data-producto]');
		$productos.each(function(i) {
			// console.log($(this).attr("data-producto"));
			prod = $(this).attr("data-producto");
			calcular_importe(prod);
		});
	});

    /////////////////////////////////////////////////////////////////////////////
	document.getElementById('forma_pago').addEventListener('change', () => {
		calcular_descuentoF();
	});
    function calcular_descuentoF() {
		let filas = document.getElementById('ventas').children[2].children;
		for (let i = 0; i < filas.length; ++i) {
			let cantidad = filas[i].children[3].children[0],
				precio = filas[i].children[5].children[0],
				importe = filas[i].children[7].innerText,
				descuento = filas[i].children[1].children[0].value;
			let subtotal = (parseFloat(precio.value) * parseFloat(cantidad.value)).toFixed(2);
			let porcentaje = ((100 - parseFloat(descuento)) / 100).toFixed(2);
			if (descuento !== '0' && document.getElementById('forma_pago').value === '1')
				subtotal = (subtotal * porcentaje).toFixed(2);
			filas[i].children[7].innerText = subtotal;
		}
		calcular_total();
	}


    var formato = $('[data-formato]').attr('data-formato');
	var $inicial_fecha = new Array();
	for (i = 1; i < 36; i++) {
		$inicial_fecha[i] = $('#inicial_fecha_' + i + '');
		$inicial_fecha[i].datetimepicker({
			format: 'DD-MM-YYYY'
		});
	}

	function set_cuotas() {
		var cantidad = $('#nro_cuentas').val();
		var $compras = $('#cuentasporpagar tbody');

		$("#nro_plan_pagos").val(cantidad);

		if (cantidad > 36) {
			cantidad = 36;
			$('#nro_cuentas').val("36")
		}
		for (i = 1; i <= cantidad; i++) {
			$('[data-cuota=' + i + ']').css({
				'height': 'auto',
				'overflow': 'visible'
			});
			$('[data-cuota2=' + i + ']').css({
				'margin-top': '10px;'
			});
			$('[data-cuota=' + i + ']').parent('td').css({
				'height': 'auto',
				'border-width': '1px',
				'padding': '5px'
			});
		}
		for (i = parseInt(cantidad) + 1; i <= 36; i++) {
			$('[data-cuota=' + i + ']').css({
				'height': '0px',
				'overflow': 'hidden'
			});
			$('[data-cuota2=' + i + ']').css({
				'margin-top': '0px;'
			});
			$('[data-cuota=' + i + ']').parent('td').css({
				'height': '0px',
				'border-width': '0px',
				'padding': '0px'
			});
		}
		set_cuotas_val();
		calcular_cuota(1000);
	}

	function set_cuotas_val() {
		nro = $('#nro_cuentas').val();
		valorG = parseFloat($('[data-subtotal]:first').text());

		valor = valorG / nro;
		for (i = 1; i <= nro; i++) {
			if (i == nro) {
				final = valorG - (valor.toFixed(1) * (i - 1));
				$('[data-cuota=' + i + ']').children('.monto_cuota').val(final.toFixed(1) + "0");
			} else {
				$('[data-cuota=' + i + ']').children('.monto_cuota').val(valor.toFixed(1) + "0");
			}
		}
	}

	function set_plan_pagos() {
		if ($("#forma_pago").val() == 1) {
			$('#plan_de_pagos').css({
				'display': 'none'
			});
			if ($('#nro_cuentas').val() <= 0) {
				$('#nro_cuentas').val('1');
				calcular_cuota(1000);
				$("#nro_plan_pagos").val('1');

			}
		} else {
			$('#plan_de_pagos').css({
				'display': 'block'
			});
		}
	}

	function calcular_cuota(x) {
		var cantidad = $('#nro_cuentas').val();
		var total = 0;

		for (i = 1; i <= x && i <= cantidad; i++) {
			importe = $('[data-cuota=' + i + ']').children('.monto_cuota').val();
			importe = parseFloat(importe);
			total = total + importe;
		}
		//console.log(total);
		valorTotal = parseFloat($('[data-total]:first').val());
		if (nro > x) {
			valor = (valorTotal - total) / (nro - x);
		} else {
			valor = 0;
		}

		for (i = (parseInt(x) + 1); i <= cantidad; i++) {
			if (valor >= 0) {
				if (i == cantidad) {
					valor = valorTotal - total;
					$('[data-cuota=' + i + ']').children('.monto_cuota').val(valor.toFixed(1) + "0");
				} else {
					$('[data-cuota=' + i + ']').children('.monto_cuota').val(valor.toFixed(1) + "0");
				}
				total = total + (valor.toFixed(1) * 1);
			} else {
				$('[data-cuota=' + i + ']').children('.monto_cuota').val("0.00");
			}
		}

		$('[data-totalcuota]').text(total.toFixed(1) + "0");
		valor = parseFloat($('[data-subporcentaje]:first').text());
		if (valor == total.toFixed(1) + "0") {
			$('[data-total-pagos]:first').val(1);
			$('[data-total-pagos]:first').parent('div').children('#monto_plan_pagos').attr("data-validation-error-msg", "");
		} else {
			$('[data-total-pagos]:first').val(0);
			$('[data-total-pagos]:first').parent('div').children('#monto_plan_pagos').attr("data-validation-error-msg", "La suma de las cuotas es diferente al costo total « " + total.toFixed(1) + "0" + " / " + valor.toFixed(1) + "0" + " »");
		}

	}

	function change_date(x) {
		if ($('#inicial_fecha_' + x).val() != "") {
			if (x < 36) {
				$('#inicial_fecha_' + (x + 1)).removeAttr("disabled");
			}
		} else {
			for (i = x; i <= 35; i++) {
				$('#inicial_fecha_' + (i + 1)).val("");
				$('#inicial_fecha_' + (i + 1)).attr("disabled", "disabled");
			}
		}
	}

	function setPago() {
		$('#data-tipo-pago').val(2);
	}

	function calcular_descuento_total() {
		var $ventas = $('#ventas tbody');
		var $total = $('[data-subtotal]:first');
		var $importes = $ventas.find('[data-importe]');
		var descuento = $('#descuento_porc').val();
		var importe, total = 0;
		$importes.each(function(i) {
			importe = $.trim($(this).text());
			importe = parseFloat(importe);
			total = total + importe;
		});
		$total.text(total.toFixed(2));
		var importe_total = total.toFixed(2);
		var total_descuento = 0,
			formula = 0,
			total_importe_descuento = 0;
		if (descuento == null || descuento == 0 || descuento == '') {
			$('#descuento_porc').val(0);
			descuento = 0;
			var descuento_bs = $('#descuento_bs').val();
			if (descuento_bs.trim() == '')
				descuento_bs = 0;
			total_importe_descuento = parseFloat(importe_total) - parseFloat(descuento_bs);
			$('#importe_total_descuento').html(total_importe_descuento.toFixed(2));
			$('#total_importe_descuento').val(total_importe_descuento.toFixed(2));
		} else {
			formula = (descuento / 100) * importe_total;
			total_importe_descuento = parseFloat(importe_total) - parseFloat(formula);
			$('#descuento_bs').val(0);
			$('#importe_total_descuento').html(total_importe_descuento.toFixed(2));
			$('#total_importe_descuento').val(total_importe_descuento.toFixed(2));
		}
	}

	function descuento_pago_pristine(dato = [0, 0]) {
		//Ocultar o Mostrar en base al cliente seleccionado
		if (dato[0] == 0) {
			document.getElementById('estado_descuentoF').classList.add('hidden');
			$('#descuentoGrupoF').val(0);
		} else {
			document.getElementById('estado_descuentoF').classList.remove('hidden');
			$('#descuentoGrupoF').val(dato[0]);
		}
		if (dato[1] == 0){
			// document.getElementById('CreditoF').classList.add('hidden');
		} else
			document.getElementById('CreditoF').classList.remove('hidden');
		//Reiniciar la configuracion
		tipo_descuento();
	}

	function tipo_descuento() {
		var descuento = $('#tipo').val();
		if (descuento == 0) {
			$('#div-descuento').hide();
			$('#div-descuento2').show();
			$('#descuento_bs').val($('#descuentoGrupoF').val());
			$('#descuento_porc').val(0);
		} else if (descuento == 1) {
			$('#div-descuento').show();
			$('#div-descuento2').hide();
			$('#descuento_bs').val(0);
			$('#descuento_porc').val($('#descuentoGrupoF').val());
		}
		calcular_descuento_total();
	}
    </script>
<?php require_once show_template('footer-empty'); ?>