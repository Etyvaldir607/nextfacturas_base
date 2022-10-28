<?php 
require_once show_template('header-advanced'); 
?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Escritorio</strong>
	</h3>
</div>
<style>
.medida{
	height:300px;
	overflow:scroll;
}
.medida2{
	height:200px;
	overflow:scroll;
}	
</style>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-4 col-md-3">
			<div class="row margin-bottom">
				<div class="col-xs-10 col-xs-offset-1">
					<img src="<?= imgs . '/logo-color.png'; ?>" class="img-responsive">
				</div>
			</div>
			<div class="well text-center">
				<?php if ($_user['persona_id']) : ?>
				<h4 class="margin-none">Bienvenido al sistema!</h4>
				<p>
					<strong><?= escape($_user['nombres'] . ' ' . $_user['paterno'] . ' ' . $_user['materno']); ?></strong>
				</p>
				<?php else : ?>
				<h4 class="margin-none">Bienvenido al sistema!</h4>
				<p>
					<strong><?= escape($_user['username']); ?></strong>
				</p>
				<?php endif ?>
				<p>
					<img src="<?= ($_user['avatar'] == '') ? imgs . '/avatar.jpg' : profiles . '/' . $_user['avatar']; ?>" class="img-circle" width="128" height="128" data-toggle="modal" data-target="#modal_mostrar">
				</p>
				<p class="margin-none">
					<strong><?= escape($_user['email']); ?></strong>
					<br>
					<span class="text-success">en línea</span>
				</p>
			</div>
			<div class="list-group">
				<a href="../sistema-app/storage/AppNexcorpDistribucion.apk" class="list-group-item">
					<span>Descargar aplicacion <b>PreventasApp</b></span>
				</a>
				<a href="?/home/perfil_ver" class="list-group-item">
					<span>Mostrar mi perfil</span>
					<span class="glyphicon glyphicon-menu-right pull-right"></span>
				</a>
				<a href="?/site/logout" class="list-group-item">
					<span>Cerrar mi sesión</span>
					<span class="glyphicon glyphicon-menu-right pull-right"></span>
				</a>
			</div>
		</div>
		<div class="col-sm-8 col-md-9">
			<div class="panel panel-warning">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <span class="glyphicon glyphicon-search"></span>
                        <strong>Fecha de vencimiento cercana</strong>
                    </h3>
                </div>
                <div class="panel-body">
                    <?php
                    $hoy = date('Y-m-d');
                    $dosmesesmas = date("Y-m-d",strtotime($hoy."+ 9 month")); 
                    
                    $productos_por_lote = $db->query("
                					SELECT p.codigo, p.nombre, p.nombre_factura, i.lote, i.lote_cantidad, i.vencimiento, a.almacen
                                    FROM inv_productos as p
                                    LEFT JOIN inv_ingresos_detalles as i ON p.id_producto = i.producto_id
                                    LEFT JOIN inv_ingresos as ing ON ing.id_ingreso = i.ingreso_id
                                    LEFT JOIN inv_almacenes as a ON a.id_almacen = ing.almacen_id
                                    WHERE i.vencimiento BETWEEN  '$hoy' and '$dosmesesmas' and p.visible='s'
                                    ORDER BY i.vencimiento ASC
                					")->fetch();
                					
                    if($productos_por_lote != null){ ?>
                        <div class="table-responsive medida2" id="medida2">
                            <table id="productos" class="table table-bordered table-condensed table-striped table-hover table-xs">
                                <thead>
                                <tr class="active">
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">#</th>
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Código</th>
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Nombre genérico</th>
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Nombre comercial</th>
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Lote</th>
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Stock</th>
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Vencimiento</th>
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Almacen</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php 
                                foreach ($productos_por_lote as $nro => $producto) { 
                                    if($producto['lote_cantidad']>0){
                                    ?>
                                        <tr>
                                            <td><?= $nro + 1; ?></td>
                                            <td><?= $producto['codigo']; ?></td>
                                            <td><?= $producto['nombre']; ?></td>
                                            <td><?= $producto['nombre_factura']; ?></td>
                                            <td><?= $producto['lote']; ?> </td>
                                            <td class="text-nowrap"><?= $producto['lote_cantidad']; ?></th>
                                            <td class="text-nowrap"><?= date_decode($producto['vencimiento'], $_institution['formato']); ?></th>
                                            <td class="text-nowrap"><?= $producto['almacen']; ?></th>
                                        </tr>
                                    <?php 
                                    }
                                } 
                                ?>
                                  
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <div class="table-responsive medida2" id="medida2">
                            <span>No existen productos por vencer</span>
                        </div>
                    <?php } ?>
                </div>
            </div>
	
	<div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <span class="glyphicon glyphicon-search"></span>
                        <strong>Productos vencidos</strong>
                    </h3>
                </div>
                <div class="panel-body">
                    <?php
                    $productos = $db->query("SELECT p.*, id.vencimiento, id.lote, id.lote_cantidad, a.almacen
                                FROM inv_productos p
                                LEFT JOIN inv_ingresos_detalles id ON p.id_producto = id.producto_id
                                LEFT JOIN inv_ingresos i ON id.ingreso_id = i.id_ingreso
                                LEFT JOIN inv_almacenes a ON i.almacen_id = a.id_almacen
                                WHERE id.lote_cantidad > 0
                                AND id.vencimiento <= CURDATE() and p.visible='s'
                                ORDER BY fecha_registro DESC, id_producto DESC LIMIT 20")->fetch();
                    ?>
                    <?php if ($productos) { ?>
                        <div class="table-responsive medida2" id="medida2">
                            <table id="productos" class="table table-bordered table-condensed table-striped table-hover table-xs">
                                <thead>
                                <tr class="active">
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">#</th>
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Fecha vencimiento</th>
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Nombre comercial</th>
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Nombre genérico</th>
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Cantidad actual</th>
                                    <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Almacen</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($productos as $nro => $producto) { ?>
                                    <tr>
                                        <td><?= $nro + 1; ?></td>
                                        <td><?= date_decode($producto['vencimiento'], $_institution['formato']); ?></td>
                                        <td><?= $producto['nombre']; ?></td>
                                        <td><?= $producto['nombre_factura']; ?></td>
                                        <td><?= $producto['lote_cantidad']; ?></td>
                                        <td><?= $producto['almacen']; ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>
                </div>
            </div>
            
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <span class="glyphicon glyphicon-search"></span>
                        <strong>Productos con bajo STOCK</strong>
                    </h3>
                </div>
                <div class="panel-body">
                    <?php

                    ?>
                    <div class="table-responsive medida2" id="medida2">

                        <table id="table" class="table table-bordered table-condensed table-striped table-hover table-xs">
                            <thead>
                            <tr class="active">
                                <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Código</th>
                                <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Nombre comercial</th>
                                <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Nombre genérico</th>
                                <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Mínimo</th>
                                <th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Total existencias</th>
                                <!--<th class="text-nowrap" style="background-color: #222; color:#fff; text-align: center; font-weight: bold;">Almacen</th>-->
                            </tr>
                            </thead>
                        
                            <tbody>
                            <?php
                            $productos = $db->query("SELECT p.id_producto,p.asignacion_rol, p.descuento ,p.promocion,
                                						p.descripcion,p.imagen,p.codigo,p.nombre_factura as nombre,p.nombre_factura,p.cantidad_minima,p.precio_actual,
                                						IFNULL(e.cantidad_ingresos, 0) AS cantidad_ingresos, 
                                						u.unidad, u.sigla, c.categoria
                                					FROM inv_productos p
                                					LEFT JOIN (
                                						SELECT d.producto_id, SUM(d.lote_cantidad) AS cantidad_ingresos
                                						FROM inv_ingresos_detalles d
                                						GROUP BY d.producto_id
                                					) AS e ON e.producto_id = p.id_producto
                                					LEFT JOIN inv_unidades u ON u.id_unidad = p.unidad_id
                                					LEFT JOIN inv_categorias c ON c.id_categoria = p.categoria_id
                                					WHERE p.visible='s'
                                				")->fetch();
                                				
                            foreach ($productos as $nro => $producto) {
                                $ing = intval($producto['cantidad_ingresos']);
                                
                                if ($producto['cantidad_minima'] > $ing) {
                                    ?>
                                    <tr>
                                        <td class="text-nowrap"><?= escape($producto['codigo']); ?></td>

                                        <td class="width-lg"><?= escape($producto['nombre']); ?></td>
                                        <td class="width-lg"><?= escape($producto['nombre_factura']); ?></td>

                                        <td class="text-nowrap text-right"><?= escape($producto['cantidad_minima']); ?></td>
                                        <td class="text-nowrap text-right"><strong class="text-primary"><?php echo ($ing); ?></strong></td>
                                        <!--<td class="text-nowrap text-right"><?= $almacen['almacen']?></td>-->
                                    </tr>
                                <?php
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
	
		</div>
	</div>
</div>
<?php require_once show_template('footer-advanced'); ?>